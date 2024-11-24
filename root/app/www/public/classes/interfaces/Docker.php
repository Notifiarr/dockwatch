<?php

/*
----------------------------------
 ------  Created: 082924   ------
 ------  Austin Best	   ------
----------------------------------
*/

interface DockerApi
{
    //-- CONTAINER SPECIFIC
    public const STOP_CONTAINER = '/containers/%s/stop';
    public const CREATE_CONTAINER = '/containers/create?name=%s';
}

//-- https://docs.docker.com/reference/cli/docker
interface DockerSock
{
    //-- GENERAL
    public const VERSION = '/usr/bin/docker version %s';
    public const RUN = '/usr/bin/docker run %s';
    public const LOGS = '/usr/bin/docker logs %s';
    public const PROCESSLIST_FORMAT = '/usr/bin/docker ps --all --no-trunc --size=false --format="{{json . }}" | jq -s --tab .';
    public const PROCESSLIST_CUSTOM = '/usr/bin/docker ps %s';
    public const STATS_FORMAT = '/usr/bin/docker stats --all --no-trunc --no-stream --format="{{json . }}" | jq -s --tab .';
    public const INSPECT_FORMAT = '/usr/bin/docker inspect %s --format="{{json . }}" | jq -s --tab .';
    public const INSPECT_CUSTOM = '/usr/bin/docker inspect %s %s';
    public const IMAGE_SIZES = '/usr/bin/docker images --format=\'{"ID":"{{ .ID }}", "Size": "{{ .Size }}"}\' | jq -s --tab .';
    //-- CONTAINER SPECIFIC
    public const KILL_CONTAINER = '/usr/bin/docker container kill %s';
    public const REMOVE_CONTAINER = '/usr/bin/docker container rm -f %s';
    public const START_CONTAINER = '/usr/bin/docker container start %s';
    public const STOP_CONTAINER = '/usr/bin/docker container stop %s%s';
    public const ORPHAN_CONTAINERS = '/usr/bin/docker images -f dangling=true --format="{{json . }}" | jq -s --tab .';
    public const UNUSED_CONTAINERS = '/usr/bin/docker images --format \'{{.ID}}:{{.Repository}}:{{.Tag}}\' | grep -v "$(docker ps -a --format {{.Image}})"';
    public const CONTAINER_PORT = '/usr/bin/docker port %s %s';
    //-- IMAGE SPECIFIC
    public const REMOVE_IMAGE = '/usr/bin/docker image rm %s';
    public const PULL_IMAGE = '/usr/bin/docker image pull %s';
    public const PRUNE_IMAGE = '/usr/bin/docker image prune -af';
    //-- VOLUME SPECIFIC
    public const ORPHAN_VOLUMES = '/usr/bin/docker volume ls -qf dangling=true --format="{{json . }}" | jq -s --tab .';
    public const PRUNE_VOLUME = '/usr/bin/docker volume prune -af';
    public const REMOVE_VOLUME = '/usr/bin/docker volume rm %s';
    //-- NETWORK SPECIFIC
    public const ORPHAN_NETWORKS = '/usr/bin/docker network ls -q --format="{{json . }}" | jq -s --tab .';
    public const INSPECT_NETWORK = '/usr/bin/docker network inspect %s --format="{{json . }}" | jq -s --tab .';
    public const PRUNE_NETWORK = '/usr/bin/docker network prune -af';
    public const REMOVE_NETWORK = '/usr/bin/docker network rm %s';
    public const GET_NETWORKS = '/usr/bin/docker network %s';
}
