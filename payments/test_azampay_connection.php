<?php

header("Content-Type: application/json");

$urls = [
    "sandbox" => "https://sandbox.azampay.co.tz/",
    "partners" => "https://sandbox.azampay.co.tz/api/v1/Partner/GetPaymentPartners",
    "public_key" => "https://sandbox.azampay.co.tz/azampay/v1/public-key?format=Pem",
    "authenticator" => "https://authenticator-sandbox.azampay.co.tz/"
];

$results = [];

foreach ($urls as $name => $url) {

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,

        // Force HTTP/1.1
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,

        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,

        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "User-Agent: KIJUMBE/1.0"
        ]
    ]);

    $response = curl_exec($ch);

    $results[$name] = [
        "url" => $url,
        "response" => $response,
        "curl_error" => curl_error($ch),
        "curl_info" => curl_getinfo($ch)
    ];

    curl_close($ch);
}

echo json_encode($results, JSON_PRETTY_PRINT);