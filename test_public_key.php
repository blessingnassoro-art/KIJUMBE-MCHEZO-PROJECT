<?php

header("Content-Type: application/json");

$config = require __DIR__ . "/../config/azampay.php";
require_once __DIR__ . "/../config/azampay_token.php";

$tokenResult = getAzamPayToken();

if (!$tokenResult["success"]) {
    echo json_encode([
        "stage" => "TOKEN",
        "result" => $tokenResult
    ], JSON_PRETTY_PRINT);

    exit;
}

$token = $tokenResult["token"];

$url = "https://sandbox.azampay.co.tz/azampay/v1/public-key?format=Pem";

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer " . $token,
        "X-API-Key: " . $config["api_key"]
    ],

    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 30,

    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);

$response = curl_exec($ch);

$curlError = curl_error($ch);

$info = curl_getinfo($ch);

curl_close($ch);

echo json_encode([
    "url" => $url,
    "http_code" => $info["http_code"] ?? null,
    "content_type" => $info["content_type"] ?? null,
    "curl_error" => $curlError,
    "response_length" => strlen($response ?: ""),
    "raw_response" => $response
], JSON_PRETTY_PRINT);