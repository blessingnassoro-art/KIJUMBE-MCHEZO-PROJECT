<?php

/*
|--------------------------------------------------------------------------
| KIJUMBE - AzamPay MNO Checkout
|--------------------------------------------------------------------------
*/

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../config/azampay.php";
require_once __DIR__ . "/../config/azampay_token.php";

session_start();


/*
|--------------------------------------------------------------------------
| 1. Check Login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Please login."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 2. Only POST Requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 3. Load AzamPay Configuration
|--------------------------------------------------------------------------
*/

$config = require __DIR__ . "/../config/azampay.php";


if (
    !isset($config["app_name"]) ||
    !isset($config["client_id"]) ||
    !isset($config["client_secret"])
) {

    echo json_encode([
        "success" => false,
        "message" => "AzamPay configuration is incomplete."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 4. Check API Key
|--------------------------------------------------------------------------
*/

if (
    !isset($config["api_key"]) ||
    trim($config["api_key"]) === ""
) {

    echo json_encode([
        "success" => false,
        "message" => "AzamPay API key is missing from config/azampay.php."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 5. Read JSON Input
|--------------------------------------------------------------------------
*/

$rawInput = file_get_contents("php://input");

$input = json_decode($rawInput, true);


if (!is_array($input)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 6. Get Input
|--------------------------------------------------------------------------
*/

$contribution_id = intval(
    $input["contribution_id"] ?? 0
);

$amount = floatval(
    $input["amount"] ?? 0
);

$accountNumber = trim(
    $input["accountNumber"] ?? ""
);

$provider = trim(
    $input["provider"] ?? ""
);


/*
|--------------------------------------------------------------------------
| 7. Validate Contribution ID
|--------------------------------------------------------------------------
*/

if ($contribution_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid contribution ID."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 8. Validate Amount
|--------------------------------------------------------------------------
*/

if ($amount <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Amount must be greater than zero."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 9. Validate Phone Number
|--------------------------------------------------------------------------
*/

if ($accountNumber === "") {

    echo json_encode([
        "success" => false,
        "message" => "Mobile phone number is required."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 10. Validate Provider
|--------------------------------------------------------------------------
*/

$allowedProviders = [
    "Airtel",
    "Tigo",
    "Halopesa",
    "Azampesa",
    "Mpesa"
];

if (!in_array($provider, $allowedProviders, true)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid payment provider.",
        "allowed_providers" => $allowedProviders
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 11. Find Contribution
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.id,
        c.member_id,
        c.expected_amount,
        c.paid_amount,
        c.status,
        m.full_name

    FROM contributions c

    INNER JOIN members m
        ON c.member_id = m.id

    WHERE c.id = ?

    LIMIT 1
";


try {

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $contribution_id
    ]);

    $contribution = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => "Database error while finding contribution."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 12. Check Contribution
|--------------------------------------------------------------------------
*/

if (!$contribution) {

    echo json_encode([
        "success" => false,
        "message" => "Contribution not found."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 13. Calculate Remaining Amount
|--------------------------------------------------------------------------
*/

$expected = floatval(
    $contribution["expected_amount"]
);

$paid = floatval(
    $contribution["paid_amount"]
);

$remaining = $expected - $paid;


/*
|--------------------------------------------------------------------------
| 14. Check Fully Paid
|--------------------------------------------------------------------------
*/

if ($remaining <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "This contribution is already fully paid."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 15. Prevent Overpayment
|--------------------------------------------------------------------------
*/

if ($amount > $remaining) {

    echo json_encode([
        "success" => false,
        "message" => "Amount exceeds the remaining contribution.",
        "remaining" => $remaining
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 16. Generate External ID
|--------------------------------------------------------------------------
*/

$externalId =
    "KIJUMBE-" .
    $contribution_id .
    "-" .
    time();


/*
|--------------------------------------------------------------------------
| 17. Generate AzamPay Access Token
|--------------------------------------------------------------------------
*/

$tokenResult = getAzamPayToken();


if (
    !isset($tokenResult["success"]) ||
    $tokenResult["success"] !== true
) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to authenticate with AzamPay.",
        "details" => $tokenResult["message"] ?? null
    ], JSON_PRETTY_PRINT);

    exit;
}


$accessToken = $tokenResult["token"];


/*
|--------------------------------------------------------------------------
| 18. AzamPay Checkout URL
|--------------------------------------------------------------------------
*/

$url =
    "https://sandbox.azampay.co.tz/azampay/mno/checkout";


/*
|--------------------------------------------------------------------------
| 19. Prepare Checkout Data
|--------------------------------------------------------------------------
*/

$checkoutData = [

    "accountNumber" => $accountNumber,

    "amount" => number_format(
        $amount,
        2,
        ".",
        ""
    ),

    "currency" => "TZS",

    "externalId" => $externalId,

    "provider" => $provider,

    "additionalProperties" => []

];


$jsonData = json_encode(
    $checkoutData,
    JSON_UNESCAPED_SLASHES
);


if ($jsonData === false) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to encode AzamPay request."
    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 20. Initialize cURL
|--------------------------------------------------------------------------
*/

$ch = curl_init($url);


/*
|--------------------------------------------------------------------------
| 21. Configure cURL
|--------------------------------------------------------------------------
*/

curl_setopt_array($ch, [

    CURLOPT_POST => true,

    CURLOPT_POSTFIELDS => $jsonData,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_HEADER => true,

    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

    CURLOPT_ENCODING => "",

    CURLOPT_CONNECTTIMEOUT => 10,

    CURLOPT_TIMEOUT => 30,

    CURLOPT_HTTPHEADER => [

        "Content-Type: application/json",

        "Accept: application/json",

        "Authorization: Bearer " . $accessToken,

        "X-API-Key: " . $config["api_key"],

        "User-Agent: KIJUMBE/1.0"

    ],

    CURLOPT_SSL_VERIFYPEER => true,

    CURLOPT_SSL_VERIFYHOST => 2

]);


/*
|--------------------------------------------------------------------------
| 22. Execute Request
|--------------------------------------------------------------------------
*/

$response = curl_exec($ch);

$curlError = curl_error($ch);

$curlInfo = curl_getinfo($ch);


/*
|--------------------------------------------------------------------------
| 23. Separate Headers and Body
|--------------------------------------------------------------------------
*/

$headerSize = curl_getinfo(
    $ch,
    CURLINFO_HEADER_SIZE
);

$responseHeaders = "";

$responseBody = "";

if ($response !== false) {

    $responseHeaders =
        substr(
            $response,
            0,
            $headerSize
        );

    $responseBody =
        substr(
            $response,
            $headerSize
        );
}


/*
|--------------------------------------------------------------------------
| 24. Close cURL
|--------------------------------------------------------------------------
*/

curl_close($ch);


/*
|--------------------------------------------------------------------------
| 25. Check cURL Error
|--------------------------------------------------------------------------
*/

if ($response === false) {

    echo json_encode([

        "success" => false,

        "message" =>
            "AzamPay connection failed.",

        "curl_error" =>
            $curlError,

        "http_code" =>
            $curlInfo["http_code"] ?? 0,

        "curl_info" =>
            $curlInfo

    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 26. Check HTTP Status
|--------------------------------------------------------------------------
*/

$httpCode =
    $curlInfo["http_code"] ?? 0;


/*
|--------------------------------------------------------------------------
| 27. Empty Response
|--------------------------------------------------------------------------
*/

if (trim($responseBody) === "") {

    echo json_encode([

        "success" => false,

        "message" =>
            "AzamPay returned an empty response.",

        "http_code" =>
            $httpCode,

        "external_id" =>
            $externalId,

        "request_sent" =>
            $checkoutData,

        "response_headers" =>
            $responseHeaders,

        "curl_info" =>
            $curlInfo

    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 28. Decode AzamPay Response
|--------------------------------------------------------------------------
*/

$azamResponse = json_decode(
    $responseBody,
    true
);


/*
|--------------------------------------------------------------------------
| 29. Check JSON Response
|--------------------------------------------------------------------------
*/

if ($azamResponse === null) {

    echo json_encode([

        "success" => false,

        "message" =>
            "AzamPay returned invalid JSON.",

        "http_code" =>
            $httpCode,

        "raw_response" =>
            $responseBody,

        "response_headers" =>
            $responseHeaders

    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 30. Check AzamPay Success
|--------------------------------------------------------------------------
*/

if (

    $httpCode >= 200 &&

    $httpCode < 300 &&

    isset($azamResponse["success"]) &&

    $azamResponse["success"] === true

) {


    /*
    |--------------------------------------------------------------------------
    | 31. Get Transaction ID
    |--------------------------------------------------------------------------
    */

    $transactionId =
        $azamResponse["transactionId"]
        ?? null;


    /*
    |--------------------------------------------------------------------------
    | 32. Make Sure Transaction ID Exists
    |--------------------------------------------------------------------------
    */

    if (!$transactionId) {

        echo json_encode([

            "success" => false,

            "message" =>
                "AzamPay reported success but did not provide a transaction ID.",

            "azam_response" =>
                $azamResponse

        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | 33. Save Pending Payment
    |--------------------------------------------------------------------------
    */

    $paymentMethod = "azampay";

    $paymentStatus = "pending";

    $paymentDate = date("Y-m-d");


    $insertSql = "

        INSERT INTO payments
        (
            contribution_id,
            member_id,
            amount,
            payment_method,
            reference,
            status,
            payment_date
        )

        VALUES (?, ?, ?, ?, ?, ?, ?)

    ";


    try {

        $insertStmt =
            $pdo->prepare($insertSql);

        $insertStmt->execute([

            $contribution_id,

            $contribution["member_id"],

            $amount,

            $paymentMethod,

            $transactionId,

            $paymentStatus,

            $paymentDate

        ]);

        $paymentId =
            $pdo->lastInsertId();

    } catch (PDOException $e) {

        echo json_encode([

            "success" => false,

            "message" =>
                "AzamPay payment was initiated, but it could not be saved.",

            "transaction_id" =>
                $transactionId

        ], JSON_PRETTY_PRINT);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | 34. Return Success
    |--------------------------------------------------------------------------
    */

    echo json_encode([

        "success" => true,

        "message" =>
            "AzamPay payment initiated successfully.",

        "payment_id" =>
            $paymentId,

        "transaction_id" =>
            $transactionId,

        "external_id" =>
            $externalId,

        "status" =>
            "pending"

    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 35. AzamPay Rejected Request
|--------------------------------------------------------------------------
*/

echo json_encode([

    "success" => false,

    "message" =>
        $azamResponse["message"]
        ?? "AzamPay rejected the payment request.",

    "http_code" =>
        $httpCode,

    "azam_response" =>
        $azamResponse,

    "response_headers" =>
        $responseHeaders

], JSON_PRETTY_PRINT);

exit;