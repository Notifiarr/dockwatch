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
    logger(SYSTEM_LOG, 'apiRequest() ' . $request . ' for server ' . ACTIVE_SERVER_NAME);

    if ($payload) {
        $params = http_build_query($params);

        if (!$payload['request']) {
            $payload['request'] = $request;
        }

        $curl = curl(ACTIVE_SERVER_URL . '/api/' . ($params ? '?' . $params : ''), ['x-api-key: ' . ACTIVE_SERVER_APIKEY], 'POST', json_encode($payload));
    } else {
        $params['request'] = $request;
        $builtParams = http_build_query($params);

        $curl = curl(ACTIVE_SERVER_URL . '/api/' . ($builtParams ? '?' . $builtParams : ''), ['x-api-key: ' . ACTIVE_SERVER_APIKEY]);
    }

    return $curl['response'];
}