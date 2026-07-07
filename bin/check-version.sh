#!/usr/bin/env bash
# Verify the plugin's three version locations all equal the given version.
# Usage: bin/check-version.sh 2.0.1
set -euo pipefail

EXPECTED="${1:?usage: bin/check-version.sh <version>}"
cd "$(dirname "$0")/.."

HEADER=$(sed -n 's/^ \* Version: //p' refservice-references.php)
CONSTANT=$(sed -n "s/^define('REFSERVICE_PLUGIN_VERSION', '\([^']*\)');.*/\1/p" refservice-references.php)
STABLE=$(sed -n 's/^Stable tag: //p' readme.txt | tr -d '[:space:]')

status=0
check() {
    local label="$1" actual="$2"
    if [ "$actual" != "$EXPECTED" ]; then
        echo "MISMATCH: $label is '$actual', expected '$EXPECTED'" >&2
        status=1
    fi
}
check "plugin header Version" "$HEADER"
check "REFSERVICE_PLUGIN_VERSION constant" "$CONSTANT"
check "readme.txt Stable tag" "$STABLE"

if [ "$status" -eq 0 ]; then
    echo "OK: all version locations are $EXPECTED"
fi
exit "$status"
