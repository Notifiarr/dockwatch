<?php

/*
----------------------------------
 ------  Created: 010624   ------
 ------  Austin Best	   ------
----------------------------------
*/

function dockerVersionAPI()
{
    $cmd = '/usr/bin/docker version | grep "API"';
    $shell = shell_exec($cmd . ' 2>&1');
    preg_match_all("/([0-9]\.[0-9]{1,3})/", $shell, $matches);
    return $matches[0][1];
}

function dockerCurlAPI($payload, $method, $endpoint)
{
    $version    = dockerVersionAPI();
    $headers    = '-H "Content-Type: application/json"';
    $cmd        = 'curl --silent ' . (strtolower($method) == 'post' ? '-X POST' : '');
    $cmd        .= ' --unix-socket /var/run/docker.sock http://v'. $version . $endpoint;
    if ($headers) {
        $cmd .= ' ' . $headers . ' ';
    }
    if ($payload) {
        $cmd .= ' -d \'' . $payload . '\'';
    }
    $shell = json_decode(shell_exec($cmd . ' 2>&1'), true);

    return $shell;
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
    $apiResponse = dockerCurlAPI(null, 'post', $endpoint);
}

//-- https://docs.docker.com/engine/api/v1.42/#tag/Container/operation/ContainerCreate
function dockerContainerCreateAPI($inspect = [])
{
    $inspect = $inspect[0] ? $inspect[0] : $inspect;

    $containerName = '';
    if (!empty($inspect['Config']['Env'])) {
        foreach ($inspect['Config']['Env'] as $env) {
            if (str_contains($env, 'HOST_CONTAINERNAME')) {
                list($key, $containerName) = explode('=', $env);
                break;
            }
        }
    }

    $ipamConfig = $links = $aliases = null;
    if (!empty($inspect['NetworkSettings']['Networks'])) {
        $network = array_keys($inspect['NetworkSettings']['Networks'])[0];

        if (!empty($inspect['NetworkSettings']['Networks'][$network]['IPAMConfig'])) {
            $ipamConfig = $inspect['NetworkSettings']['Networks'][$network]['IPAMConfig'];
        }
        if (!empty($inspect['NetworkSettings']['Networks'][$network]['Links'])) {
            $links = $inspect['NetworkSettings']['Networks'][$network]['Links'];
        }
        if (!empty($inspect['NetworkSettings']['Networks'][$network]['Aliases'])) {
            $aliases = $inspect['NetworkSettings']['Networks'][$network]['Aliases'];
        }
    }

    $payload                        = [
                                        'Domainname'        => $inspect['Config']['Domainname'],
                                        'User'              => $inspect['Config']['User'],
                                        'Tty'               => $inspect['Config']['Tty'],
                                        'Entrypoint'        => $inspect['Config']['Entrypoint'],
                                        'Image'             => $inspect['Config']['Image'],
                                        'WorkingDir'        => $inspect['Config']['WorkingDir'],
                                        'MacAddress'        => $inspect['NetworkSettings']['MacAddress'],
                                        'Shell'             => '' //-- NOT SURE WHERE THIS IS YET
                                    ];

    $payload['Env']                 = $inspect['Config']['Env'];
    $payload['Cmd']                 = $inspect['Config']['Cmd'];
    $payload['Healthcheck']         = $inspect['Config']['Healthcheck'];
    $payload['Labels']              = $inspect['Config']['Labels'];
    $payload['Volumes']             = $inspect['Config']['Volumes'];
    $payload['ExposedPorts']        = $inspect['NetworkSettings']['Ports'];
    $payload['HostConfig']          = $inspect['HostConfig'];
    $payload['NetworkingConfig']    = [
                                        'EndpointsConfig'   => [
                                                                'isolated_nw'   => [
                                                                                    'IPAMConfig'    => $ipamConfig,
                                                                                    'Links'         => $links,
                                                                                    'Aliases'       => $aliases
                                                                                ]
                                                            ]
                                    ];

    //-- API VALIDATION STUFF
    if (empty($payload['HostConfig']['PortBindings'])) {
        $payload['HostConfig']['PortBindings'] = null;
    }

    $endpoint       = '/containers/create?name=' . $containerName;
    $apiResponse    = dockerCurlAPI(json_encode($payload), 'post', $endpoint);

    return $apiResponse;
}
