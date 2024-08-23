<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

/*
    USAGE:
        $api            = $docker->apiCreateContainer($inspect);
        $apiResponse    = $docker->apiCurl($request, 'post');
*/

trait API
{
    public function apiIsAvailable()
    {
        if ($_SERVER['DOCKER_HOST']) { //-- IF A DOCKER HOST IS PROVIDED, DONT EVEN TRY TO USE THE SOCK
            $curl = curl($_SERVER['DOCKER_HOST']);
    
            if ($curl['code'] == 403) {
                return true;
            }
    
            return false;
        }
    
        if (file_exists('/var/run/docker.sock')) {
            return true;
        }
    
        return false;
    }

    public function apiVersion()
    {
        $cmd    = sprintf(DockerSock::VERSION, '| grep "API"');
        $shell  = shell_exec($cmd . ' 2>&1');
        preg_match_all("/([0-9]\.[0-9]{1,3})/", $shell, $matches);
    
        return $matches[0][1];
    }

    public function apiCurl($request, $method)
    {
        $payload    = $request['payload'];
        $endpoint   = $request['endpoint'];
        $container  = $request['container'];
        $debug      = $request['debug'];
    
        //-- WRITE THE PAYLOAD TO A FILE
        $filename = $container . '_' . time() . '.json';
        file_put_contents(TMP_PATH . $filename, json_encode($payload, JSON_UNESCAPED_SLASHES));
    
        //-- BUILD THE CURL CALL
        $headers    = '-H "Content-Type: application/json"';
        $cmd        = 'curl --silent ' . (strtolower($method) == 'post' ? '-X POST' : '');
    
        if ($_SERVER['DOCKER_HOST']) {
            $cmd        .= ' ' . $_SERVER['DOCKER_HOST'] . $endpoint;
        } else {
            $version    = $this->apiVersion();
            $cmd        .= ' --unix-socket /var/run/docker.sock http://v'. $version . $endpoint;
        }
    
        if ($headers) {
            $cmd .= ' ' . $headers;
        }
        if ($payload) {
            $cmd .= ' -d @' . TMP_PATH . $filename;
        }
        $shell = shell_exec($cmd . ' 2>&1');
    
        if (!$shell) {
            $shell = ['result' => 'failed', 'cmd' => $cmd, 'shell' => $shell];
        } else {
            $shell = json_decode($shell, true);
    
            if (!is_array($shell)) {
                $shell = ['result' => 'failed', 'cmd' => $cmd, 'shell' => $shell];
            } else {
                $shell['cmd'] = $cmd;
            }
        }
    
        $shell['debug'] = $debug;
    
        return $shell;
    }

    //-- https://docs.docker.com/engine/api/v1.41/#tag/Container/operation/ContainerStop
    public function apiStopContainer($container)
    {
        /*
            Only added as a simple way to test it.
            $docker->apiStopContainer('container');
            $apiResponse if it works: 
                Array
                (
                    [message] => No such container: container
                )
        */

        return ['endpoint' => sprintf(DockerApi::STOP_CONTAINER, $container), 'payload' => ''];
    }

