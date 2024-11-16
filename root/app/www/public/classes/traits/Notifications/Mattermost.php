<?php

/*
----------------------------------
 ------  Created: 111224   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Mattermost
{
    public function mattermost($logfile, $url, $payload, $test = false)
    {
        if (!$url) {
            return ['code' => 400, 'error' => 'Missing webhook url'];
        }

        $message    = $this->buildMattermostMessage($payload, $test);
        $payload    = ['text' => $message];
        $curl       = curl($url, [], 'POST', json_encode($payload));

        logger($logfile, 'notification response:' . json_encode($curl), ($curl['code'] != 200 ? 'error' : ''));

        $return = ['code' => 200];
        if (!str_equals_any($curl['code'], [200, 201, 400, 401])) {
            logger($logfile, 'sending a retry in 5s...');
            sleep(5);

            $curl = curl($url, [], 'POST', json_encode($payload));
            logger($logfile, 'notification response:' . json_encode($curl), ($curl['code'] != 200 ? 'error' : ''));

            if ($curl['code'] != 200) {
                $return = ['code' => $curl['code'], 'error' => $curl['response']['description']];
            }
        }

        return $return;
    }

    public function buildMattermostMessage($payload, $test = false)
    {
        $message = '';

        switch ($payload['event']) {
            case 'test':
                $message .= '##### ' . APP_NAME . ': Test' . "\n\n";
                $message .= $payload['message'];
                $message .= "\n\n";
                break;
            case 'health':
                $message .= '##### ' . APP_NAME . ': Health' . "\n";
                $message .= 'Server: _' . $payload['server']['name'] . '_' . "\n\n";
                $message .= '*' . $payload['container'] . '* → Restarted at ' . date('g:i A');
                $message .= "\n\n";
                break;
            case 'prune':
                $message .= '##### ' . APP_NAME . ': Pruned' . "\n";
                $message .= 'Server: _' . $payload['server']['name'] . '_' . "\n\n";

                $table = [];
                $table[] = '| Type | Pruned |';
                $table[] = '| ----- | ----- |';

                if ($payload['network']) {
                    $pruned = [];
                    foreach ($payload['network'] as $network) {
                        if (!trim($network)) {
                            continue;
                        }

                        $pruned[] = $network;
                    }

                    if ($pruned) {
                        $table[] = '| Networks | ' . implode(', ', $pruned) . ' |';
                    }
                }
                if ($payload['volume']) {
                    $pruned = [];
                    foreach ($payload['volume'] as $volume) {
                        if (!trim($volume)) {
                            continue;
                        }

                        if ($pruned) {
                            $pruned[] = truncateMiddle($volume, 20);
                        }
                    }

                    $table[] = '| Volumes | ' . implode(', ', $pruned) . ' |';
                }
                if ($payload['image']) {
                    $pruned = [];
                    foreach ($payload['image'] as $image) {
                        if (!trim($image)) {
                            continue;
                        }

                        if ($pruned) {
                            $pruned[] = $image;
                        }
                    }

                    $table[] = '| Images | ' . implode(', ', $pruned) . ' |';
                }
                if ($payload['imageList']) {
                    $pruned = [];
                    foreach ($payload['imageList'] as $imageList) {
                        if (!trim($imageList['cr'])) {
                            continue;
                        }

                        if ($pruned) {
                            $pruned[] = $imageList['cr'] . ' (' . byteConversion($imageList['size']) . ')';
                        }
                    }

                    if ($pruned) {
                        $table[] = '| Image List | ' . implode(', ', $pruned) . ' |';
                    } else {
                        $table = [];
                    }
                }

                if (count($table) > 2) {
                    $message .= implode("\n", $table) . "\n";
                } else {
                    $message = '';
                }
                break;
            case 'state':
                $message .= '##### ' . APP_NAME . ': Container state change' . "\n";
                $message .= 'Server: _' . $payload['server']['name'] . '_' . "\n\n";

                $table = [];
                $table[] = '| Type | Container |';
                $table[] = '| ----- | ----- |';

                if ($payload['added']) {
                    $state = [];
                    foreach ($payload['added'] as $added) {
                        if (!trim($added['container'])) {
                            continue;
                        }

                        $state[] = $added['container'];
                    }

                    if ($state) {
                        $table[] = '| Added | ' . implode(', ', $state) . ' |';
                    }
                }

                if ($payload['removed']) {
                    $state = [];
                    foreach ($payload['removed'] as $removed) {
                        if (!trim($removed['container'])) {
                            continue;
                        }

                        $state[] = $removed['container'];
                    }

                    if ($state) {
                        $table[] = '| Removed | ' . implode(', ', $state) . ' |';
                    }
                }

                if ($payload['changes']) {
                    $state = [];
                    foreach ($payload['changes'] as $changes) {
                        if (!trim($changes['container'])) {
                            continue;
                        }

                        $state[] = $changes['container'] . ' [' . $changes['previous'] . ' → ' . $changes['current'] . ']';
                    }

                    if (!$state) {
                        $table[] = '| Changed | ' . implode("\n", $state) . ' |';
                    }
                }

                if (count($table) > 2) {
                    $message .= implode("\n", $table) . "\n";
                } else {
                    $message = '';
                }
                break;
            case 'updates':
                $message .= '##### ' . APP_NAME . ': Updates' . "\n";
                $message .= 'Server: _' . $payload['server']['name'] . '_' . "\n\n";

                $table = [];
                $table[] = '| Type | Container |';
                $table[] = '| ----- | ----- |';

                if ($payload['available']) {
                    $updates = [];
                    foreach ($payload['available'] as $available) {
                        if (!trim($available['container'])) {
                            continue;
                        }

                        $updates[] = $available['container'];
                    }

                    if ($updates) {
                        $table[] = '| Available | ' . implode(', ', $updates) . ' |';
                    }
                }

                if ($payload['updated']) {
                    $updates = [];
                    foreach ($payload['updated'] as $updated) {
                        if (!trim($updated['container'])) {
                            continue;
                        }

                        $updates[] = $updated['container'] . ' [' . $updated['pre'] . ' → ' . $updated['post'] . ']';
                    }

                    if ($updates) {
                        $table[] = '| Updated | ' . implode("\n", $updates) . ' |';
                    }
                }

                if (count($table) > 2) {
                    $message .= implode("\n", $table) . "\n";
                } else {
                    $message = '';
                }
                break;
            case 'usage':
                $message .= '##### :warning: ' . APP_NAME . ': High usage' . "\n";
                $message .= 'Server: _' . $payload['server']['name'] . '_' . "\n\n";

                if ($payload['mem']) {
                    $message .= '*Memory* (>= ' . $payload['memThreshold'] . '%):' . "\n";
                    foreach ($payload['mem'] as $mem) {
                        $message .=  '- ' . $mem['container'] . ' → ' . $mem['usage'] . "%\n";
                    }
                    $message .= "\n";
                }

                if ($payload['cpu']) {
                    $message .= '*CPU* (>= ' . $payload['cpuThreshold'] . '%):' . "\n";
                    foreach ($payload['cpu'] as $cpu) {
                        $message .= '- ' . $cpu['container'] . ' → ' . $cpu['usage'] . "%\n";
                    }
                    $message .= "\n";
                }
                break;
        }

        return $test ? $message .= '`[TEST NOTIFICATION]`' : $message;
    }
}
