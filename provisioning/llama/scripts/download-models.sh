#!/usr/bin/env bash
# Download GGUF model files declared in models.lock to var/llama/models/.
# Idempotent: skips files whose existing sha256 already matches.
# Preflight: refuses to start if less than 15 GiB free at the target volume.

set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
llama_dir="$(cd "$script_dir/.." && pwd)"
repo_root="$(cd "$llama_dir/../.." && pwd)"
models_dir="$repo_root/var/llama/models"
lockfile="$llama_dir/models.lock"

mkdir -p "$models_dir"

free_gib=$(df -g "$models_dir" | awk 'NR==2 {print $4}')
if [[ "$free_gib" -lt 15 ]]; then
    echo "download-models: only ${free_gib} GiB free at $models_dir — need ≥15 GiB. Clean up disk first." >&2
    exit 1
fi

jq -c '.models[]' "$lockfile" | while read -r row; do
    filename=$(jq -r '.filename' <<<"$row")
    url=$(jq -r '.url' <<<"$row")
    sha=$(jq -r '.sha256' <<<"$row")
    target="$models_dir/$filename"

    if [[ -f "$target" ]]; then
        actual=$(shasum -a 256 "$target" | awk '{print $1}')
        if [[ "$actual" == "$sha" ]]; then
            echo "download-models: $filename — already present, sha256 OK"
            continue
        fi
        echo "download-models: $filename — sha256 mismatch, redownloading"
        rm -f "$target"
    fi

    echo "download-models: fetching $filename ..."
    curl -L --fail --retry 3 --retry-delay 5 -o "$target.partial" "$url"

    actual=$(shasum -a 256 "$target.partial" | awk '{print $1}')
    if [[ "$actual" != "$sha" ]]; then
        echo "download-models: $filename — sha256 verification FAILED" >&2
        echo "  expected: $sha"  >&2
        echo "  actual:   $actual" >&2
        rm -f "$target.partial"
        exit 1
    fi

    mv "$target.partial" "$target"
    echo "download-models: $filename — verified"
done

echo "download-models: all models present and verified"
