<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../config/azampay_token.php";


/*
|--------------------------------------------------------------------------
| Helper: Return JSON and stop
|--------------------------------------------------------------------------
*/

function respond($success, $message, $extra = [])
{
    echo json_encode(
        array_merge([
            "success" => $success,
            "message" => $message
        ], $extra),
        JSON_PRETTY_PRINT
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| 1. Only POST requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    respond(false, "Only POST requests are allowed.");
}


/*
|--------------------------------------------------------------------------
| 2. Read callback body
|--------------------------------------------------------------------------
*/

$rawBody = file_get_contents("php://input");

if (trim($rawBody) === "") {

    respond(false, "Empty callback body.");
}


/*
|--------------------------------------------------------------------------
| 3. Decode JSON
|--------------------------------------------------------------------------
*/

$data = json_decode($rawBody, true);

if (!is_array($data)) {

    respond(false, "Invalid JSON callback.");
}


/*
|--------------------------------------------------------------------------
| 4. Extract callback fields
|--------------------------------------------------------------------------
*/

$transactionStatus = trim(
    $data["transactionstatus"] ?? ""
);

$reference = trim(
    $data["reference"] ?? ""
);

$externalReference = trim(
    $data["externalreference"] ?? ""
);

$utilityRef = trim(
    $data["utilityref"] ?? ""
);

$amount = floatval(
    $data["amount"] ?? 0
);

$transactionId = trim(
    $data["transid"] ?? ""
);

$operator = trim(
    $data["operator"] ?? ""
);

$msisdn = trim(
    $data["msisdn"] ?? ""
);

$mnoReference = trim(
    $data["mnoreference"] ?? ""
);

$signature = trim(
    $data["signature"] ?? ""
);


/*
|--------------------------------------------------------------------------
| 5. Validate required callback information
|--------------------------------------------------------------------------
*/

if (
    $transactionStatus === "" ||
    $transactionId === "" ||
    $signature === ""
) {

    respond(false, "Required callback information is missing.");
}


/*
|--------------------------------------------------------------------------
| 6. Verify callback signature
|--------------------------------------------------------------------------
|
| AzamPay specification:
|
| utilityref + externalreference +
| transactionstatus + operator
|
| Signature algorithm:
| SHA256withRSA / PKCS#1 v1.5
|
|--------------------------------------------------------------------------
*/


function verifyCallbackSignature(
    $utilityRef,
    $externalReference,
    $transactionStatus,
    $operator,
    $signatureBase64,
    $publicKeyPem
) {

    $dataToVerify =
        $utilityRef .
        $externalReference .
        $transactionStatus .
        $operator;


    $signatureBytes = base64_decode(
        $signatureBase64,
        true
    );


    if ($signatureBytes === false) {
        return false;
    }


    $publicKey = openssl_pkey_get_public(
        $publicKeyPem
    );


    if ($publicKey === false) {
        return false;
    }


    $result = openssl_verify(
        $dataToVerify,
        $signatureBytes,
        $publicKey,
        OPENSSL_ALGO_SHA256
    );


    return $result === 1;
}


/*
|--------------------------------------------------------------------------
| 7. Get AzamPay public key
|--------------------------------------------------------------------------
*/

function getAzamPayPublicKey()
{
    $config = require __DIR__ . "/../config/azampay.php";

    /*
    |--------------------------------------------------------------------------
    | Get AzamPay access token
    |--------------------------------------------------------------------------
    */

    $tokenResult = getAzamPayToken();

    if (
        !isset($tokenResult["success"]) ||
        $tokenResult["success"] !== true
    ) {
        return [
            "success" => false,
            "message" => "Unable to obtain AzamPay access token.",
            "token_response" => $tokenResult
        ];
    }

    $accessToken = $tokenResult["token"];

    /*
    |--------------------------------------------------------------------------
    | AzamPay public key endpoint
    |--------------------------------------------------------------------------
    */

    $url =
        "https://sandbox.azampay.co.tz/azampay/v1/public-key?format=Pem";

    $ch = curl_init($url);

    curl_setopt_array($ch, [

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [

            "Content-Type: application/json",

            "Accept: application/json",

            "Authorization: Bearer " . $accessToken,

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

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    /*
    |--------------------------------------------------------------------------
    | Connection failure
    |--------------------------------------------------------------------------
    */

    if ($response === false) {

        return [
            "success" => false,
            "message" => "Failed to connect to AzamPay public key endpoint.",
            "curl_error" => $curlError,
            "http_code" => $httpCode
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Empty response
    |--------------------------------------------------------------------------
    */

    if (trim($response) === "") {

        return [
            "success" => false,
            "message" => "AzamPay public key endpoint returned an empty response.",
            "http_code" => $httpCode
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Decode response
    |--------------------------------------------------------------------------
    */

    $result = json_decode(
        $response,
        true
    );

    if (!is_array($result)) {

        return [
            "success" => false,
            "message" => "AzamPay returned invalid JSON.",
            "http_code" => $httpCode,
            "raw_response" => $response
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Check public key
    |--------------------------------------------------------------------------
    */

    if (
        !isset($result["publicKey"]) ||
        trim($result["publicKey"]) === ""
    ) {

        return [
            "success" => false,
            "message" => "AzamPay response did not contain a public key.",
            "http_code" => $httpCode,
            "azam_response" => $result
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    return [
        "success" => true,
        "publicKey" => $result["publicKey"],
        "http_code" => $httpCode
    ];
}


/*
|--------------------------------------------------------------------------
| 8. Get public key
|--------------------------------------------------------------------------
*/

$publicKeyResult = getAzamPayPublicKey();


if (
    !isset($publicKeyResult["success"]) ||
    $publicKeyResult["success"] !== true
) {

    respond(
        false,
        "Unable to obtain AzamPay public key.",
        [
            "details" => $publicKeyResult
        ]
    );
}


$publicKeyPem =
    $publicKeyResult["publicKey"];


/*
|--------------------------------------------------------------------------
| 9. Verify signature
|--------------------------------------------------------------------------
*/

$isValidSignature = verifyCallbackSignature(

    $utilityRef,

    $externalReference,

    $transactionStatus,

    $operator,

    $signature,

    $publicKeyPem
);


if (!$isValidSignature) {

    respond(
        false,
        "Invalid AzamPay callback signature."
    );
}


/*
|--------------------------------------------------------------------------
| 10. Find the payment
|--------------------------------------------------------------------------
|
| Our checkout stores AzamPay transactionId
| in payments.reference.
|
|--------------------------------------------------------------------------
*/

try {

    $sql = "
        SELECT
            p.id,
            p.contribution_id,
            p.member_id,
            p.amount,
            p.payment_method,
            p.reference,
            p.status,
            c.round_id,
            c.expected_amount,
            c.paid_amount,
            c.status AS contribution_status,
            m.group_id,
            m.full_name
        FROM payments p

        INNER JOIN contributions c
            ON p.contribution_id = c.id

        INNER JOIN members m
            ON p.member_id = m.id

        WHERE p.reference = ?

        LIMIT 1
    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $transactionId
    ]);


    $payment = $stmt->fetch(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    respond(
        false,
        "Database error while finding payment."
    );
}


/*
|--------------------------------------------------------------------------
| 11. Payment not found
|--------------------------------------------------------------------------
*/

if (!$payment) {

    respond(
        false,
        "Payment record not found.",
        [
            "transaction_id" => $transactionId
        ]
    );
}


/*
|--------------------------------------------------------------------------
| 12. Prevent duplicate processing
|--------------------------------------------------------------------------
*/

if ($payment["status"] === "successful") {

    respond(
        true,
        "Payment has already been processed.",
        [
            "payment_id" => $payment["id"],
            "transaction_id" => $transactionId,
            "status" => "successful"
        ]
    );
}


/*
|--------------------------------------------------------------------------
| 13. Determine payment result
|--------------------------------------------------------------------------
*/

$status = strtolower(
    $transactionStatus
);


$isSuccessful = (
    $status === "success" ||
    $status === "successful"
);


/*
|--------------------------------------------------------------------------
| 14. FAILED PAYMENT
|--------------------------------------------------------------------------
*/

if (!$isSuccessful) {

    try {

        $updateSql = "
            UPDATE payments

            SET
                status = 'failed'

            WHERE id = ?
        ";


        $updateStmt = $pdo->prepare(
            $updateSql
        );


        $updateStmt->execute([
            $payment["id"]
        ]);


    } catch (PDOException $e) {

        respond(
            false,
            "Payment failed, but database update could not be completed."
        );
    }


    respond(
        true,
        "AzamPay payment failed.",
        [
            "payment_id" => $payment["id"],
            "transaction_id" => $transactionId,
            "status" => "failed"
        ]
    );
}


/*
|--------------------------------------------------------------------------
| 15. SUCCESSFUL PAYMENT
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Start database transaction
    |--------------------------------------------------------------------------
    */

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | 16. Update payment
    |--------------------------------------------------------------------------
    */

    $updatePaymentSql = "
        UPDATE payments

        SET
            status = 'successful',
            reference = ?

        WHERE id = ?
    ";


    $updatePaymentStmt = $pdo->prepare(
        $updatePaymentSql
    );


    $updatePaymentStmt->execute([

        $transactionId,

        $payment["id"]

    ]);


    /*
    |--------------------------------------------------------------------------
    | 17. Calculate new contribution amount
    |--------------------------------------------------------------------------
    */

    $newPaidAmount =
        floatval($payment["paid_amount"]) +
        floatval($payment["amount"]);


    $expectedAmount =
        floatval($payment["expected_amount"]);


    /*
    |--------------------------------------------------------------------------
    | 18. Determine contribution status
    |--------------------------------------------------------------------------
    */

    if ($newPaidAmount >= $expectedAmount) {

        $newContributionStatus = "paid";

    } else {

        $newContributionStatus = "partial";
    }


    /*
    |--------------------------------------------------------------------------
    | 19. Update contribution
    |--------------------------------------------------------------------------
    */

    $updateContributionSql = "
        UPDATE contributions

        SET
            paid_amount = ?,
            status = ?,
            paid_date = CURDATE()

        WHERE id = ?
    ";


    $updateContributionStmt = $pdo->prepare(
        $updateContributionSql
    );


    $updateContributionStmt->execute([

        $newPaidAmount,

        $newContributionStatus,

        $payment["contribution_id"]

    ]);


    /*
    |--------------------------------------------------------------------------
    | 20. Create financial transaction
    |--------------------------------------------------------------------------
    */

    $transactionDescription =
        "AzamPay contribution payment - " .
        "Transaction ID: " .
        $transactionId;


    $insertTransactionSql = "
        INSERT INTO transactions
        (
            group_id,
            type,
            amount,
            description,
            transaction_date,
            recorded_by
        )

        VALUES (?, 'contribution', ?, ?, NOW(), NULL)
    ";


    $insertTransactionStmt = $pdo->prepare(
        $insertTransactionSql
    );


    $insertTransactionStmt->execute([

        $payment["group_id"],

        $payment["amount"],

        $transactionDescription

    ]);


    /*
    |--------------------------------------------------------------------------
    | 21. Create audit log
    |--------------------------------------------------------------------------
    */

    $auditDetails =
        "AzamPay payment successful. " .
        "Payment ID: " .
        $payment["id"] .
        ", Amount: " .
        $payment["amount"] .
        ", Transaction ID: " .
        $transactionId;


    $auditSql = "
        INSERT INTO audit_logs
        (
            user_id,
            action,
            details
        )

        VALUES (NULL, ?, ?)
    ";


    $auditStmt = $pdo->prepare(
        $auditSql
    );


    $auditStmt->execute([

        "AzamPay Payment Successful",

        $auditDetails

    ]);


    /*
    |--------------------------------------------------------------------------
    | 22. Commit everything
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


} catch (PDOException $e) {


    /*
    |--------------------------------------------------------------------------
    | Rollback if something failed
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    respond(
        false,
        "Database transaction failed.",
        [
            "error" => $e->getMessage()
        ]
    );
}


/*
|--------------------------------------------------------------------------
| 23. Return final success
|--------------------------------------------------------------------------
*/

respond(
    true,
    "AzamPay payment completed successfully.",
    [
        "payment_id" => $payment["id"],
        "transaction_id" => $transactionId,
        "amount" => $payment["amount"],
        "payment_status" => "successful",
        "contribution_status" => $newContributionStatus,
        "paid_amount" => $newPaidAmount
    ]
);