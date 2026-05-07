# Codebase Cleanup Plan — shrink to the 18-file live core

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Delete the deprecated codebase down to the 18 PHP files needed to serve the three live API routes (`/api/callback`, `/api/healthcheck`, `/api/twitter/highlights`), plus their config/test/composer dependencies. Removes ~190 of 208 PHP files (91% deletion) and the matching Doctrine entity mappings, XML mapping files, service definitions, dead tests, unused composer packages.

**Architecture:** Three live anchors (TrendsController, HealthcheckController, TokenAuthenticator) anchor the closure. Two prerequisite refactors strip dead constructor injections and unused methods so the static closure matches the runtime call graph. Then a single coordinated deletion pass removes everything outside the closure. Verification gates: `bin/console cache:clear` clean, `phpunit` green on the 5 controller tests, manual smoke against the three routes.

**Tech Stack:** Symfony 5.4, PHP 8.4 runtime (per provisioning), Doctrine ORM 2, PHPUnit 9.6, Composer Flex.

**Reference design:** `docs/superpowers/specs/2026-05-06-symfony-7.4-doctrine-3-migration-design.md` (will be revised after this plan completes — the Symfony 7.4 hop becomes a much smaller plan against this slim codebase).

**Sequencing relative to existing Stage 0 plan:** This plan supersedes most of `docs/superpowers/plans/2026-05-06-symfony-migration-stage-0.md`. Already-done items from that plan (DB URL template, security.yaml restructure, controller tests) survive as commits; the remaining tooling tasks (Rector install, PHPStan baseline, deprecation log) move to a follow-up plan and run against the cleaned codebase.

---

## File structure — definitive 18-file keep-set

These files survive. Everything in `src/` not listed here gets deleted.

```
src/Kernel.php
src/bootstrap.php

src/Trends/Domain/Repository/PopularPublicationRepositoryInterface.php
src/Trends/Domain/Repository/SearchParamsInterface.php
src/Trends/Infrastructure/Controller/TrendsController.php
src/Trends/Infrastructure/Repository/PopularPublicationRepository.php

src/Twitter/Domain/Membership/Repository/MemberRepositoryInterface.php
src/Twitter/Infrastructure/Cache/RedisCache.php
src/Twitter/Infrastructure/DependencyInjection/LoggerTrait.php
src/Twitter/Infrastructure/DependencyInjection/Membership/MemberRepositoryTrait.php
src/Twitter/Infrastructure/Healthcheck/Controller/HealthcheckController.php
src/Twitter/Infrastructure/Http/SearchParams.php
src/Twitter/Infrastructure/Repository/Membership/MemberRepository.php
src/Twitter/Infrastructure/Security/Authentication/TokenAuthenticator.php
src/Twitter/Infrastructure/Security/Cors/CorsHeadersAwareTrait.php

src/Membership/Domain/Entity/Legacy/Member.php
src/Membership/Domain/Entity/MemberInterface.php
src/Membership/Domain/Model/Member.php
```

## What else gets touched

  - `config/packages/doctrine.yaml` — slim from 6 entity mapping groups to 1 (Membership only); DQL extensions catalog (`doctrine_extensions_dql` parameter) deleted entirely.
  - `config/model/curation/`, `config/model/trends/` — entire directories deleted.
  - `config/services.yaml`, `config/services/*.yml`, `config/services_test.yaml` — service definitions for deleted classes removed.
  - `config/bundles.php` — likely drop `JoliTypoBundle` (if grep shows no references in keep-set).
  - `tests/` — keep only `Trends/Infrastructure/Controller/TrendsControllerTest.php`, `Twitter/Infrastructure/Healthcheck/Controller/HealthcheckControllerTest.php`, `Twitter/Infrastructure/Cache/InMemoryRedisCache.php`, `NewsReview/Infrastructure/Repository/InMemoryPopularPublicationRepository.php`, `bootstrap.php`, plus the fixture file `Resources/Response/ListHighlights.b64`. Everything else goes.
  - `composer.json` — drop unused dependencies (see Task 9).
  - `translations/` — best-effort orphan removal (low priority).

---

### Task 1: Strip dead constructor injections from `TrendsController`

`TrendsController` has 7 properties; only 3 are actually invoked. The other 4 (`tokenRepository`, `memberRepository`, `highlightRepository`, `router`) are wired by the container but never called. Remove them.

**Files:**
- Modify: `src/Trends/Infrastructure/Controller/TrendsController.php`
- Modify: `config/services/controller.yml`

- [ ] **Step 1: Verify zero usages of the 4 dead properties**

```bash
for prop in tokenRepository memberRepository highlightRepository router; do
  count=$(grep -c "this->${prop}" src/Trends/Infrastructure/Controller/TrendsController.php)
  echo "  \$this->$prop : $count hits in method bodies"
done
```

Expected: all 4 report `0 hits`.

- [ ] **Step 2: Remove the 4 dead property declarations from `TrendsController`**

In `src/Trends/Infrastructure/Controller/TrendsController.php`, find:

```php
    public TokenRepository $tokenRepository;

    public MemberRepository $memberRepository;

    public HighlightRepository $highlightRepository;

    public LoggerInterface $logger;

    public RedisCache $redisCache;

    public RouterInterface $router;

    public PopularPublicationRepositoryInterface $popularPublicationRepository;
```

Replace with:

