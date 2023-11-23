# Docker Watcher

### Purpose
Simple UI driven way to manage updates & notifications for containers. As this is meant to be simple, there is no db container required or setup. This will save things it needs to files in the `/config` mount instead.

### Notification triggers
- Notify when a container is added
- Notify when a container is removed
- Notify when a container changes state (running -> stopped)
- Nofity when an update is available
- Notify when an update is applied
- Notify if memory is > n%
- Notify if CPU is > n%

### Notification platforms
- Notifiarr

### Update options
- Ignore
- Auto update
- Check for update

### Features
- Setup update schedules on a container by container basis
- Setup notify only or update on a container by container basis
- Mass cleanup orphan containers
- Mass select containers and generate `docker run` commands
- Mass select containers and generate a docker compose for them
- Mass select containers and start/restart/stop/pull
- Memcached support (optional)

### Permissions
No matter how docker is installed (native, unraid, etc), it is required that the user running the container has permission to use the docker commands. View `root/app/www/public/functions/docker.php` to see what is used
	
Unraid: This is built into the container with
```
addgroup -g 281 unraiddocker && \
usermod -aG unraiddocker abc
```

Ubuntu: This is an example
```
usermod -aG ping abc
```

### Run
This is an unraid example, adjust paths and settings for your setup

```
docker run \
  -d \
  --name='dockwatch' \
  --net='custom-bridge' \
  -e TZ="America/New_York" \
  -e HOST_OS="Unraid" \
  -e 'PUID'='1001' \
  -e 'PGID'='100' \
  -e 'UMASK'='022' \
  -l net.unraid.docker.managed=dockerman \
  -l net.unraid.docker.webui='http://[IP]:[PORT:9999]' \
  -l net.unraid.docker.icon='https://golift.io/crontabs.png' \
  -p '9999:80/tcp' \
  -v '/mnt/disk1/appdata/dockwatch/config':'/config':'rw' \
  -v '/var/run/docker.sock':'/var/run/docker.sock':'rw' 'ghcr.io/notifiarr/dockwatch:main'
```

### Compose
This is an example, adjust paths and settings for your setup

```
dockwatch:
	container_name: dockwatch
	image: ghcr.io/notifiarr/dockwatch:main
	ports:
	  - 9999:80/tcp
	environment:
	  - PUID=1001
	  - PGID=100
	  - UMASK=022
	  - TZ=Etc/UTC
	volumes:
	  - /appdata/dockwatch/config:/config
	  - /var/run/docker.sock:/var/run/docker.sock
```

### Manual
`docker pull ghcr.io/notifiarr/dockwatch:main`

### ENV
These are my settings, adjust them to fit your setup!!

Volumes
| Name | Host | Container |
| ----- | ----- | ----- |
| App Config | /mnt/disk1/appdata/dockwatch/config | /config |
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

### Login
There is support for a simple login mechanism but i would recomment using something like a reverse proxy with authentication
- Add a file `logins` to `/config`
- Add `admin:password` to the file and save it
- Reload
- Multiple logins, drop a line and add another `admin:password`

### Development
Firstly i **am not** a docker expert so there are likely other/better ways to do this. What i list below is just how i work on it without having to rebuilt the container for every change and a reminder for me on what i did
- Fork the repo
- Open the `Dockerfile` and comment out the `COPY root/ /` line at the bottom
- Copy the files from `root/app/www/public/*` to `/config/www/*`
- Copy the cron feom `root/etc/crontabs/abc` to `/config/crontabs/abc` (You'll need to add an ENV variable for `DOCKER_MODS=linuxserver/mods:universal-cron`)
- Copy the ini from `root/etc/php82/conf.d/dockwatch.ini` to `/config/php/php-local.ini`
- This should allow you to run the container while making changes to the files in `/config` and when done, just copy the files back into the `root/` directories and push your fork so it builds a new container

### Screenshots
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/bac13748-fffd-4624-bc94-6631e054d536)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/835e095a-ca5f-4671-852e-588276787c37)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/57b57a2a-808b-4ac9-85fe-60c71bbb57e5)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/6b89da1a-975a-4757-a4b2-2db02635330d)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/93e66d78-ce87-4fbc-b8b3-de3ec547e9ac)

