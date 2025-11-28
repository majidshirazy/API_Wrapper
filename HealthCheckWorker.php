<?php

while (true) {
    $config = require __DIR__ . "/Config.php";
    $redis = new Redis();
    $redis->connect($config['redis']['host'], $config['redis']['port']);
    $aliveKey = $config['redis']['alive_key'];

    $backends = $config['backends'];
    $interval = $config['healthcheck_interval'] ?? 30;

    echo "[".date('Y-m-d H:i:s')."] Performing HealthCheck...\n";
    $aliveBackends = [];
    foreach ($backends as $b) {
        $url = rtrim($b['url'], '/') . $config['HealthCheck']; 
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 500) {
            $aliveBackends[] = $b['url'];
            echo "[HealthCheck] {$b['name']} alive\n";
        } else {
            echo "[HealthCheck] {$b['name']} dead\n";
        }
    }

    $redis->del($aliveKey);
    foreach ($aliveBackends as $url) {
        $redis->rPush($aliveKey, $url);
    }

    echo "Sleeping $interval seconds...\n";
    sleep($interval);
}
