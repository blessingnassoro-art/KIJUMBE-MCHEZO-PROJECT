<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../config/azampay_token.php";

session_start();


/*
|--------------------------------------------------------------------------
| 1. Check login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized. Please login."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| 2. Only POST requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| 3. Read JSON input
|--------------------------------------------------------------------------
*/

$input = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($input)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| 4. Get input
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
| 5. Validate contribution ID
|--------------------------------------------------------------------------
*/

if ($contribution_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid contribution ID."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| 6. Validate amount
|--------------------------------------------------------------------------
*/

if ($amount <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Amount must be greater than zero."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| 7. Validate phone number
|--------------------------------------------------------------------------
*/

if ($accountNumber === "") {

    echo json_encode([
        "success" => false,
        "message" => "Mobile phone number is required."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| 8. Validate provider
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
        "message" => "Invalid payment provider."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| 9. Find contribution
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

    $contribution = $stmt->fetch();

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => "Database error while finding contribution.",
        "error" => $e->getMessage()
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| 10. Check contribution
|--------------------------------------------------------------------------
*/

if (!$contribution) {

    echo json_encode([
        "success" => false,
        "message" => "Contribution not found."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| 11. Calculate remaining amount
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
| 12. Check fully paid
|--------------------------------------------------------------------------
*/

if ($remaining <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "This contribution is already fully paid."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| 13. Prevent overpayment
|--------------------------------------------------------------------------
*/

if ($amount > $remaining) {

    echo json_encode([
        "success" => false,
        "message" => "Amount exceeds the remaining contribution.",
        "remaining" => $remaining
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| 14. Generate external ID
|--------------------------------------------------------------------------
*/

$externalId =
    "KIJUMBE-" .
    $contribution_id .
    "-" .
    time();


/*
|--------------------------------------------------------------------------
| 15. Generate AzamPay access token
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
    ]);

    exit;
}


$accessToken = $tokenResult["token"];


/*
|--------------------------------------------------------------------------
| 16. AzamPay Checkout URL
|--------------------------------------------------------------------------
*/

$url =
    "https://sandbox.azampay.co.tz/azampay/mno/checkout";


/*
|--------------------------------------------------------------------------
| 17. Prepare checkout data
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

    "provider" => $provider

];


/*
|--------------------------------------------------------------------------
| 18. Initialize cURL
|--------------------------------------------------------------------------
*/

$ch = curl_init($url);


/*
|--------------------------------------------------------------------------
| 19. cURL options
|--------------------------------------------------------------------------
*/

curl_setopt_array($ch, [

    CURLOPT_POST => true,

    CURLOPT_POSTFIELDS =>
        json_encode($checkoutData),

    CURLOPT_HTTPHEADER => [

        "Content-Type: application/json",

        "Accept: application/json",

        "Authorization: Bearer " . $accessToken

    ],

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_TIMEOUT => 30,

    CURLOPT_CONNECTTIMEOUT => 10,

    CURLOPT_SSL_VERIFYPEER => true,

    CURLOPT_SSL_VERIFYHOST => 2

]);


/*
|--------------------------------------------------------------------------
| 20. Execute request
|--------------------------------------------------------------------------
*/

$response = curl_exec($ch);

$curlError = curl_error($ch);

$curlInfo = curl_getinfo($ch);

curl_close($ch);


/*
|--------------------------------------------------------------------------
| 21. Check cURL error
|--------------------------------------------------------------------------
*/

if ($response === false) {

    echo json_encode([

        "success" => false,

        "message" =>
            "AzamPay connection failed.",

        "curl_error" => $curlError,

        "http_code" =>
            $curlInfo["http_code"] ?? 0,

        "curl_info" => $curlInfo

    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 22. Check empty response
|--------------------------------------------------------------------------
*/

if (trim($response) === "") {

    echo json_encode([

        "success" => false,

        "message" =>
            "AzamPay returned an empty response.",

        "http_code" =>
            $curlInfo["http_code"] ?? 0,

        "curl_info" => $curlInfo

    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 23. Decode response
|--------------------------------------------------------------------------
*/

$azamResponse = json_decode(
    $response,
    true
);


if ($azamResponse === null) {

    echo json_encode([

        "success" => false,

        "message" =>
            "AzamPay returned invalid JSON.",

        "http_code" =>
            $curlInfo["http_code"] ?? 0,

        "raw_response" => $response

    ], JSON_PRETTY_PRINT);

    exit;
}


/*
|--------------------------------------------------------------------------
| 24. Check AzamPay result
|--------------------------------------------------------------------------
*/

if (
    ($curlInfo["http_code"] ?? 0) >= 200 &&
    ($curlInfo["http_code"] ?? 0) < 300 &&
    isset($azamResponse["success"]) &&
    $azamResponse["success"] === true
) {


    /*
    |--------------------------------------------------------------------------
    | Get transaction ID
    |--------------------------------------------------------------------------
    */

    $transactionId =
        $azamResponse["transactionId"]
        ?? null;


    /*
    |--------------------------------------------------------------------------
    | 25. Save pending payment
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

        $insertStmt = $pdo->prepare(
            $insertSql
        );

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

            "error" =>
                $e->getMessage(),

            "transaction_id" =>
                $transactionId

        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | 26. Return success
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
| 27. AzamPay rejected request
|--------------------------------------------------------------------------
*/

echo json_encode([

    "success" => false,

    "message" =>
        $azamResponse["message"]
        ?? "AzamPay rejected the payment request.",

    "http_code" =>
        $curlInfo["http_code"] ?? 0,

    "azam_response" =>
        $azamResponse

], JSON_PRETTY_PRINT);