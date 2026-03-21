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
            'security'    => ['event' => 'security', 'changed' => true, 'containers' => 1, 'critical' => 1, 'high' => 1, 'medium' => 1, 'low' => 1, 'unknown' => 1, 'details' => ["ghcr.io/notifiarr/dockwatch:develop" => [["id" => "CVE-2026-32767", "pkg" => "libexpat", "installed" => "2.7.4-r0", "fixed" => "2.7.5-r0", "status" => "fixed", "severity" => "CRITICAL", "title" => "SiYuan: Authorization Bypass Allows Arbitrary SQL Execution via Search API", "published" => "", "changeType" => "new"], ["id" => "CVE-2026-22184", "pkg" => "zlib", "installed" => "1.3.1-r2", "fixed" => "1.3.2-r0", "status" => "fixed", "severity" => "HIGH", "title" => "zlib: zlib: Arbitrary code execution via buffer overflow in untgz utility", "published" => "2026-01-07T21:16:01.563Z", "changeType" => "updated"], ["id" => "CVE-2026-27171", "pkg" => "zlib", "installed" => "1.3.1-r2", "fixed" => "1.3.2-r0", "status" => "fixed", "severity" => "MEDIUM", "title" => "zlib: zlib: Denial of Service via infinite loop in CRC32 combine functions", "published" => "2026-02-18T04:16:01.263Z", "changeType" => "updated"], ["id" => "CVE-2026-27138", "pkg" => "stdlib", "installed" => "v1.26.0", "fixed" => "1.26.1", "status" => "fixed", "severity" => "LOW", "title" => "crypto/x509: Panic in name constraint checking for malformed certificates in crypto/x509", "published" => "2026-03-06T22:16:00.963Z", "changeType" => "new"], ["id" => "CVE-2026-2219", "pkg" => "dpkg", "installed" => "1.22.21", "fixed" => "1.22.22", "status" => "fixed", "severity" => "UNKNOWN", "title" => "It was discovered that dpkg-deb (a component of dpkg, the Debian packa ...", "published" => "2026-03-07T09:16:07.823Z", "changeType" => "updated"]]]]
        ];
    }
}
