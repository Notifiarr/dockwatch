<?php

/*
----------------------------------
 ------  Created: 050325   ------
 ------  Austin Best	   ------
----------------------------------
*/

switch ($path) {
    case 'containers': //-- .../api/stats/containers
        switch (true) {
            case $IS_GET:
                $request = explode(',', $_GET['servers']) ?: [];
                $apiRequestResponse = apiResponse(200, getContainerStats(validateServers($request)));
                break;
            default:
                apiResponse(405, ['error' => 'Invalid method for requested path']);
                break;
        }
        break;
    case 'metrics': //-- .../api/stats/metrics
        switch (true) {
            case $IS_GET:
                $request = explode(',', $_GET['servers']) ?: [];
                $apiRequestResponse = apiResponse(200, getUsageMetrics(validateServers($request)));
                break;
            default:
                apiResponse(405, ['error' => 'Invalid method for requested path']);
                break;
        }
        break;
    case 'overview': //-- .../api/stats/overview
        switch (true) {
            case $IS_GET:
                $request = explode(',', $_GET['servers']) ?: [];
                $apiRequestResponse = apiResponse(200, getOverviewStats(validateServers($request)));
                break;
            default:
                apiResponse(405, ['error' => 'Invalid method for requested path']);
                break;
        }
        break;
    default:
        apiResponse(400, ['error' => 'Invalid path for requested route']);
        break;
}
