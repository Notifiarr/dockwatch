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
            'updated'     => ['event' => 'updates', 'updated' => [['container' => APP_NAME, 'image' => APP_IMAGE, 'pre' => ['version' => '0.0.0', 'digest' => '1ede6888ea9ca58217f6100218548b9b5d0720390452d5be6d3f83ac2449956c'], 'post' => ['version' => '0.0.1', 'digest' => '1ede6888ea9ca58217f6100218548b9b5d0720390452d5be6d3f83ac2449956c']]]],
            'updates'     => ['event' => 'updates', 'available' => [['container' => APP_NAME]]],
            'stateChange' => ['event' => 'state', 'changes' => [['container' => APP_NAME, 'previous' => 'Exited', 'current' => 'Running']]],
            'added'       => ['event' => 'state', 'added' => [['container' => APP_NAME]]],
            'removed'     => ['event' => 'state', 'removed' => [['container' => APP_NAME]]],
            'prune'       => ['event' => 'prune', 'network' => [strtolower(APP_NAME) . '_default'], 'volume' => ['1ede6888ea9ca58217f6100218548b9b5d0720390452d5be6d3f83ac2449956c'], 'image' => ['9ae98bd7cfec', '4f12ad575d3d'], 'imageList' => [['cr' => 'ghcr.io/notifiarr/dockwatch:main', 'created' => time(), 'size' => '400000']]],
            'cpuHigh'     => ['event' => 'usage', 'cpu' => [['container' => APP_NAME, 'usage' => '12.34']], 'cpuThreshold' => '10'],
            'memHigh'     => ['event' => 'usage', 'mem' => [['container' => APP_NAME, 'usage' => '12.34']], 'memThreshold' => '10'],
            'health'      => ['event' => 'health', 'container' => APP_NAME, 'restarted' => time()],
            'test'        => ['event' => 'test', 'title' => APP_NAME . ' test', 'message' => 'This is a test message sent from ' . APP_NAME],
            'security'    => ['event' => 'security', 'container' => APP_NAME, 'image' => APP_IMAGE, 'count' => 2, 'vulns' => [["id" => "CVE-2026-1229", "pkg" => "github.com/cloudflare/circl", "installed" => "v1.6.1", "fixed" => "1.6.3", "status" => "fixed", "severity" => "LOW", "title" => "CIRCL has an incorrect calculation in secp384r1 CombinedMult", "published" => "2026-02-24T08:16:28.407Z", "changeType" => "new"], ["id" => "CVE-2026-24051", "pkg" => "go.opentelemetry.io/otel/sdk", "installed" => "v1.38.0", "fixed" => "1.40.0", "status" => "fixed", "severity" => "HIGH", "title" => "OpenTelemetry Go SDK Vulnerable to Arbitrary Code Execution via PATH Hijacking", "published" => "2026-02-02T23:16:07.963Z", "changeType" => "updated"]]]
        ];
    }
}
