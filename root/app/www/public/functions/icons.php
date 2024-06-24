<?php

/*
----------------------------------
 ------  Created: 112823   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getIcons($bustCache = false)
{
    $update = false;
    if ($bustCache) {
        $update = true;
    } else {
        if (file_exists(LOGO_FILE)) {
            $age = filemtime(LOGO_FILE);
            if ($age + 86400 <= time()) {
                $update = true;
            }
        } else {
            $update = true;
        }
    }

    if ($update) {
        $iconList = getIconListFromGithub();

        if (!empty($iconList)) {
            setFile(LOGO_FILE, $iconList);
        } else { //-- GH REQUST FAILED, USE EXISTING
            $iconList = getFile(LOGO_FILE);
        }
    } else {
        $iconList = getFile(LOGO_FILE);
    }

    return $iconList;
}

function getIcon($inspect)
{
    if ($inspect[0]['Config']['Labels']['net.unraid.docker.icon']) {
        return $inspect[0]['Config']['Labels']['net.unraid.docker.icon'];
    } else {
        //-- TRY AN EXACT MATCH
        $icons = getIcons();
        $image = explode('/', $inspect[0]['Config']['Image']);
        $image = $image[count($image) - 1];
        $image = explode(':', $image);
        $image = $image[0];

        if ($icons[$image]) {
            return ICON_URL . $icons[$image];
        }

        //-- TRY THE ALIAS FILES
        $aliasFiles = [EXTERNAL_ICON_ALIAS_FILE, ABSOLUTE_PATH . INTERNAL_ICON_ALIAS_FILE];

        foreach ($aliasFiles as $aliasFile) {
            if (file_exists($aliasFile)) {
                $aliasList = getFile($aliasFile);

                foreach ($aliasList as $name => $aliasOptions) {
                    if (in_array($image, $aliasOptions)) {
                        return str_contains($name, 'http') ? $name : ICON_URL . $icons[$name];
                    }
                }
            }
        }

        return;
    }
}