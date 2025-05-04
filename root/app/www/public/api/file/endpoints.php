<?php

/*
----------------------------------
 ------  Created: 050325   ------
 ------  Austin Best	   ------
----------------------------------
*/

switch ($path) {
    case 'dependency': //-- .../api/file/dependency
    case 'metrics': //-- .../api/file/metrics
    case 'pull': //-- .../api/file/pull
    case 'sse': //-- .../api/file/sse
    case 'state': //-- .../api/file/state
    case 'stats': //-- .../api/file/stats
        $fileConstant = constant(strtoupper($path) . '_FILE');

        switch (true) {
            case $IS_GET:
                $apiRequestResponse = getFile($fileConstant);
                break;
            case $IS_POST:
                if (!array_key_exists('contents', $payload)) {
                    apiResponse(400, ['error' => 'Missing ' . $fileName . ' object data']);
                }

                setFile($fileConstant, $payload['contents']);
                $apiRequestResponse = $fileConstant . ' contents updated';
                break;
        }
        break;
}
