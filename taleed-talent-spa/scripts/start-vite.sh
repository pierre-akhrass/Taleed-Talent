#!/bin/sh
set -eu

lock_hash=$(sha256sum package-lock.json | cut -d ' ' -f 1)
lock_marker=node_modules/.taleed-lock-hash
installed_hash=$(cat "$lock_marker" 2>/dev/null || true)

if [ ! -x node_modules/.bin/vite ] || [ ! -x node_modules/.bin/tsc ] || [ "$installed_hash" != "$lock_hash" ]; then
    npm ci
    printf '%s\n' "$lock_hash" > "$lock_marker"
fi

exec npm run dev