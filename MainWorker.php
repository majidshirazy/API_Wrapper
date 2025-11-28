<?php
$config = require __DIR__ . "/Config.php";
require __DIR__ . "/MessagesProcessingRules.php";

$redis = new Redis();
$redis->connect($config['redis']['host'], $config['redis']['port']);

$method = $_SERVER["REQUEST_METHOD"];
$requestUri = $_SERVER["REQUEST_URI"];
$query = $_SERVER['QUERY_STRING'] ?? '';

$prefix = "/sms";
$path = preg_replace("#^" . preg_quote($prefix, "#") . "#", "", $requestUri);
$pathOnly = strtok($path, "?");

$rawBody = file_get_contents("php://input");
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

$body = null;
if (stripos($contentType, 'application/json') !== false) {
    $body = json_decode($rawBody, true);
}

// Chdecking validation rukes - beforce sending to backends
if (function_exists('validateRequest')) {
    $validation = validateRequest($pathOnly, $method, $body);
    if ($validation !== null) {
        if (isset($validation['custom_response'])) {
            http_response_code($validation['custom_response']['status']);
            header("Content-Type: application/json");
            echo json_encode($validation['custom_response']);
            exit;
        } else {
            http_response_code($validation["status"]);
            header("Content-Type: application/json");
            echo json_encode(["error" => $validation["error"]]);
            exit;
        }
    }
}

//Get alive backends from redis
$aliveBackends = $redis->lRange($config['redis']['alive_key'], 0, -1);
if (empty($aliveBackends)) {

    $requestData = [
        "method" => $method,
        "path" => $pathOnly,
        "headers" => $forwardHeaders,
        "body" => $body
    ];

    $redis->rPush($config['redis']['queue_key'], json_encode($requestData));
    
    http_response_code(503);
    echo json_encode(["error" => "Your Request is Queued"]);
    exit;
}

//selection from alive backends based on strategy
switch ($config['strategy']) {
    case 'round-robin':
        $lastIndex = $redis->get('sms_wrapper_last_index') ?: -1;
        $lastIndex = ($lastIndex + 1) % count($aliveBackends);
        $redis->set('sms_wrapper_last_index', $lastIndex);
        $targetUrl = $aliveBackends[$lastIndex] . $pathOnly;
        break;
    case 'failover':
        $targetUrl = $aliveBackends[0] . $pathOnly;
        break;
    case 'random':
        $targetUrl = $aliveBackends[array_rand($aliveBackends)] . $pathOnly;
        break;
    default:
        $targetUrl = $aliveBackends[0] . $pathOnly;
}

//adding query of request
if (!empty($query)) {
    $targetUrl .= "?" . $query;
}

// Forward headers
$forwardHeaders = [];
$allHeaders = getallheaders();
foreach ($allHeaders as $k => $v) {
    if (strcasecmp($k, 'Host') === 0) continue;
    $forwardHeaders[] = "$k: $v";
}
$forwardHeaders[] = 'X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR'];

//sedn request to selected backend
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $targetUrl,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $forwardHeaders,
    CURLOPT_HEADER => false,
]);

if (in_array($method, ["POST", "PUT", "PATCH"])) {
    curl_setopt($curl, CURLOPT_POSTFIELDS, $rawBody);
}

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

//Pass to rules for post-processing check afer getting responce of backends
if (function_exists('postProcessResponse')) {
    $custom = postProcessResponse($pathOnly, $method, $body, $httpCode, $response);
    if ($custom !== null) {
        http_response_code($httpCode);
        header("Content-Type: application/json");
        echo json_encode($custom);
        exit;
    }
}

$contentTypeFromBackend = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
curl_close($curl);

http_response_code($httpCode);
if ($contentTypeFromBackend) header("Content-Type: $contentTypeFromBackend");
else header("Content-Type: application/json");
echo $response;
