# Chat module — post-`composer install` activation

The scaffolding for `App\Chat` is in the repo, but four steps must be
performed once `composer install` has resolved the Symfony AI initiative
packages and `firebase/php-jwt`.

## 1. Install dependencies

```bash
composer install
```

This resolves:

- `symfony/ai-platform ^0.9`
- `symfony/ai-store ^0.9`
- `symfony/ai-bundle ^0.9`
- `symfony/chat ^0.9`
- `firebase/php-jwt ^6.10`

These are pre-1.0 and **not** covered by Symfony's BC promise. Pin to the
exact resolved version in `composer.lock`; review the changelog before
bumping.

## 2. Activate the bundle-dependent config

Three `.dist` files in `config/packages/` carry the wiring that
references the AI bundle's service ids. Rename them to drop the suffix:

```bash
cd config/packages
mv ai.yaml.dist            ai.yaml
mv services.chat.yaml.dist services.chat.yaml
mv security.chat.yaml.dist security.chat.yaml
```

The framework loads every `*.yaml` under `config/packages/` at boot, so
no further `imports:` entry is needed.

## 3. Drop the PHPStan excludes

`phpstan.neon` excludes `src/Chat/Infrastructure/Symfony/Ai` and
`src/Chat/Infrastructure/Security/BlueskyJwtAuthenticator.php` because the
classes those files reference don't exist until step 1 is done. After
install, delete both lines so static analysis covers them:

```diff
     excludePaths:
         - src/Bluesky/Resources
         - tests/Resources
-        - src/Chat/Infrastructure/Symfony/Ai
-        - src/Chat/Infrastructure/Security/BlueskyJwtAuthenticator.php
```

Then `make phpstan` should be green.

## 4. Verify the Symfony AI service ids

The `services.chat.yaml.dist` and `ai.yaml.dist` are written against the
v0.9 docs. Confirm the bundle exposes the expected ids:

```bash
bin/console debug:container ai.
# Expect to see:
#   ai.indexer.chat_publications
#   ai.retriever.chat_publications
#   ai.platform.failover.chat_default
```

If any id differs in your resolved v0.9.x, update the `arguments:` in
`services.chat.yaml` accordingly. The `TODO(v0.9-API-check)` comments
in `src/Chat/Infrastructure/Symfony/Ai/*` flag the same uncertainty in
the adapter response-shape — those `getMetadata()` / `getContent()`
calls should be confirmed against the actual returned types.

## 5. Generate the shared JWT secret

```bash
make chat-jwt-secret
# Paste the printed line into:
#   .env.local (this repo, API verifier)
#   ../org.revue-de-presse.benchmark/nuxt/.env (Nuxt signer)
#   Netlify env vars for prod
```

## 6. Run migrations and bootstrap the vector store

```bash
make migrate
bin/console ai:store:setup chat_publications
```

The first creates `chat_conversation` + `chat_turn` and enables the
`vector` extension (no-op in prod since pgvector is already installed
on `io_marianne-database-1`). The second creates
`chat_publication_embedding` with its HNSW index.

## 7. Backfill the embedding corpus

```bash
make chat-embed-snapshots ARGS="--from=2025-03-04"
# or run synchronously inside the container:
docker exec -ti $(docker ps -a | awk '/api[-]service/ {print $1; exit}') \
    bin/console chat:embed-snapshots --from=2025-03-04
```

Wall time: ~2 minutes against the Mistral free tier (≈ 131 batched
HTTP calls for 14 months × 10 publications/day).

## 8. Smoke test

```bash
# Mint a one-shot JWT for curl:
NUXT_JWT=$(node -e "console.log(require('jsonwebtoken').sign(
  { sub: 'did:plc:example', iss: 'nuxt.revue-de-presse.org' },
  process.env.API_JWT_SECRET,
  { algorithm: 'HS256', expiresIn: '60s' }
))")

curl -N -H "Authorization: Bearer $NUXT_JWT" \
     -H 'Accept: text/event-stream' \
     -H 'Content-Type: application/json' \
     -d '{"userMessage":"Quelle était la une du 4 mars 2025 ?"}' \
     https://api.revue-de-presse.org/api/chat/turns
```

Expect SSE frames: a series of `event: token` deltas followed by one
`event: done` carrying citations.