```php
    public LoggerInterface $logger;

    public RedisCache $redisCache;

    public PopularPublicationRepositoryInterface $popularPublicationRepository;
```

- [ ] **Step 3: Remove the 4 dead `use` imports from `TrendsController`**

In the same file, delete these import lines:

```php
use App\Twitter\Infrastructure\Http\AccessToken\Repository\TokenRepository;
use App\Twitter\Infrastructure\Publication\Repository\HighlightRepository;
use App\Twitter\Infrastructure\Repository\Membership\MemberRepository;
use Symfony\Component\Routing\RouterInterface;
```

- [ ] **Step 4: Update `config/services/controller.yml`**

In `config/services/controller.yml`, find the `App\Trends\Infrastructure\Controller\TrendsController` `properties:` block. Remove the four lines wiring `tokenRepository`, `memberRepository`, `highlightRepository`, and `router`. Keep `logger`, `redisCache`, `popularPublicationRepository`. Final block should be:

```yaml
    App\Trends\Infrastructure\Controller\TrendsController:
        public: true
        class: 'App\Trends\Infrastructure\Controller\TrendsController'
        arguments:
            - '%allowed.origin%'
            - '%kernel.environment%'
        properties:
            logger:                         '@logger'
            redisCache:                     '@app.cache.redis'
            popularPublicationRepository:   '@App\Trends\Infrastructure\Repository\PopularPublicationRepository'
```

- [ ] **Step 5: Verify**

```bash
php -l src/Trends/Infrastructure/Controller/TrendsController.php
bin/console cache:clear --env=test 2>&1 | tail -3
bin/phpunit --group controller -c phpunit.xml.dist 2>&1 | head -7
```

Expected: lint passes; cache:clear succeeds; phpunit reports `OK (5 tests, ...)`.

- [ ] **Step 6: Commit**

```bash
git add src/Trends/Infrastructure/Controller/TrendsController.php config/services/controller.yml
git commit -m "refactor(trends): strip 4 dead injections from TrendsController

Remove tokenRepository, memberRepository, highlightRepository, router from
TrendsController — all four were container-wired but never called from any
method body. Update services/controller.yml wiring to match. Keeps logger,
redisCache, popularPublicationRepository which are the actual live deps."
```

---

### Task 2: Strip dead injections from `PopularPublicationRepository`

The constructor accepts 5 parameters; only 2 (`projectDir`, `logger`) are used. `HighlightRepository`, `MembersListRepositoryInterface`, and `string $defaultPublishersList` are stored but never read.

**Files:**
- Modify: `src/Trends/Infrastructure/Repository/PopularPublicationRepository.php`
- Modify: `config/services/repository.yml`

- [ ] **Step 1: Verify zero usages of the 3 dead properties**

```bash
for prop in highlightRepository listRepository defaultPublishersList; do
  count=$(grep -c "this->${prop}" src/Trends/Infrastructure/Repository/PopularPublicationRepository.php)
  echo "  \$this->$prop : $count hits"
done
```

Expected: all `0 hits`.

- [ ] **Step 2: Replace constructor and property block in `PopularPublicationRepository.php`**

In `src/Trends/Infrastructure/Repository/PopularPublicationRepository.php`, find the property declarations + constructor:

```php
    private string $projectDir;

    private LoggerInterface $logger;

    private HighlightRepository $highlightRepository;

    private MembersListRepositoryInterface $listRepository;

    private string $defaultPublishersList;

    public function __construct(
        string $projectDir,
        string $defaultPublishersList,
        HighlightRepository $highlightRepository,
        MembersListRepositoryInterface $publishersListRepository,
        LoggerInterface $logger
    )
    {
        $this->projectDir = $projectDir;
        $this->defaultPublishersList = $defaultPublishersList;
        $this->highlightRepository = $highlightRepository;
        $this->listRepository = $publishersListRepository;
        $this->logger = $logger;
    }
```

Replace with:

```php
    private string $projectDir;

    private LoggerInterface $logger;

    public function __construct(
        string $projectDir,
        LoggerInterface $logger
    ) {
        $this->projectDir = $projectDir;
        $this->logger = $logger;
    }
```

- [ ] **Step 3: Remove dead imports from `PopularPublicationRepository.php`**

Delete these `use` lines:

```php
use App\Ownership\Domain\Exception\UnknownListException;
use App\Ownership\Domain\Repository\MembersListRepositoryInterface;
use App\Ownership\Domain\Entity\MembersListInterface;
use App\Twitter\Infrastructure\Publication\Repository\HighlightRepository;
```

Also delete the `try { ... } catch (UnknownListException) { ... }` block from `findBy()` if it references `UnknownListException` — replace with the inner `try` body's content (the `getHighlightsSnapshot` call without the catch).

- [ ] **Step 4: Update `config/services/repository.yml`**

In `config/services/repository.yml`, find the `App\Trends\Infrastructure\Repository\PopularPublicationRepository` definition. Reduce its `arguments` to two (projectDir + logger). Example final shape:

```yaml
    App\Trends\Infrastructure\Repository\PopularPublicationRepository:
        class: 'App\Trends\Infrastructure\Repository\PopularPublicationRepository'
        arguments:
            - '%kernel.project_dir%'
            - '@logger'
```

(Adjust to match your existing key style; the point is dropping `defaultPublishersList`, `highlightRepository`, and `publishersListRepository` arguments.)

