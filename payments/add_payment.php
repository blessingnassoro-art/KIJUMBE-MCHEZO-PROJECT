<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/audit.php";

requireRole(["admin", "treasurer"]);

header("Content-Type: application/json");

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);

        echo json_encode([
            "success" => false,
            "message" => "Only POST requests are allowed."
        ]);

        exit;
    }

    $contribution_id = intval($_POST["contribution_id"] ?? 0);
    $amount = floatval($_POST["amount"] ?? 0);
    $payment_method = trim($_POST["payment_method"] ?? "");
    $reference = trim($_POST["reference"] ?? "");

    // Validate contribution
    if ($contribution_id <= 0) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid contribution ID."
        ]);

        exit;
    }

    // Validate amount
    if ($amount <= 0) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Payment amount must be greater than zero."
        ]);

        exit;
    }

    // Validate payment method
    $allowedMethods = [
        "cash",
        "mobile_money",
        "bank",
        "azampay"
    ];

    if (!in_array($payment_method, $allowedMethods)) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid payment method."
        ]);

        exit;
    }

    // Get contribution
    $stmt = $pdo->prepare("
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
    ");

    $stmt->execute([$contribution_id]);

    $contribution = $stmt->fetch();

    if (!$contribution) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Contribution not found."
        ]);

        exit;
    }

    // Calculate remaining amount
    $remaining =
        floatval($contribution["expected_amount"])
        - floatval($contribution["paid_amount"]);

    if ($remaining <= 0) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "This contribution has already been fully paid."
        ]);

        exit;
    }

    // Prevent overpayment
    if ($amount > $remaining) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Payment amount cannot exceed the remaining contribution.",
            "remaining" => $remaining
        ]);

        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    /*
     * 1. Insert payment transaction
     */
    $stmt = $pdo->prepare("
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
        VALUES (?, ?, ?, ?, ?, 'successful', CURDATE())
    ");

    $stmt->execute([
        $contribution_id,
        $contribution["member_id"],
        $amount,
        $payment_method,
        $reference !== "" ? $reference : null
    ]);

    /*
     * 2. Update contribution paid amount
     */
    $newPaidAmount =
        floatval($contribution["paid_amount"])
        + $amount;

    if ($newPaidAmount >= floatval($contribution["expected_amount"])) {

        $newStatus = "paid";

    } else {

        $newStatus = "partial";
    }

    $stmt = $pdo->prepare("
        UPDATE contributions
        SET
            paid_amount = ?,
            status = ?,
            paid_date = CURDATE()
        WHERE id = ?
    ");

    $stmt->execute([
        $newPaidAmount,
        $newStatus,
        $contribution_id
    ]);

    /*
     * 3. Recalculate round collected amount
     */
    $stmt = $pdo->prepare("
        UPDATE rounds r
        INNER JOIN contributions c
            ON c.round_id = r.id
        SET r.collected_amount = (
            SELECT COALESCE(SUM(paid_amount), 0)
            FROM contributions
            WHERE round_id = c.round_id
        )
        WHERE c.id = ?
    ");

    $stmt->execute([
        $contribution_id
    ]);

  $pdo->commit();

logAudit(
    $_SESSION["user_id"],
    "Record Payment",
    "Recorded a " . $payment_method .
    " payment of " . $amount .
    " for member \"" . $contribution["full_name"] .
    "\" (Contribution ID: " . $contribution_id . ")."
);

echo json_encode([
        "success" => true,
        "message" => "Payment recorded successfully.",
        "payment_amount" => $amount,
        "new_paid_amount" => $newPaidAmount,
        "contribution_status" => $newStatus
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to record payment."
    ]);
}