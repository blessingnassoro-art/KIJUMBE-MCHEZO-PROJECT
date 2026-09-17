<?php

header("Content-Type: application/json");

// Load AzamPay configuration
$config = require "../config/azampay.php";

// AzamPay Sandbox token endpoint
$url = "https://authenticator-sandbox.azampay.co.tz/AppRegistration/GenerateToken";

// Data required by AzamPay
$data = [
    "appName" => $config["app_name"],
    "clientId" => $config["client_id"],
    "clientSecret" => $config["client_secret"]
];

// Initialize cURL
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Accept: application/json"
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute request
$response = curl_exec($ch);

// Get HTTP status
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check cURL error
if ($response === false) {

    $error = curl_error($ch);

    curl_close($ch);

    echo json_encode([
        "success" => false,
        "message" => "cURL request failed",
        "error" => $error
    ]);

    exit;
}

curl_close($ch);

// Convert response to PHP array
$result = json_decode($response, true);

// Check JSON response
if ($result === null) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON response from AzamPay",
        "http_code" => $httpCode,
        "raw_response" => $response
    ]);

    exit;
}

// Return response for testing
echo json_encode([
    "success" => ($httpCode >= 200 && $httpCode < 300),
    "http_code" => $httpCode,
    "azam_response" => $result
], JSON_PRETTY_PRINT);