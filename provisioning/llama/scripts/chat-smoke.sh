#!/usr/bin/env bash
# End-to-end smoke test for the llama-server stack.
#  1. GET /v1/models on chat port
#  2. GET /v1/models on embed port
#  3. POST /v1/chat/completions (asks Mistral for "OK")
#  4. POST /v1/embeddings (bge-m3, fixed input "test")
#  5. Symfony DI sanity check
#  6. Symfony ai:platform:invoke roundtrip
#
# Designed to run from the deploy host. Steps 5-6 use docker compose exec
# and require the API container to be up.

set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
llama_dir="$(cd "$script_dir/.." && pwd)"
repo_root="$(cd "$llama_dir/../.." && pwd)"
fingerprints_dir="$llama_dir/fingerprints"

CHAT_PORT="${LLAMA_CHAT_PORT:-8081}"
EMBED_PORT="${LLAMA_EMBED_PORT:-8082}"

set -a
[[ -f "$repo_root/.env" ]]       && . "$repo_root/.env"
[[ -f "$repo_root/.env.local" ]] && . "$repo_root/.env.local"
set +a
: "${LLAMA_CHAT_MODEL:?LLAMA_CHAT_MODEL must be set}"

echo "==> [1/6] GET http://127.0.0.1:${CHAT_PORT}/v1/models"
curl -sf "http://127.0.0.1:${CHAT_PORT}/v1/models" \
    | jq -e --arg m "$LLAMA_CHAT_MODEL" '.data[0].id == $m' >/dev/null
echo "    ok: chat model $LLAMA_CHAT_MODEL"

echo "==> [2/6] GET http://127.0.0.1:${EMBED_PORT}/v1/models"
curl -sf "http://127.0.0.1:${EMBED_PORT}/v1/models" \
    | jq -e '.data[0].id == "bge-m3"' >/dev/null
echo "    ok: embed model bge-m3"

echo "==> [3/6] POST chat completion (asks for OK)"
chat_payload=$(jq -nc --arg m "$LLAMA_CHAT_MODEL" '
    {model: $m,
     messages: [{role: "user", content: "Réponds uniquement par OK."}],
     max_tokens: 4}')
chat_resp=$(curl -sf -X POST "http://127.0.0.1:${CHAT_PORT}/v1/chat/completions" \
    -H 'Content-Type: application/json' -d "$chat_payload")
echo "$chat_resp" | jq -e '.choices[0].message.content | test("OK"; "i")' >/dev/null
echo "    ok: chat response contains 'OK'"

echo "==> [4/6] POST embedding (bge-m3, input 'test')"
embed_payload='{"model":"bge-m3","input":"test"}'
embed_resp=$(curl -sf -X POST "http://127.0.0.1:${EMBED_PORT}/v1/embeddings" \
    -H 'Content-Type: application/json' -d "$embed_payload")
dims=$(echo "$embed_resp" | jq '.data[0].embedding | length')
if [[ "$dims" != "1024" ]]; then
    echo "    FAIL: expected 1024 dims, got $dims" >&2
    exit 1
fi
echo "    ok: 1024-dim embedding"

fingerprint_file="$fingerprints_dir/bge-m3.json"
first4=$(echo "$embed_resp" | jq -c '.data[0].embedding[0:4]')
if [[ -f "$fingerprint_file" ]] && [[ "$(jq -r '.first4 // empty | if type=="array" and length>0 then "yes" else empty end' "$fingerprint_file")" == "yes" ]]; then
    expected=$(jq -c '.first4' "$fingerprint_file")
    tolerance=$(jq -r '.tolerance // 0.0001' "$fingerprint_file")
    drift=$(jq -n --argjson a "$first4" --argjson b "$expected" '
        [ ($a | to_entries[]) as $e
          | ($b[$e.key] // 0) as $bv
          | (($e.value - $bv) | if . < 0 then -. else . end) ]
        | max')
    awk -v d="$drift" -v t="$tolerance" 'BEGIN{exit !(d <= t)}' || {
        echo "    FAIL: bge-m3 fingerprint drift $drift exceeds tolerance $tolerance" >&2
        echo "    expected: $expected"  >&2
        echo "    actual:   $first4"    >&2
        exit 1
    }
    echo "    ok: fingerprint within $tolerance"
else
    echo "    note: no fingerprint recorded yet; capturing this run as the baseline"
    mkdir -p "$fingerprints_dir"
    jq -n --argjson f "$first4" --arg ts "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
        '{first4: $f, tolerance: 0.0001, captured_at: $ts}' \
        > "$fingerprint_file"
    echo "    wrote $fingerprint_file — commit it to lock the baseline"
fi

# Production PHP-FPM container name is `service` (per docker-compose.yaml).
# The dev clone has a separate `php-worker` (frankenphp profile, opt-in) which
# is not the default deploy target. Override via LLAMA_SMOKE_PHP_SERVICE if
# someone runs the smoke against a different service name.
PHP_SERVICE="${LLAMA_SMOKE_PHP_SERVICE:-service}"

echo "==> [5/6] Symfony DI: debug:container ai.platform.generic.llama_chat (in container '$PHP_SERVICE')"
docker compose -f "$repo_root/provisioning/containers/docker-compose.yaml" \
    exec -T "$PHP_SERVICE" bin/console debug:container ai.platform.generic.llama_chat --quiet
echo "    ok: DI knows about ai.platform.generic.llama_chat"

echo "==> [6/6] Symfony invoke: ai:platform:invoke ai.platform.generic.llama_chat $LLAMA_CHAT_MODEL"
docker compose -f "$repo_root/provisioning/containers/docker-compose.yaml" \
    exec -T "$PHP_SERVICE" bin/console ai:platform:invoke \
    ai.platform.generic.llama_chat "$LLAMA_CHAT_MODEL" "Réponds uniquement par OK." \
    | tee /tmp/llama-smoke-invoke.out
grep -qi 'OK' /tmp/llama-smoke-invoke.out
echo "    ok: Symfony invoke round-trip succeeded"

echo "==> smoke: ALL PASS"
