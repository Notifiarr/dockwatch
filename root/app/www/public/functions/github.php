<?php

/*
----------------------------------
 ------  Created: 112623   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getIconListFromGithub()
{
    $iconList = [];

    //-- GET THE CURRENT SHA SINCE CONTENTS IS LIMITED TO 1k ITEMS
    $url        = 'https://api.github.com/repos/' . ICON_REPO . '/contents';
    $headers    = ['Accept: application/vnd.github+json'];
    $curl       = curl($url, $headers);
    $sha        = '';
    foreach ($curl['response'] as $treeItems) {
        if ($treeItems['name'] == 'icons') {
            $sha = $treeItems['sha'];
            break;
        }
    }

    //-- LIST BY THE SHA, LIMITED TO 100k ITEMS
    if ($sha) {
        $url        = 'https://api.github.com/repos/' . ICON_REPO . '/git/trees/' . $sha;
        $headers    = ['Accept: application/vnd.github+json'];
        $curl       = curl($url, $headers);
        $icons      = $curl['response']['tree'];

        $iconList = [];
        foreach ($icons as $icon) {
            list($name, $ext) = explode('.', $icon['path']);
            $iconList[$name] = $icon['path'];
        }
    }

    return $iconList;
}