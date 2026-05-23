# Newsletter context

Daily-report mailer. See the design at
`internal-spec/specs/2026-05-23-org.revue-de-presse.api-newsletter-daily-report-design.md`
(in the documentation repo, symlinked from `org.revue-de-presse.benchmark/internal-spec/`).

## Commands

- `bin/console newsletter:enroll <email>` — enrol a recipient (sends confirm email).
- `bin/console newsletter:list [--status=active|pending|unsubscribed]` — list.
- `bin/console newsletter:send-daily [--date=YYYY-MM-DD] [--dry-run]` — cron entry.
- `bin/console newsletter:rotate-encryption-key` — re-encrypt rows with NEXT key.
- `bin/console newsletter:sync-tokens` — verify design tokens haven't drifted.

## Local dev

```bash
docker run -d --name mailpit -p 1025:1025 -p 8025:8025 axllent/mailpit
echo "MAILER_DSN=smtp://localhost:1025" >> .env.local
echo "NEWSLETTER_ENCRYPTION_KEY=$(openssl rand -base64 32)" >> .env.local
make install   # builds containers, runs install-app-requirements, clears+warms cache, runs migrations
symfony serve -d
open http://localhost:8000/_dev/newsletter/
```

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
