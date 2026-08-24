#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
stage_root="$(mktemp -d)"
package_root="$(mktemp -d)"
cleanup() {
  rm -rf "$stage_root" "$package_root"
}
trap cleanup EXIT

if [[ -f "$project_root/.env.local" ]]; then
  set -a
  # shellcheck disable=SC1091
  source "$project_root/.env.local"
  set +a
fi

: "${NEXT_PUBLIC_SUPABASE_URL:?NEXT_PUBLIC_SUPABASE_URL is required in .env.local}"
if [[ -z "${NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY:-}" && -z "${NEXT_PUBLIC_SUPABASE_ANON_KEY:-}" ]]; then
  echo "NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY is required in .env.local" >&2
  exit 1
fi

rsync -a "$project_root/" "$stage_root/" \
  --exclude '.git/' \
  --exclude '.next*/' \
  --exclude 'node_modules/' \
  --exclude 'out/' \
  --exclude '.env*' \
  --exclude 'app/api/' \
  --exclude 'deployment/infinityfree/htdocs/' \
  --exclude 'deployment/infinityfree/milanolodge-htdocs.zip'
ln -s "$project_root/node_modules" "$stage_root/node_modules"

(
  cd "$stage_root"
  NEXT_PUBLIC_SITE_URL='https://milanolodge.gt.tc' \
  NEXT_PUBLIC_USE_SUPABASE_EDGE_API='true' \
  NEXT_PUBLIC_REQUIRE_EMAIL_CONFIRMATION='false' \
  INFINITYFREE_STATIC_EXPORT='true' \
  npm run build
)

mkdir -p "$package_root/htdocs"
rsync -a "$stage_root/out/" "$package_root/htdocs/"
rsync -a "$project_root/deployment/infinityfree/static-files/" "$package_root/htdocs/"
target="$project_root/deployment/infinityfree/htdocs"
previous="$project_root/deployment/infinityfree/htdocs.previous"
rm -rf "$previous"
if [[ -d "$target" ]]; then mv "$target" "$previous"; fi
mv "$package_root/htdocs" "$target"
rm -rf "$previous"

(
  cd "$target"
  rm -f "$project_root/deployment/infinityfree/milanolodge-htdocs.zip"
  zip -qr "$project_root/deployment/infinityfree/milanolodge-htdocs.zip" .
)

echo "InfinityFree package generated:"
echo "  $target"
echo "  $project_root/deployment/infinityfree/milanolodge-htdocs.zip"
