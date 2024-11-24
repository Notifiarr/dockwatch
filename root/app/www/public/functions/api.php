<?PHP

/*
----------------------------------
 ------  Created: 052521   ------
 ------  Austin Best	   ------
----------------------------------
*/

function generateApikey($length = 32)
{
    return bin2hex(random_bytes($length));
}

function apiSetActiveServer($serverId, $serversTable = [])
{
    global $database;

    $serversTable                   = $serversTable ?: $database->getServers();
    $_SESSION['activeServerId']     = $serversTable[$serverId]['id'];
    $_SESSION['activeServerName']   = $serversTable[$serverId]['name'];
    $_SESSION['activeServerUrl']    = rtrim($serversTable[$serverId]['url'], '/');
    $_SESSION['activeServerApikey'] = $serversTable[$serverId]['apikey'];
}

function apiGetActiveServer()
{
    global $database;

    if ($_SESSION['activeServerId'] && !$_SESSION['activeServerApikey']) {
        $serversTable                   = $database->getServers();
        $_SESSION['activeServerId']     = $serversTable[$_SESSION['activeServerId']]['id'];
        $_SESSION['activeServerName']   = $serversTable[$_SESSION['activeServerId']]['name'];
        $_SESSION['activeServerUrl']    = rtrim($serversTable[$_SESSION['activeServerId']]['url'], '/');
        $_SESSION['activeServerApikey'] = $serversTable[$_SESSION['activeServerId']]['apikey'];
    }

    return  [
            'id'        => $_SESSION['activeServerId'],
            'name'      => $_SESSION['activeServerName'],
            'url'       => $_SESSION['activeServerUrl'],
            'apikey'    => $_SESSION['activeServerApikey']
        ];
}

