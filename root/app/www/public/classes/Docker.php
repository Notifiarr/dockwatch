<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- BRING IN THE TRAITS
$traits     = ABSOLUTE_PATH . 'classes/traits/Docker/';
$traitsDir  = opendir($traits);
while ($traitFile = readdir($traitsDir)) {
    if (str_contains($traitFile, '.php')) {
        require $traits . $traitFile;
    }
}
closedir($traitsDir);

class Docker
{
    use API;
    use Container;
    use Image;
    use Network;
    use Process;
    use Volume;

    public function __construct()
    {

    }

    public function stats($useCache)
    {
        if (file_exists(STATS_FILE)) {
            $statsFile = file_get_contents(STATS_FILE);
        }
    
        if ($statsFile && $useCache) {
            return $statsFile;
        }

        $cmd    = DockerSock::STATS_FORMAT;
        $shell  = shell_exec($cmd . ' 2>&1');
        setServerFile('stats', $shell);

        return $shell;
    }

    public function inspect($what, $useCache = true, $format = true, $params = '')
    {
        if ($format) {
            $cmd = sprintf(DockerSock::INSPECT_FORMAT, $what);
        } else {
            $cmd = sprintf(DockerSock::INSPECT_CUSTOM, $what, $params);
        }
    
        $shell  = shell_exec($cmd . ' 2>&1');

        return $shell;
    }

    public function logs($container)
    {
        $cmd    = sprintf(DockerSock::LOGS, $container);
        $shell  = shell_exec($cmd . ' 2>&1');
        $in     = ["\n", '[36m', '[31m', '[0m'];
        $out    = ['<br>', '', '', ''];

        return str_replace($in, $out, $shell);
    }
}

//-- https://docs.docker.com/reference/cli/docker
interface DockerSock
{
    //-- GENERAL
    public const LOGS = '/usr/bin/docker logs %s';
    public const PROCESSLIST_FORMAT = '/usr/bin/docker ps --all --no-trunc --size=false --format="{{json . }}" | jq -s --tab .';
    public const PROCESSLIST_CUSTOM = '/usr/bin/docker ps %s';
    public const STATS_FORMAT = '/usr/bin/docker stats --all --no-trunc --no-stream --format="{{json . }}" | jq -s --tab .';
    public const INSPECT_FORMAT = '/usr/bin/docker inspect %s --format="{{json . }}" | jq -s --tab .';
    public const INSPECT_CUSTOM = '/usr/bin/docker inspect %s %s';
    //-- CONTAINER SPECIFIC
    public const REMOVE_CONTAINER = '/usr/bin/docker container rm -f %s';
    public const START_CONTAINER = '/usr/bin/docker container start %s';
    public const STOP_CONTAINER = '/usr/bin/docker container stop %s';
    public const ORPHAN_CONTAINERS = '/usr/bin/docker images -f dangling=true --format="{{json . }}" | jq -s --tab .';
    //-- IMAGE SPECIFIC
    public const REMOVE_IMAGE = '/usr/bin/docker image rm %s';
    public const PULL_IMAGE = '/usr/bin/docker image pull %s';
    public const PRUNE_IMAGE = '/usr/bin/docker image prune -af';
    //-- VOLUME SPECIFIC
    public const ORPHAN_VOLUMES = '/usr/bin/docker volume ls -qf dangling=true --format="{{json . }}" | jq -s --tab .';
    //-- NETWORK SPECIFIC
    public const ORPHAN_NETWORKS = '/usr/bin/docker network ls -q --format="{{json . }}" | jq -s --tab .';
    public const INSPECT_NETWORK = '/usr/bin/docker network inspect %s --format="{{json . }}" | jq -s --tab .';
}
