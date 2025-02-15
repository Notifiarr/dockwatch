<?php

/*
----------------------------------
 ------  Created: 092024   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Telegram
{
    public function telegram($logfile, $botToken, $chatId, $payload, $test = false)
    {
        if (!$botToken) {
            return ['code' => 400, 'error' => 'Missing bot token'];
        }
        if (!$chatId) {
            return ['code' => 400, 'error' => 'Missing chat id'];
        }

        $message    = $this->buildTelegramMessage($payload, $test);
        $url        = 'https://api.telegram.org/bot%s/sendMessage';
        $payload    = ['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'MarkdownV2', 'disable_web_page_preview' => true];
        $url        = sprintf($url, $botToken);
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

    public function buildTelegramMessage($payload, $test = false)
    {
        $message = '';

        switch ($payload['event']) {
            case 'test':
                $message .= APP_NAME . ': Test' . "\n\n";
                $message .= $payload['message'];
                $message .= "\n\n";
                break;
            case 'health':
                $message .= APP_NAME . ': Health' . "\n";
                $message .= 'Server: _' . $payload['server']['name'] . '_' . "\n\n";
                $message .= '*' . $payload['container'] . '* → Restarted at ' . date('g:i A');
                $message .= "\n\n";
                break;
            case 'prune':
                $message .= APP_NAME . ': Pruned' . "\n";
                $message .= 'Server: _' . $payload['server']['name'] . '_' . "\n\n";

                if ($payload['network']) {
                    $message .= "*Networks:*\n";
                    foreach ($payload['network'] as $network) {
                        $message .= '- ' . str_replace('_', '\_', $network) . "\n";
                    }
                    $message .= "\n";
                }
                if ($payload['volume']) {
                    $message .= "*Volumes:*\n";
                    foreach ($payload['volume'] as $volume) {
                        $message .= '- ' . truncateMiddle($volume, 20) . "\n";
                    }
                    $message .= "\n";

                }
                if ($payload['image']) {
                    $message .= "*Images:*\n";
                    foreach ($payload['image'] as $image) {
                        $message .= '- ' . $image . "\n";
                    }
                    $message .= "\n";

                }
                if ($payload['imageList']) {
                    $message .= "*Image List:*\n";
                    foreach ($payload['imageList'] as $imageList) {
                        $message .= '- ' . $imageList['cr'] . ' (' . byteConversion($imageList['size']) . ')' . "\n";
                    }
                    $message .= "\n";

                }
                break;
            case 'state':
                $message .= APP_NAME . ': Container state change' . "\n";
                $message .= 'Server: _' . $payload['server']['name'] . '_' . "\n\n";

                if ($payload['added']) {
                    $message .= '*Added*:' . "\n";
                    foreach ($payload['added'] as $added) {
                        $message .= '- ' . $added['container'] . "\n";
                    }
                    $message .= "\n";
                }
                if ($payload['removed']) {
                    $message .= '*Removed*:' . "\n";
                    foreach ($payload['removed'] as $removed) {
                        $message .= '- ' . $removed['container'] . "\n";
                    }
                    $message .= "\n";
                }
                if ($payload['changes']) {
                    $message .= '*Changed*:' . "\n";
                    foreach ($payload['changes'] as $changes) {
                        $message .= '- ' . $changes['container'] . ' [' . $changes['previous'] . ' → ' . $changes['current'] . ']' . "\n";
                    }
                    $message .= "\n";
                }
                break;
            case 'updates':
                $message .= APP_NAME . ': Updates' . "\n";
                $message .= 'Server: _' . $payload['server']['name'] . '_' . "\n\n";

                if ($payload['available']) {
                    $message .= '*Available*:' . "\n";
                    foreach ($payload['available'] as $available) {
                        $message .= '- ' . $available['container'] . "\n";
                    }
                    $message .= "\n";
                }
                if ($payload['updated']) {
                    $message .= '*Updated*:' . "\n";
                    foreach ($payload['updated'] as $updated) {
                        $message .= '- ' . $updated['container'] . ' [' . $updated['pre']['version'] . ' → ' . $updated['post']['version'] . ']' . "\n";
                    }
                    $message .= "\n";
                }
                break;
            case 'usage':
                $message .= APP_NAME . ': High usage' . "\n";
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

        $message = $test ? $message .= '`[TEST NOTIFICATION]`' : $message;

        return $this->escapeTelegramNotification($message);
    }

    public function escapeTelegramNotification($message)
    {
        $chars = ['-', '.', '(', ')', '<', '>', '=', '[', ']', '_'];
        foreach ($chars as $char) {
            $message = str_replace($char, '\\' . $char, $message);
        }

        return $message;
    }
}
