# `org.revue-de-presse.api` — NestJS port

Drop-in replacement for the PHP/Symfony 7 + API Platform 4 HTTP API in this repo. Same routes, same DB schema, same Redis namespace for tokens and the highlights cache. Lives on branch `http-api_nest-js` off `http-api`.

The PHPUnit suite at `../tests/` is the parity oracle — every PHP test has a 1:1 Jest counterpart in `test/`.

## Prerequisites

- Node 24 LTS (`engines.node: >=24.0.0` in `package.json`)
- npm 10+
- Redis 7 (for local prod-style runs; tests use an in-memory double)
- PostgreSQL 14+ in dev/prod; SQLite `:memory:` in tests

## Quick start

```bash
make install                  # npm ci
make typecheck                # tsc --noEmit
make test                     # unit + component (88 tests, no Redis needed)
make test-e2e                 # 6 suites, 15 tests, supertest-driven
make test-all                 # lint + types + every tier except perf
make start-dev                # nest start --watch
```

`make help` lists every target with one-line descriptions.

## Endpoints

| Route | Auth | Notes |
|---|---|---|
| `GET  /api/healthcheck` | public | Returns `[]` + `Cache-Control: no-store` |
| `POST /api/token` | Basic | `Basic :<apiKey>` → 201 + `{access_token, token_type: "Bearer", expires_in: 900}` |
| `GET  /api/highlights` | Bearer | Hydra collection envelope w/ `@type: "Collection"`. `Accept: application/ld+json` returns the JSON-LD form; default JSON strips `@*` keys |
| `GET  /api/docs` | public | Swagger UI |
| `GET  /api/docs.json` | public | OpenAPI 3 |
| `GET  /api/docs.jsonld` | public | JSON-LD wrapper around the OpenAPI doc |

## Configuration

Env vars are zod-validated at boot (`src/config/env.ts`); the app refuses to start on missing/malformed values.

| Var | Required | Use |
|---|---|---|
| `APP_ENV` | yes (`dev`/`prod`/`test`) | x-benchmark gate, log format, sqlite vs pg branch |
| `DATABASE_URL` | yes | `postgresql://…` or `sqlite:///:memory:` |
| `REDIS_HOST`, `REDIS_PORT` | yes | ioredis connection |
| `ALLOWED_ORIGIN` | yes (regex) | CORS — also accepts `CORS_ALLOW_ORIGIN` as a fallback |
| `RATE_LIMIT_ENABLED` | yes (`true`/`false`) | Toggles the global `RateLimitGuard` |
| `TRUSTED_PROXIES` | no | Passed to Express `trust proxy` |
| `PROJECT_DIR` | no (`process.cwd()`) | `FilesystemSnapshotReader` reads `${PROJECT_DIR}/src/Bluesky/Resources/<date>.json` |
| `PORT` | no (3000) | HTTP listener |
| `PG_POOL_MIN` / `PG_POOL_MAX` | no (1 / 10) | `pg.Pool` |

`dotenv-flow` loads `.env*` from the repo root in the same precedence Symfony uses: `.env` → `.env.local` → `.env.<APP_ENV>` → `.env.<APP_ENV>.local`.

## Architecture

Hexagonal layout under `src/`:

```
src/
├── compose.ts               framework-agnostic composition root (builds full service graph)
├── config/env.ts            zod env loader (pure, shared by core and adapters)
├── core/                    pure TypeScript — zero NestJS / Drizzle / ioredis imports
│   ├── ports/logger.ts
│   ├── errors/
│   ├── auth/                AccessTokenRecord, AccessTokenStore (port), AccessTokenMinter, BasicCredentialsExtractor
│   ├── highlights/          HighlightDto, filters, normalizer, cache-key, SnapshotReader (port), HighlightsService, Hydra serializer, PerformanceMetrics
│   ├── members/             Member entity, MembersRepository (interface + MEMBERS_REPOSITORY symbol)
│   └── rate-limit/          RateLimiter (interface + RATE_LIMITER symbol), Policy types
└── adapters/
    ├── persistence/
    │   ├── drizzle/         DbModule, DB token, weavingUser schema, DrizzleMembersRepository
    │   └── redis/           RedisModule, REDIS_CLIENT token, RedisAccessTokenStore, RedisRateLimiter
    ├── snapshots/           FilesystemSnapshotReader
    └── nestjs/              NestJS HTTP adapter
        ├── auth/            AuthModule, BearerGuard (global APP_GUARD), @Public, TokenController, AccessTokenResponseDto
        ├── highlights/      HighlightsModule, HighlightsController
        ├── health/          HealthModule, HealthcheckController
        ├── rate-limit/      RateLimitModule, RateLimitGuard (global APP_GUARD)
        ├── common/          ProblemJsonFilter (RFC 7807), CacheLabelInterceptor (x-cache), PinoLogger, Swagger setup
        ├── config/          ConfigModule (provides ENV token)
        ├── app.module.ts    composes all feature modules
        └── main.ts          bootstrap (CORS, Swagger, problem+json filter)
```

