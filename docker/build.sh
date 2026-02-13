#!/bin/sh
# docker/build.sh - Build a Docker image and tag it locally

set -e

IMAGE_NAME="ghcr.io/notifiarr/dockwatch"
TAG="local"

usage() {
    echo "Usage: $0 [options]"
    echo "Options:"
    echo "  -h         Show this help message"
    echo "  -t TAG     Set image tag (default: local)"
    exit 0
}

while getopts "ht:" opt; do
    case $opt in
        h) usage ;;
        t) TAG="$OPTARG" ;;
    esac
done
shift $((OPTIND -1))

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
