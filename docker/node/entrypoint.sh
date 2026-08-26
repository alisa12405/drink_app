#!/usr/bin/env bash
# Auto-scaffold Vue 3 + Vite (frontend/) khi container khoi dong LAN DAU (khi
# thu muc mounted ./frontend chua co package.json). Idempotent - lan sau chi
# chay npm install roi start dev server.
set -euo pipefail

cd /app

if [ ! -f "package.json" ]; then
  echo "[entrypoint] Chua co frontend, dang tao Vue 3 + Vite (Router + Pinia) ..."
  # Dung ten thu muc TUONG DOI (khong phai duong dan tuyet doi) - create-vue
  # dung ten thu muc lam goi y package name va se hoi lai (interactive) neu
  # ten khong hop le (vd chua "/"), du da truyen feature flags.
  rm -rf /tmp/vue-skeleton
  ( cd /tmp && echo "" | npm create vue@latest vue-skeleton -- --router --pinia --eslint --prettier --force )
  shopt -s dotglob nullglob
  cp -rf /tmp/vue-skeleton/. .
  shopt -u dotglob nullglob
  rm -rf /tmp/vue-skeleton
fi

if [ ! -d "node_modules" ]; then
  echo "[entrypoint] npm install ..."
  # --legacy-peer-deps: template eslint/oxlint cua create-vue hien co xung
  # dot peer-dependency version giua chinh cac goi no sinh ra - bo qua strict
  # peer resolution de tranh loi ERESOLVE.
  npm install --legacy-peer-deps
fi

if ! grep -q '"axios"' package.json 2>/dev/null; then
  echo "[entrypoint] Cai axios de goi API backend ..."
  npm install axios --legacy-peer-deps
fi

mkdir -p src/services
if [ ! -f "src/services/api.js" ]; then
  echo "[entrypoint] Tao src/services/api.js (axios client) ..."
  cat > src/services/api.js <<'JS'
import axios from 'axios'

// Xem SPEC_smart-drink-recommendation-app.md muc 2.3 (Thiet ke API chinh)
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

export default apiClient
JS
fi

if [ ! -f ".env" ]; then
  echo "VITE_API_BASE_URL=${VITE_API_BASE_URL:-http://localhost:8080/api}" > .env
fi

echo "[entrypoint] Xong. Frontend da san sang tai /app."

exec "$@"
