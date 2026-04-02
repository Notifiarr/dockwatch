<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Container
{
    private $output;
    private $process;
    private $pipes;

    public function getContainerPort($containerName, $params = '')
    {
        $cmd = sprintf(DockerSock::CONTAINER_PORT, $this->shell->prepare($containerName), $this->shell->prepare($params));
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function removeContainer($containerName)
    {
        $cmd = sprintf(DockerSock::REMOVE_CONTAINER, $this->shell->prepare($containerName));
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function startContainer($containerName)
    {
        $cmd = sprintf(DockerSock::START_CONTAINER, $this->shell->prepare($containerName));
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function killContainer($containerName)
    {
        $cmd = sprintf(DockerSock::KILL_CONTAINER, $this->shell->prepare($containerName));
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function stopContainer($containerName)
    {
        $nameHash  = md5($containerName);
        $container = $this->database->getContainerFromHash($nameHash);
        $delay     = intval($container['shutdownDelaySeconds']) >= 5 ? ' -t ' . $container['shutdownDelaySeconds'] : ' -t 120';

        if ($container['shutdownDelay']) {
            logger(SYSTEM_LOG, 'stopContainer() delaying stop command for container ' . $containerName . ' with ' . $delay, 'warn');
        }

        $cmd = sprintf(DockerSock::STOP_CONTAINER, $this->shell->prepare($containerName), ($container['shutdownDelay'] ? $this->shell->prepare($delay) : ''));

        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function getUnusedContainers()
    {
        $unused     = [];
        $cmd        = DockerSock::UNUSED_CONTAINERS;
        $containers = $this->shell->exec($cmd . ' 2>&1');

        if ($containers) {
            $containerList = explode("\n", $containers);
            foreach ($containerList as $container) {
                list($id, $image, $tag) = explode(':', $container);

                if (!$id) {
                    continue;
                }

                $unused[] = ['ID' => $id, 'Repository' => $image, 'Tag' => $tag];
            }
        }

        return json_encode($unused);
    }

    public function getOrphanContainers()
    {
        $cmd = DockerSock::ORPHAN_CONTAINERS;
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function findContainer($query = [])
    {
        if ($query['id']) {
            foreach ($query['data'] as $process) {
                if ($process['ID'] == $query['id']) {
                    return $process['Names'];
                }
            }
        }

        if ($query['hash']) {
            if (!$query['data']) {
                $query['data'] = apiRequest('file/state')['result'];
            }

            foreach ($query['data'] as $container) {
                if (md5($container['Names']) == $query['hash']) {
                    return $container;
                }
            }
        }
    }

    public function getContainerNetworkDependencies($parentId, $processList)
    {
        $dependencies = [];

        foreach ($processList as $process) {
            $networkMode = $process['inspect'][0]['HostConfig']['NetworkMode'];

            if (str_contains($networkMode, ':')) {
                list($null, $networkContainer) = explode(':', $networkMode);

                if ($networkContainer == $parentId) {
                    $dependencies[] = $process['Names'];
                }
            }
        }

        return $dependencies;
    }

    public function getContainerLabelDependencies($containerName, $processList)
    {
        $dependencies = [];

        foreach ($processList as $process) {
            $labels = $process['inspect'][0]['Config']['Labels'] ? $process['inspect'][0]['Config']['Labels'] : [];

            foreach ($labels as $name => $key) {
                if (str_contains($name, 'depends_on')) {
                    list($container, $condition) = explode(':', $key);

                    if ($container == $containerName) {
                        $dependencies[] = $process['Names'];
                    }
                }
            }
        }

        return $dependencies;
    }

    public function setContainerDependencies($processList)
    {
        $dependencyList = [];
        foreach ($processList as $process) {
            $dependencies = $this->getContainerNetworkDependencies($process['ID'], $processList);

            if ($dependencies) {
                $dependencyList[$process['Names']] = ['id' => $process['ID'], 'containers' => $dependencies];
            }
        }

        apiRequest('file/dependency', [], ['contents' => $dependencyList]);

        return $dependencyList;
    }

    public function exec($container, $command)
    {
        $this->output = '';

        //-- CHECK IF CONTAINER IS RUNNING
        $checkCmd       = sprintf(DockerSock::CONTAINER_PROCESS, escapeshellarg($container));
        $containerCheck = trim(shell_exec($checkCmd));
        if (empty($containerCheck)) {
            return "Container " . $container . " is not running or does not exist";
        }

        //-- DETECT AVAILABLE SHELL
        $shellCheckCmd = sprintf(DockerSock::EXEC, escapeshellarg($container), "sh -c 'command -v sh || command -v bash || command -v ash || echo no_shell'");
        $shellCheck    = trim(shell_exec($shellCheckCmd));
        $shell         = '/bin/sh';
        if ($shellCheck === 'no_shell') {
            return "No shell found in container " . $container . " (tried sh, bash, ash)";
        } else if (!empty($shellCheck)) {
            $shell = $shellCheck;
        }

        //-- EXECUTE COMMAND
        $execCmd = sprintf(DockerSock::EXEC, escapeshellarg($container), escapeshellarg($shell) . " -c " . escapeshellarg($command) . " 2>&1");
        $output  = shell_exec($execCmd);

        //-- CLEAN OUTPUT
        $output       = preg_replace('/\x1B\[[0-9;]*[a-zA-Z]/', '', $output); //-- REMOVE ANSI CODES
        $output       = trim($output);
        $this->output = $output;

        return $this->output;
    }

    public function getContainerVersion($inspect, $ui = true)
    {
        $return = '';

        //-- CHECK: Config.Labels -> org.opencontainers.image.version
        foreach ($inspect[0]['Config']['Labels'] as $label => $val) {
            if (str_contains($label, 'image.version')) {
                $return = $val;
                break;
            }
        }
        //-- CHECK: Config.Env -> VERSION
        foreach ($inspect[0]['Config']['Env'] as $env => $val) {
            if (str_starts_with(strtoupper($val), 'VERSION=')) {
                $return = explode('=', $val)[1];
                break;
            }
        }
        //-- ADD LINKS IF POSSIBLE
        $rev    = substr($inspect[0]['Config']['Labels']['org.opencontainers.image.revision'] ?? '', 0, 7);
        $source = $inspect[0]['Config']['Labels']['org.opencontainers.image.source'] ?? '';
        if (!empty($rev) && !empty($source)) {
            $link    = $source . '/commit/' . $rev;
            $return .= $ui ? ' <a href="' . $link . '" target="_blank">' . $rev . '</a>' : ' ' . $rev;
        }
        return $return;
    }
}
