<?php
$config = require __DIR__ . "/Config.php";
require __DIR__ . "/MessagesProcessingRules.php";

$redis = new Redis();
$redis->connect($config['redis']['host'], $config['redis']['port']);

$queueKey = $config['redis']['queue_key'];
$aliveKey = $config['redis']['alive_key'];
$backendTimeout = $config['backend_timeout'] ?? 2;
$checkInterval = $config['healthcheck_interval'] ?? 30;

$roundRobinIndex = -1;


function sendToBackend($url, $method, $headers, $body, $timeout) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers ?? [],
        CURLOPT_HEADER => false,
        CURLOPT_TIMEOUT => $timeout,
    ]);
    if (in_array($method, ["POST","PUT","PATCH"]) && !empty($body)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, is_array($body) ? json_encode($body) : $body);
    }
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return [$httpCode, $response];
}

while (true) {
    $startTime = time();

    while ($data = $redis->lPop($queueKey)) {
        $req = json_decode($data, true);
        if (!$req) continue;

        // Get Alive Backends
        $aliveBackends = $redis->lRange($aliveKey, 0, -1);
        if (!is_array($aliveBackends)) $aliveBackends = [];

        if (empty($aliveBackends)) {
            //save to queue whene no  alive backend available
            $redis->rPush($queueKey, $data);
            break; 
        }

        //selection of alive backend based on strategy on config file
        switch ($config['strategy']) {
            case 'round-robin':
                $lastIndex = $redis->get('sms_wrapper_last_index') ?: -1;
                $lastIndex = ($lastIndex + 1) % count($aliveBackends);
                $redis->set('sms_wrapper_last_index', $lastIndex);
                $backendUrl = $aliveBackends[$lastIndex] . $req['path'];
                break;
            case 'failover':
                $backendUrl = $aliveBackends[0] . $req['path'];
                break;
            case 'random':
                $backendUrl = $aliveBackends[array_rand($aliveBackends)] . $req['path'];
                break;
            default:
                $backendUrl = $aliveBackends[0] . $req['path'];
        }

        list($httpCode, $resp) = sendToBackend($backendUrl, $req['method'], $req['headers'], $req['body'], $backendTimeout);

        if ($httpCode >= 500) {
            $redis->rPush($queueKey, $data); 
            error_log("[Worker] Backend $backendUrl failed, request requeued");
        } else {
            error_log("[Worker] Request sent to $backendUrl, status: $httpCode, Responce: $resp");
            if (function_exists('postProcessResponse')) {
                $customResp = postProcessResponse($req['path'], $req['method'], $req['body'] ?? null, $httpCode, $resp ?? null);
                if ($customResp !== null) {
                    error_log("[Worker] Custom response triggered for {$req['path']}");
                }
            }
        }
    }

    $elapsed = time() - $startTime;
    if ($elapsed < $checkInterval) {
        sleep($checkInterval - $elapsed);
    }
}
