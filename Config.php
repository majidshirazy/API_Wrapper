<?php
return [
    'strategy' => 'round-robin', // round-robin | failover | random
    'backend_timeout' => 2, //Seconds
    'healthcheck_interval' => 3, //Seconds
    'backends' => [
        ['url' => "http://1.2.3.4:80", 'name' => 'api_01'],
        ['url' => "http://1.2.3.6:8090", 'name' => 'Api_02'],
        ['url' => 'https://1.1.1.1:9098', 'name' => 'api03']
    ],
    'HealthCheck' => '/health',
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'queue_key' => 'sms_wrapper_queue',
        'alive_key' => 'sms_wrapper_alive_backends'
    ]
];
