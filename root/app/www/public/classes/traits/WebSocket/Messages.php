<?php

/*
----------------------------------
 ------  Created: 090426   ------
 ------  nzxl	           ------
----------------------------------
*/

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

trait WebSocketMessages
{
    public function onMessage(ConnectionInterface $from, MessageInterface $msg)
    {
        $isBinary = $msg->isBinary();
        $payload  = $msg->getPayload();

        if ($payload === '') {
            return;
        }

        //-- BINARY FRAME = RAW SHELL TERMINAL INPUT
        if ($isBinary) {
            $this->writeToContainer($from, $payload);
            return;
        }

        //-- TEXT FRAME = JSON CONTROL COMMAND
        $data = json_decode($payload, true);
        if (!is_array($data)) {
            $this->writeToContainer($from, $payload);
            return;
        }

        //-- ACTIONS THAT DO NOT REQUIRE A CONTAINER SHELL SESSION
        switch ($data['action'] ?? '') {
            case WebSocketActions::ACTION_COMPOSE:
                if (($data['composeAction'] ?? '') === 'composeGenerate') {
                    $this->runComposeGenerate($from, $data['container'] ?? '');
                } else {
                    $this->runCompose($from, $data['path'] ?? '', $data['composeAction'] ?? '');
                }
                return;
        }

        //-- SHELL-ONLY ACTIONS BELOW (RESIZE, RAW INPUT, ...) REQUIRE AN ACTIVE SESSION
        $session = $this->container_sessions[$from->resourceId] ?? null;

        //-- REPORT WHEN THERE IS NO ACTIVE SESSION YET
        if (!$session || $session['closed']) {
            $this->sendText($from, ['error' => 'Not connected to any container']);
            return;
        }

        switch ($data['action'] ?? '') {
            case WebSocketActions::ACTION_RESIZE:
                $this->resizeTerminal($from, $data['cols'] ?? 0, $data['rows'] ?? 0);
                break;
            default:
                //-- FALLBACK: IF A COMMAND STRING IS PROVIDED, WRITE IT AS RAW INPUT
                if (isset($data['command'])) {
                    $this->writeToContainer($from, $data['command']);
                }
        }
    }
}