function apiResponse($code, $response)
{
    if (!str_contains_any($_SERVER['PHP_SELF'], ['/api/'])) {
        return ['code' => $code, 'result' => $response];
    }

    session_unset();
    session_destroy();

    http_response_code($code);

    $return['code'] = $code;
    if ($response['error']) {
        $return['error'] = $response['error'];
    } else {
        $return['response'] = $response;
    }

    $return = json_encode($return, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    header('Content-Length: ' . strlen($return));
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    echo $return;

    die();
}

function apiRequest($endpoint, $parameters = [], $payload = [])
{
    $activeServer = apiGetActiveServer();
    if ($activeServer['id'] == APP_SERVER_ID) {
        return ['code' => 200, 'result' => apiRequestLocal($endpoint, $parameters, $payload)];
    }

    return apiRequestRemote($endpoint, $parameters, $payload);
}

function apiRequestRemote($endpoint, $parameters = [], $payload = [])
{
    $activeServer = apiGetActiveServer();
    logger(SYSTEM_LOG, 'apiRequestRemote() ' . $endpoint . ' for server ' . $activeServer['name']);

    if ($payload) {
        $parameters['request'] = $endpoint;
        $parameters = http_build_query($parameters);

        if (!$payload['request']) {
            $payload['request'] = $endpoint;
        }

        $curl = curl($activeServer['url'] . '/api/' . ($parameters ? '?' . $parameters : ''), ['x-api-key: ' . $activeServer['apikey']], 'POST', json_encode($payload), [], REMOTE_SERVER_TIMEOUT);
    } else {
        $parameters['request'] = $endpoint;
        $builtParams = http_build_query($parameters);

        $curl = curl($activeServer['url'] . '/api/' . ($builtParams ? '?' . $builtParams : ''), ['x-api-key: ' . $activeServer['apikey']], 'GET', '', [], REMOTE_SERVER_TIMEOUT);
    }

    if (!str_equals_any($curl['code'], [200, 400, 401, 405])) {
        if ($endpoint != 'server-ping') {
            if ($curl['code'] == 200 && !is_array($curl['response'])) {
                apiResponse(500, ['error' => 'Remote request failed with error: ' . $curl['response']]);
            } else {
                apiResponse(500, ['error' => 'Remote request failed with code ' . $curl['code']]);
            }
        }
    }

    $curl['response']   = makeArray($curl['response']);
    $result['code']     = $curl['response']['code'];
    $result['result']   = $curl['response']['response']['result'];
    $result['error']    = $curl['response']['error'];

    return $result;
}

function apiRequestLocal($endpoint, $parameters = [], $payload = [])
{
    global $database, $docker, $notifications;

    if (!$payload) { //-- GET
        switch ($endpoint) {
            case 'database/container/hash':
            case 'database-getContainerFromHash':
                if (!$parameters['hash']) {
                    apiResponse(400, ['error' => 'Missing hash parameter']);
                }

                $containersTable    = $database->getContainers();
                $container          = $database->getContainerFromHash($parameters['hash'], $containersTable);

                if (!$container['id']) {
                    $database->addContainer(['hash' => $parameters['hash']]);
                    $container = $database->getContainerFromHash($parameters['hash']);
                }

                return $container;
            case 'database/group/hash':
            case 'database-getContainerGroupFromHash':
                if (!$parameters['hash']) {
                    apiResponse(400, ['error' => 'Missing hash parameter']);
                }
                $groupsTable = $database->getContainerGroups();

                return $database->getContainerGroupFromHash($parameters['hash'], $groupsTable);
            case 'database/containers';
            case 'database-getContainers':
                return $database->getContainers();
            case 'database/groups';
            case 'database-getContainerGroups':
                return $database->getContainerGroups();
            case 'database/group/links';
            case 'database-getContainerGroupLinks':
                return $database->getContainerGroupLinks();
            case 'database/group/container/links':
            case 'database-getGroupLinkContainersFromGroupId';
                if (!$parameters['group']) {
                    apiResponse(400, ['error' => 'Missing group parameter']);
                }
                $containersTable        = $database->getContainers();
                $containerLinksTable    = $database->getContainerGroupLinks();

                return $database->getGroupLinkContainersFromGroupId($containerLinksTable, $containersTable, $parameters['group']);
            case 'database/notification/links':
            case 'database-getNotificationLinks':
                return $database->getNotificationLinks();
            case 'database/notification/link/platform/name':
            case 'database-getNotificationLinkPlatformFromName':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return $database->getNotificationLinkPlatformFromName($parameters['name']);
            case 'database/notification/platforms':
            case 'database-getNotificationPlatforms':
                return $database->getNotificationPlatforms();
            case 'database/notification/triggers':
            case 'database-getNotificationTriggers':
                return $database->getNotificationTriggers();
            case 'database/servers':
            case 'database-getServers':
                return $database->getServers();
            case 'database/settings':
            case 'database-getSettings':
                return $database->getSettings();
            case 'database/notification/trigger/enabled':
            case 'database-isNotificationTriggerEnabled':
                if (!$parameters['trigger']) {
                    apiResponse(400, ['error' => 'Missing trigger parameter']);
                }

                return $database->isNotificationTriggerEnabled($parameters['trigger']);
            case 'database/migrations':
            case 'database-migrations':
                $database->migrations();
                return 'migrations applied';
            case 'docker/create/compose':
            case 'docker-autoCompose':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return dockerAutoCompose($parameters['name']);
            case 'docker/create/run':
            case 'docker-autoRun':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return dockerAutoRun($parameters['name']);
            case 'docker/orphans/containers':
            case 'docker-getOrphanContainers':
                return $docker->getOrphanContainers();
            case 'docker/orphans/networks':
            case 'docker-getOrphanNetworks':
                return $docker->getOrphanNetworks();
            case 'docker/orphans/volumes':
            case 'docker-getOrphanVolumes':
                return $docker->getOrphanVolumes();
            case 'docker/unused/containers':
            case 'docker-getUnusedContainers':
                return $docker->getUnusedContainers();
            case 'docker/images/sizes':
            case 'docker-imageSizes':
                return $docker->getImageSizes();
            case 'docker/container/inspect':
            case 'docker-inspect':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return $docker->inspect($parameters['name'], $parameters['useCache'], $parameters['format'], $parameters['params']);
            case 'docker/container/logs':
            case 'docker-logs':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return $docker->logs($parameters['name']);
            case 'docker/networks':
            case 'docker-networks':
                return $docker->getNetworks($parameters['params']);
            case 'docker/permissions':
            case 'docker-permissionCheck':
                return dockerPermissionCheck();
            case 'docker/container/ports':
            case 'docker-port':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return $docker->getContainerPort($parameters['name'], $parameters['params']);
            case 'docker/processList':
            case 'docker-processList':
                return $docker->processList($parameters['useCache'], $parameters['format'], $parameters['params']);
            case 'docker/stats':
            case 'docker-stats':
                return $docker->stats($parameters['useCache']);
            case 'dockerAPI/container/create':
            case 'dockerAPI-createContainer':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $inspect = json_decode($docker->inspect($parameters['name'], false, true), true);
                if (!$inspect) {
                    apiResponse(400, ['error' => 'Failed to get inspect for container: ' . $parameters['name']]);
                }

                return json_encode($docker->apiCreateContainer($inspect));
            case 'file/dependency':
            case 'file-dependency':
            case 'file/pull':
            case 'file-pull':
            case 'file/sse':
            case 'file-sse':
            case 'file/state':
            case 'file-state':
            case 'file/stats':
            case 'file-stats':
                $file = strtoupper(str_replace('file-', '', $endpoint));
                return getFile(constant($file . '_FILE'));
            case 'server/log':
            case 'server-log':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return viewLog($parameters['name']);
            case 'server/ping':
            case 'server-ping':
                if (getFile(MIGRATION_FILE)) {
                    apiResponse(423, ['error' => 'Migration in progress']);
                }

                return gitVersion(true);
            case 'stats/containers':
            case 'stats-getContainersList':
                return apiResponse(200, getContainerStats());
            case 'stats/overview':
            case 'stats-getOverview':
                return apiResponse(200, getOverviewStats());
            default:
                apiResponse(405, ['error' => 'Invalid GET request (endpoint=' . $endpoint . ')']);
                break;
        }
    } else { //-- POST
        unset($payload['request']);

        switch ($endpoint) {
            case 'database/container/add':
            case 'database-addContainer':
                if (!$payload['hash']) {
                    apiResponse(400, ['error' => 'Missing hash parameter']);
                }

                return $database->addContainer($payload);
            case 'database/container/group/add':
            case 'database-addContainerGroup':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return $database->addContainerGroup($payload['name']);
            case 'database/notification/link/add':
            case 'database-addNotificationLink':
                if (!$parameters['platformId']) {
                    apiResponse(400, ['error' => 'Missing platformId parameter']);
                }

                return $database->addNotificationLink($parameters['platformId'], $payload['triggerIds'], $payload['platformParameters'], $payload['senderName']);
            case 'database/container/group/delete':
            case 'database-deleteContainerGroup':
                if (!$payload['id']) {
                    apiResponse(400, ['error' => 'Missing id parameter']);
                }

                return $database->deleteContainerGroup($payload['id']);
            case 'database/notification/link/delete':
            case 'database-deleteNotificationLink':
                if (!$payload['linkId']) {
                    apiResponse(400, ['error' => 'Missing linkId parameter']);
                }

                return $database->deleteNotificationLink($payload['linkId']);
            case 'database/group/container/update':
            case 'database-updateContainerGroup':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }
                if (!$payload['id']) {
                    apiResponse(400, ['error' => 'Missing id parameter']);
                }

                return $database->updateContainerGroup($payload['id'], ['name' => $database->prepare($payload['name'])]);
            case 'database/group/container/link/add':
            case 'database-addContainerGroupLink':
                if (!$payload['groupId']) {
                    apiResponse(400, ['error' => 'Missing groupId parameter']);
                }
                if (!$payload['containerId']) {
                    apiResponse(400, ['error' => 'Missing containerId parameter']);
                }

                return $database->addContainerGroupLink($payload['groupId'], $payload['containerId']);
            case 'database/group/container/link/remove':
            case 'database-removeContainerGroupLink':
                if (!$payload['groupId']) {
                    apiResponse(400, ['error' => 'Missing groupId parameter']);
                }
                if (!$payload['containerId']) {
                    apiResponse(400, ['error' => 'Missing containerId parameter']);
                }

                return $database->removeContainerGroupLink($payload['groupId'], $payload['containerId']);
            case 'database/servers':
            case 'database-setServers':
                if (!$payload['serverList']) {
                    apiResponse(400, ['error' => 'Missing serverList parameter']);
                }

                return $database->setServers($payload['serverList']);
            case 'database/setting':
            case 'database-setSetting':
                if (!$payload['setting']) {
                    apiResponse(400, ['error' => 'Missing setting parameter']);
                }
                if (!array_key_exists('value', $payload)) {
                    apiResponse(400, ['error' => 'Missing value parameter']);
                }

                return $database->setSetting($payload['setting'], $payload['value']);
            case 'database/settings':
            case 'database-setSettings':
                if (!$payload['newSettings']) {
                    apiResponse(400, ['error' => 'Missing newSettings parameter']);
                }
                $settingsTable = $database->getSettings();

                return $database->setSettings($payload['newSettings'], $settingsTable);
            case 'database/container/update':
            case 'database-updateContainer':
                if (!$payload['hash']) {
                    apiResponse(400, ['error' => 'Missing hash parameter']);
                }

                return $database->updateContainer($payload['hash'], $payload);
            case 'database/notification/link/update':
            case 'database-updateNotificationLink':
                if (!$payload['linkId']) {
                    apiResponse(400, ['error' => 'Missing linkId parameter']);
                }

                return $database->updateNotificationLink($payload['linkId'], $payload['triggerIds'], $payload['platformParameters'], $payload['senderName']);
            case 'docker/container/create':
            case 'docker-createContainer':
                if (!$payload['inspect']) {
                    apiResponse(400, ['error' => 'Missing inspect parameter']);
                }

                return dockerCreateContainer(json_decode($payload['inspect'], true));
            case 'docker/container/kill':
            case 'docker-killContainer':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return $docker->killContainer($payload['name']);
            case 'docker/container/pull':
            case 'docker-pullContainer':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return $docker->pullImage($payload['name']);
            case 'docker/container/remove':
            case 'docker-removeContainer':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return $docker->removeContainer($payload['name']);
            case 'docker/image/remove':
            case 'docker-removeImage':
                if (!$payload['image']) {
                    apiResponse(400, ['error' => 'Missing image parameter']);
                }

                return $docker->removeImage($payload['image']);
            case 'docker/network/remove':
            case 'docker-removeNetwork':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                return $docker->removeNetwork($payload['id']);
            case 'docker/volume/remove':
            case 'docker-removeVolume':
                if (!$payload['id']) {
                    apiResponse(400, ['error' => 'Missing id parameter']);
                }

                return $docker->removeVolume($payload['name']);
            case 'docker/container/restart':
            case 'docker-restartContainer':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $stopContainer = $docker->stopContainer($payload['name']);
                $return[] = 'docker-stopContainer: ' . json_encode($stopContainer, JSON_UNESCAPED_SLASHES);
                $startContainer = $docker->startContainer($payload['name']);
                $return[] = 'docker-startContainer: ' . json_encode($startContainer, JSON_UNESCAPED_SLASHES);

                if ($payload['dependencies']) {
                    $dependencyFile = getFile(DEPENDENCY_FILE);
                    $dependencies   = $dependencyFile[$payload['name']]['containers'];
                    $dependencies   = is_array($dependencies) ? $dependencies : [];

                    if ($dependencies) {
                        $return[] = 'restarting dependenices...';
            
                        foreach ($dependencies as $dependency) {
                            $stopContainer = $docker->stopContainer($dependency);
                            $return[] = 'docker-stopContainer: ' . json_encode($stopContainer, JSON_UNESCAPED_SLASHES);
                            $startContainer = $docker->startContainer($dependency);
                            $return[] = 'docker-startContainer: ' . json_encode($startContainer, JSON_UNESCAPED_SLASHES);
                        }
                    }
                }

                return $payload['dependencies'] ? $return : $startContainer;
            case 'docker/container/start':
            case 'docker-startContainer':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $startContainer = $docker->startContainer($payload['name']);
                $return[] = 'docker-startContainer: ' . json_encode($startContainer, JSON_UNESCAPED_SLASHES);

                if ($payload['dependencies']) {
                    $dependencyFile = getFile(DEPENDENCY_FILE);
                    $dependencies   = $dependencyFile[$payload['name']]['containers'];
                    $dependencies   = is_array($dependencies) ? $dependencies : [];

                    if ($dependencies) {
                        $return[] = 'starting dependenices...';
            
                        foreach ($dependencies as $dependency) {
                            $startContainer = $docker->startContainer($dependency);
                            $return[] = 'docker-startContainer: ' . json_encode($startContainer, JSON_UNESCAPED_SLASHES);
                        }
                    }
                }

                return $payload['dependencies'] ? $return : $startContainer;
            case 'docker/container/stop':
            case 'docker-stopContainer':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $stopContainer = $docker->stopContainer($payload['name']);
                $return[] = 'docker-stopContainer: ' . json_encode($stopContainer, JSON_UNESCAPED_SLASHES);

                if ($payload['dependencies']) {
                    $dependencyFile = getFile(DEPENDENCY_FILE);
                    $dependencies   = $dependencyFile[$payload['name']]['containers'];
                    $dependencies   = is_array($dependencies) ? $dependencies : [];

                    if ($dependencies) {
                        $return[] = 'stopping dependenices...';
            
                        foreach ($dependencies as $dependency) {
                            $stopContainer = $docker->stopContainer($dependency);
                            $return[] = 'docker-stopContainer: ' . json_encode($stopContainer, JSON_UNESCAPED_SLASHES);
                        }
                    }
                }

                return $payload['dependencies'] ? $return : $stopContainer;
            case 'file/dependency':
            case 'file-dependency':
            case 'file/pull':
            case 'file-pull':
            case 'file/sse':
            case 'file-sse':
            case 'file/state':
            case 'file-state':
            case 'file/stats':
            case 'file-stats':
                $fileName       = strtoupper(str_replace('file-', '', $endpoint));
                $fileConstant   = constant($fileName . '_FILE');

                if (!array_key_exists('contents', $payload)) {
                    apiResponse(400, ['error' => 'Missing ' . $fileName . ' object data']);
                }

                setFile($fileConstant, $payload['contents']);
                return $fileConstant . ' contents updated';
            case 'notification/test':
            case 'notify-test':
                $testNotification = $notifications->sendTestNotification($payload['linkId'], $payload['name']);

                if ($testNotification['code'] != 200) {
                    $result = 'Test notification failed: ' . $testNotification['result'];
                    apiResponse($testNotification['code'], ['error' => $result]);
                }

                return ['code' => $testNotification['code'], 'result' => $result];
            case 'server/log/delete':
            case 'server-deleteLog';
                if (!$payload['log']) {
                    apiResponse(400, ['error' => 'Missing log parameter']);
                }

                return deleteLog($payload['log']);
            case 'server/log/purge':
            case 'server-purgeLogs':
                if (!$payload['group']) {
                    apiResponse(400, ['error' => 'Missing group parameter']);
                }

                return purgeLogs($payload['group']);
            case 'server/task/run':
            case 'server-runTask':
                if (!$payload['task']) {
                    apiResponse(400, ['error' => 'Missing task parameter']);
                }

                return executeTask($payload['task']);
            default:
                apiResponse(405, ['error' => 'Invalid POST request (endpoint=' . $endpoint . ')']);
                break;
        }
    }
}

function apiRequestServerPings()
{
    global $database;

    $database ??= new Database();
    $serversTable = $database->getServers();

    $servers = [];
    foreach ($serversTable as $server) {
        if ($server['id'] == APP_SERVER_ID) {
            $servers[strtolower($server['name'])] = ['id' => $server['id'], 'name' => $server['name'] . ' [' . gitVersion(true) . ']', 'code' => 200];
        } else {
            apiSetActiveServer($server['id'], $serversTable);

            $apiRequest = apiRequest('server-ping');
            $servers[strtolower($server['name'])] = ['id' => $server['id'], 'name' => $server['name'] . ($apiRequest['result'] ? ' [' . $apiRequest['result'] . ']' : ''), 'url' => $server['url'], 'code' => intval($apiRequest['code'])];
        }
    }
    ksort($servers);

    apiSetActiveServer(APP_SERVER_ID, $serversTable);

    return $servers;
}