Two guards are registered as `APP_GUARD`: `BearerGuard` (from `AuthModule`) runs first, `RateLimitGuard` (from `RateLimitModule`) runs second. Public routes (`@Public()`) skip the bearer check; the rate-limiter's path table skips `/api/healthcheck`.

## Wire-compatibility with the PHP service

The two apps share the same Redis namespace during cutover:

- **Bearer tokens** — `auth:bearer:<sha256(token)>` key, `{m: <memberId>, i: <issuedAtUnix>, e: <expiresAtUnix>}` JSON payload. Tokens minted in PHP keep working in NestJS and vice versa.
- **Highlights cache** — `highlights:items:<sha1>`. The sha1 is computed via `src/highlights/cache-key.ts` with byte-parity rules covered in `test/unit/highlights/highlight-cache-key.spec.ts` (Europe/Paris dateHour, `media` inversion, PHP `SORT_REGULAR`–compatible aggregate sort, two fixed PHP fixtures).
- **Rate-limit state** — *NOT* shared. Uses a dedicated `rl:` prefix to avoid double-counting during cutover.

## Testing

| Tier | Runner | DB | Redis | Count |
|---|---|---|---|---|
| Unit | Jest | none | in-memory double | 12 suites |
| Component | Jest + `Test.createTestingModule` | SQLite `:memory:` | in-memory double | 9 suites |
| E2E | Jest + supertest | SQLite `:memory:` | in-memory double (or eval-capable double for rate-limit) | 6 suites |
| Perf | Jest + `fetch` | (remote) | (remote) | 1 suite, auto-skipped without `BENCHMARK_HOST` |

The merge gates from the spec:

1. All 21 ported PHP tests have a Jest counterpart — checked by filename in CI.
2. All ported tests pass.
3. `make schema-parity` passes — `drizzle-kit introspect` against a Doctrine-migrated Postgres, diffed against `src/db/schema.ts`.
4. `test/unit/highlights/highlight-cache-key.spec.ts` byte-parity case is green.

```bash
make test-all                 # local gate
```

CI runs the same gates plus the schema-parity job. See `.github/workflows/api-nest.yml`.

## Deployment

`provisioning/containers/api-nest/Dockerfile` is a multi-stage build on `node:24-bookworm-slim`. Bluesky snapshots are baked into the image at `${PROJECT_DIR}/src/Bluesky/Resources/<date>.json` so the path resolves identically to the PHP container.

`provisioning/containers/docker-compose.yaml` adds the `api-nest` service under the `nest` profile, sharing the same network as the PHP service. Run both side-by-side and ramp reverse-proxy traffic.

## Cutover

See spec §9 (`internal-spec/specs/2026-05-17-nestjs-api-port-design.md`). Short version: deploy alongside PHP, ramp reverse-proxy weight 0% → 1% → 5% → 25% → 100%, watch `x-cache` hit-rates and p95 latency match. Roll back at any step by flipping the weight.

## Design decisions

- **No Passport / `@nestjs/passport`** — single bearer scheme; the guard is short and the store is the only stateful piece. Passport's "strategies" abstraction would add indirection without value.
- **No JWT** — tokens are opaque 64-hex strings; the server holds the truth in Redis (matches PHP).
- **No refresh tokens** — clients re-mint via `POST /api/token`.
- **Drizzle, not TypeORM** — SQL-first, lightweight, dual-driver (pg + sqlite). Migrations stay with Doctrine on the PHP side; `drizzle-kit introspect` is used as a parity check only.
- **No coverage thresholds** — coverage is published for visibility, not enforced. Mirrors the PHP suite's policy.
- **Hand-rolled Lua rate limiter** — Symfony's `cache.rate_limiter` pool format is internal, and double-counting during cutover would be worse than running two independent rate-limit states.
