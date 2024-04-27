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

    public function getDockerLogs($container, $log)
    {
        if ($log != 'docker' && file_exists('/appdata/' . $container . '/logs/' . $log . '.log')) {
            $logFile    = file('/appdata/' . $container . '/logs/' . $log . '.log');
            $return     = '';

            foreach ($logFile as $line) {
                $line = json_decode($line, true);
                $return .= '[' . $line['timestamp'] . '] {' . $line['level'] . '} ' . $line['message'] . "\n";
            }

            return $return;
        }
    
        if ($log == 'docker') {
            return $this->logs($container);
        }
    }

    public function isIO($name)
    {
        if (!$name) {
            return;
        }
    
        return str_contains($name, '/') ? $name : 'library/' . $name;
    }
}

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
    public const REMOVE_CONTAINER = '/usr/bin/docker container rm -f %s';
    public const START_CONTAINER = '/usr/bin/docker container start %s';
    public const STOP_CONTAINER = '/usr/bin/docker container stop %s';
    public const ORPHAN_CONTAINERS = '/usr/bin/docker images -f dangling=true --format="{{json . }}" | jq -s --tab .';
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
