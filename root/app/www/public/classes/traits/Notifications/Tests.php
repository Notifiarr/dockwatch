<?php

/*
----------------------------------
 ------  Created: 092024   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait NotificationTests
{
    public function getTestPayloads()
    {
        return [
                'updated'       => ['event' => 'updates', 'updated' => [['container' => APP_NAME, 'image' => APP_IMAGE, 'pre' => '0.0.0', 'post' => '0.0.1']]],
                'updates'       => ['event' => 'updates', 'available' => [['container' => APP_NAME]]],
                'stateChange'   => ['event' => 'state', 'changes' => [['container' => APP_NAME, 'previous' => 'Exited', 'current' => 'Running']]],
                'added'         => ['event' => 'state', 'added' => [['container' => APP_NAME]]],
                'removed'       => ['event' => 'state', 'removed' => [['container' => APP_NAME]]],
                'prune'         => ['event' => 'prune', 'network' => [strtolower(APP_NAME) . '_default'], 'volume' => ['1ede6888ea9ca58217f6100218548b9b5d0720390452d5be6d3f83ac2449956c'], 'image' => ['9ae98bd7cfec', '4f12ad575d3d'], 'imageList' => [['cr' => 'ghcr.io/notifiarr/dockwatch:main', 'created' => time(), 'size' => '400000']]],
                'cpuHigh'       => ['event' => 'usage', 'cpu' => [['container' => APP_NAME, 'usage' => '12.34']], 'cpuThreshold' => '10'],
                'memHigh'       => ['event' => 'usage', 'mem' => [['container' => APP_NAME, 'usage' => '12.34']], 'memThreshold' => '10'],
                'health'        => ['event' => 'health', 'container' => APP_NAME, 'restarted' => time()],
                'test'          => ['event' => 'test', 'title' => APP_NAME . ' test', 'message' => 'This is a test message sent from ' . APP_NAME]
            ];
    }
}
