<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "treasurer"]);

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$contributionId = (int) ($_POST["contribution_id"] ?? 0);
$amount = $_POST["amount"] ?? "";

if ($contributionId <= 0 || $amount === "") {

    echo json_encode([
        "success" => false,
        "message" => "Contribution and amount are required."
    ]);

    exit;
}

if (!is_numeric($amount) || $amount <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Amount must be greater than zero."
    ]);

    exit;
}

$amount = (float) $amount;

try {

    /*
     * Get the existing contribution.
     */
    $stmt = $pdo->prepare(
        "SELECT
            id,
            round_id,
            expected_amount,
            paid_amount,
            status
         FROM contributions
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([$contributionId]);

    $contribution = $stmt->fetch();

    if (!$contribution) {

        echo json_encode([
            "success" => false,
            "message" => "Contribution record not found."
        ]);

        exit;
    }


    /*
     * Calculate the remaining amount.
     */
    $remaining =
        (float) $contribution["expected_amount"]
        -
        (float) $contribution["paid_amount"];


    /*
     * Do not allow payment above the expected amount.
     */
    if ($amount > $remaining) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Amount exceeds the remaining contribution of TSh "
                . number_format($remaining, 2)
        ]);

        exit;
    }


    /*
     * Calculate new paid amount.
     */
    $newPaidAmount =
        (float) $contribution["paid_amount"]
        + $amount;


    /*
     * Determine the new status.
     */
    if ($newPaidAmount >= (float) $contribution["expected_amount"]) {

        $status = "paid";

    } else {

        $status = "partial";
    }


    /*
     * Update the contribution.
     */
    $update = $pdo->prepare(
        "UPDATE contributions
         SET paid_amount = ?,
             status = ?,
             paid_date = CURDATE()
         WHERE id = ?"
    );

    $update->execute([
        $newPaidAmount,
        $status,
        $contributionId
    ]);


    /*
     * Recalculate the round's total collected amount.
     */
    $roundStmt = $pdo->prepare(
        "SELECT COALESCE(SUM(paid_amount), 0) AS total_collected
         FROM contributions
         WHERE round_id = ?"
    );

    $roundStmt->execute([
        $contribution["round_id"]
    ]);

    $roundData = $roundStmt->fetch();

    $totalCollected =
        (float) $roundData["total_collected"];


    /*
     * Update the round.
     */
    $roundUpdate = $pdo->prepare(
        "UPDATE rounds
         SET collected_amount = ?
         WHERE id = ?"
    );

    $roundUpdate->execute([
        $totalCollected,
        $contribution["round_id"]
    ]);


    echo json_encode([
        "success" => true,
        "message" => "Contribution recorded successfully."
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to record contribution."
    ]);
}