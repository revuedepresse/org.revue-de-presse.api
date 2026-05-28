# llama.cpp host-native backend

Two `llama-server` processes (chat + embeddings) running natively on the
Mac M2 deploy host. Replaces the Ollama Docker container.

## Topology

```
PHP container ──http://host.docker.internal:8081──► llama-server (Mistral 7B Q5_K_M)  ┐
PHP container ──http://host.docker.internal:8082──► llama-server (bge-m3 FP16)         │ Metal
                                                                                       │ on M2
                                                          ▲ launchd KeepAlive ▲────────┘
```

## First-time install on the deploy host

Pre-flight: ≥15 GB free disk, Homebrew installed, repo checked out on
the `chat` branch.

```bash
cd /Users/shal/repositories/deploying/org.revue-de-presse.api
git fetch origin chat
git checkout chat

# Ensure LLAMA_CHAT_MODEL is set in .env.local
grep '^LLAMA_CHAT_MODEL=' .env.local || echo 'LLAMA_CHAT_MODEL=mistral-7b-instruct-v0.3' >> .env.local

# One-shot install: brew, models, plists, doctor.
make llama-install
```

After install, both LaunchAgents are loaded and start on user login.
Verify with:

```bash
make llama-status
make chat-smoke
```

## Day-2 operations

| Need | Command |
|---|---|
| Start | `make llama-start` |
| Stop | `make llama-stop` (no auto-restart) |
| Restart | `make llama-restart` |
| Status | `make llama-status` |
| Logs (live tail) | `make llama-logs` |
| Verify after restart | `make llama-doctor` |
| Full smoke test | `make chat-smoke` (needs PHP container up) |

## Log rotation (one-time, sudo)

macOS uses `newsyslog` for log rotation. Install the config once:

```bash
sudo bash provisioning/llama/scripts/install-logrotate.sh
```

Rotation runs daily at ~03:00 via the system `newsyslog` cron. To force
a rotation now: `sudo newsyslog -v -F`.

## Bumping the chat model

1. Edit `LLAMA_CHAT_MODEL` in `.env.local`.
2. Edit `provisioning/llama/models.lock` — add the new model's URL and
   sha256 (and optionally remove the old entry to free disk).
3. Add the new alias to `LlamaCppModelCatalog::__construct`.
4. `make llama-install` (re-renders plists, re-downloads, restarts).
5. `make chat-smoke` to confirm.

## Bumping the llama.cpp build

1. Edit `provisioning/llama/llama-cpp.version` to the new tag.
2. `brew unpin llama.cpp && brew upgrade llama.cpp && brew pin llama.cpp`.
3. `make llama-doctor` to confirm the version match.
4. `make chat-smoke` to confirm wire-format compat.

## Rollback to Ollama

Reversible until the volume-purge step:

```bash
git checkout http-api
make llama-uninstall
docker compose --profile embeddings up -d ollama
```

The final, one-way step is removing the Ollama volume — only do this
after a week of stable host-native chat:

```bash
rm -rf provisioning/volumes/ollama
rm -rf var/llama/models
```

## What lives where

```
provisioning/llama/
├── Makefile-llama.mk      # all llama-* + chat-smoke targets
├── README.md              # this file
├── fingerprints/
│   └── bge-m3.json        # first-4-dim baseline for drift detection
├── llama-cpp.version      # pinned brew build (hard pin in llama-doctor)
├── models.lock            # GGUF urls + sha256s
├── plists/
│   ├── io.shal.llama-chat.plist     # template (__PLACEHOLDER__ tokens)
│   └── io.shal.llama-embed.plist    # template
└── scripts/
    ├── chat-smoke.sh
    ├── download-models.sh
    ├── install-launchagents.sh
    ├── install-logrotate.sh
    ├── io.shal.llama.conf            # newsyslog config dropped at /etc/newsyslog.d/
    ├── render-plist.sh
    └── uninstall-launchagents.sh

var/llama/                  # gitignored
├── logs/                   # *.out.log / *.err.log (rotated by newsyslog)
├── models/                 # GGUF files (~7 GB total)
└── run/                    # PID files (currently unused)
```
