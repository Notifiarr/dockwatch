#!/bin/sh
# Usage: sh /etc/scripts/pull_images.sh lscr.io/linuxserver/nginx:1.28.0 ghcr.io/notifiarr/dockwatch:develop

# Check if at least one argument is passed
if [ $# -eq 0 ]; then
  echo "Usage: $0 <image1> <image2> ..."
  exit 1
fi

# Loop through all arguments and pull each image in background
for image in "$@"; do
  echo "Pulling $image..."
  docker pull "$image" &
done

# Wait for all background jobs to complete
wait

echo "Done!"