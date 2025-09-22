#!/bin/sh
# docker/build.sh - Build a Docker image and tag it locally

set -e

# Image name and tag
IMAGE_NAME="ghcr.io/notifiarr/dockwatch"
TAG="local"

# Build metadata
BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
COMMITS="0"
BRANCH="local"
COMMIT="unknown"

# Platforms to build for
PLATFORMS="linux/amd64"

# Enable Docker Buildx
docker buildx create --use --name builder 2>/dev/null || docker buildx use builder

# Build image using repo root as context
docker buildx build \
  --file docker/Dockerfile \
  --platform "$PLATFORMS" \
  --build-arg BUILD_DATE="$BUILD_DATE" \
  --build-arg COMMITS="$COMMITS" \
  --build-arg BRANCH="$BRANCH" \
  --build-arg COMMIT="$COMMIT" \
  --tag "$IMAGE_NAME:$TAG" \
  --label "org.opencontainers.image.created=$BUILD_DATE" \
  --label "org.opencontainers.image.revision=$COMMIT" \
  --label "org.opencontainers.image.version=$TAG" \
  --load \
  --no-cache \
  . "$@"
