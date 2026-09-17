<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/audit.php";

requireRole(["admin", "treasurer"]);

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}


$groupId = (int)($_POST["group_id"] ?? 0);
$type = $_POST["type"] ?? "";
$amount = $_POST["amount"] ?? "";
$description = trim($_POST["description"] ?? "");
$transactionDate = $_POST["transaction_date"] ?? "";


if ($groupId <= 0 || $type === "" || $amount === "") {

    echo json_encode([
        "success" => false,
        "message" => "Group, transaction type and amount are required."
    ]);

    exit;
}


if (!is_numeric($amount) || $amount <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Transaction amount must be greater than zero."
    ]);

    exit;
}


$allowedTypes = [
    "contribution",
    "payout",
    "fine",
    "other"
];

if (!in_array($type, $allowedTypes, true)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid transaction type."
    ]);

    exit;
}


try {

    // Check that the Mchezo group exists
$stmt = $pdo->prepare("
    SELECT id, group_name
    FROM mchezo_groups
    WHERE id = ?
    LIMIT 1
");

    $stmt->execute([$groupId]);

$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {

        echo json_encode([
            "success" => false,
            "message" => "Selected Mchezo group does not exist."
        ]);

        exit;
    }


    // Use current date/time if no date was provided
    if (!$transactionDate) {
        $transactionDate = date("Y-m-d H:i:s");
    }


    // Logged-in user
    $recordedBy = $_SESSION["user_id"] ?? null;


    // Insert transaction
    $stmt = $pdo->prepare("
        INSERT INTO transactions
        (
            group_id,
            type,
            amount,
            description,
            transaction_date,
            recorded_by
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([
        $groupId,
        $type,
        $amount,
        $description ?: null,
        $transactionDate,
        $recordedBy
    ]);

    logAudit(
    $_SESSION["user_id"],
    "Record Transaction",
    "Recorded a " . $type .
    " transaction of " . $amount .
    " for Mchezo group \"" . $group["group_name"] .
    "\". Description: " . ($description !== "" ? $description : "None") . "."
);

    echo json_encode([
        "success" => true,
        "message" => "Transaction recorded successfully.",
        "transaction_id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to record transaction."
    ]);
}