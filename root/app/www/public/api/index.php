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

$apikey = $_SERVER['HTTP_X_API_KEY'] ? $_SERVER['HTTP_X_API_KEY'] : $_GET['apikey'];
if ($apikey != $serversTable[APP_SERVER_ID]['apikey']) {
    apiResponse(401, ['error' => 'Invalid apikey']);
}

//-- DO ANYTHING SPECIAL NEEDED BEFORE SENDING IT
if ($_GET['endpoint']) {
    $_GET['request'] = $_GET['endpoint'];
}

$_POST      = json_decode(file_get_contents('php://input'), true);
$response   = ['result' => apiRequestLocal($_GET['request'], ($_POST ?: $_GET), $_POST)];

//-- RETURN
apiResponse(200, $response);
