<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- BRING IN THE EXTRAS
loadClassExtras('Docker');

class Docker
{
    use Container;
    use DockersApi;
    use Image;
    use Network;
    use Process;
    use Volume;

    protected $shell;
    protected $database;

    public function __construct()
    {
        global $shell, $database;

        $this->shell    = $shell ?? new Shell();
        $this->database = $database ?? new Database();
    }

    public function stats($useCache)
    {
        if (file_exists(STATS_FILE)) {
            $statsFile = file_get_contents(STATS_FILE);
        }

        if ($statsFile && $useCache) {
            return $statsFile;
        }

        $cmd    = DockerSock::STATS_FORMAT;
        $shell  = $this->shell->exec($cmd . ' 2>&1');

        if ($shell) {
            apiRequest('file/stats', [], ['contents' => $shell]);
        }

        return $shell;
    }

    public function inspect($what, $useCache = true, $format = true, $params = '')
    {
        if ($format) {
            $cmd = sprintf(DockerSock::INSPECT_FORMAT, $this->shell->prepare($what));
        } else {
            $cmd = sprintf(DockerSock::INSPECT_CUSTOM, $this->shell->prepare($what), $this->shell->prepare($params));
        }

        $shell = $this->shell->exec($cmd . ' 2>&1');

        return $shell;
    }

    public function logs($container)
    {
        $cmd    = sprintf(DockerSock::LOGS, $this->shell->prepare($container));
        $shell  = $this->shell->exec($cmd . ' 2>&1');
        $in     = ["\n", '[36m', '[31m', '[0m'];
        $out    = ['<br>', '', '', ''];

        return str_replace($in, $out, $shell);
    }

    public function login($registry, $username, $password)
    {
        $cmd    = sprintf(DockerSock::LOGIN, $this->shell->prepare($password), $this->shell->prepare($registry), $this->shell->prepare($username));
        $shell  = $this->shell->exec($cmd . ' 2>&1');

        return $shell;
    }

    public function getDockerLogs($container, $log)
    {
        if ($log != 'docker' && file_exists('/appdata/' . $container . '/logs/' . $log . '.log')) {
            $logFile    = file('/appdata/' . $container . '/logs/' . $log . '.log');
            $return     = '';

            foreach ($logFile as $line) {
                $line = json_decode($line, true);
                $return .= '[' . $line['timestamp'] . '] {' . $line['level'] . '} ' . $line['message'] . "\n";
            }

            return $return;
        }

        if ($log == 'docker') {
            return $this->logs($container);
        }
    }

    public function isIO($name)
    {
        if (!$name) {
            return;
        }

        return str_contains($name, '/') ? $name : 'library/' . $name;
    }
}