- [ ] **Step 5: Verify**

```bash
php -l src/Trends/Infrastructure/Repository/PopularPublicationRepository.php
bin/console cache:clear --env=test 2>&1 | tail -3
bin/phpunit --group controller -c phpunit.xml.dist 2>&1 | head -7
```

Expected: lint passes; cache:clear succeeds; 5 tests pass.

- [ ] **Step 6: Commit**

```bash
git add src/Trends/Infrastructure/Repository/PopularPublicationRepository.php config/services/repository.yml
git commit -m "refactor(trends): slim PopularPublicationRepository to its actual deps

Constructor reduced from 5 parameters to 2: projectDir + logger. Drops
HighlightRepository, MembersListRepositoryInterface, and the unused
\$defaultPublishersList — all three were stored but never read in any
method body. findBy() reads local Bluesky JSON snapshots, no Doctrine
or list-repository involvement at runtime."
```

---

### Task 3: Slim `MemberRepository` to its single live method

`MemberRepository` has dozens of methods but only `getMemberHavingApiKey()` is invoked from the live keep-set (TokenAuthenticator's `getCredentials` for OPTIONS preflight). Drop all other methods, drop their dead imports, drop the membersListRepository property.

**Files:**
- Modify: `src/Twitter/Infrastructure/Repository/Membership/MemberRepository.php`
- Modify: `config/services/repository.yml` (or wherever MemberRepository is defined)

**Note:** `PaginationAwareTrait` import + `use` mixin + `countTotalPages()` method were already stripped from `MemberRepository.php` during the brainstorming session and are in the working tree (uncommitted). This task replaces the entire file with the slim version below, which subsumes those edits.

- [ ] **Step 1: Replace MemberRepository with a slim version**

Read the current file to find the class declaration line and the `getMemberHavingApiKey` method definition. The end-state file should contain only:

```php
<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Repository\Membership;

use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * @method MemberInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method MemberInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method MemberInterface[]    findAll()
 * @method MemberInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MemberRepository extends ServiceEntityRepository implements MemberRepositoryInterface
{
    use LoggerTrait;

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getMemberHavingApiKey(): MemberInterface
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $queryBuilder->andWhere('u.apiKey is not null');

        return $queryBuilder->getQuery()->getSingleResult();
    }
}
```

If `MemberRepositoryInterface` declares methods beyond `getMemberHavingApiKey`, this slimming will break the interface contract. Check first:

```bash
grep -E "function" src/Twitter/Domain/Membership/Repository/MemberRepositoryInterface.php
```

If the interface declares methods we just dropped, slim the interface too — keep only `getMemberHavingApiKey(): MemberInterface;` — or drop the interface entirely if it adds no value (this is the previously-discussed candidate B in the brainstorming).

- [ ] **Step 2: Verify the file lints and the test suite passes**

```bash
php -l src/Twitter/Infrastructure/Repository/Membership/MemberRepository.php
bin/console cache:clear --env=test 2>&1 | tail -3
bin/phpunit -c phpunit.xml.dist 2>&1 | head -7
```

Expected: 12 tests still pass (5 controller tests + 7 pre-existing). If pre-existing tests fail (e.g., `MemberRepositoryBuilder` or repository tests covering deleted methods), those tests are part of Task 7's deletion sweep — note them and proceed.

- [ ] **Step 3: Commit**

```bash
git add src/Twitter/Infrastructure/Repository/Membership/MemberRepository.php src/Twitter/Domain/Membership/Repository/MemberRepositoryInterface.php
git commit -m "refactor(membership): slim MemberRepository to getMemberHavingApiKey()

Drop ~30 methods that have zero callers in the live runtime path. The only
caller (TokenAuthenticator::getCredentials on OPTIONS preflight) needs
exactly one query: 'find any member with a non-null apiKey'. Aligns the
interface to match.

This refactor enables the next batch of deletions: Translator, Accessor,
StatusAccessor, NotFoundMember, ProtectedMember, SuspendedMember,
ExceptionalMember, InvalidMemberIdentifier, InvalidMemberException,
NotFoundMemberException, MemberIdentity, and the entire Twitter\\Domain\\
Curation/Publication/Resource namespaces are no longer reachable."
```

---

### Task 4: Delete the 190 PHP files outside the keep-set

Mass deletion. Closure walker output drives the list. Run after Tasks 1-3 land so the keep-set's static closure matches.

**Files:** ~190 PHP files across `src/Conversation/`, `src/Media/`, `src/Ownership/`, most of `src/Membership/`, most of `src/Trends/`, most of `src/Twitter/`.

- [ ] **Step 1: Compute the delete-list from the keep-set**

The keep-set (top of this plan) lists 18 files. Everything else under `src/` is the delete-list. Generate it by inverse:

```bash
cat <<'EOF' > /tmp/keep-set.txt
src/Kernel.php
src/bootstrap.php
src/Trends/Domain/Repository/PopularPublicationRepositoryInterface.php
src/Trends/Domain/Repository/SearchParamsInterface.php
src/Trends/Infrastructure/Controller/TrendsController.php
src/Trends/Infrastructure/Repository/PopularPublicationRepository.php
src/Twitter/Domain/Membership/Repository/MemberRepositoryInterface.php
src/Twitter/Infrastructure/Cache/RedisCache.php
src/Twitter/Infrastructure/DependencyInjection/LoggerTrait.php
src/Twitter/Infrastructure/DependencyInjection/Membership/MemberRepositoryTrait.php
src/Twitter/Infrastructure/Healthcheck/Controller/HealthcheckController.php
src/Twitter/Infrastructure/Http/SearchParams.php
src/Twitter/Infrastructure/Repository/Membership/MemberRepository.php
src/Twitter/Infrastructure/Security/Authentication/TokenAuthenticator.php
src/Twitter/Infrastructure/Security/Cors/CorsHeadersAwareTrait.php
src/Membership/Domain/Entity/Legacy/Member.php
src/Membership/Domain/Entity/MemberInterface.php
src/Membership/Domain/Model/Member.php
EOF

find src -type f -name "*.php" | sort > /tmp/all-php.txt
comm -23 /tmp/all-php.txt <(sort /tmp/keep-set.txt) > /tmp/delete-list.txt
wc -l /tmp/delete-list.txt
```

Expected: ~190 lines.

- [ ] **Step 2: Delete in one rm pass**

```bash
xargs -I{} rm "{}" < /tmp/delete-list.txt
```

- [ ] **Step 3: Remove now-empty directories**

```bash
find src -type d -empty -delete
```

Expected: directories like `src/Conversation/`, `src/Media/`, `src/Ownership/`, `src/Trends/Domain/Entity/`, `src/Trends/Infrastructure/Repository/` (since only one file remains), etc. get pruned.

- [ ] **Step 4: Verify the 18-file keep-set is what survives**

```bash
find src -type f -name "*.php" | sort
find src -type f -name "*.php" | wc -l
```

Expected: 18 files, matching the keep-set listed at the top of this plan.

- [ ] **Step 5: Run cache:clear — this WILL fail until config/services + doctrine config are also slimmed (next tasks)**

```bash
bin/console cache:clear --env=test 2>&1 | tail -10
```

Expected: failure with "service X references nonexistent class Y" or similar. **Do not commit yet.** Proceed to Task 5.

---

### Task 5: Slim `config/packages/doctrine.yaml`

Drop the `doctrine_extensions_dql` parameter (massive DQL function catalog from oro/beberlei — none of those functions are called by any keep-set query). Drop 5 of 6 entity mapping groups. Keep only the Membership annotation mapping.

**Files:**
- Modify: `config/packages/doctrine.yaml`

- [ ] **Step 1: Replace the whole file with the slim version**

End-state of `config/packages/doctrine.yaml`:

```yaml
doctrine:
    dbal:
        connections:
            default:
                url: '%env(resolve:DATABASE_URL)%'
                server_version: '5.7'
                charset: utf8mb4
                logging: false
            write:
                url: '%env(resolve:DATABASE_WRITE_URL)%'
                server_version: '5.7'
                charset: utf8mb4
                logging: false
        types:
            uuid:  Ramsey\Uuid\Doctrine\UuidType

    orm:
        default_entity_manager:         default
        auto_generate_proxy_classes:    true
        entity_managers:
            default:
                naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
                auto_mapping: false
                connection: default
                mappings:
                    Membership:
                        alias:      'Membership'
                        dir:        '%kernel.project_dir%/src/Membership/Domain/Entity/Legacy'
                        is_bundle:  false
                        prefix:     'App\Membership\Domain\Entity\Legacy'
                        type:       annotation
```

This drops:
  - The entire `parameters: doctrine_extensions_dql:` block (~110 lines of MySQL DQL function registrations).
  - The `dql: "%doctrine_extensions_dql%"` reference under `entity_managers.default`.
  - The 4 deleted annotation mappings: Http, Membership-via-different-prefix, MembersList, Publication.
  - The 2 XML mappings: Curation, Trends.

If `Ramsey\Uuid\Doctrine\UuidType` is not used by any property of the surviving Member entity, also drop the `types:` block. Check first:

```bash
grep -nE "@ORM\\\\Column.*type=\"uuid\"" src/Membership/Domain/Entity/Legacy/Member.php
```

If empty, drop the `types:` block.

- [ ] **Step 2: Verify cache:clear succeeds**

```bash
bin/console cache:clear --env=test 2>&1 | tail -3
```

Expected: `[OK] Cache for the "test" environment ...`. If it still fails, the failing service is in `config/services/*.yml` — proceed to Task 6 to slim those.

---

### Task 6: Slim `config/services.yaml`, `config/services/*.yml`, `config/services_test.yaml`

Most service definitions reference deleted classes. Trim to only what's needed for the 18-file keep-set.

**Files:**
- Modify: `config/services.yaml`
- Modify: `config/services/*.yml` (all files in the directory)
- Modify: `config/services_test.yaml`

- [ ] **Step 1: Identify which service definitions reference deleted classes**

```bash
for cfg in config/services.yaml config/services/*.yml config/services_test.yaml; do
  echo "=== $cfg ==="
  grep -nE "App\\\\(Conversation|Media|Ownership|Trends\\\\Domain\\\\Entity|Twitter\\\\Domain\\\\(Curation|Publication|Resource|Http)|Twitter\\\\Infrastructure\\\\(Http\\\\(Accessor|AccessToken|Entity|Exception|Repository)|Publication|Database|Operation|Clock|Exception|Translation|Curation))" "$cfg" 2>/dev/null
done
```

Expected: many hits across the service files. Each is a definition referencing a deleted class — they all need to be removed.

- [ ] **Step 2: Slim each service config file**

For each `config/services/*.yml` file: open, identify each top-level service ID that references a deleted class (either in `class:` line or via `@App\Foo\Bar` argument), remove the entire definition block. Keep only definitions that wire one of the 18 keep-set classes.

Concrete keepers (the only services that should remain after this step):

In `config/services.yaml`:
  - `_defaults` block (autowire, autoconfigure, public defaults)
  - `app.cache.redis` definition + the `App\Twitter\Infrastructure\Cache\RedisCache` alias
  - `parameters:` block — keep `redis_host`, `redis_port`, `redis_cache.class`, `allowed.origin`. Drop everything else (any param referenced only by deleted services).

In `config/services/repository.yml`:
  - `App\Trends\Infrastructure\Repository\PopularPublicationRepository`
  - `App\Twitter\Infrastructure\Repository\Membership\MemberRepository` (likely defined as a Doctrine ServiceEntityRepository — confirm pattern)

In `config/services/controller.yml`:
  - `App\Trends\Infrastructure\Controller\TrendsController`
  - `App\Twitter\Infrastructure\Healthcheck\Controller\HealthcheckController`

`config/services/*.yml` files that ONLY define deleted services (e.g., a hypothetical `event.yml` or `accessor.yml`) get **deleted entirely**:

```bash
ls config/services/
```

Inspect each file and `rm` any file that has zero remaining definitions after the trim.

In `config/services_test.yaml`:
  - `_defaults` block
  - `App\Tests\NewsReview\Infrastructure\Repository\InMemoryPopularPublicationRepository` definition
  - `app.cache.redis` override pointing at `App\Tests\Twitter\Infrastructure\Cache\InMemoryRedisCache`
  - Drop the three `test.event_repository.member_profile_collected` and `test.App\Twitter\Infrastructure\Curation\Repository\…` aliases — those reference deleted classes.

- [ ] **Step 3: Verify cache:clear succeeds across all envs**

```bash
bin/console cache:clear --env=dev 2>&1 | tail -3
bin/console cache:clear --env=test 2>&1 | tail -3
bin/console cache:clear --env=prod 2>&1 | tail -3
```

Expected each: `[OK]`. If any fails with "service X references nonexistent class Y", the y is still listed somewhere — go back and remove.

- [ ] **Step 4: Delete the XML mapping directories**

```bash
rm -rf config/model/curation config/model/trends
```

If `config/model/` is empty after this, also `rmdir config/model`.

- [ ] **Step 5: Drop `JoliTypoBundle` if unused**

```bash
grep -rn "JoliTypo\|joli_typo\|JoliTypoBundle" src/ config/ 2>&1 | grep -v "config/bundles.php\|config/packages/joli_typo.yaml"
```

If empty (no usages outside its registration + config), remove:
  - The `JoliTypo\Bridge\Symfony\JoliTypoBundle::class` line from `config/bundles.php`
  - The `config/packages/joli_typo.yaml` file

- [ ] **Step 6: Final cache:clear and route check**

```bash
bin/console cache:clear --env=dev --env=test --env=prod
bin/console debug:router 2>&1 | head -10
```

Expected: cache clears clean; router shows the 3 keep routes (`callback`, `healthcheck`, `highlight`).

- [ ] **Step 7: Commit Tasks 4-6 together**

```bash
git add -A
git commit -m "feat: delete deprecated codebase down to 18 PHP files

Removes 190 PHP files outside the live runtime closure for the three API
routes (/api/callback, /api/healthcheck, /api/twitter/highlights). Cascading
config cleanup:

- doctrine.yaml: 6 entity mapping groups -> 1 (Membership only); drop the
  doctrine_extensions_dql DQL catalog (oro+beberlei functions never called
  by any surviving query).
- config/services*.yml: trim service definitions to wire only the 18-file
  keep-set; drop all definitions referencing deleted classes.
- config/model/{curation,trends}/: XML mappings for deleted entities gone.
- config/bundles.php: drop JoliTypoBundle if unused (verified by grep).
- src/: delete src/Conversation, src/Media, src/Ownership entire dirs;
  prune src/Membership/, src/Trends/, src/Twitter/ to keep-set only.

Verification: bin/console cache:clear clean across dev/test/prod; the 3
api.yaml routes are reachable per debug:router."
```

---

### Task 7: Slim the test suite

Most existing tests cover deleted code. Keep only tests for the 18-file keep-set.

**Files:**
- Delete most files under `tests/`

- [ ] **Step 1: List the test files to keep**

```bash
cat <<'EOF'
tests/bootstrap.php
tests/Trends/Infrastructure/Controller/TrendsControllerTest.php
tests/Twitter/Infrastructure/Healthcheck/Controller/HealthcheckControllerTest.php
tests/Twitter/Infrastructure/Cache/InMemoryRedisCache.php
tests/NewsReview/Infrastructure/Repository/InMemoryPopularPublicationRepository.php
tests/Resources/Response/ListHighlights.b64
EOF
```

(The last is a fixture binary file, base64-encoded JSON used by the in-memory repo.)

- [ ] **Step 2: Identify and delete everything else under `tests/`**

```bash
find tests -type f -not -path "tests/bootstrap.php" \
  -not -path "tests/Trends/Infrastructure/Controller/TrendsControllerTest.php" \
  -not -path "tests/Twitter/Infrastructure/Healthcheck/Controller/HealthcheckControllerTest.php" \
  -not -path "tests/Twitter/Infrastructure/Cache/InMemoryRedisCache.php" \
  -not -path "tests/NewsReview/Infrastructure/Repository/InMemoryPopularPublicationRepository.php" \
  -not -path "tests/Resources/Response/ListHighlights.b64" \
  -delete

find tests -type d -empty -delete
```

- [ ] **Step 3: Trim `phpunit.xml.dist` group whitelist**

The existing `<groups><include>` whitelist contains many groups that no longer have tests. Reduce to:

```xml
<groups>
  <include>
    <group>controller</group>
  </include>
</groups>
```

(Drop the other 17 group names — they no longer match any test.)

Also reduce the `<coverage>` block to drop paths that no longer exist:

```xml
<coverage processUncoveredFiles="true">
  <include>
    <directory suffix=".php">src</directory>
  </include>
</coverage>
```

- [ ] **Step 4: Verify the surviving 5 controller tests pass**

```bash
bin/phpunit -c phpunit.xml.dist 2>&1 | head -7
```

Expected: `OK (5 tests, 14 assertions)` (the 5 controller tests; the other 7 pre-existing tests are gone with their target code).

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "test: prune tests/ to cover only the 18-file keep-set

Surviving tests:
- TrendsControllerTest (3 tests over /api/callback + /api/twitter/highlights)
- HealthcheckControllerTest (2 tests over /api/healthcheck)
- InMemoryRedisCache + InMemoryPopularPublicationRepository (test doubles
  used by the controller tests)

phpunit.xml.dist <groups> whitelist trimmed to 'controller' only; coverage
include path simplified to 'src/'."
```

---

### Task 8: Add authentication functional test for TrendsController

The existing test suite bypasses auth via `firewalls.main.security: false` in `when@test:`. Re-enable real auth in the test env (mirroring prod's access_control), schema-create the `Member` table, insert a Member fixture with a known apiKey, and add a test that authenticates by passing that token in the `x-auth-token` header. Existing `getHighlights` tests are updated to send the same header so they keep passing.

Sequenced after Task 7 (test pruning) so we're working in the slim post-cleanup layout: only `Member` is mapped, schema creation is trivial.

**Files:**
- Modify: `config/packages/security.yaml` (`when@test:` block)
- Modify: `tests/Trends/Infrastructure/Controller/TrendsControllerTest.php` (add new test, update existing `getHighlights` tests to pass the token)

- [ ] **Step 1: Restore real access_control in `when@test:`**

In `config/packages/security.yaml`, replace the existing `when@test:` block:

```yaml
when@test:
    security:
        firewalls:
            main:
                security: false
        access_control:
            - { path: ^/api, role: IS_AUTHENTICATED_ANONYMOUSLY }
```

with:

```yaml
when@test:
    security:
        access_control:
            - { path: ^/api/callback,    role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/api/healthcheck, role: IS_AUTHENTICATED_ANONYMOUSLY }
            - { path: ^/api,             role: ROLE_USER }
```

This mirrors prod: callback and healthcheck stay anonymous (their existing tests don't need a token); `/api/twitter/highlights` falls under the `^/api, ROLE_USER` rule and now requires real authentication via `TokenAuthenticator`.

- [ ] **Step 2: Update `TrendsControllerTest` — add a `setUp` that creates schema + Member fixture, plus the new auth test**

Replace the entire content of `tests/Trends/Infrastructure/Controller/TrendsControllerTest.php` with:

```php
<?php
declare(strict_types=1);

namespace App\Tests\Trends\Infrastructure\Controller;

use App\Membership\Domain\Entity\Legacy\Member;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group controller
 */
class TrendsControllerTest extends WebTestCase
{
    private const DUMMY_TOKEN = 'dummy-test-token';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        $member = new Member();
        $member->setApiKey(self::DUMMY_TOKEN);
        $member->setEmail('dummy@test.local');
        $member->setTwitterID('1');
        $member->setTwitterUsername('dummy_user');
        $member->setScreenName('dummy_user');
        $member->setFullName('Dummy User');
        $em->persist($member);
        $em->flush();
    }

    public function test_callback_returns_acknowledgement_payload(): void
    {
        // /api/callback is IS_AUTHENTICATED_ANONYMOUSLY — no token needed.
        $this->client->request('GET', '/api/callback');

        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertJson($response->getContent());

        $body = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsString($body);
        self::assertStringContainsString("That's all folks!", $body);
    }

    public function test_get_highlights_authenticates_with_dummy_token_and_returns_collection_shape(): void
    {
        $this->client->request(
            'GET',
            '/api/twitter/highlights',
            [
                'startDate'       => '2024-01-01 00:00:00',
                'endDate'         => '2024-01-01 23:59:59',
                'includeRetweets' => '0',
            ],
            [],
            ['HTTP_X_AUTH_TOKEN' => self::DUMMY_TOKEN]
        );

        $response = $this->client->getResponse();

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Authenticated /api/twitter/highlights request must succeed with a Member fixture and a matching x-auth-token header'
        );
        self::assertTrue($response->headers->has('x-total-pages'));
        self::assertTrue($response->headers->has('x-page-index'));

        $body = json_decode($response->getContent(), true);
        self::assertIsArray($body);
        self::assertArrayHasKey('aggregates', $body);
        self::assertArrayHasKey('statuses', $body);
    }

    public function test_get_highlights_rejects_unauthenticated_request(): void
    {
        // No x-auth-token header — firewall must reject before reaching the controller.
        $this->client->request(
            'GET',
            '/api/twitter/highlights',
            [
                'startDate'       => '2024-01-01 00:00:00',
                'endDate'         => '2024-01-01 23:59:59',
                'includeRetweets' => '0',
            ]
        );

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }
}
```

Notes:
  - The `setUp()` drops + creates the schema each test for isolation. Since the test uses `sqlite:///:memory:`, this is fast.
  - `Member::set...` calls cover the typically-required fields (`apiKey`, `email`, `twitterID`, `twitterUsername`, `screenName`, `fullName`). If the Member entity has additional NOT NULL columns surfaced as the test runs, fix the fixture by adding the appropriate setters until the persist succeeds — this is a one-time discovery.
  - The previously-existing `test_get_highlights_rejects_request_without_required_params` test has been replaced by `test_get_highlights_rejects_unauthenticated_request` (the unauthenticated check now rejects earlier in the pipeline, before param validation; the param-validation path is exercised by the authenticated-but-malformed scenario which is out of scope for this minimum test).

