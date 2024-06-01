![Logo](https://repository-images.githubusercontent.com/718854440/29604111-7881-4c70-82e5-58710371e1eb)

# Dockwatch

## Purpose

Simple UI driven way to manage updates & notifications for Docker containers.  
No database required. All settings are stored locally in a volume mount.

## Notification triggers

**Notify when:**

-   Container (re-)created/removed
-   Container state changes (running -> stopped or healthy -> unhealthy)
-   Update for container image tag is available
-   Update for container image tag has been applied
-   Orphan images, volumes & networks are pruned
-   Memory and CPU usage is over a set limit

## Notification platforms

-   Notifiarr

## Update options

-   Ignore
-   Auto update
-   Check for updates

## Additional features

-   Link and control multiple servers
-   Automatically locate and match container icons for non Unraid usage\*
-   Update schedules for container image tags by a container basis
-   Notifications by a container basis
-   Automatically try to restart unhealthy containers
-   Mass prune orphan images, volumes & networks
-   Mass actions for containers [(re-)start/stop, pull, update]\*\*
-   Group containers in a table view for easier management

\*If icon is available at [Notifiarr/images](https://github.com/Notifiarr/images).  
\*\*Also includes generating a `docker run` command, `docker-compose.yml` and comparing mounts.

## Icons

**Unraid:**

-   Icons show up automatically using unraid labels

**Non-Unraid:**

-   It tries to match the container image to an icon from <https://github.com/Notifiarr/images> (Feel free to add more icons to that repo for others to use)
-   If the icon name is not the same as the official image or the app has multiple images then an alias would be used:
    -   Internal alias file: <https://github.com/Notifiarr/dockwatch/blob/main/root/app/www/public/container-alias.json> - This can be modified to add more links to official images as needed
    -   If you have your own custom images that you want to point to an icon:
        -   Create `/config/container-alias.json` and use the same format as the internal file

## Network dependencies

Dockwatch can automatically recognize if containers depend on specific network containers, for example Gluetun.

-   Restart Gluetun -> restart dependencies
-   Stop Gluetun -> stop dependencies
-   Update Gluetun -> re-create dependencies with updated network mode attached

## Docker Socket Proxy

Dockwatch is compatible with a Socket proxy + tested with LSIO and Tecnativa. You need to enable the following:
```
    - CONTAINERS=1
    - IMAGES=1
    - PORTS=1
    - NETWORKS=1
    - VOLUMES=1
```
proxy env settings and add a `DOCKER_HOST` env variable with the example value `http://socket-proxy:2375` (this points to your socket-proxy container) to your compose.

**Make sure the socket proxy runs on the same network as Dockwatch**

## Pre-requirements

Dockwatch heavily relies on the Docker API to work.

**Dependencies:**

-   Docker v25 or later
-   Docker-Compose v2.27 or later

**Getting the values of PUID and PGID:**

-   Get the group id that docker uses with the following command:

```
grep docker /etc/group
```

-   Get the user id from the user you want to run Dockwatch as with the following command:

```
id -u <username>
```

## Run

**Docker:**

```
docker run \
  -d \
  --name "/dockwatch" \
  --volume "/home/dockwatch/config:/config:rw" \
  --volume "/var/run/docker.sock:/var/run/docker.sock:rw" \
  --restart "unless-stopped" \
  --publish "9999:80/tcp" \
  --network "bridge" \
  --env "TZ=America/New_York" \
  --env "PUID=1001" \
  --env "PGID=999" \
  "ghcr.io/notifiarr/dockwatch:main"
```

**Docker Compose:**

```
services:
  dockwatch:
    container_name: dockwatch
    image: ghcr.io/notifiarr/dockwatch:main
    restart: unless-stopped
    ports:
      - 9999:80/tcp
    environment:
      #-DOCKER_HOST=127.0.0.1:2375 # Uncomment and adjust accordingly if you use a socket proxy
      - PUID=1001
      - PGID=999
      - TZ=America/New_York
    volumes:
      - /home/dockwatch/config:/config
      - /var/run/docker.sock:/var/run/docker.sock # Comment this line if you use a socket proxy
```

**Manual:**

`docker pull ghcr.io/notifiarr/dockwatch:main`

## Environment variables

Volumes
| Name | Host | Container |
| ----- | ----- | ----- |
| App config | /mnt/disk1/appdata/dockwatch/config | /config |
| Docker sock | /var/run/docker.sock | /var/run/docker.sock |

Ports
| Inside | Outside |
| ----- | ----- |
| 80 | 9999 |

Variables
| Name | Key | Value |
| ----- | ----- | ----- |
| DOCKER_HOST (optional: only for socket proxy) | DOCKER_HOST | ip:port |
| PUID | PUID | 1001 |
| PGID | PGID | 999 |
| TZ | TZ | America/New_York |

## Login

Dockwatch has basic functionality for protecting the UI with a username and password.  
**It is strongly recommended to use a reverse proxy with authentication instead.**

-   Create file `logins` in `/config`
-   Append `admin:password` to the file and save it
-   For multiple logins, drop a line and add another `admin:password`

## Development

**Option 1:**

-   Fork the repo
-   Create a directory symlink of the forked repo to `/config/www`.
    -   Linux example: `ln -s /home/user/dockwatch-git /home/user/config/www`
    -   Windows example: `mklink /D "C:\dockwatch-git" "C:\dockwatch-config\www"`
-   Open the UI, Navigate to Settings->Development and set environment from Internal to External
-   Save and restart Dockwatch container

**Option 2:**

-   Fork the repo
-   Open the `Dockerfile` and comment out the `COPY root/ /` line at the bottom
-   Copy the files from `root/app/www/public/*` to `/config/www/*`
-   Copy the cron from `root/etc/crontabs/abc` to `/config/crontabs/abc` (You'll need to add an ENV variable for `DOCKER_MODS=linuxserver/mods:universal-cron`)
-   Copy the ini from `root/etc/php82/conf.d/dockwatch.ini` to `/config/php/php-local.ini`
-   This should allow you to run the container while making changes to the files in `/config` and when done, just copy the files back into the `root/` directories and push your fork so it builds a new container

## Screenshots

UI

![image](https://github.com/Notifiarr/dockwatch/assets/8321115/87fc88d0-3430-43ba-a636-9c89992c7f59)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/d338a736-1c1b-4fa5-ac9e-6d5ab6f885ff)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/6e41ca48-1347-4a7f-8a2a-5e5c5020bf41)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/9d4fc121-c457-4b85-981d-7c615c037946)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/2257d9bf-a9a8-46e3-8712-f3cb2c037199)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/0c5c64b1-ea87-4269-b9fc-d91744c7219d)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/952d424a-f171-4366-8f2e-f673618e8e51)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/911efa2e-10e2-4787-985f-d5dd77a4b935)

Notifications

![image](https://github.com/Notifiarr/dockwatch/assets/8321115/f3f3b7cc-646c-4eaf-a344-99d0c1c81767)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/3b30e241-87ee-4e5d-a9f0-5b52ae5cb776)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/bae49f20-573f-4b7e-99f8-35abd5a7b932)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/217f4c81-3b84-40f8-b3ce-a51dabda0e1f)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/f48b47db-125c-4caa-bbdb-50de224861e2)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/ca8ea590-5fd6-4808-90f2-04eca15f83b1)
