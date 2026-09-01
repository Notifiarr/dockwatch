<?php

/*
----------------------------------
 ------  Created: 070626   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getIntrusionIp()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

        return trim($parts[0]);
    }

    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function getIntrusionRequest()
{
    return [
        'time'      => date('c'),
        'ip'        => getIntrusionIp(),
        'referrer'  => $_SERVER['HTTP_REFERER'] ?? '',
        'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'method'    => $_SERVER['REQUEST_METHOD'] ?? '',
        'uri'       => $_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? ''),
    ];
}

function getWebSocketIntrusionRequest($conn, array $queryParams = [])
{
    $ip = $conn->remoteAddress ?? '';

    if (str_contains($ip, '://')) {
        $ip = parse_url($ip, PHP_URL_HOST) ?: $ip;
    }

    if (str_contains($ip, ':')) {
        $ip = explode(':', $ip)[0];
    }

    $httpRequest = $conn->httpRequest ?? null;
    $uri         = '';

    if ($httpRequest) {
        $uri = $httpRequest->getUri()->getPath();

        if ($httpRequest->getUri()->getQuery()) {
            $uri .= '?' . $httpRequest->getUri()->getQuery();
        }
    }

    return [
        'time'      => date('c'),
        'ip'        => $ip,
        'referrer'  => $httpRequest ? $httpRequest->getHeaderLine('Referer') : '',
        'userAgent' => $httpRequest ? $httpRequest->getHeaderLine('User-Agent') : '',
        'method'    => 'GET',
        'uri'       => $uri,
    ];
}

function buildIntrusionEntry($type, $details = [])
{
    $entryFields = ['time', 'ip', 'referrer', 'userAgent', 'method', 'uri', 'username', 'apikey', 'container', 'token'];
    $merged      = $details;

    if (!isset($merged['ip'])) {
        $merged = array_merge(getIntrusionRequest(), $merged);
    }

    $entry = ['type' => $type];

    foreach ($entryFields as $field) {
        $entry[$field] = $merged[$field] ?? '';
    }

    $extras = [];

    foreach ($merged as $key => $val) {
        if (!in_array($key, $entryFields) && $key !== 'type') {
            $extras[$key] = $val;
        }
    }

    $entry['details'] = $extras ? json_encode($extras) : '';

    return $entry;
}

function formatIntrusionLogMessage($entry)
{
    $message = $entry['type'] ?? 'unknown';
    $fields  = ['ip', 'method', 'uri', 'username', 'apikey', 'container', 'token', 'referrer', 'userAgent', 'details'];

    foreach ($fields as $field) {
        if (!empty($entry[$field])) {
            $message .= ' ' . $field . '=' . $entry[$field];
        }
    }

    return $message;
}

function recordIntrusion($type, $details = [])
{
    global $database;

    $entry = buildIntrusionEntry($type, $details);
    logger(INTRUSION_LOG, formatIntrusionLogMessage($entry));

    if ($database && !IS_MAINTENANCE) {
        if (!$database->addIntrusionHistory($entry)) {
            logger(SYSTEM_LOG, 'Failed to write intrusion history for type: ' . ($entry['type'] ?? ''), 'error');
        }
    }

    notifyIntrusion($entry);
}

function notifyIntrusion($entry)
{
    global $database, $notifications;

    if (!$database || IS_MAINTENANCE) {
        return;
    }

    if (!$database->isNotificationTriggerEnabled('intrusion')) {
        return;
    }

    $settingsTable = $database->getSettings() ?: [];
    if (!empty($settingsTable['loginWhitelist'])) {
        $ipandmask = explode(',', $settingsTable['loginWhitelist']) ?: [];
        if (count($ipandmask) > 0) {
            foreach ($ipandmask as $ipmask) {
                $ipmask = trim($ipmask);

                if (ipMatchesSubnet($entry['ip'], $ipmask)) {
                    return;
                }
            }
        }
    }

    $notifications ??= new Notifications();

    $payload = [
        'event'     => 'intrusion',
        'type'      => $entry['type'] ?? '',
        'time'      => $entry['time'] ?? '',
        'ip'        => $entry['ip'] ?? '',
        'method'    => $entry['method'] ?? '',
        'uri'       => $entry['uri'] ?? '',
        'referrer'  => $entry['referrer'] ?? '',
        'userAgent' => $entry['userAgent'] ?? '',
        'username'  => $entry['username'] ?? '',
        'apikey'    => $entry['apikey'] ?? '',
        'container' => $entry['container'] ?? '',
        'token'     => $entry['token'] ?? '',
        'details'   => $entry['details'] ?? '',
    ];

    $notifications->notify(0, 'intrusion', $payload);
}