- [ ] **Step 3: Verify the suite passes**

```bash
bin/phpunit -c phpunit.xml.dist 2>&1 | head -10
```

Expected: `OK (4 tests, ...)` (the 2 healthcheck tests + 3 trends tests = 5 tests; or adjust to whatever the actual count is). All assertions pass.

If `setUp()` fails on `persist($member)` due to a NOT NULL constraint on a column not yet set, add the corresponding `$member->set...()` call and re-run. Once green, the fixture is settled.

- [ ] **Step 4: Commit**

```bash
git add config/packages/security.yaml tests/Trends/Infrastructure/Controller/TrendsControllerTest.php
git commit -m "test(trends): cover TrendsController auth flow with a dummy x-auth-token

Re-enables real auth in the test env (when@test no longer sets
firewalls.main.security: false; access_control mirrors prod). The slim
post-cleanup schema (only Member is mapped) makes a SchemaTool::createSchema
+ Member fixture in setUp() trivially fast against sqlite:///:memory:.

Adds:
- test_get_highlights_authenticates_with_dummy_token_and_returns_collection_shape
  — sends x-auth-token: dummy-test-token, verifies 200 and the collection
  shape (statuses, aggregates, x-total-pages, x-page-index headers).
- test_get_highlights_rejects_unauthenticated_request — no header, asserts
  403 from the firewall before the controller runs.

The /api/callback test stays anonymous (mirrors prod access_control)."
```

