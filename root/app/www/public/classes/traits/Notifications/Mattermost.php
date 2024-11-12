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
                        $pruned[] = str_replace('_', '\_', $network);
                    }

                    $table[] = '| Networks | ' . implode(', ', $pruned) . ' |';
                }
                if ($payload['volume']) {
                    $pruned = [];
                    foreach ($payload['volume'] as $volume) {
                        $pruned[] = truncateMiddle($volume, 20);
                    }

                    $table[] = '| Volumes | ' . implode(', ', $pruned) . ' |';
                }
                if ($payload['image']) {
                    $pruned = [];
                    foreach ($payload['image'] as $image) {
                        $pruned[] = $image;
                    }

                    $table[] = '| Images | ' . implode(', ', $pruned) . ' |';
                }
                if ($payload['imageList']) {
                    $pruned = [];
                    foreach ($payload['imageList'] as $imageList) {
                        $pruned[] = $imageList['cr'] . ' (' . byteConversion($imageList['size']) . ')';
                    }

                    $table[] = '| Image List | ' . implode(', ', $pruned) . ' |';
                }

                $message .= implode("\n", $table) . "\n";
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
                        $state[] = $added['container'];
                    }
                    $table[] = '| Added | ' . implode(', ', $state) . ' |';
                } else {
                    $table[] = '| Added | None |';
                }

                if ($payload['removed']) {
                    $state = [];
                    foreach ($payload['removed'] as $removed) {
                        $state[] = $removed['container'];
                    }
                    $table[] = '| Removed | ' . implode(', ', $state) . ' |';
                } else {
                    $table[] = '| Removed | None |';
                }

                if ($payload['changes']) {
                    $state = [];
                    foreach ($payload['changes'] as $changes) {
                        $state[] = $changes['container'] . ' [' . $changes['previous'] . ' → ' . $changes['current'] . ']';
                    }
                    $table[] = '| Changed | ' . implode("\n", $state) . ' |';
                } else {
                    $table[] = '| Changed | None |';
                }

                $message .= implode("\n", $table) . "\n";
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
                        $updates[] = $available['container'];
                    }
                    $table[] = '| Available | ' . implode(', ', $updates) . ' |';
                } else {
                    $table[] = '| Available | None |';
                }
                if ($payload['updated']) {
                    $updates = [];
                    foreach ($payload['updated'] as $updated) {
                        $updates[] = $updated['container'] . ' [' . $updated['pre'] . ' → ' . $updated['post'] . ']';
                    }
                    $table[] = '| Updated | ' . implode("\n", $updates) . ' |';
                } else {
                    $table[] = '| Updated | None |';
                }

                $message .= implode("\n", $table) . "\n";
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
