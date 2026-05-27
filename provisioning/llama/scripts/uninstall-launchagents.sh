#!/usr/bin/env bash
# Unload llama-server LaunchAgents and remove the installed plists.
# Leaves models, logs, and PIDs intact (see README for full purge).

set -euo pipefail

agents_dir="$HOME/Library/LaunchAgents"

for label in io.shal.llama-chat io.shal.llama-embed; do
    launchctl bootout "gui/$(id -u)/${label}" 2>/dev/null || true
    rm -f "$agents_dir/${label}.plist"
done

echo "uninstall-launchagents: removed io.shal.llama-chat and io.shal.llama-embed plists"
echo "uninstall-launchagents: models and logs left at var/llama/ — purge manually if desired"
