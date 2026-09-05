<?php

/*
----------------------------------
 ------  Created: 090426   ------
 ------  nzxl	           ------
----------------------------------
*/

trait WebSocketAuth
{
    private function extractTokenFromHandshake($httpRequest)
    {
        if (!$httpRequest) {
            return null;
        }

        $header = $httpRequest->getHeaderLine('Sec-WebSocket-Protocol');
        if ($header === '') {
            return null;
        }

        //-- CLIENT SENDS: "dockwatch-ws, <token>"
        $parts = array_map('trim', explode(',', $header));
        foreach ($parts as $part) {
            if ($part !== '' && $part !== self::SUBPROTOCOL) {
                return $part;
            }
        }

        return null;
    }

    private function verifyToken($scope, $id, $token)
    {
        if ($token === '') {
            return false;
        }

        $key    = sprintf(MEMCACHE_PREFIX . '%s-%s', $scope, $id);
        $stored = $this->memcached->get($key);

        if (!is_array($stored)) {
            return false;
        }

        foreach ($stored as $candidate) {
            if (is_string($candidate) && $candidate !== '' && hash_equals($candidate, $token)) {
                return true;
            }
        }

        return false;
    }

    private function revokeToken($scope, $id, $token)
    {
        $key    = sprintf(MEMCACHE_PREFIX . '%s-%s', $scope, $id);
        $stored = $this->memcached->get($key);

        if (!is_array($stored)) {
            return;
        }

        $updated = array_values(array_filter($stored, function ($candidate) use ($token) {
            return !(is_string($candidate) && hash_equals($candidate, $token));
        }));

        if ($updated === []) {
            $this->memcached->delete($key);
        } else {
            $this->memcached->set($key, $updated, MEMCACHE_WS_TOKEN_TIME);
        }
    }
}
