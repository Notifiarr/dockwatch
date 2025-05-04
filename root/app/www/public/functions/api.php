<?PHP

/*
----------------------------------
 ------  Created: 052524   ------
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

function apiRequestRemote($endpoint, $parameters = [], $payload = [], $activeServer = [])
{
    if (empty($activeServer)) {
        $activeServer = apiGetActiveServer();
    }
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
        if ($endpoint != 'server/ping') {
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

    //-- FORMAT RESPONSE BANDAGE (WILL FIX LATER)
    if (str_equals_any($endpoint, ['stats/overview', 'stats/containers', 'stats/metrics'])) {
        $result['result']['result'] = $curl['response']['response'];
    }

    return $result;
}

function apiRequestLocal($endpoint, $parameters = [], $payload = [])
{
    global $database, $docker, $notifications;

    $IS_POST    = !empty($payload);
    $IS_GET     = !$IS_POST;

    if ($IS_POST) {
        unset($payload['request']);
    }

    //-- ...api/$route/$path/$action/$method/$field
    list($route, $path, $action, $method, $field) = explode('/', $endpoint);

    if (file_exists(ABSOLUTE_PATH . 'api/' . $route . '/endpoints.php')) {
        require ABSOLUTE_PATH . 'api/' . $route . '/endpoints.php';
        return $apiRequestResponse;
    }

    apiResponse(400, ['error' => 'Invalid route']);
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

            $apiRequest = apiRequest('server/ping');
            $servers[strtolower($server['name'])] = ['id' => $server['id'], 'name' => $server['name'] . ($apiRequest['result'] ? ' [' . $apiRequest['result'] . ']' : ''), 'url' => $server['url'], 'code' => intval($apiRequest['code'])];
        }
    }
    ksort($servers);

    apiSetActiveServer(APP_SERVER_ID, $serversTable);

    return $servers;
}
