#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.."; pwd)"
OUT="$ROOT/build"
PKG_DIR="$OUT/package"
VERSION="$(date +%Y%m%d-%H%M%S)"
NAME="email-validator-demo-${VERSION}"

rm -rf "$PKG_DIR" "$OUT/${NAME}.tar.gz" "$OUT/${NAME}.zip"
mkdir -p "$PKG_DIR"

echo "[1/5] Ensure Swagger assets & OpenAPI exist (dev env)…"
composer run docs:assets || true
composer run docs:sync   || true

echo "[2/5] Copy app sources…"
rsync -a \
  --exclude '.git' \
  --exclude '.idea' \
  --exclude '.vscode' \
  --exclude 'build' \
  --exclude 'vendor' \
  "$ROOT/public" "$ROOT/src" "$ROOT/config" "$ROOT/composer.json" "$ROOT/composer.lock" \
  "$PKG_DIR/"

# (optional) provide .htaccess if not present
if [ ! -f "$PKG_DIR/public/.htaccess" ]; then
  cat > "$PKG_DIR/public/.htaccess" <<'HTACCESS'
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  # vorhandene Dateien/Ordner direkt ausliefern
  RewriteCond %{REQUEST_FILENAME} -f [OR]
  RewriteCond %{REQUEST_FILENAME} -d
  RewriteRule ^ - [L]
  # sonst auf index.php umleiten
  RewriteRule ^ index.php [L]
</IfModule>
HTACCESS
fi

echo "[3/5] Install prod vendor (no dev, no scripts)…"
(
  cd "$PKG_DIR"
  composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts
)

echo "[4/5] Package…"
(
  cd "$OUT"
  tar -czf "${NAME}.tar.gz" package
  zip -qr  "${NAME}.zip"    package
)

echo "[5/5] Done."
echo "Artifacts:"
echo " - $OUT/${NAME}.tar.gz"
echo " - $OUT/${NAME}.zip"
