<?php

function getAzamPayToken()
{
    $config = require __DIR__ . "/azampay.php";

    $url = "https://authenticator-sandbox.azampay.co.tz/AppRegistration/GenerateToken";

    $data = [
        "appName" => $config["app_name"],
        "clientId" => $config["client_id"],
        "clientSecret" => $config["client_secret"]
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        json_encode($data)
    );

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Accept: application/json"
    ]);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {

        $error = curl_error($ch);

        curl_close($ch);

        return [
            "success" => false,
            "message" => "cURL error",
            "error" => $error
        ];
    }

    curl_close($ch);

    $result = json_decode($response, true);

    if ($result === null) {

        return [
            "success" => false,
            "message" => "Invalid JSON response",
            "http_code" => $httpCode
        ];
    }

    if (
        isset($result["success"]) &&
        $result["success"] === true &&
        isset($result["data"]["accessToken"])
    ) {

        return [
            "success" => true,
            "token" => $result["data"]["accessToken"]
        ];
    }

    return [
        "success" => false,
        "message" => $result["message"] ?? "Token generation failed",
        "response" => $result
    ];
}