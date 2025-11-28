<?php
function validateRequest($path, $method, $body) {

    if (in_array($method, ["POST", "PUT", "PATCH"]) && empty($body)) {
        if($path != "/connect/token") {
            return ["status" => 400, "error" => "Body cannot be empty"];
        }
    }

    if (strpos($path, "/api/message/send") !== false) {
        if(is_array($body) && count($body) > 10) {
            return ["status" => 400, "error" => "This Endpoint is just for transactional and OTP"];
        }
    }

    $items = [];
    if (is_array($body) && array_keys($body) === range(0, count($body) - 1)) {
        $items = $body;
    } elseif (is_array($body)) {
        $items = [$body];
    }

    foreach ($items as $item) {
        $itemLower = array_change_key_case($item, CASE_LOWER);
        if (!isset($itemLower['destinationaddress'])) continue;
        $destField = $itemLower['destinationaddress'];
        $destList = is_array($destField) ? $destField : [$destField];

        foreach ($destList as $dest) {
            $allowedPatterns = [
                '/^98\d{10}$/',
                '/^\+98\d{10}$/',
                '/^\d{10,15}$/'
            ];
            $valid = false;
            foreach ($allowedPatterns as $pattern) {
                if (preg_match($pattern, $dest)) {
                    $valid = true;
                    break;
                }
            }
            if (!$valid) {
                return ["status" => 400, "error" => "Invalid DestinationAddress format"];
            }
        }
    }

    // GET query validation
    if ($method === "GET") {
        $queryLower = array_change_key_case($_GET, CASE_LOWER);
        if (isset($queryLower['destinationaddress'])) {
            $destField = $queryLower['destinationaddress'];
            $destList = is_array($destField) ? $destField : [$destField];
            foreach ($destList as $dest) {
                $allowedPatterns = [
                    '/^98\d{10}$/',
                    '/^\+98\d{10}$/',
                    '/^\d{10,15}$/'
                ];
                $valid = false;
                foreach ($allowedPatterns as $pattern) {
                    if (preg_match($pattern, $dest)) {
                        $valid = true;
                        break;
                    }
                }
                if (!$valid) {
                    return ["status" => 400, "error" => "Invalid DestinationAddress format"];
                }
            }
        }
    }

    return null;
}


function postProcessResponse($path, $method, $body, $httpCode, $backendResponse) {
    if ($path === "/api/Tools/Ping" && $httpCode === 200) {
        return ["message" => "PONG"];
    }

    return null; 
}



