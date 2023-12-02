<?PHP

/*
----------------------------------
 ------  Created: 052521   ------
 ------  Austin Best	   ------
----------------------------------
*/

function apiResponse($code, $response)
{
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

function apiRequest($request, $params = [], $payload = [])
{
    global $serverOverride;

    $serverUrl      = is_array($serverOverride) && $serverOverride['url'] ? $serverOverride['url'] : ACTIVE_SERVER_URL;
    $serverName     = is_array($serverOverride) && $serverOverride['name'] ? $serverOverride['name'] : ACTIVE_SERVER_NAME;
    $serverApikey   = is_array($serverOverride) && $serverOverride['apikey'] ? $serverOverride['apikey'] : ACTIVE_SERVER_APIKEY;

    logger(SYSTEM_LOG, 'apiRequest() ' . $request . ' for server ' . $serverName);

    if ($payload) {
        $params = http_build_query($params);

        if (!$payload['request']) {
            $payload['request'] = $request;
        }

        $curl = curl($serverUrl . '/api/' . ($params ? '?' . $params : ''), ['x-api-key: ' . $serverApikey], 'POST', json_encode($payload));
    } else {
        $params['request'] = $request;
        $builtParams = http_build_query($params);

        $curl = curl($serverUrl . '/api/' . ($builtParams ? '?' . $builtParams : ''), ['x-api-key: ' . $serverApikey]);
    }

    return $curl['response'];
}