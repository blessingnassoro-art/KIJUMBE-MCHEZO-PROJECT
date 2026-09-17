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

$fineId = (int)($_POST["fine_id"] ?? 0);

if ($fineId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Fine ID is required."
    ]);

    exit;
}

try {

    // Check that the fine exists
$stmt = $pdo->prepare("
    SELECT
        f.id,
        f.status,
        f.amount,
        m.full_name
    FROM fines f
    INNER JOIN members m
        ON f.member_id = m.id
    WHERE f.id = ?
    LIMIT 1
");

    $stmt->execute([$fineId]);

    $fine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fine) {
        echo json_encode([
            "success" => false,
            "message" => "Fine not found."
        ]);

        exit;
    }

    // Prevent paying an already-paid fine
    if ($fine["status"] === "paid") {
        echo json_encode([
            "success" => false,
            "message" => "This fine has already been paid."
        ]);

        exit;
    }

    // Mark fine as paid
    $stmt = $pdo->prepare("
        UPDATE fines
        SET
            status = 'paid',
            paid_date = CURDATE()
        WHERE id = ?
    ");

$stmt->execute([$fineId]);

logAudit(
    $_SESSION["user_id"],
    "Pay Fine",
    "Marked fine of " . $fine["amount"] .
    " for member \"" . $fine["full_name"] .
    "\" as paid (Fine ID: " . $fineId . ")."
);

echo json_encode([
        "success" => true,
        "message" => "Fine marked as paid successfully."
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to mark fine as paid."
    ]);
}