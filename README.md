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

### Image
`ghcr.io/notifiarr/dockwatch:main`

### Install
`docker pull ghcr.io/notifiarr/dockwatch:main`

### Run
This is an example from an Unraid install

```
docker run
  -d
  --name='dockwatch'
  --net='custom-bridge'
  -e TZ="America/New_York"
  -e HOST_OS="Unraid"
  -e 'PUID'='1001'
  -e 'PGID'='100'
  -e 'UMASK'='022'
  -l net.unraid.docker.managed=dockerman
  -l net.unraid.docker.webui='http://[IP]:[PORT:9999]'
  -l net.unraid.docker.icon='https://golift.io/crontabs.png'
  -p '9999:80/tcp'
  -v '/mnt/disk1/appdata/dockwatch/config':'/config':'rw'
  -v '/mnt/disk1/appdata/dockwatch/logs':'/logs':'rw'
  -v '/var/run/docker.sock':'/var/run/docker.sock':'rw' 'ghcr.io/notifiarr/dockwatch:main'
```

### Permissions
No matter how docker is installed (native, unraid, etc), it is required that the user running the container has permission to use the docker commands

Unraid: This is built into the container with
```
addgroup -g 281 unraiddocker && \
usermod -aG unraiddocker abc
```

Ubuntu: This is an example
```
usermod -aG ping abc
```

### ENV
These are my settings, adjust them to fit your setup!!

Volumes
| Name | Host | Container |
| ----- | ----- | ----- |
| App Config | /mnt/disk1/appdata/dockwatch/config | /config |
| Logs | /mnt/disk1/appdata/dockwatch/logs | /logs |
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

### Screenshots
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/bac13748-fffd-4624-bc94-6631e054d536)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/d76842e2-d362-4e3b-9c01-168f0497e464)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/3df6c25c-5329-4289-bb92-23220ebac9be)
![image](https://github.com/Notifiarr/dockwatch/assets/8321115/271e4b7d-cc72-4d4f-ae24-a7e2f91a8141)
