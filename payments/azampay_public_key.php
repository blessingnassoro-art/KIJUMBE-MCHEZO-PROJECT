<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/azampay_token.php";


/*
|--------------------------------------------------------------------------
| Generate access token
|--------------------------------------------------------------------------
*/

$tokenResult = getAzamPayToken();

if (
    !isset($tokenResult["success"]) ||
    $tokenResult["success"] !== true
) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to generate AzamPay access token."
    ]);

    exit;
}

$accessToken = $tokenResult["token"];


/*
|--------------------------------------------------------------------------
| AzamPay public key endpoint
|--------------------------------------------------------------------------
*/

$url =
    "https://sandbox.azampay.co.tz/azampay/v1/public-key?format=Pem";


/*
|--------------------------------------------------------------------------
| Request public key
|--------------------------------------------------------------------------
*/

$ch = curl_init($url);

curl_setopt_array($ch, [

    CURLOPT_HTTPGET => true,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_TIMEOUT => 30,

    CURLOPT_HTTPHEADER => [

        "Accept: application/json",

        "Authorization: Bearer " . $accessToken

    ]

]);


$response = curl_exec($ch);

$curlError = curl_error($ch);

$curlInfo = curl_getinfo($ch);

curl_close($ch);


/*
|--------------------------------------------------------------------------
| cURL error
|--------------------------------------------------------------------------
*/

if ($response === false) {

    echo json_encode([

        "success" => false,

        "message" => "Public key request failed.",

        "curl_error" => $curlError,

        "curl_info" => $curlInfo

    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| Empty response
|--------------------------------------------------------------------------
*/

if (trim($response) === "") {

    echo json_encode([

        "success" => false,

        "message" => "AzamPay returned an empty public-key response.",

        "http_code" =>
            $curlInfo["http_code"] ?? 0

    ], JSON_PRETTY_PRINT);

    exit;
}




echo json_encode([

    "success" => true,

    "http_code" =>
        $curlInfo["http_code"] ?? 0,

    "response" => $response

], JSON_PRETTY_PRINT);