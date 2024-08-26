<?php

/*
----------------------------------
 ------  Created: 010624   ------
 ------  Austin Best	   ------
----------------------------------
*/

/*
    USAGE:
        $api            = dockerContainerCreateAPI($inspect);
        $apiResponse    = dockerCurlAPI($api, 'post');
*/

function dockerCommunicateAPI()
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

function dockerVersionAPI()
{
    global $shell;

    $cmd = '/usr/bin/docker version | grep "API"';
    $shell = $shell->exec($cmd . ' 2>&1');
    preg_match_all("/([0-9]\.[0-9]{1,3})/", $shell, $matches);

    return $matches[0][1];
}

function dockerCurlAPI($create, $method)
{
    global $shell;

    $payload    = $create['payload'];
    $endpoint   = $create['endpoint'];
    $container  = $create['container'];
    $debug      = $create['debug'];

    //-- WRITE THE PAYLOAD TO A FILE
    $filename = $container . '_' . time() . '.json';
    file_put_contents(TMP_PATH . $filename, json_encode($payload, JSON_UNESCAPED_SLASHES));

    //-- BUILD THE CURL CALL
    $headers    = '-H "Content-Type: application/json"';
    $cmd        = 'curl --silent ' . (strtolower($method) == 'post' ? '-X POST' : '');

    if ($_SERVER['DOCKER_HOST']) {
        $cmd        .= ' ' . $_SERVER['DOCKER_HOST'] . $endpoint;
    } else {
        $version    = dockerVersionAPI();
        $cmd        .= ' --unix-socket /var/run/docker.sock http://v'. $version . $endpoint;
    }

    if ($headers) {
        $cmd .= ' ' . $headers;
    }
    if ($payload) {
        $cmd .= ' -d @' . TMP_PATH . $filename;
    }
    $shell = $shell->exec($cmd . ' 2>&1');

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

function dockerEscapePayloadAPI($payload)
{
    $in     = ["\\", '"', '$'];
    $out    = ["\\\\", '\"', '\$'];

    return str_replace($in, $out, $payload);
}

//-- https://docs.docker.com/engine/api/v1.41/#tag/Container/operation/ContainerStop
function dockerContainerStopAPI($name)
{
    /*
        Only added as a simple way to test it.
        dockerContainerStopAPI('container');
        $apiResponse if it works: 
            Array
            (
                [message] => No such container: container
            )
    */

    $endpoint = '/containers/' . $name . '/stop';

    return ['endpoint' => $endpoint, 'payload' => ''];
}

//-- https://docs.docker.com/engine/api/v1.42/#tag/Container/operation/ContainerCreate
function dockerContainerCreateAPI($inspect = [])
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

    $endpoint   = '/containers/create?name=' . $containerName;
    $debug      = ['dependencyFile' => $dependencyFile];

    return ['container' => $containerName, 'endpoint' => $endpoint, 'payload' => $payload, 'debug' => $debug];
}
