# Project Overview

Dockwatch is a self-hosted, PHP-based web application for managing Docker containers: monitor status, manage updates, run actions, open shells, and manage Docker Compose stacks. It ships as the `ghcr.io/notifiarr/dockwatch` image (MIT) and runs inside an Alpine/Nginx container, talking to the Docker engine over the engine API.

## Architecture

- **Web Server:** Nginx + PHP-FPM (php ^8.4, built on `linuxserver/baseimage-alpine-nginx:3.23`) serves the UI from `root/app/www/public`.

- **WebSocket daemon:** `websocket.php` boots a long-running Ratchet server (`classes/WebSocket.php` + traits in `classes/traits/WebSocket/`) on port 9910 (overridable in settings). Connections are fanned out into **channels** selected by `?type=` (currently `shell` and `compose`), each authorized with one-time memcached-backed tokens and the `dockwatch-ws` sub-protocol. Traits:
  - `Server.php` — handshake, channel creation, message lifecycle
  - `Auth.php` — memcached token issue/verify/revoke
  - `Messages.php` — action routing (`resize`, `compose`)
  - `Process.php` — streams external commands, retains full output via `buffer` + `onComplete` hook
  - `Shell.php` — `docker exec` TTY streams (xterm.js UI)
  - `Compose.php` — streams compose commands; generates compose with `docker-autocompose`
  - `DockerSocket.php` — raw HTTP request/response to the Docker engine API (incl. exec)

- **Docker access:** HTTP against the engine API via `DOCKER_HOST` (defaults to the unix socket). The dev stack runs `tecnativa/docker-socket-proxy` and sets `DOCKER_HOST=tcp://socket-proxy:2375`. Streaming `docker exec` TTYs through the proxy requires the client to send `Upgrade: tcp` on `POST /exec/{id}/start` or stdin writes are dropped.

- **Compose management:** stacks live under `/config/compose/{name}/docker-compose.yml`. Add/modify/save in an Ace editor; validation is Symfony `Yaml::parse` (fast syntax) then `docker compose -f <tmp> config` (exit-code based, authoritative), committed via atomic rename. Generate a compose from running containers (mass-trigger modal) with `ghcr.io/red5d/docker-autocompose` streamed over the compose channel.

- **Command execution:** in transition to WebSockets. Container actions (start/stop/restart/pull/remove/...), compose logs/ps, and most UI commands still run through ajax; compose `up/down/pull/stop/restart` and compose generation already stream over the compose websocket channel, and a `docker exec` TTY streams over the shell channel. The remaining ajax commands are planned to be migrated to websocket channels in the UI.

- **Backend services/tools:** Docker engine, `regctl` (digest checks), Memcached (cache + websocket tokens), MariaDB, security scanners grype/snyk/trivy (downloaded on demand), notification platforms.

- **Cron jobs:** defined in `root/etc/crontabs/abc` — `sse`, `stats`, `commands`, `state`, `pulls`, `housekeeper`, `health`, `prune`, `security` — started via s6-overlay.

- **Database:** MariaDB (bundled `mariadb` package, runs in-container) via `mysqli`, host `localhost`. Migrations under `root/app/www/public/migrations/` are applied by the app (`023_mysql_conversion.php` moved the project off SQLite). Logs under `/config/logs/`, settings/state JSON under `/config`.

## Building and Running

- **Build:** `sh docker/build.sh` (tags `ghcr.io/notifiarr/dockwatch:local`; `-t <tag>` for a custom tag, `-v vX.Y.Z` generates a changelog).
- **Run (dev):** `docker compose -f docker/compose.yml up -d` on port 10000. The dev stack hot-mounts `root/app/www/public` and `.data/dockwatch/config`, and connects to Docker through the socket-proxy service.

## Development Conventions

- **Dependencies (Composer):** `cboden/ratchet`, `symfony/yaml ^7.0`, `chialab/ip`; PHP ^8.4.
- **Compose validation:** Symfony `Yaml::parse` (fast syntax gate) + `docker compose -f <tmp> config` (exit-code based, authoritative), committed via atomic rename (`composeValidateAndWrite` in `ajax/compose.php`).
- **Path safety:** compose dirs are always normalized via `basename()`/strict regex; websocket stack names must match `^[A-Za-z0-9_\-.]+$`.
- **Config/state:** MariaDB + migrations, JSON files under `/config`, memcached-backed one-time tokens for websocket channels.

## Code Style and Linting

- **PHP:** [DEVSENSE](https://www.devsense.com), PSR-12, PHP ^8.4.
- **PHP (anti-patterns):** never use the `@` error-suppression operator before functions (`@file_put_contents`, `@unlink`, `@mkdir`, ...). Handle failures explicitly: check the return value and return/propagate an error message. Avoid PHP code smells generally — no dead/unreachable code, no redundant or duplicated branches, no implicit type juggling, and never silently swallow exceptions.
- **JavaScript:** ESLint — `npm run lint` (`eslint root/app/www/public/js/`), `npm run lint:fix`.
- **File headers:** every new file must begin with the standard header block used across the codebase — containing the author's handle/name and a 6-digit `mmddyy` creation date:

    ```php
    <?php

    /*
    ----------------------------------
     ------  Created: <mmddyy>   ------
     ------  <Author>            ------
    ----------------------------------
    */
    ```

  (PHP files add the `<?php` line above the block.)
- **Separators:** separate functions/sections with the same "spacer" divider already used in the codebase — `// ---------------------------------------------------------------------------------------------` (93 dashes) between functions in JS files, and the same style for section markers in PHP.
- **Comments:** inline comments begin with `//-- ` and are written in ALL CAPS — short but comprehensive (e.g. `//-- ROW VALUES`, `//-- WEBSOCKET STREAM SUPPORTED ACTIONS`). Use the same style in both PHP and JS.