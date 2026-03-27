<?php

/*
----------------------------------
 ------  Created: 021626   ------
 ------       nzxl         ------
----------------------------------
*/

//-- BRING IN THE EXTRAS
loadClassExtras('Security');

class Security
{
    protected $shell;
    protected $docker;
    protected $memcache;

    public function __construct()
    {
        global $shell, $docker;
        $this->shell  = $shell ?? new Shell();
        $this->docker = $docker ?? new Docker();

        $this->memcache = $memcache ?? new Memcached();
        $this->memcache->addServer(MEMCACHE_HOST, MEMCACHE_PORT);
    }

    /**
     * Scan image for vulnerabilities
     * @param mixed Image (hash or full tag)
     * @return bool|string|null
     */
    public function scanImage($image, $scanner = SecurityScanner::GRYPE_SCAN_ID, $snykApiKey)
    {
        //-- BUST CACHE
        $this->memcache->delete(sprintf(MEMCACHE_SECURITY_VULNS_KEY, $image));
        $this->memcache->delete(sprintf(MEMCACHE_SECURITY_VULNS_COUNT_KEY, $image));
        $this->memcache->delete(sprintf(MEMCACHE_SECURITY_SCAN_HISTORY_COUNT_KEY, $image));
        $this->memcache->delete(sprintf(MEMCACHE_SECURITY_SCAN_HISTORY_KEY, $image));
        $this->memcache->delete(sprintf(MEMCACHE_SECURITY_NEW_VULNS_KEY, $image));

        $cmd        = [];
        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        createDirectoryTree(SECURITY_PATH . $hashPrefix);

        switch ($scanner) {
            case SecurityScanner::TRIVY_ID:
                $cmd = sprintf(
                    SecurityScanner::TRIVY_SCAN_IMAGE,
                    SECURITY_PATH,
                    SECURITY_PATH,
                    $this->shell->prepare('"' . SECURITY_PATH . $hashPrefix . '/result_' . time() . '.json"'),
                    $this->shell->prepare($image),
                );
                break;
            case SecurityScanner::GRYPE_ID:
                $cmd = sprintf(
                    SecurityScanner::GRYPE_SCAN_IMAGE,
                    SECURITY_PATH,
                    $this->shell->prepare('"' . SECURITY_PATH . $hashPrefix . '/result_' . time() . '.json"'),
                    $this->shell->prepare($image),
                );
                break;
            case SecurityScanner::SNYK_ID:
                $cmd = sprintf(
                    SecurityScanner::SNYK_SCAN_IMAGE,
                    SECURITY_PATH,
                    $this->shell->prepare($snykApiKey),
                    $this->shell->prepare(SECURITY_PATH . $hashPrefix . '/result_' . time() . '.json'),
                    $this->shell->prepare($image),
                );
                break;
        }

        $shell = $this->shell->exec($cmd . ' 2>&1');
        return $shell;
    }

