#!/usr/bin/env bash
# Install the newsyslog config for llama-server logs. Requires sudo.
# Idempotent: re-installs the config (overwrites in place).

set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source_conf="$script_dir/io.shal.llama.conf"
target_conf="/etc/newsyslog.d/io.shal.llama.conf"

if [[ "$EUID" -ne 0 ]]; then
    echo "install-logrotate: must run as root (use sudo)" >&2
    echo "  sudo bash $0" >&2
    exit 1
fi

cp "$source_conf" "$target_conf"
chown root:wheel "$target_conf"
chmod 644 "$target_conf"
echo "install-logrotate: $target_conf installed (rotation runs daily ~03:00)"
