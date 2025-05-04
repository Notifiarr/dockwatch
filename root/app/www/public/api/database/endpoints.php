<?php

/*
----------------------------------
 ------  Created: 050325   ------
 ------  Austin Best	   ------
----------------------------------
*/

switch ($path) {
    case 'container': //-- .../api/database/container/$action
        switch (true) {
            case $IS_GET && $action == 'group':
                if (!$parameters['hash']) {
                    apiResponse(400, ['error' => 'Missing hash parameter']);
                }
                $groupsTable = $database->getContainerGroups();

                $apiRequestResponse = $database->getContainerGroupFromHash($parameters['hash'], $groupsTable);
                break;
            case $IS_GET && $action == 'hash':
                if (!$parameters['hash']) {
                    apiResponse(400, ['error' => 'Missing hash parameter']);
                }

                $containersTable    = $database->getContainers();
                $container          = $database->getContainerFromHash($parameters['hash'], $containersTable);

                if (!$container['id']) {
                    $database->addContainer(['hash' => $parameters['hash']]);
                    $container = $database->getContainerFromHash($parameters['hash']);
                }

                $apiRequestResponse = $container;
                break;
            case $IS_POST && $action == 'add':
                if (!$payload['hash']) {
                    apiResponse(400, ['error' => 'Missing hash parameter']);
                }

                $database->addContainer($payload);
                break;
            case $IS_POST && $action == 'group' && $method == 'add':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = $database->addContainerGroup($payload['name']);
                break;
            case $IS_POST && $action == 'group' && $method == 'delete':
                if (!$payload['id']) {
                    apiResponse(400, ['error' => 'Missing id parameter']);
                }

                $database->deleteContainerGroup($payload['id']);
                break;
            case $IS_POST && $action == 'update':
                if (!$payload['hash']) {
                    apiResponse(400, ['error' => 'Missing hash parameter']);
                }

                $apiRequestResponse = $database->updateContainer($payload['hash'], $payload);
                break;
        }
        break;
    case 'containers': //-- .../api/database/containers
        $apiRequestResponse = $database->getContainers();
        break;
    case 'group': //-- .../api/database/group/$action/$method
        switch (true) {
            case $IS_GET && $action == 'container' && $method == 'links':
                if (!$parameters['group']) {
                    apiResponse(400, ['error' => 'Missing group parameter']);
                }
                $containersTable        = $database->getContainers();
                $containerLinksTable    = $database->getContainerGroupLinks();

                $apiRequestResponse = $database->getGroupLinkContainersFromGroupId($containerLinksTable, $containersTable, $parameters['group']);
                break;
            case $IS_GET && $action == 'links':
                $apiRequestResponse = $database->getContainerGroupLinks();
                break;
            case $IS_POST && $action == 'container' && $method == 'update':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }
                if (!$payload['id']) {
                    apiResponse(400, ['error' => 'Missing id parameter']);
                }

                $database->updateContainerGroup($payload['id'], ['name' => $database->prepare($payload['name'])]);
                break;
            case $IS_POST && $action == 'container' && $method == 'link' && $field == 'add':
                if (!$payload['groupId']) {
                    apiResponse(400, ['error' => 'Missing groupId parameter']);
                }
                if (!$payload['containerId']) {
                    apiResponse(400, ['error' => 'Missing containerId parameter']);
                }

                $database->addContainerGroupLink($payload['groupId'], $payload['containerId']);
                break;
            case $IS_POST && $action == 'container' && $method == 'link' && $field == 'remove':
                if (!$payload['groupId']) {
                    apiResponse(400, ['error' => 'Missing groupId parameter']);
                }
                if (!$payload['containerId']) {
                    apiResponse(400, ['error' => 'Missing containerId parameter']);
                }

                $database->removeContainerGroupLink($payload['groupId'], $payload['containerId']);
                break;
        }
        break;
    case 'groups': //-- .../api/database/groups
        $apiRequestResponse = $database->getContainerGroups();
        break;
    case 'migrations': //-- .../api/database/migrations
        $database->migrations();
        break;
    case 'notification': //-- .../api/database/notification/$action/$method/$field
        switch (true) {
            case $IS_GET && $action == 'link' && $method == 'platform' && $field == 'name':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = $database->getNotificationLinkPlatformFromName($parameters['name']);
                break;
            case $IS_GET && $action == 'links':
                $apiRequestResponse = $database->getNotificationLinks();
                break;
            case $IS_GET && $action == 'platforms':
                $apiRequestResponse = $database->getNotificationPlatforms();
                break;
            case $IS_GET && $action == 'trigger' && $method == 'enabled':
                if (!$parameters['trigger']) {
                    apiResponse(400, ['error' => 'Missing trigger parameter']);
                }

                $apiRequestResponse = $database->isNotificationTriggerEnabled($parameters['trigger']);
                break;
            case $IS_GET && $action == 'triggers':
                $apiRequestResponse = $database->getNotificationTriggers();
                break;
            case $IS_POST && $action == 'link' && $method == 'add':
                if (!$parameters['platformId']) {
                    apiResponse(400, ['error' => 'Missing platformId parameter']);
                }

                $apiRequestResponse = $database->addNotificationLink($parameters['platformId'], $payload['triggerIds'], $payload['platformParameters'], $payload['senderName']);
                break;
            case $IS_POST && $action == 'link' && $method == 'delete':
                if (!$payload['linkId']) {
                    apiResponse(400, ['error' => 'Missing linkId parameter']);
                }

                $apiRequestResponse = $database->deleteNotificationLink($payload['linkId']);
                break;
            case $IS_POST && $action == 'link' && $method == 'update':
                if (!$payload['linkId']) {
                    apiResponse(400, ['error' => 'Missing linkId parameter']);
                }

                $apiRequestResponse = $database->updateNotificationLink($payload['linkId'], $payload['triggerIds'], $payload['platformParameters'], $payload['senderName']);
                break;
        }
        break;
    case 'servers': //-- .../api/database/servers
        switch (true) {
            case $IS_GET:
                $apiRequestResponse = $database->getServers();
                break;
            case $IS_POST:
                if (!$payload['serverList']) {
                    apiResponse(400, ['error' => 'Missing serverList parameter']);
                }

                $apiRequestResponse = $database->setServers($payload['serverList']);
                break;
        }
    case 'setting': //-- .../api/database/setting
        switch (true) {
            case $IS_POST:
                if (!$payload['setting']) {
                    apiResponse(400, ['error' => 'Missing setting parameter']);
                }
                if (!array_key_exists('value', $payload)) {
                    apiResponse(400, ['error' => 'Missing value parameter']);
                }

                $apiRequestResponse = $database->setSetting($payload['setting'], $payload['value']);
                break;
        }
        break;
    case 'settings': //-- .../api/database/settings
        switch (true) {
            case $IS_GET:
                $apiRequestResponse = $database->getSettings();
                break;
            case $IS_POST:
                if (!$payload['newSettings']) {
                    apiResponse(400, ['error' => 'Missing newSettings parameter']);
                }

                $apiRequestResponse = $database->setSettings($payload['newSettings'], $database->getSettings());
                break;
        }
}