    /**
     * Get image vulnerabilities from most recent scan file
     * @param mixed Image (hash or full tag)
     * @param string|null $file Specific scan file to read
     * @return array|null
     */
    public function getVulns($image, $file = null)
    {
        $cache = $this->memcache->get(sprintf(MEMCACHE_SECURITY_VULNS_KEY, $file ?: $image));
        if (!empty($cache)) {
            return $cache;
        }

        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        $imagePath  = SECURITY_PATH . $hashPrefix;

        if (!is_dir($imagePath)) {
            return [];
        }

        $resultFiles = glob($imagePath . '/result_*.json');
        if (empty($resultFiles)) {
            return [];
        }

        usort($resultFiles, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        if ($file) {
            $scanFile = $imagePath . '/' . $file;
            if (!file_exists($scanFile)) {
                return [];
            }
        } else {
            $scanFile = $resultFiles[0];
        }

        $scanData    = json_decode(file_get_contents($scanFile), true);
        $parsedVulns = $this->parseVulns($scanData);

        if (empty($parsedVulns)) {
            return [];
        }

        $this->memcache->set(sprintf(MEMCACHE_SECURITY_VULNS_KEY, $file ?: $image), $parsedVulns, MEMCACHE_SECURITY_VULNS_TIME);
        return $parsedVulns;
    }

    public function getVulnCounts($image)
    {
        $cache = $this->memcache->get(sprintf(MEMCACHE_SECURITY_VULNS_COUNT_KEY, $image));
        if (!empty($cache)) {
            return $cache;
        }

        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        $imagePath  = SECURITY_PATH . $hashPrefix;

        $counts = [
            'critical' => 0,
            'high'     => 0,
            'medium'   => 0,
            'low'      => 0,
            'unknown'  => 0,
            'lastScan' => null,
        ];

        if (!is_dir($imagePath)) {
            return $counts;
        }

        $resultFiles = glob($imagePath . '/result_*.json');
        if (empty($resultFiles)) {
            return $counts;
        }

        usort($resultFiles, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $counts['lastScan'] = filemtime($resultFiles[0]);

        $latestScan  = $resultFiles[0];
        $scanData    = json_decode(file_get_contents($latestScan), true);
        $parsedVulns = $this->parseVulns($scanData);

        if (empty($parsedVulns)) {
            return $counts;
        }

        foreach ($parsedVulns as $vuln) {
            $severity = strtolower($vuln['severity'] ?? 'unknown');
            if (isset($counts[$severity])) {
                $counts[$severity]++;
            } else {
                $counts['unknown']++;
            }
        }

        $this->memcache->set(sprintf(MEMCACHE_SECURITY_VULNS_COUNT_KEY, $image), $counts, MEMCACHE_SECURITY_VULNS_COUNT_TIME);
        return $counts;
    }

    public function getScanHistoryCount($image)
    {
        $cache = $this->memcache->get(sprintf(MEMCACHE_SECURITY_SCAN_HISTORY_COUNT_KEY, $image));
        if (!empty($cache)) {
            return $cache;
        }

        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        $imagePath  = SECURITY_PATH . $hashPrefix;

        if (!is_dir($imagePath)) {
            return 0;
        }

        $resultFiles = glob($imagePath . '/result_*.json');

        $this->memcache->set(sprintf(MEMCACHE_SECURITY_SCAN_HISTORY_COUNT_KEY, $image), count($resultFiles), MEMCACHE_SECURITY_SCAN_HISTORY_COUNT_TIME);
        return count($resultFiles);
    }

    public function getScanHistory($image)
    {
        $cache = $this->memcache->get(sprintf(MEMCACHE_SECURITY_SCAN_HISTORY_KEY, $image));
        if (!empty($cache)) {
            return $cache;
        }

        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        $imagePath  = SECURITY_PATH . $hashPrefix;

        if (!is_dir($imagePath)) {
            return [];
        }

        $resultFiles = glob($imagePath . '/result_*.json');
        if (empty($resultFiles)) {
            return [];
        }

        usort($resultFiles, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $history = [];
        foreach ($resultFiles as $file) {
            $scanData    = json_decode(file_get_contents($file), true);
            $parsedVulns = $this->parseVulns($scanData);
            $counts      = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'unknown' => 0];

            if (!empty($parsedVulns)) {
                foreach ($parsedVulns as $vuln) {
                    $severity = strtolower($vuln['severity'] ?? 'unknown');
                    if (isset($counts[$severity])) {
                        $counts[$severity]++;
                    } else {
                        $counts['unknown']++;
                    }
                }
            }

            $history[] = [
                'file'      => basename($file),
                'timestamp' => filemtime($file),
                'date'      => date('Y-m-d H:i:s', filemtime($file)),
                'counts'    => $counts,
            ];
        }

        $this->memcache->set(sprintf(MEMCACHE_SECURITY_SCAN_HISTORY_KEY, $image), $history, MEMCACHE_SECURITY_SCAN_HISTORY_TIME);
        return $history;
    }

    /**
     * Parse vulnerabilities from scan data
     * @param array $scanData
     * @return array
     */
    private function parseVulns($scanData)
    {
        $vulns = [];

        //-- TRIVY SCAN
        if (!empty($scanData['Results'])) {
            foreach ($scanData['Results'] as $result) {
                if (!empty($result['Vulnerabilities'])) {
                    foreach ($result['Vulnerabilities'] as $vuln) {
                        $pkgName = $vuln['PkgName'] ?: ($vuln['PkgID'] ?? '');
                        $vulns[] = [
                            'id'        => $vuln['VulnerabilityID'],
                            'source'    => "https://nvd.nist.gov/vuln/detail/" . $vuln['VulnerabilityID'],
                            'pkg'       => $pkgName,
                            'installed' => $vuln['InstalledVersion'],
                            'fixed'     => $vuln['FixedVersion'],
                            'status'    => $vuln['Status'],
                            'severity'  => $vuln['Severity'],
                            'title'     => $vuln['Title'] ?: $pkgName,
                            'published' => $vuln['PublishedDate'] ?? ''
                        ];
                    }
                }
            }
        }

        //-- GRYPE SCAN
        if (!empty($scanData['matches'])) {
            foreach ($scanData['matches'] as $result) {
                if (!empty($result['vulnerability'])) {
                    $vuln     = $result['vulnerability'];
                    $artifact = $result['artifact'] ?? [];
                    $pkgName  = $artifact['name'] ?? 'unknown';
                    $vulns[]  = [
                        'id'        => $vuln['id'],
                        'source'    => $vuln['dataSource'],
                        'pkg'       => $pkgName,
                        'installed' => $artifact['version'] ?? 'unknown',
                        'fixed'     => $vuln['fix']['versions'][0] ?? null,
                        'status'    => $vuln['fix']['state'] ?? 'unknown',
                        'severity'  => $vuln['severity'],
                        'title'     => truncateEnd($vuln['description'], 80) ?: $pkgName,
                        'published' => $vuln['epss'][0]['date'] ?? ''
                    ];
                }
            }
        }

        //-- SNYK SCAN - Root level vulnerabilities
        if (!empty($scanData['vulnerabilities'])) {
            foreach ($scanData['vulnerabilities'] as $vuln) {
                $pkgName = $vuln['packageName'] ?? 'unknown';
                $vulns[] = [
                    'id'        => $vuln['id'],
                    'source'    => str_starts_with($vuln['id'], 'snyk:lic:') ? 'https://snyk.io/security' : 'https://snyk.io/vuln/' . $vuln['id'],
                    'pkg'       => $pkgName,
                    'installed' => $vuln['version'] ?? 'unknown',
                    'fixed'     => $vuln['fixedIn'][0] ?? null,
                    'status'    => !empty($vuln['fixedIn']) ? 'fixed' : 'unknown',
                    'severity'  => ucfirst($vuln['severity'] ?? 'unknown'),
                    'title'     => $vuln['title'] ?: $pkgName,
                    'published' => $vuln['publicationTime'] ?? ''
                ];
            }
        }

        //-- SNYK SCAN - Application level vulnerabilities
        if (!empty($scanData['applications'])) {
            foreach ($scanData['applications'] as $app) {
                if (!empty($app['vulnerabilities'])) {
                    foreach ($app['vulnerabilities'] as $vuln) {
                        $pkgName = $vuln['packageName'] ?? 'unknown';
                        $vulns[] = [
                            'id'        => $vuln['id'],
                            'source'    => str_starts_with($vuln['id'], 'snyk:lic:') ? 'https://snyk.io/security' : 'https://snyk.io/vuln/' . $vuln['id'],
                            'pkg'       => $pkgName,
                            'installed' => $vuln['version'] ?? 'unknown',
                            'fixed'     => $vuln['fixedIn'][0] ?? null,
                            'status'    => !empty($vuln['fixedIn']) ? 'fixed' : 'unknown',
                            'severity'  => ucfirst($vuln['severity'] ?? 'unknown'),
                            'title'     => $vuln['title'] ?: $pkgName,
                            'published' => $vuln['publicationTime'] ?? ''
                        ];
                    }
                }
            }
        }

        return $vulns;
    }

    /**
     * Get new or updated vulnerabilities by comparing most recent scan with previous scan
     * @param mixed Image (hash or full tag)
     * @return array Array of new/updated vulnerabilities with 'type' => 'new'|'updated'
     */
    public function getNewVulns($image)
    {
        $cache = $this->memcache->get(sprintf(MEMCACHE_SECURITY_NEW_VULNS_KEY, $image));
        if (!empty($cache)) {
            return $cache;
        }

        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        $imagePath  = SECURITY_PATH . $hashPrefix;

        if (!is_dir($imagePath)) {
            return [];
        }

        $resultFiles = glob($imagePath . '/result_*.json');
        if (empty($resultFiles)) {
            return [];
        }

        usort($resultFiles, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $latestScan  = json_decode(file_get_contents($resultFiles[0]), true);
        $latestVulns = $this->parseVulns($latestScan);

        if (count($resultFiles) < 2) {
            $newVulns = [];
            foreach ($latestVulns as $vuln) {
                $vuln['changeType'] = 'new';
                $newVulns[]         = $vuln;
            }
            return $newVulns;
        }

        $previousScan  = json_decode(file_get_contents($resultFiles[1]), true);
        $previousVulns = $this->parseVulns($previousScan);

        $previousMap = [];
        foreach ($previousVulns as $vuln) {
            $previousMap[$vuln['id']] = $vuln;
        }

        $newVulns = [];
        foreach ($latestVulns as $vuln) {
            if (!isset($previousMap[$vuln['id']])) {
                $vuln['changeType'] = 'new';
                $newVulns[]         = $vuln;
            } elseif ($vuln['status'] !== $previousMap[$vuln['id']]['status']) {
                $vuln['changeType']     = 'updated';
                $vuln['previousStatus'] = $previousMap[$vuln['id']]['status'];
                $newVulns[]             = $vuln;
            }
        }

        $this->memcache->set(sprintf(MEMCACHE_SECURITY_NEW_VULNS_KEY, $image), $newVulns, MEMCACHE_SECURITY_NEW_VULNS_TIME);
        return $newVulns;
    }

    public function __toString()
    {
        return 'Class loaded: Security';
    }
}
