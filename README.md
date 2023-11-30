# Docker Watcher

## Purpose
Simple UI driven way to manage updates & notifications for containers. As this is meant to be simple, there is no db container required or setup. This will save things it needs to files in the `/config` mount instead.

## Notification triggers
- Notify when a container is added
- Notify when a container is removed
- Notify when a container changes state (running -> stopped)
- Nofity when an update is available
- Notify when an update is applied
- Notify when images are pruned
- Notify when volumes are pruned
- Notify when images/volumes have been pruned
- Notify if memory is > n%
- Notify if CPU is > n%

## Notification platforms
- Notifiarr

## Update options
- Ignore
- Auto update
- Check for update

## Features
- Link and control multiple servers
- Try to find/match icons for non unraid usage
- Setup update schedules on a container by container basis
- Setup notify only or update on a container by container basis
- Mass prune/remove orphan images and volumes
- Mass select containers and generate `docker run` commands
- Mass select containers and generate a `docker-compose` for them
- Mass select containers and start/restart/stop/pull/update
- Memcached support (optional)

## Permissions
No matter how docker is installed (native, unraid, etc), it is required that the user running the container has permission to use the docker commands. View `root/app/www/public/functions/docker.php` to see what is used

Unraid: This is built into the container with
```
addgroup -g 281 unraiddocker && \
usermod -aG unraiddocker abc
```

Ubuntu: This is an example to get the group id to use
```
grep docker /etc/group
```

## Run
This is just an example; change the TZ, PGID, network and volume paths accordingly
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
  --env "PGID=122" \
  "ghcr.io/notifiarr/dockwatch:main"
```

## Compose
This is an example, adjust paths and settings for your setup
```
version: "2.1"
services:
  dockwatch:
    container_name: dockwatch
    image: ghcr.io/notifiarr/dockwatch:main
    ports:
      - 9999:80/tcp
    environment:
      - PGID=999
      - TZ=America/New_York
    volumes:
      - /home/dockwatch/config:/config
      - /var/run/docker.sock:/var/run/docker.sock
```

## Manual
`docker pull ghcr.io/notifiarr/dockwatch:main`

## ENV
These are my settings, adjust them to fit your setup!!

Volumes
| Name | Host | Container |
| ----- | ----- | ----- |
| App Config | /mnt/disk1/appdata/dockwatch/config | /config |
| Proc | /proc | /proc |
| Docker sock | /var/run/docker.sock | /var/run/docker.sock |

Ports
| Inside | Outside |
| ----- | ----- |
| 80 | 9999 |

Variables
| Name | Key | Value |
| ----- | ----- | ----- |
| PUID | PUID | 1001 |
| PGID | PGID | 100 |
| UMASK | UMASK | 022 |

## Login
There is support for a simple login mechanism but i would recomment using something like a reverse proxy with authentication
- Add a file `logins` to `/config`
- Add `admin:password` to the file and save it
- Reload
- Multiple logins, drop a line and add another `admin:password`

## Development
Firstly i **am not** a docker expert so there are likely other/better ways to do this. What i list below is just how i work on it without having to rebuild the container for every change and a reminder for me on what i did. Since this involves messing with the contents of the container, if an update is applied these steps will need re-applied

Option 1:
- Fork the repo
- Open the `Dockerfile` and comment out the `COPY root/ /` line at the bottom
- Copy the files from `root/app/www/public/*` to `/config/www/*`
- Copy the cron feom `root/etc/crontabs/abc` to `/config/crontabs/abc` (You'll need to add an ENV variable for `DOCKER_MODS=linuxserver/mods:universal-cron`)
- Copy the ini from `root/etc/php82/conf.d/dockwatch.ini` to `/config/php/php-local.ini`
- This should allow you to run the container while making changes to the files in `/config` and when done, just copy the files back into the `root/` directories and push your fork so it builds a new container

Option 2:
- SSH into the container as root
- Run `chown -R abc:abc /app/www`
- Open the UI to Settings -> Development and change the environment from Internal to External & save
- Restart the container and it is now looking at `/config/www` for the working files so make sure you copy the files to there!

## Icons
Unraid:
- Icons show up automatically using unraid labels

Non-Unraid:
- It tries to match the container image to an icon from <https://github.com/Notifiarr/images> (Feel free to add more icons to that repo for others to use)
- If the icon name is not the same as the official image or the app has multiple images then an alias would be used:
	- Internal alias file: <https://github.com/Notifiarr/dockwatch/blob/main/root/app/www/public/container-alias.json>
  		- This can be modified to add more links to official images as needed
	- If you have your own custom images that you want to point to an icon:
		- Create `/config/container-alias.json` and use the same format as the internal file

## Screenshots
UI

![image](https://github.com/Notifiarr/dockwatch/assets/8321115/9bfd385e-9b2c-4881-95f5-31c64b073424)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/835e095a-ca5f-4671-852e-588276787c37)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/55f2d852-5dba-467a-b7a2-0243bb4bbe19)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/57b57a2a-808b-4ac9-85fe-60c71bbb57e5)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/de74591a-ae22-40ca-8232-e5d7d29d4083)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/ae518014-0c39-4f9a-871e-c285f3dbffde)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/93e66d78-ce87-4fbc-b8b3-de3ec547e9ac)

Notifications

![image](https://github.com/Notifiarr/dockwatch/assets/8321115/f3f3b7cc-646c-4eaf-a344-99d0c1c81767)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/c8f75b40-6564-40ab-96ac-afa4d9cc0e65)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/bae49f20-573f-4b7e-99f8-35abd5a7b932)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/217f4c81-3b84-40f8-b3ce-a51dabda0e1f)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/f48b47db-125c-4caa-bbdb-50de224861e2)


