<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/azampay.php";
require_once __DIR__ . "/../config/azampay_token.php";

$config = require __DIR__ . "/../config/azampay.php";

$tokenResult = getAzamPayToken();

if (
    !isset($tokenResult["success"]) ||
    $tokenResult["success"] !== true
) {
    echo json_encode([
        "success" => false,
        "stage" => "token",
        "message" => "Could not generate AzamPay access token."
    ], JSON_PRETTY_PRINT);

    exit;
}

$accessToken = $tokenResult["token"];

if (
    !isset($config["api_key"]) ||
    trim($config["api_key"]) === ""
) {
    echo json_encode([
        "success" => false,
        "stage" => "config",
        "message" => "AzamPay API key is missing."
    ], JSON_PRETTY_PRINT);

    exit;
}

$url = "https://sandbox.azampay.co.tz/api/v1/Partner/GetPaymentPartners";

$ch = curl_init($url);

curl_setopt_array($ch, [

    CURLOPT_CUSTOMREQUEST => "GET",

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_HEADER => true,

    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

    CURLOPT_CONNECTTIMEOUT => 10,

    CURLOPT_TIMEOUT => 30,

    CURLOPT_ENCODING => "",

    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer " . $accessToken,
        "X-API-Key: " . $config["api_key"],
        "User-Agent: KIJUMBE/1.0"
    ],

    CURLOPT_SSL_VERIFYPEER => true,

    CURLOPT_SSL_VERIFYHOST => 2
]);

$response = curl_exec($ch);

$curlError = curl_error($ch);

$curlInfo = curl_getinfo($ch);

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

curl_close($ch);

if ($response === false) {

    echo json_encode([
        "success" => false,
        "stage" => "curl",
        "message" => "cURL request failed.",
        "curl_error" => $curlError,
        "curl_info" => $curlInfo
    ], JSON_PRETTY_PRINT);

    exit;
}

$responseHeaders = substr($response, 0, $headerSize);

$responseBody = substr($response, $headerSize);

echo json_encode([
    "success" => true,

    "http_code" => $curlInfo["http_code"] ?? 0,

    "content_type" => $curlInfo["content_type"] ?? null,

    "response_headers" => $responseHeaders,

    "response_body_length" => strlen($responseBody),

    "response_body" => $responseBody,

    "curl_info" => $curlInfo

], JSON_PRETTY_PRINT);