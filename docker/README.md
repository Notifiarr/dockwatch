# Docker

## How to use
1. Run `sh docker/build.sh` in root directory
2. Run `docker compose -f docker/compose.yml up -d --force-recreate`
3. If you want to use the outer proxy, you will need to navigate to Dockwatch settings and set the base url to `/dockwatch` first (and update the WebSocket url too, if you need shell support).

> Dev instance runs on port `:10000` and user `1000:990` by default  
> Outer proxy runs on port `:10001` by default