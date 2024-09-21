<?php

/*
----------------------------------
 ------  Created: 090324   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait NotificationTemplates
{
    function getTemplate($trigger)
    {
        switch ($trigger) {
            case 'health':
                return [
                        'event'     => '', 
                        'container' => '', 
                        'restarted' => ''
                    ];
            case 'prune':
                return [
                        'event'     => '', 
                        'network'   => '', 
                        'volume'    => '', 
                        'image'     => '', 
                        'imageList' => ''
                    ];
            case 'added':
            case 'removed':
            case 'stateChange':
                return [
                        'event'     => '', 
                        'changes'   => '',
                        'added'     => '',
                        'removed'   => ''
                    ];
            case 'test':
                return [
                        'event'     => '', 
                        'title'     => '', 
                        'message'   => ''
                    ];
            case 'updated':
            case 'updates':
                return [
                        'event'     => '', 
                        'available' => '', 
                        'updated'   => ''
                ];
            case 'cpuHigh':
            case 'memHigh':
                return [
                        'event'         => '', 
                        'container'     => '',
                        'usage'         => '',
                        'cpu'           => '', 
                        'cpuThreshold'  => '',
                        'mem'           => '',
                        'memThreshold'  => ''
                ];
            default:
                return [];
        }
    }
}
