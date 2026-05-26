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

## 7. Daily sync via systemd timer

Each day the upstream news-review pipeline writes a fresh top-10
snapshot to `src/Bluesky/Resources/{YYYY-MM-DD}.json` (read by
`App\NewsReview\Infrastructure\Repository\FilesystemSnapshotReader`).
The cron's job is to embed **yesterday's** snapshot into pgvector once
it is guaranteed complete — i.e., shortly after Europe/Paris midnight.

We use a systemd oneshot + timer pair on the Docker host. Pick this
over a host crontab because (a) journalctl gives you logs and
last-run state without any extra plumbing, (b) `Persistent=true`
catches up a missed fire after a reboot, (c) `OnFailure=` can hook
straight into your alerting unit.

### Idempotency

Re-running the same date is safe. `SymfonyAiPublicationEmbedder`
maps each highlight to a `TextDocument` keyed by `publication_id`
(the at-proto URI), and the underlying `PostgresStore` upserts by
that id. Re-runs do, however, re-issue embedding HTTP calls to
Mistral — keep that in mind if you wire retries.

### Exit codes (from `chat:embed-snapshots`)

| Code | Meaning                                            |
|------|----------------------------------------------------|
| 0    | All resolved dates either embedded or empty        |
| 1    | Some publications embedded, some dates failed      |
| 2    | All resolved dates failed                          |

systemd treats 1 and 2 as `failed`, so an `OnFailure=` hook will
fire for either.

### `/etc/systemd/system/chat-embed-snapshots.service`

```ini
[Unit]
Description=Embed yesterday's Bluesky top-10 snapshot into pgvector
Wants=docker.service
After=docker.service

[Service]
Type=oneshot
WorkingDirectory=/opt/org.revue-de-presse.api/provisioning/containers
# Path to your checkout; docker-compose picks COMPOSE_PROJECT_NAME up
# from ../../.env.local (org_revue-de-presse_api).
EnvironmentFile=/opt/org.revue-de-presse.api/.env.local
ExecStart=/bin/bash -c '\
  TARGET_DATE=$(TZ=Europe/Paris date -d "yesterday" +%%F); \
  /usr/bin/docker compose \
    -f docker-compose.yaml \
    -f docker-compose.override.yaml \
    exec -T app \
    bin/console chat:embed-snapshots --date="$TARGET_DATE" --no-interaction'
# Tighten the box a little; the command only needs to talk to docker.
NoNewPrivileges=true
ProtectSystem=strict
ProtectHome=true
PrivateTmp=true
```

Adjust `WorkingDirectory` / `EnvironmentFile` paths to where the
repo is checked out on the host. The `%%` is a literal `%` escaped
for systemd unit-file syntax.

### `/etc/systemd/system/chat-embed-snapshots.timer`

```ini
[Unit]
Description=Daily run of chat-embed-snapshots.service (02:30 Europe/Paris)

[Timer]
# Fires at 02:30 local Paris time; ~2.5h after midnight is enough
# slack for the upstream snapshot writer to finish. Adjust if you
# know the upstream's own finish-by SLA.
OnCalendar=*-*-* 02:30:00 Europe/Paris
# Catch up after host downtime — better a late embed than none.
Persistent=true
Unit=chat-embed-snapshots.service

[Install]
WantedBy=timers.target
```

### Install / enable / verify

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now chat-embed-snapshots.timer

# Confirm it is scheduled
systemctl list-timers chat-embed-snapshots.timer

# Trigger an immediate ad-hoc run (useful for first-time validation)
sudo systemctl start chat-embed-snapshots.service

# Tail logs
journalctl -u chat-embed-snapshots.service -f
```

A successful run prints the same SymfonyStyle summary as the manual
invocation in step 5 — e.g. `10 publication(s) embedded across 1
snapshot(s) (0 skipped, 0 failed)`.

### Failure handling (optional)

Drop a sibling unit and reference it from the service via
`OnFailure=chat-embed-snapshots-failed.service`. Common patterns:

- `curl` to a webhook (Slack / Discord / Healthchecks.io)
- a one-line wrapper around `mail` to the on-call alias
- `journalctl -u chat-embed-snapshots.service -n 50 --no-pager`
  piped into the notifier so the alert carries context

Don't paper over a failure by adding `Restart=` — embedding is
expensive and most failures are upstream (Mistral 5xx, snapshot
file missing). A single best-effort run with a loud failure signal
is what you want.