    public function apiCreateContainer($inspect = [])
    {
        $inspect    = $inspect[0] ? $inspect[0] : $inspect;
        $image      = $inspect['Config']['Image'];

        $containerName = '';
        if ($inspect['Name']) {
            $containerName = str_replace('/', '', $inspect['Name']);
        }

        if (!$containerName && !empty($inspect['Config']['Env'])) {
            foreach ($inspect['Config']['Env'] as $env) {
                if (str_contains($env, 'HOST_CONTAINERNAME')) {
                    list($key, $containerName) = explode('=', $env);
                    break;
                }
            }
        }

        $payload                        = [
                                            'Hostname'      => $inspect['Config']['Hostname'] ? $inspect['Config']['Hostname'] : $containerName,
                                            'Domainname'    => $inspect['Config']['Domainname'],
                                            'User'          => $inspect['Config']['User'],
                                            'Tty'           => $inspect['Config']['Tty'],
                                            'Entrypoint'    => $inspect['Config']['Entrypoint'],
                                            'Image'         => $inspect['Config']['Image'],
                                            'StopTimeout'   => $inspect['Config']['StopTimeout'],
                                            'StopSignal'    => $inspect['Config']['StopSignal'],
                                            'WorkingDir'    => $inspect['Config']['WorkingDir']
                                        ];

        $payload['Env']                 = $inspect['Config']['Env'];
        $payload['Cmd']                 = $inspect['Config']['Cmd'];
        $payload['Healthcheck']         = $inspect['Config']['Healthcheck'];
        $payload['Labels']              = $inspect['Config']['Labels'];
        $payload['Volumes']             = $inspect['Config']['Volumes'];
        $payload['ExposedPorts']        = $inspect['Config']['ExposedPorts'];
        $payload['HostConfig']          = $inspect['HostConfig'];
        $payload['NetworkingConfig']    = [
                                            'EndpointsConfig' => $inspect['NetworkSettings']['Networks']
                                        ];

        //-- API VALIDATION STUFF
        if (empty($payload['HostConfig']['PortBindings'])) {
            $payload['HostConfig']['PortBindings'] = null;
        }

        if (empty($payload['NetworkingConfig']['EndpointsConfig'])) {
            $payload['NetworkingConfig']['EndpointsConfig'] = null;
        } else {
            foreach ($payload['NetworkingConfig']['EndpointsConfig'] as $endpoint => $endpointData) {
                if (empty($payload['NetworkingConfig']['EndpointsConfig'][$endpoint]['IPAMConfig'])) {
                    $payload['NetworkingConfig']['EndpointsConfig'][$endpoint]['IPAMConfig'] = new StdClass();
                }
            }
        }

        if (is_array($payload['HostConfig']['DeviceRequests'])) {
            foreach ($payload['HostConfig']['DeviceRequests'] as $deviceRequestIndex => $deviceRequest) {
                if (empty($payload['HostConfig']['DeviceRequests'][$deviceRequestIndex]['Options'])) {
                    $payload['HostConfig']['DeviceRequests'][$deviceRequestIndex]['Options'] = new StdClass();
                }
            }
        }

        if (empty($payload['ExposedPorts'])) {
            $payload['ExposedPorts'] = null;
        } else {
            foreach ($payload['ExposedPorts'] as $port => $value) {
                $payload['ExposedPorts'][$port] = null;
            }
        }

        if (empty($payload['Volumes'])) {
            $payload['Volumes'] = null;
        } else {
            foreach ($payload['Volumes'] as $volume => $value) {
                $payload['Volumes'][$volume] = null;
            }
        }

        if (empty($payload['HostConfig']['LogConfig']['Config'])) {
            $payload['HostConfig']['LogConfig']['Config'] = null;
        }

        //-- PART OF A CONTAINER NETWORK
        if (str_contains($payload['HostConfig']['NetworkMode'], 'container:')) {
            $payload['Hostname']        = null;
            $payload['ExposedPorts']    = null;

            //-- MAKE SURE THE ID IS UPDATED
            $dependencyFile = getServerFile('dependency');
            $dependencyFile = is_array($dependencyFile['file']) && !empty($dependencyFile['file']) ? $dependencyFile['file'] : [];
    
            foreach ($dependencyFile as $parent => $parentSettings) {
                if (in_array($containerName, $parentSettings['containers'])) {
                    $payload['HostConfig']['NetworkMode'] = 'container:' . $parentSettings['id'];
                    break;
                }
            }
        }

        if (empty($payload['HostConfig']['Mounts'])) {
            $payload['HostConfig']['Mounts'] = null;
        } else {
            foreach ($payload['HostConfig']['Mounts'] as $index => $mount) {
                if (empty($payload['HostConfig']['Mounts'][$index]['VolumeOptions'])) {
                    $payload['HostConfig']['Mounts'][$index]['VolumeOptions'] = null;
                }
            }
        }

        if ($payload['NetworkSettings']['Networks']) {
            foreach ($payload['NetworkSettings']['Networks'] as $network => $networkSettings) {
                if ($networkSettings['Aliases']) {
                    if ($payload['Hostname'] == $networkSettings['Aliases'][0] && count($networkSettings['Aliases']) == 1) { //-- REMOVE SELF ALIAS
                        $networkSettings['Aliases'] = null;
                        break;
                    } else { //-- REMOVE ALIAS FROM PREVIOUS CONTAINER ID
                        foreach ($networkSettings['Aliases'] as $index => $alias) {
                            if ($alias == substr($inspect['Id'], 0, 12)) {
                                unset($payload['NetworkSettings']['Networks'][$network]['Aliases'][$index]);
                            }
                        }
                    }
                }
            }
        }

        return [
                'container' => $containerName, 
                'endpoint'  => sprintf(DockerApi::CREATE_CONTAINER, $containerName), 
                'payload'   => $payload, 
                'debug'     => ['dependencyFile' => $dependencyFile]
            ];
    }
}
