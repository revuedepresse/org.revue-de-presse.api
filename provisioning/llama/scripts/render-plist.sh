#!/usr/bin/env bash
# Render a plist template by substituting __KEY__ → value pairs.
#
# Usage: render-plist.sh <template-path> KEY=value [KEY=value ...]
# Output: rendered plist on stdout.
#
# Fails loudly if any __KEY__ remains unsubstituted in the output (catches
# typos in placeholder names before they hit launchd).

set -euo pipefail

if [[ $# -lt 1 ]]; then
    echo "usage: $0 <template-path> KEY=value [KEY=value ...]" >&2
    exit 64
fi

template="$1"
shift

if [[ ! -f "$template" ]]; then
    echo "render-plist: template not found: $template" >&2
    exit 66
fi

rendered=$(cat "$template")
for pair in "$@"; do
    key="${pair%%=*}"
    value="${pair#*=}"
    # % is not in any plist path or alias we use, so it's safe as the sed delimiter.
    rendered=$(printf '%s' "$rendered" | sed "s%__${key}__%${value}%g")
done

if printf '%s' "$rendered" | grep -qE '__[A-Z_]+__'; then
    echo "render-plist: unsubstituted placeholders remain:" >&2
    printf '%s' "$rendered" | grep -oE '__[A-Z_]+__' | sort -u >&2
    exit 65
fi

printf '%s\n' "$rendered"