---

### Task 9: Drop unused composer dependencies

Most composer deps are now orphan. Remove them in one resolved update.

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Affected: `vendor/`

- [ ] **Step 1: Identify candidates**

For each candidate, check if any keep-set file references it:

```bash
for dep_namespace in 'Abraham\\TwitterOAuth' 'JoliCode\\\|JoliTypo\\' 'BeBerlei\\\|Beberlei\\' 'Oro\\\|DoctrineExtensions\\' 'Scienta\\' 'Ramsey\\Uuid\\Doctrine'; do
  echo "=== $dep_namespace ==="
  grep -rln "use $dep_namespace" src/ 2>/dev/null | head -3
done
```

For each that returns empty, the package is removable.

Likely confirmed orphans:
  - `abraham/twitteroauth` (Twitter API client gone)
  - `jolicode/jolitypo` (typography bundle dropped)
  - `beberlei/doctrineextensions`
  - `oro/doctrine-extensions`
  - `scienta/doctrine-json-functions`
  - `ramsey/uuid-doctrine` (only if the Member entity has no `uuid` columns — verify)
  - `ocramius/package-versions` (deferred follow-up from earlier brainstorming — verify no usage)
  - `elvanto/litemoji` (verify no usage in keep-set)

Likely keepers:
  - `predis/predis` — RedisCache uses Predis client at runtime
  - `symfony/*` framework components — needed
  - `doctrine/orm`, `doctrine/doctrine-bundle`, `doctrine/doctrine-migrations-bundle` — Member entity hydration
  - `beberlei/assert`, `thecodingmachine/safe`, `psr/log`, etc. — likely transitively or directly used

