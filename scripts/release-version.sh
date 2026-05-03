#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage:
  scripts/release-version.sh [--allow-dirty] <version>

Examples:
  scripts/release-version.sh 0.1.1
  scripts/release-version.sh v0.1.1
  scripts/release-version.sh --allow-dirty 0.1.1
EOF
}

allow_dirty=0
raw_version=""

for arg in "$@"; do
  case "$arg" in
    --allow-dirty)
      allow_dirty=1
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      if [[ -n "$raw_version" ]]; then
        echo "Error: too many arguments."
        usage
        exit 1
      fi
      raw_version="$arg"
      ;;
  esac
done

if [[ -z "$raw_version" ]]; then
  echo "Error: missing version argument."
  usage
  exit 1
fi

version="${raw_version#v}"
if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+([-.][0-9A-Za-z]+)*$ ]]; then
  echo "Error: invalid version '$raw_version'. Expected semantic format like 1.2.3 or 1.2.3-rc1."
  exit 1
fi

tag="v${version}"

if [[ "$allow_dirty" -ne 1 ]] && [[ -n "$(git status --porcelain)" ]]; then
  echo "Error: working tree is not clean. Commit/stash changes or use --allow-dirty."
  exit 1
fi

if git rev-parse -q --verify "refs/tags/${tag}" >/dev/null; then
  echo "Error: tag '${tag}' already exists."
  exit 1
fi

if [[ ! -f config/app.php ]]; then
  echo "Error: config/app.php not found."
  exit 1
fi

if ! grep -Eq '^\$appVersion = "[^"]+";' config/app.php; then
  echo 'Error: expected line like $appVersion = "x.y.z"; in config/app.php'
  exit 1
fi

current_version="$(grep -E '^\$appVersion = "[^"]+";' config/app.php | head -n1 | sed -E 's/^\$appVersion = "([^"]+)";/\1/')"
if [[ "$current_version" == "$version" ]]; then
  echo "Error: app version is already '${version}'."
  exit 1
fi

sed -E -i 's|^\$appVersion = "[^"]+";|$appVersion = "'"${version}"'";|' config/app.php

if git diff --quiet -- config/app.php; then
  echo "Error: no changes applied to config/app.php."
  exit 1
fi

git add config/app.php
git commit -m "chore(release): bump version to ${version}"
git tag "${tag}"

echo "Release prepared:"
echo "- app version: ${version}"
echo "- tag: ${tag}"
