# Chat module — post-`composer install` activation

The scaffolding for `App\Chat` is committed to the repo with all
its config in place under `config/packages/`. Activation steps after
cloning:

## 1. Install dependencies

```bash
composer install
```

This resolves (already declared in `composer.json`):

- `symfony/ai-platform ^0.9`
- `symfony/ai-store ^0.9`
- `symfony/ai-bundle ^0.9`
- `lcobucci/jwt ^5.4` (used by `BlueskyJwtAuthenticator` to verify the
  Nuxt-signed JWT — picked over `firebase/php-jwt` which is currently
  flagged by `roave/security-advisories` on v6.10–v6.11)

These are pre-1.0 and **not** covered by Symfony's BC promise. Pin to the
exact resolved version in `composer.lock`; review the changelog before
bumping.

`config/packages/{ai,services.chat,security.chat}.yaml` load
automatically as soon as composer makes the bundles + library available
— no rename or copy step required.

## 2. Verify the Symfony AI service ids

The wiring under `config/packages/services.chat.yaml` is written against
the v0.9 docs. Confirm the bundle exposes the expected ids:

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

## 3. Generate the shared JWT secret

```bash
make chat-jwt-secret
# Paste the printed line into:
#   .env.local (this repo, API verifier)
#   ../org.revue-de-presse.benchmark/nuxt/.env (Nuxt signer)
#   Netlify env vars for prod
```

## 4. Run migrations and bootstrap the vector store

```bash
make migrate
make chat-store-setup
```

`make migrate` runs the Doctrine migrations: enable the `vector`
extension (no-op in prod since pgvector is already installed on
`io_marianne-database-1`) and create `chat_conversation` +
`chat_turn`.

`make chat-store-setup` provisions the `chat_publication_embedding`
table + HNSW index owned by `symfony/ai-store` (via
`bin/console ai:store:setup chat_publications`). Idempotent; safe to
re-run.

Both steps are **wired into `make install`**: the bootstrap script
calls `run_doctrine_migrations` followed by `run_chat_store_setup`
automatically, so a fresh `make install` brings the chat module fully
up. The standalone targets exist for partial / repeat invocations.

## 5. Backfill the embedding corpus

```bash
make chat-embed-snapshots ARGS="--from=2025-03-04"
# or run synchronously inside the container:
docker exec -ti $(docker ps -a | awk '/api[-]service/ {print $1; exit}') \
    bin/console chat:embed-snapshots --from=2025-03-04
```

Wall time: ~2 minutes against the Mistral free tier (≈ 131 batched
HTTP calls for 14 months × 10 publications/day).

## 6. Smoke test

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