- [ ] **Step 2: Remove the confirmed orphans**

```bash
composer remove --no-scripts \
  abraham/twitteroauth \
  jolicode/jolitypo \
  beberlei/doctrineextensions \
  oro/doctrine-extensions \
  scienta/doctrine-json-functions
```

(Add `ramsey/uuid-doctrine`, `ocramius/package-versions`, `elvanto/litemoji` to the same line if Step 1 confirmed they're orphan.)

Expected: composer resolves; lock + vendor updated; `composer audit` reports clean.

- [ ] **Step 3: Verify the suite still passes and cache clears**

```bash
composer audit
bin/console cache:clear --env=test
bin/phpunit -c phpunit.xml.dist 2>&1 | head -7
```

Expected: audit clean; cache clear OK; 5 tests pass.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock
git commit -m "build: drop composer deps orphaned by codebase cleanup

Removes:
- abraham/twitteroauth (Twitter API client removed with src/Twitter/...)
- jolicode/jolitypo (JoliTypoBundle dropped, no typography usage)
- beberlei/doctrineextensions, oro/doctrine-extensions (DQL function
  catalog removed from doctrine.yaml; no surviving query uses them)
- scienta/doctrine-json-functions (only used by deleted PublicationRepository)

(Plus any from {ramsey/uuid-doctrine, ocramius/package-versions,
elvanto/litemoji} confirmed orphan during Step 1 inspection.)"
```

---

### Task 10: Final verification and PR

- [ ] **Step 1: Run the full Stage 0 exit gate against the cleaned codebase**

```bash
bin/phpunit -c phpunit.xml.dist 2>&1 | head -7
composer audit 2>&1 | tail -3
bin/console cache:clear --env=dev 2>&1 | tail -2
bin/console cache:clear --env=test 2>&1 | tail -2
bin/console cache:clear --env=prod 2>&1 | tail -2
bin/console debug:router 2>&1 | head -10
git status
```

Expected:
  - phpunit: `OK (5 tests, 14 assertions)`
  - audit: `No security vulnerability advisories found.`
  - cache:clear: `[OK]` × 3
  - debug:router: shows the 3 keep routes (callback, healthcheck, highlight)
  - git status: `nothing to commit, working tree clean`

- [ ] **Step 2: Do a manual smoke check of the three routes**

Start the dev server (or whatever the project's local-run mechanism is — `symfony server:start` or `bin/console server:run` per Symfony version) and curl each route:

```bash
# Run in another terminal: bin/console server:run --env=dev
curl -i http://localhost:8000/api/healthcheck
# Expected: 200, body []

curl -i http://localhost:8000/api/callback
# Expected: 200, JSON body containing "That's all folks!"

curl -i "http://localhost:8000/api/twitter/highlights?startDate=2024-01-01+00:00:00&endDate=2024-01-01+23:59:59&includeRetweets=0"
# Expected: 200, JSON with statuses + aggregates keys, plus
# x-total-pages and x-page-index headers
```

Document the smoke results in the PR description.

- [ ] **Step 3: Push the branch and open the PR**

```bash
git push -u origin http-api
gh pr create --base main --title "Codebase cleanup: shrink to 18-file live core" --body "$(cat <<'EOF'
## Summary

Deletes ~190 of 208 PHP files (91% of \`src/\`) plus their config, tests, XML mappings, and unused composer deps. Surviving keep-set is the minimum needed to serve the three live API routes:

- \`/api/callback\` (TrendsController::callback)
- \`/api/healthcheck\` (HealthcheckController::areServicesHealthy)
- \`/api/twitter/highlights\` (TrendsController::getHighlights)

### What changed

- **18 PHP files survive** under \`src/\`: TrendsController, HealthcheckController, TokenAuthenticator, MemberRepository (slimmed to \`getMemberHavingApiKey()\` only), MemberRepositoryInterface, PopularPublicationRepository (constructor reduced 5 → 2 params), PopularPublicationRepositoryInterface, RedisCache, SearchParams + Interface, CorsHeadersAwareTrait, MemberRepositoryTrait, LoggerTrait, Member (Legacy) + Model + Interface, plus Kernel and bootstrap.
- **Pre-cleanup refactors**: stripped 4 dead injections from TrendsController (\`tokenRepository\`, \`memberRepository\`, \`highlightRepository\`, \`router\` were container-wired but never invoked); stripped 3 dead injections from PopularPublicationRepository; slimmed MemberRepository.
- **Doctrine config**: \`doctrine.yaml\` reduced from 6 entity mapping groups to 1; the \`doctrine_extensions_dql\` parameter (oro/beberlei DQL catalog) deleted entirely.
- **Service config**: \`services.yaml\`, \`services/*.yml\`, \`services_test.yaml\` trimmed to the surviving classes only. \`config/model/curation/\` and \`config/model/trends/\` XML mapping directories deleted.
- **Tests**: trimmed to the 5 controller tests + the 2 in-memory test doubles.
- **Composer deps removed**: \`abraham/twitteroauth\`, \`jolicode/jolitypo\`, \`beberlei/doctrineextensions\`, \`oro/doctrine-extensions\`, \`scienta/doctrine-json-functions\` (plus any others confirmed orphan during Task 8).

### Test plan

- [x] \`bin/phpunit\` — 5 tests pass
- [x] \`composer audit\` — clean
- [x] \`bin/console cache:clear\` clean across dev, test, prod
- [x] Manual smoke: GET each of the 3 routes returns expected payload (capture in PR comments)

### Out of scope

- The Symfony 5.4 → 7.4 LTS migration is the sequel. After this PR merges, the migration plan is rewritten against the slim codebase — single-hop direct 5.4 → 7.4 likely viable.

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

- [ ] **Step 4: Print PR URL**

```bash
gh pr view --json url --jq .url
```

---

## Done

Cleanup complete when this plan's tasks are checked off and Task 9's PR has merged to `main`.

**Next:** brainstorm + plan the Symfony 5.4 → 7.4 LTS migration against the 18-file core. Likely a single-PR direct hop (no intermediate 6.4 stop) given how small the surface area is.

The cleanup PR ships with three tests (one anonymous on `/api/callback`, two authenticated/unauthenticated on `/api/twitter/highlights`) plus the two healthcheck tests, so the auth path is now covered going into the migration.
