<?php

/*
----------------------------------
 ------  Created: 021626   ------
 ------  nzxl	             ------
----------------------------------
*/

//-- BRING IN THE EXTRAS
loadClassExtras('Trivy');

class Trivy
{
    protected $shell;
    protected $docker;

    public function __construct()
    {
        global $shell, $docker;
        $this->shell  = $shell ?? new Shell();
        $this->docker = $docker ?? new Docker();
    }

    public function downloadDB()
    {
        createDirectoryTree(TRIVY_PATH);

        $cmd   = sprintf(TrivyCLI::UPDATE_DB, TRIVY_PATH);
        $shell = $this->shell->exec($cmd . ' 2>&1');

        $dbPath = TRIVY_PATH . 'db/trivy.db';
        if (!file_exists($dbPath) || filesize($dbPath) == 0) {
            //-- FAILED TO UPDATE DB
        }

        return $shell;
    }

    public function downloadJavaDB()
    {
        $cmd   = sprintf(TrivyCLI::UPDATE_DB_JAVA, TRIVY_PATH);
        $shell = $this->shell->exec($cmd . ' 2>&1');

        $dbPath = TRIVY_PATH . 'java-db/trivy-java.db';
        if (!file_exists($dbPath) || filesize($dbPath) == 0) {
            //-- FAILED TO UPDATE DB
        }

        return $shell;
    }


    /**
     * Scan image for vulnerabilities
     * @param mixed Image (hash or full tag)
     * @return bool|string|null
     */
    public function scanImage($image)
    {
        $hash       = $this->docker->getImageHash($image);
        $hashPrefix = substr(preg_replace('/sha256\:/', '', $hash), 0, 4);
        createDirectoryTree(TRIVY_PATH . $hashPrefix);

        $cmd   = sprintf(
            TrivyCLI::SCAN_IMAGE,
            TRIVY_PATH,
            TRIVY_PATH,
            $this->shell->prepare('"' . TRIVY_PATH . $hashPrefix . '/result_' . time() . '.json"'),
            $this->shell->prepare($image)
        );
        $shell = $this->shell->exec($cmd . ' 2>&1');

        return $shell;
    }

    /**
     * Get image vulnerabilities from most recent scan file
     * @param mixed Image (hash or full tag)
     * @return array|null
     */
    public function getVulns($image)
    {
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

        $latestScan = $resultFiles[0];
        $scanData   = json_decode(file_get_contents($latestScan), true);

        if (empty($scanData['Results'])) {
            return null;
        }

        return $this->parseVulns($scanData);
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

        return $newVulns;
    }

    public function __toString()
    {
        return 'Class loaded: Trivy';
    }
}
