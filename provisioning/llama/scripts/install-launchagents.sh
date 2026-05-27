#!/usr/bin/env bash
# Render both llama-server plists with values from .env / .env.local and
# install them as user LaunchAgents under ~/Library/LaunchAgents.
# Idempotent: re-running unloads, re-renders, re-loads.
#
# Required env (read from .env.local first, .env as fallback):
#   LLAMA_CHAT_MODEL — chat model alias (e.g. mistral-7b-instruct-v0.3)
#
# Derived:
#   __REPO_ROOT__ — absolute path to the repo (computed from this script's location)
#   __CHAT_MODEL_FILE__ — chat-model GGUF filename from models.lock matching LLAMA_CHAT_MODEL

set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
llama_dir="$(cd "$script_dir/.." && pwd)"
repo_root="$(cd "$llama_dir/../.." && pwd)"

# .env.local takes precedence (Symfony convention).
set -a
[[ -f "$repo_root/.env" ]]       && . "$repo_root/.env"
[[ -f "$repo_root/.env.local" ]] && . "$repo_root/.env.local"
set +a

: "${LLAMA_CHAT_MODEL:?LLAMA_CHAT_MODEL must be set (see .env.local)}"

chat_file=$(jq -er --arg alias "$LLAMA_CHAT_MODEL" \
    '.models[] | select(.alias == $alias and .role == "completions") | .filename' \
    "$llama_dir/models.lock")

agents_dir="$HOME/Library/LaunchAgents"
mkdir -p "$agents_dir"

for label in io.shal.llama-chat io.shal.llama-embed; do
    target="$agents_dir/${label}.plist"

    launchctl bootout "gui/$(id -u)/${label}" 2>/dev/null || true

    if [[ "$label" == "io.shal.llama-chat" ]]; then
        "$script_dir/render-plist.sh" "$llama_dir/plists/${label}.plist" \
            REPO_ROOT="$repo_root" \
            CHAT_MODEL_FILE="$chat_file" \
            CHAT_MODEL_ALIAS="$LLAMA_CHAT_MODEL" \
            > "$target"
    else
        "$script_dir/render-plist.sh" "$llama_dir/plists/${label}.plist" \
            REPO_ROOT="$repo_root" \
            > "$target"
    fi

    chmod 644 "$target"
    plutil -lint "$target" >/dev/null

    mkdir -p "$repo_root/var/llama/logs" "$repo_root/var/llama/run" "$repo_root/var/llama/models"

    launchctl bootstrap "gui/$(id -u)" "$target"
    launchctl enable "gui/$(id -u)/${label}"
done

echo "install-launchagents: installed io.shal.llama-chat and io.shal.llama-embed"
