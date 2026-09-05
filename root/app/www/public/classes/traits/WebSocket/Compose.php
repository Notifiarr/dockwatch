<?php

/*
----------------------------------
 ------  Created: 090426   ------
 ------  nzxl	           ------
----------------------------------
*/

use Ratchet\ConnectionInterface;

trait WebSocketCompose
{
    /**
     * Execute a long-running docker compose command and stream its output to the client.
     */
    protected function runCompose(ConnectionInterface $client, $path, $action)
    {
        //-- ONLY THE COMPOSE CHANNEL MAY EXECUTE COMPOSE COMMANDS
        if (!isset($this->channels[$client->resourceId]) || $this->channels[$client->resourceId]['type'] !== WebSocketActions::TYPE_COMPOSE) {
            $this->sendText($client, ['type' => 'error', 'message' => 'Compose is not available on this connection']);
            return;
        }

        if (!is_string($path) || !preg_match('/^[A-Za-z0-9_\-.]+$/', $path)) {
            $this->sendText($client, ['type' => 'error', 'message' => 'Invalid compose stack name']);
            return;
        }

        $dir = COMPOSE_PATH . $path;
        if (!is_dir($dir)) {
            $this->sendText($client, ['type' => 'error', 'message' => 'Compose stack not found: ' . $path]);
            return;
        }

        $cmd = [];
        switch ($action) {
            case 'composeUp':
                $cmd = DockerSock::COMPOSE_UP;
                break;
            case 'composeDown':
                $cmd = DockerSock::COMPOSE_DOWN;
                break;
            case 'composePull':
                $cmd = DockerSock::COMPOSE_PULL;
                break;
            case 'composeStop':
                $cmd = DockerSock::COMPOSE_STOP;
                break;
            case 'composeRestart':
                $cmd = DockerSock::COMPOSE_RESTART;
                break;
        }

        if (empty($cmd)) {
            $this->sendText($client, ['type' => 'error', 'message' => 'Unsupported compose action: ' . $action]);
            return;
        }

        $this->startProcessStream($client, sprintf($cmd, escapeshellarg($dir)));
    }

    /**
     * Generate a compose file from a running container (docker-autocompose).
     *
     * The raw command output is streamed to the client; once the process exits
     * the noise is filtered out and the clean YAML is sent as a 'compose-gen' frame.
     */
    protected function runComposeGenerate(ConnectionInterface $client, $containers)
    {
        //-- ONLY THE COMPOSE CHANNEL MAY EXECUTE COMPOSE COMMANDS
        if (!isset($this->channels[$client->resourceId]) || $this->channels[$client->resourceId]['type'] !== WebSocketActions::TYPE_COMPOSE) {
            $this->sendText($client, ['type' => 'error', 'message' => 'Compose is not available on this connection']);
            return;
        }

        if (!is_string($containers)) {
            $this->sendText($client, ['type' => 'error', 'message' => 'Invalid container name']);
            return;
        }

        $containers = preg_replace('/[^A-Za-z0-9 -_]/', '', $containers);
        if (empty(trim($containers))) {
            $this->sendText($client, ['type' => 'error', 'message' => 'At least one container is required']);
            return;
        }

        $cmd = sprintf(DockerSock::RUN, '--rm -v /var/run/docker.sock:/var/run/docker.sock ghcr.io/red5d/docker-autocompose ' . $containers);

        $this->startProcessStream($client, $cmd, function ($buffer) use ($client) {
            $compose = $this->filterAutoComposeOutput($buffer);

            if ($compose === '') {
                $this->sendText($client, ['type' => 'error', 'message' => 'docker-autocompose produced no output, check the container name(s)']);
                return;
            }

            $this->sendText($client, ['type' => 'compose-gen', 'compose' => $compose]);
        });
    }

    /**
     * Strip the docker-autocompose overhead (image pulls, version line) leaving clean YAML.
     */
    protected function filterAutoComposeOutput($raw)
    {
        $compose = [];
        $skip    = true; //-- IGNORE ALL THE IMAGE PULL NOISE

        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {

            //-- OBSOLETE VERSIONING LINE
            if (str_contains($line, 'version:')) {
                continue;
            }

            if (str_contains($line, 'networks:') || str_contains($line, 'services:')) {
                $skip = false;
            }

            if (!$skip && trim($line)) {
                $compose[] = $line;
            }
        }

        return implode("\n", $compose);
    }
}
