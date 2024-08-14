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
        $icons = getIcons();

        //-- GET JUST IMAGE NAME
        $imageName = explode('/', $inspect[0]['Config']['Image']);
        $imageName = $imageName[count($imageName) - 1];
        $imageName = explode(':', $imageName);
        $imageName = $imageName[0];

        //-- GET SOURCE/IMAGE
        list($fullImage, $tag) = explode(':', $inspect[0]['Config']['Image']);

        //-- GET CONTAINER
        $container = str_replace('/', '', $inspect[0]['Name']);

        if ($icons[$imageName] || $icons[$container]) {
            return $icons[$container] ? ICON_URL . $icons[$container] : ICON_URL . $icons[$imageName];
        }

        //-- TRY THE ALIAS FILES
        $aliasFiles     = [EXTERNAL_ICON_ALIAS_FILE, ABSOLUTE_PATH . INTERNAL_ICON_ALIAS_FILE];
        $matchOptions   = [$imageName, $fullImage, $container];

        foreach ($aliasFiles as $aliasFile) {
            if (file_exists($aliasFile)) {
                $aliasList = getFile($aliasFile);

                foreach ($aliasList as $name => $aliasOptions) {
                    if (array_equals_any($aliasOptions, $matchOptions)) {
                        return str_contains($name, 'http') ? $name : ICON_URL . $icons[$name];
                    }
                }
            }
        }

        return;
    }
}