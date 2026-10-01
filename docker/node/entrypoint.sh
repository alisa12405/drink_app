#!/usr/bin/env bash
set -euo pipefail

cd /app

if [ ! -f "package-lock.json" ]; then
  echo "[entrypoint] Frontend source is incomplete: expected package-lock.json." >&2
  exit 1
fi

image_lock_checksum="$(sha256sum /opt/package-lock.json | awk '{print $1}')"
if [ ! -d "node_modules" ] || [ "$(cat node_modules/.image-lock-checksum 2>/dev/null || true)" != "$image_lock_checksum" ]; then
  echo "[entrypoint] Hydrating node_modules from dependencies installed at image build..."
  # node_modules is a named-volume mount point and cannot itself be removed.
  find node_modules -mindepth 1 -maxdepth 1 -exec rm -rf {} +
  mkdir -p node_modules
  cp -a /opt/node_modules/. node_modules/
  printf '%s\n' "$image_lock_checksum" > node_modules/.image-lock-checksum
fi

if [ ! -f ".env" ]; then
  echo "VITE_API_BASE_URL=${VITE_API_BASE_URL:-http://localhost:8080/api}" > .env
fi

exec "$@"
