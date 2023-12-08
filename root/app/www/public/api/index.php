<?php

/*
----------------------------------
 ------  Created: 112723   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_PARSE);

define('ABSOLUTE_PATH', '../');

require ABSOLUTE_PATH . 'loader.php';

session_unset();
session_destroy();

$code = 200;
$apikey = $_SERVER['HTTP_X_API_KEY'] ;
if ($apikey != $serversFile[0]['apikey']) {
    apiResponse(401, ['error' => 'Invalid apikey']);
}

$_POST = json_decode(file_get_contents('php://input'), true);

switch (true) {
    case $_GET['request']:
        //-- GETTERS
        switch ($_GET['request']) {
            case 'dockerAutoCompose':
                if (!$_GET['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $response = ['docker' => dockerAutoCompose($_GET['name'])];
                break;
            case 'dockerAutoRun':
                if (!$_GET['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $response = ['docker' => dockerAutoRun($_GET['name'])];
                break;
            case 'dockerGetOrphanContainers':
                $response = ['docker' => dockerGetOrphanContainers()];
                break;
            case 'dockerGetOrphanVolumes':
                $response = ['docker' => dockerGetOrphanVolumes()];
                break;
            case 'dockerImageSizes':
                $response = ['docker' => dockerImageSizes()];
                break;
            case 'dockerInspect':
                if (!$_GET['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $response = ['docker' => dockerInspect($_GET['name'], $_GET['useCache'], $_GET['format'], $_GET['params'])];
                break;
            case 'dockerNetworks':
                $response = ['docker' => dockerNetworks($_GET['params'])];
                break;
            case 'dockerPermissionCheck':
                $response = ['docker' => dockerPermissionCheck()];
                break;
            case 'dockerPort':
                if (!$_GET['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $response = ['docker' => dockerPort($_GET['name'], $_GET['params'])];
                break;
            case 'dockerProcessList':
                $response = ['docker' => dockerProcessList($_GET['useCache'], $_GET['format'], $_GET['size'], $_GET['params'])];
                break;
            case 'dockerPruneImage':
                $response = ['docker' => dockerPruneImage()];
                break;
            case 'dockerPruneVolume':
                $response = ['docker' => dockerPruneVolume()];
                break;
            case 'dockerState':
                $response = ['docker' => dockerState()];
                break;
            case 'dockerStats':
                $response = ['docker' => dockerStats($_GET['useCache'])];
                break;
            case 'health':
                $response = ['health' => getFile(HEALTH_FILE)];
                break;
            case 'ping':
                $response = ['result' => 'pong from ' . ACTIVE_SERVER_NAME];
                break;
            case 'pull':
                $response = ['pull' => getFile(PULL_FILE)];
                break;
            case 'settings':
                $response = ['settings' => getFile(SETTINGS_FILE)];
                break;
            case 'state':
                $response = ['state' => getFile(STATE_FILE)];
                break;
            case 'servers':
                $response = ['servers' => getFile(SERVERS_FILE)];
                break;
            case 'viewLog':
                $response = ['result' => viewLog($_GET['name'])];
                break;
            default:
                apiResponse(405, ['error' => 'Invalid GET request']);
                break;
        }
        break;
    case $_POST['request']:
        switch ($_POST['request']) {
            //-- SETTERS
            case 'health':
                if (!$_POST['contents']) {
                    apiResponse(400, ['error' => 'Missing settings object']);
                }
            
                setFile(HEALTH_FILE, $_POST['contents']);
                $response = ['result' => HEALTH_FILE . ' updated'];
                break;
            case 'pull':
                if (!$_POST['contents']) {
                    apiResponse(400, ['error' => 'Missing pull object']);
                }
            
                setFile(PULL_FILE, $_POST['contents']);
                $response = ['result' => PULL_FILE . ' updated'];
                break;
            case 'settings':
                if (!$_POST['contents']) {
                    apiResponse(400, ['error' => 'Missing settings object']);
                }
            
                setFile(SETTINGS_FILE, $_POST['contents']);
                $response = ['result' => SETTINGS_FILE . ' updated'];
                break;
            case 'servers':
                if (!$_POST['contents']) {
                    apiResponse(400, ['error' => 'Missing servers object']);
                }
            
                setFile(SERVERS_FILE, $_POST['contents']);
                $response = ['result' => SERVERS_FILE . ' updated'];
                break;
            case 'state':
                if (!$_POST['contents']) {
                    apiResponse(400, ['error' => 'Missing state object']);
                }
            
                setFile(STATE_FILE, $_POST['contents']);
                $response = ['result' => STATE_FILE . ' updated'];
                break;
            //-- ACTIONS
            case 'deleteLog':
                if (!$_POST['log']) {
                    apiResponse(400, ['error' => 'Missing log parameter']);
                }

                $response = ['result' => deleteLog($_POST['log'])];
                break;
            case 'dockerPullContainer':
                if (!$_POST['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $response = ['docker' => dockerPullContainer($_POST['name'])];
                break;
            case 'dockerRemoveContainer':
                if (!$_POST['id']) {
                    apiResponse(400, ['error' => 'Missing id parameter']);
                }

                $response = ['docker' => dockerRemoveContainer($_POST['id'])];
                break;
            case 'dockerRemoveImage':
                if (!$_POST['id']) {
                    apiResponse(400, ['error' => 'Missing id parameter']);
                }

                $response = ['docker' => dockerRemoveImage($_POST['id'])];
                break;
            case 'dockerRemoveVolume':
                if (!$_POST['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $response = ['docker' => dockerRemoveVolume($_POST['name'])];
                break;
            case 'dockerStartContainer':
                if (!$_POST['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $response = ['docker' => dockerStartContainer($_POST['name'])];
                break;
            case 'dockerStopContainer':
                if (!$_POST['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $response = ['docker' => dockerStopContainer($_POST['name'])];
                break;         
            case 'dockerUpdateContainer':
                if (!$_POST['command']) {
                    apiResponse(400, ['error' => 'Missing command parameter']);
                }

                $response = ['docker' => dockerUpdateContainer($_POST['command'])];
                break;
            case 'purgeLogs':
                if (!$_POST['group']) {
                    apiResponse(400, ['error' => 'Missing group parameter']);
                }

                $response = ['result' => purgeLogs($_POST['group'])];
                break;
            case 'runTask':
                if (!$_POST['task']) {
                    apiResponse(400, ['error' => 'Missing task parameter']);
                }

                $response = ['result' => executeTask($_POST['task'])];
                break;
            case 'testNotify':
                $testNotification = sendTestNotification($_POST['platform']);
            
                if ($testNotification) {
                    $code = '400';
                }
            
                $response = ['result' => $testNotification];
                break;
            default:
                apiResponse(405, ['error' => 'Invalid POST request']);
                break;
        }
        break;
}

//-- RETURN
apiResponse($code, $response);