<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Process
{
    public function processList($useCache = true, $format = true, $params = '')
    {
        logger(SYSTEM_LOG, 'dockerProcessList ->');

        if ($format) {
            $cmd = DockerSock::PROCESSLIST_FORMAT;
        } else {
            $cmd = sprintf(DockerSock::PROCESSLIST_CUSTOM, $params);
        }
        logger(SYSTEM_LOG, '$cmd=' . $cmd);

        $shell = shell_exec($cmd . ' 2>&1');
        logger(SYSTEM_LOG, 'dockerProcessList <-');

        return $shell;
    }
}
