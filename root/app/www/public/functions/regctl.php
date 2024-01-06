<?php

/*
----------------------------------
 ------  Created: 010524   ------
 ------  Austin Best	   ------
----------------------------------
*/

function regctlCheck($image)
{
    if (!file_exists(REGCTL_PATH . REGCTL_BINARY)) {
        return 'Error: regctl binary (\'' . REGCTL_PATH . REGCTL_BINARY . '\') is not avaialable or there is a permissions error';
    }

    $regctl = shell_exec(REGCTL_PATH . REGCTL_BINARY . ' image digest --list ' . $image);
    return $regctl;
}

function regctlExists()
{
    if (!file_exists(REGCTL_PATH . REGCTL_BINARY)) {
        //-- GET ARCH
        $inspectImage   = apiRequest('dockerInspect', ['name' => APP_IMAGE, 'useCache' => false, 'format' => true]);
        $inspectImage   = json_decode($inspectImage['response']['docker'], true);
        $arch           = $inspectImage[0]['Architecture'];
        $regctl         = 'https://github.com/regclient/regclient/releases/latest/download/regctl-linux-' . $arch;

        $remote = fopen($regctl, 'rb');
        if ($remote) {
            echo 'found remote..<br>';
            $local = fopen(REGCTL_PATH . REGCTL_BINARY, 'wb');
            if ($local) {
                echo 'found local..<br>';
                while (!feof($remote)) {
                    fwrite($local, fread($remote, 1024 * 8), 1024 * 8);
                }
            }
        }

        if ($remote) {
            fclose($remote);
        }

        if ($local) {
            fclose($local);
        }

        if (file_exists(REGCTL_PATH . REGCTL_BINARY)) {
            shell_exec('chmod +x ' . REGCTL_PATH . REGCTL_BINARY);
        }
    }
}
