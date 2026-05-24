# Newsletter context

Daily-report mailer. See the design at
`internal-spec/specs/2026-05-23-org.revue-de-presse.api-newsletter-daily-report-design.md`
(in the documentation repo, symlinked from `org.revue-de-presse.benchmark/internal-spec/`).

## Commands

- `bin/console newsletter:enroll <email>` — enrol a recipient (sends confirm email).
- `bin/console newsletter:list [--status=active|pending|unsubscribed]` — list.
- `bin/console newsletter:send-daily [--date=YYYY-MM-DD] [--dry-run]` — cron entry.
- `bin/console newsletter:rotate-encryption-key` — re-encrypt rows with NEXT key.
- `bin/console newsletter:check-design-tokens` — audit design tokens against the source-of-truth JSON (read-only; reports drift).

## Local dev — end to end (enrol a recipient → send a real top-10 mail)

### 0. Prerequisites you bring yourself

The repo's docker-compose stack does **not** include Postgres; you must point
`DATABASE_URL` at a Postgres you already run (or install one). Everything else
(`app`, `cache`, `service`, the reverse-proxy if you want it) comes from the
stack.

### 1. Seed `.env.local`

```bash
cp .env.local.dist .env.local
# Fill in the standard keys: APP_SECRET, DATABASE_URL / POSTGRES_*,
# PROJECT, PROJECT_OWNER_UID, PROJECT_OWNER_GID, REDIS_HOST etc.
# The defaults in .env.local.dist explain each one.
```

Append the newsletter-specific keys:

```bash
cat >> .env.local <<'EOF'

MAILER_DSN=smtp://localhost:1025
NEWSLETTER_ENCRYPTION_KEY=$(openssl rand -base64 32)
# IMPORTANT: override the production default so confirm + unsubscribe links
# point at YOUR local app, not api.revue-de-presse.org. Set this to whatever
# URL you actually reach the app on locally — see step 4.
NEWSLETTER_BASE_URL=http://localhost:8000
EOF
# (substitute the openssl output for the bash expression — `>>` doesn't eval it)
```

### 2. Start Mailpit (captures every outgoing email at http://localhost:8025)

```bash
docker run -d --name mailpit -p 1025:1025 -p 8025:8025 axllent/mailpit
```

### 3. Build and migrate

```bash
make install   # builds containers, installs app requirements, clears+warms cache, runs Doctrine migrations
```

If you change migrations later: `make migrate` (idempotent; uses `--allow-no-migration`).

### 4. Serve the app locally

Pick one — whichever you use must match `NEWSLETTER_BASE_URL` from step 1:

- **Symfony CLI** (simplest if you have PHP on the host):
  `symfony serve -d` → reaches the app on `http://localhost:8000`.
- **The Docker reverse-proxy** (Traefik, behind the `frankenphp` compose profile):
  `make start-benchmark-stack` → reaches the app on `http://localhost`.
  Set `NEWSLETTER_BASE_URL=http://localhost`.
- **Just the `app` container** with an exposed port: uncomment / add a `ports:`
  block in `docker-compose.override.yaml` and use whatever port you bound.

### 5. Enrol yourself and confirm

```bash
bin/console newsletter:enroll you@example.com
# In a browser open http://localhost:8025 (Mailpit) — there's one new email.
# Click the "Confirmer mon abonnement" link inside it; it points at
# NEWSLETTER_BASE_URL/newsletter/confirm/<token>, which is your local app.
# You should land on the "Votre abonnement est confirmé" page.

bin/console newsletter:list   # expect 1 row with status=active
```

### 6. Send a daily top-10

`send-daily` needs a highlights snapshot file at
`src/Bluesky/Resources/<date>.json`. Several recent dates ship with the repo —
pick one that exists:

```bash
ls src/Bluesky/Resources/ | sort -r | head -3      # find latest snapshot dates
bin/console newsletter:send-daily --date=2026-05-07 --dry-run   # preview
bin/console newsletter:send-daily --date=2026-05-07             # actually send
# Mailpit (http://localhost:8025) now shows the daily top-10 mail.
```

### 7. Inspect templates without sending

While iterating on the Twig templates, hit the env-gated previews at:

```
http://localhost:8000/_dev/newsletter/                 (index)
http://localhost:8000/_dev/newsletter/daily-report
http://localhost:8000/_dev/newsletter/daily-report.txt
http://localhost:8000/_dev/newsletter/confirmed
http://localhost:8000/_dev/newsletter/confirm-failed
http://localhost:8000/_dev/newsletter/unsubscribe-confirm
http://localhost:8000/_dev/newsletter/unsubscribed
```

These routes are loaded only under `when@dev:` / `when@test:`, so they 404 in
prod.

## Cron entry (production)

```cron
30 5 * * *  rdp-api  cd /opt/rdp/api && bin/console newsletter:send-daily >> /var/log/rdp-newsletter/send.log 2>&1
```

## Key rotation

1. `openssl rand -base64 32` → new key.
2. Edit `.env.local`: put **old** key in `NEWSLETTER_ENCRYPTION_KEY`, **new** key in `NEWSLETTER_ENCRYPTION_KEY_NEXT`.
3. `bin/console newsletter:rotate-encryption-key`.
4. Swap envs: new key → `NEWSLETTER_ENCRYPTION_KEY`, unset NEXT.
5. Restart the app.

## Exit codes (newsletter:send-daily)

| Code | Meaning |
|---|---|
| 0 | Success or dry-run. |
| 1 | Lock held (already sent today). |
| 2 | Upstream highlights fetch failed. |
| 3 | Mailer failure for ≥1 recipient. |
| 4 | Config invalid. |
