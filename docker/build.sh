#!/bin/sh
# docker/build.sh - Build a Docker image and tag it locally

set -e

IMAGE_NAME="ghcr.io/notifiarr/dockwatch"
TAG="local"

usage() {
    echo "Usage: $0 [options]"
    echo "Options:"
    echo "  -h           Show this help message"
    echo "  -t TAG       Set image tag (default: local)"
    echo "  -v VERSION   Set version for changelog (e.g., v0.6.505) - also triggers changelog generation"
    exit 0
}

generate_changelog() {
    FROM_TAG=$(git for-each-ref --sort=-creatordate --format '%(refname:short)' refs/tags | grep -E '^v[0-9]+\.[0-9]+\.[0-9]+$' | head -1)
    [ -z "$FROM_TAG" ] && { echo "Error: No version tags found" >&2; exit 1; }

    COMMITS=$(git log "$FROM_TAG..HEAD" --pretty=format:"- [\`%h\`](https://github.com/notifiarr/dockwatch/commit/%H): %s by @%an" --reverse 2>/dev/null | grep -v "renovate\[bot\]")
    [ -z "$COMMITS" ] && COMMITS="- no changes"

    CONTRIBUTORS=$(git log "$FROM_TAG..HEAD" --pretty=format:"@%an" 2>/dev/null | grep -v "renovate\[bot\]" | sort -u | tr '\n' ' ')

    cat <<EOF
## 📝 What's Changed

$COMMITS

## 👥 Contributors

$CONTRIBUTORS

**Full Changelog**: https://github.com/notifiarr/dockwatch/compare/$FROM_TAG...$1
EOF
}

while getopts "ht:v:" opt; do
    case $opt in
        h) usage ;;
        t) TAG="$OPTARG" ;;
        v) VERSION="$OPTARG" ;;
    esac
done
shift $((OPTIND -1))

if [ -n "$VERSION" ]; then
    generate_changelog "$VERSION" > "CHANGELOG-$VERSION.md"
    echo "Changelog generated: CHANGELOG-$VERSION.md" >&2
    exit 0
fi

command -v docker >/dev/null 2>&1 || { echo "Docker is required"; exit 1; }

BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
COMMITS=$(git rev-list --count --all 2>/dev/null || echo "0")
BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "local")
COMMIT=$(git rev-parse HEAD 2>/dev/null || echo "unknown")

docker build \
  --file docker/Dockerfile \
  --build-arg BUILD_DATE="$BUILD_DATE" \
  --build-arg COMMITS="$COMMITS" \
  --build-arg BRANCH="$BRANCH" \
  --build-arg COMMIT="$COMMIT" \
  --tag "$IMAGE_NAME:$TAG" \
  --label "org.opencontainers.image.created=$BUILD_DATE" \
  --label "org.opencontainers.image.revision=$COMMIT" \
  --label "org.opencontainers.image.version=$TAG" \
  . "$@"
