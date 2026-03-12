<?php

/*
----------------------------------
 ------  Created: 021626   ------
 ------       nzxl         ------
----------------------------------
*/

//-- BRING IN THE EXTRAS
loadClassExtras('Trivy');

class Trivy
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

    public function getDBSize()
    {
        createDirectoryTree(TRIVY_PATH);

        $dbPath = TRIVY_PATH . 'db/trivy.db';
        if (!file_exists($dbPath)) {
            return 0;
        }

        return filesize($dbPath) ?: 0;
    }

    public function getJavaDBSize()
    {
        createDirectoryTree(TRIVY_PATH);

        $dbPath = TRIVY_PATH . 'java-db/trivy-java.db';
        if (!file_exists($dbPath)) {
            return 0;
        }

        return filesize($dbPath) ?: 0;
    }

    public function downloadDB()
    {
        createDirectoryTree(TRIVY_PATH);

        $cmd   = sprintf(TrivyCLI::UPDATE_DB, TRIVY_PATH);
        $shell = $this->shell->exec($cmd . ' 2>&1');

        return $shell;
    }

    public function downloadJavaDB()
    {
        createDirectoryTree(TRIVY_PATH);

        $cmd   = sprintf(TrivyCLI::UPDATE_DB_JAVA, TRIVY_PATH);
        $shell = $this->shell->exec($cmd . ' 2>&1');

        return $shell;
    }

    /**
     * Scan image for vulnerabilities
     * @param mixed Image (hash or full tag)
     * @return bool|string|null
     */
    public function scanImage($image)
    {
        //-- BUST CACHE
        $this->memcache->delete(sprintf(MEMCACHE_TRIVY_VULNS_KEY, $image));
        $this->memcache->delete(sprintf(MEMCACHE_TRIVY_VULNS_COUNT_KEY, $image));
        $this->memcache->delete(sprintf(MEMCACHE_TRIVY_SCAN_HISTORY_COUNT_KEY, $image));
        $this->memcache->delete(sprintf(MEMCACHE_TRIVY_SCAN_HISTORY_KEY, $image));
        $this->memcache->delete(sprintf(MEMCACHE_TRIVY_NEW_VULNS_KEY, $image));

        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        createDirectoryTree(TRIVY_PATH . $hashPrefix);

        $cmd   = sprintf(
            TrivyCLI::SCAN_IMAGE,
            TRIVY_PATH,
            TRIVY_PATH,
            $this->shell->prepare('"' . TRIVY_PATH . $hashPrefix . '/result_' . time() . '.json"'),
            $this->shell->prepare($image),
        );
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
        $cache = $this->memcache->get(sprintf(MEMCACHE_TRIVY_VULNS_KEY, $image));
        if (!empty($cache)) {
            return $cache;
        }

        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        $imagePath  = TRIVY_PATH . $hashPrefix;

        if (!is_dir($imagePath)) {
            return null;
        }

        $resultFiles = glob($imagePath . '/result_*.json');
        if (empty($resultFiles)) {
            return null;
        }

        usort($resultFiles, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        if ($file) {
            $scanFile = $imagePath . '/' . $file;
            if (!file_exists($scanFile)) {
                return null;
            }
        } else {
            $scanFile = $resultFiles[0];
        }

        $scanData = json_decode(file_get_contents($scanFile), true);

        if (empty($scanData['Results'])) {
            return null;
        }

        $parsedVulns = $this->parseVulns($scanData);
        $this->memcache->set(sprintf(MEMCACHE_TRIVY_VULNS_KEY, $file ?: $image), $parsedVulns, MEMCACHE_TRIVY_VULNS_TIME);

        return $parsedVulns;
    }

    public function getVulnCounts($image)
    {
        $cache = $this->memcache->get(sprintf(MEMCACHE_TRIVY_VULNS_COUNT_KEY, $image));
        if (!empty($cache)) {
            return $cache;
        }

        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        $imagePath  = TRIVY_PATH . $hashPrefix;

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

        $latestScan = $resultFiles[0];
        $scanData   = json_decode(file_get_contents($latestScan), true);

        if (empty($scanData['Results'])) {
            return $counts;
        }

        foreach ($scanData['Results'] as $result) {
            if (!empty($result['Vulnerabilities'])) {
                foreach ($result['Vulnerabilities'] as $vuln) {
                    $severity = strtolower($vuln['Severity'] ?? 'unknown');
                    if (isset($counts[$severity])) {
                        $counts[$severity]++;
                    } else {
                        $counts['unknown']++;
                    }
                }
            }
        }

        $this->memcache->set(sprintf(MEMCACHE_TRIVY_VULNS_COUNT_KEY, $image), $counts, MEMCACHE_TRIVY_VULNS_COUNT_TIME);
        return $counts;
    }

    public function getScanHistoryCount($image)
    {
        $cache = $this->memcache->get(sprintf(MEMCACHE_TRIVY_SCAN_HISTORY_COUNT_KEY, $image));
        if (!empty($cache)) {
            return $cache;
        }

        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        $imagePath  = TRIVY_PATH . $hashPrefix;

        if (!is_dir($imagePath)) {
            return 0;
        }

        $resultFiles = glob($imagePath . '/result_*.json');

        $this->memcache->set(sprintf(MEMCACHE_TRIVY_SCAN_HISTORY_COUNT_KEY, $image), count($resultFiles), MEMCACHE_TRIVY_SCAN_HISTORY_COUNT_TIME);
        return count($resultFiles);
    }

    public function getScanHistory($image)
    {
        $cache = $this->memcache->get(sprintf(MEMCACHE_TRIVY_SCAN_HISTORY_KEY, $image));
        if (!empty($cache)) {
            return $cache;
        }

        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        $imagePath  = TRIVY_PATH . $hashPrefix;

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
            $scanData = json_decode(file_get_contents($file), true);
            $counts   = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'unknown' => 0];

            if (!empty($scanData['Results'])) {
                foreach ($scanData['Results'] as $result) {
                    if (!empty($result['Vulnerabilities'])) {
                        foreach ($result['Vulnerabilities'] as $vuln) {
                            $severity = strtolower($vuln['Severity'] ?? 'unknown');
                            if (isset($counts[$severity])) {
                                $counts[$severity]++;
                            } else {
                                $counts['unknown']++;
                            }
                        }
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

        $this->memcache->set(sprintf(MEMCACHE_TRIVY_SCAN_HISTORY_KEY, $image), $history, MEMCACHE_TRIVY_SCAN_HISTORY_TIME);
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
        if (empty($scanData['Results'])) {
            return $vulns;
        }

        foreach ($scanData['Results'] as $result) {
            if (!empty($result['Vulnerabilities'])) {
                foreach ($result['Vulnerabilities'] as $vuln) {
                    $pkgName = $vuln['PkgName'] ?: ($vuln['PkgID'] ?? '');
                    $vulns[] = [
                        'id'        => $vuln['VulnerabilityID'],
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

        return $vulns;
    }

    /**
     * Get new or updated vulnerabilities by comparing most recent scan with previous scan
     * @param mixed Image (hash or full tag)
     * @return array Array of new/updated vulnerabilities with 'type' => 'new'|'updated'
     */
    public function getNewVulns($image)
    {
        $cache = $this->memcache->get(sprintf(MEMCACHE_TRIVY_NEW_VULNS_KEY, $image));
        if (!empty($cache)) {
            return $cache;
        }

        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        $imagePath  = TRIVY_PATH . $hashPrefix;

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

        $previousScan = json_decode(file_get_contents($resultFiles[1]), true);

        $latestVulns   = $this->parseVulns($latestScan);
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

        $this->memcache->set(sprintf(MEMCACHE_TRIVY_NEW_VULNS_KEY, $image), $newVulns, MEMCACHE_TRIVY_NEW_VULNS_TIME);
        return $newVulns;
    }

    public function __toString()
    {
        return 'Class loaded: Trivy';
    }
}
