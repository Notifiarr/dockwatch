<?php

/*
----------------------------------
 ------  Created: 090426   ------
 ------  nzxl	           ------
----------------------------------
*/

trait WebSocketDockerSocket
{
    private function dockerSocketString()
    {
        if (!empty($_SERVER['DOCKER_HOST'])) {
            $host = $_SERVER['DOCKER_HOST'];
            if (str_contains($host, 'tcp://')) {
                return str_replace('tcp://', 'tcp://', $host);
            }
            if (str_contains($host, 'http://')) {
                $host = substr($host, 7);
                return 'tcp://' . $host;
            }
            return $host;
        }

        return 'unix:///var/run/docker.sock';
    }

    /**
     * Perform a request/response call against the docker engine API.
     */
    private function dockerRequest($method, $path, array $payload = null, $timeout = 5.0)
    {
        $sock = stream_socket_client(
            $this->dockerSocketString(),
            $errno,
            $errstr,
            $timeout
        );

        if (!$sock) {
            logger(WEBSOCKET_LOG, 'dockerRequest connect failed: ' . $errstr, 'error');
            return null;
        }

        $body = $payload === null ? '' : json_encode($payload, JSON_UNESCAPED_SLASHES);

        $headers = [
            "$method $path HTTP/1.1",
            'Host: docker',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
            'Connection: close',
        ];
        $request = implode("\r\n", $headers) . "\r\n\r\n" . $body;

        fwrite($sock, $request);
        stream_set_timeout($sock, $timeout);

        $response = '';
        while (!feof($sock)) {
            $chunk = fread($sock, 8192);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $response .= $chunk;
        }
        fclose($sock);

        if ($response === '') {
            return null;
        }

        $split = explode("\r\n\r\n", $response, 2);
        $raw   = $split[1] ?? '';
        $head  = $split[0] ?? '';

        preg_match('#HTTP/\d\.\d (\d{3})#', $head, $m);
        $status = (int) ($m[1] ?? 0);

        return [
            'status' => $status,
            'body'   => $raw,
        ];
    }

    private function dockerExecCreate($containerId, array $cmd, array $ttySize = null)
    {
        $payload = [
            'AttachStdin'  => true,
            'AttachStdout' => true,
            'AttachStderr' => true,
            'Tty'          => true,
            'Cmd'          => $cmd,
            'Env'          => ['TERM=xterm-256color'],
        ];

        if ($ttySize && isset($ttySize['cols'], $ttySize['rows'])) {
            $payload['ConsoleSize'] = [
                'Height' => (int) $ttySize['rows'],
                'Width'  => (int) $ttySize['cols'],
            ];
        }

        $res = $this->dockerRequest('POST', '/containers/' . urlencode($containerId) . '/exec', $payload);
        if ($res === null || $res['status'] !== 201) {
            return null;
        }

        $json = json_decode($res['body'], true);
        return $json['Id'] ?? null;
    }

    /**
     * Open a persistent stream to the docker exec TTY.
     * Returns ['sock'=>resource,'status'=>int] positioned at the body start.
     */
    private function dockerExecStart($execId)
    {
        $sock = stream_socket_client($this->dockerSocketString(), $errno, $errstr, 5.0);
        if (!$sock) {
            logger(WEBSOCKET_LOG, 'dockerExecStart connect failed: ' . $errstr, 'error');
            return null;
        }

        $payload = [
            'Detach' => false,
            'Tty'    => true,
        ];
        $body    = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $headers = [
            "POST /exec/$execId/start HTTP/1.1",
            'Host: docker',
            'Upgrade: tcp',
            'Connection: Upgrade',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
        ];
        $request = implode("\r\n", $headers) . "\r\n\r\n" . $body;

        fwrite($sock, $request);

        //-- READ THE RESPONSE HEADERS ONLY
        $headerData = '';
        while (!str_contains($headerData, "\r\n\r\n")) {
            $chunk = fread($sock, 1);
            if ($chunk === false || $chunk === '') {
                fclose($sock);
                return null;
            }
            $headerData .= $chunk;
            if (strlen($headerData) > 65536) {
                fclose($sock);
                return null;
            }
        }

        if (!preg_match('#HTTP/\d\.\d (\d{3})#', $headerData, $m)) {
            fclose($sock);
            return null;
        }

        //-- 101 = HIJACKED/UPGRADED CONNECTION (FULL-DUPLEX, WORKS THROUGH PROXIES);
        //-- 200 = DAEMON-ONLY PATH WITHOUT A PROXY UPGRADE, STILL A RAW TTY STREAM.
        $status = (int) $m[1];
        if ($status !== 101 && $status !== 200) {
            fclose($sock);
            return null;
        }

        logger(WEBSOCKET_LOG, 'docker exec TTY stream ' . ($status === 101 ? 'upgraded (101)' : 'started (200, no upgrade)') . ' for exec ' . $execId);

        stream_set_blocking($sock, false);

        return [
            'sock'   => $sock,
            'status' => $status,
        ];
    }

    private function dockerExecResize($execId, $cols, $rows)
    {
        $query = http_build_query([
            'h' => (int) $rows,
            'w' => (int) $cols,
        ]);
        $this->dockerRequest('POST', '/exec/' . $execId . '/resize?' . $query);
    }
}
