<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/audit.php";

requireRole(["admin", "treasurer", "member"]);

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


    $role = currentRole();


    /*
     * ==========================================================
     * INPUT
     * ==========================================================
     */

    $contribution_id =
        intval($_POST["contribution_id"] ?? 0);

    $amount =
        floatval($_POST["amount"] ?? 0);

    $payment_method =
        trim($_POST["payment_method"] ?? "");

    $reference =
        trim($_POST["reference"] ?? "");


    /*
     * ==========================================================
     * VALIDATE CONTRIBUTION ID
     * ==========================================================
     */

    if ($contribution_id <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid contribution ID."
        ]);

        exit;
    }


    /*
     * ==========================================================
     * VALIDATE AMOUNT
     * ==========================================================
     */

    if ($amount <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Payment amount must be greater than zero."
        ]);

        exit;
    }


    /*
     * ==========================================================
     * VALIDATE PAYMENT METHOD
     * ==========================================================
     */

    $allowedMethods = [
        "cash",
        "mobile_money",
        "bank",
        "azampay"
    ];

    if (!in_array($payment_method, $allowedMethods, true)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid payment method."
        ]);

        exit;
    }


    /*
     * ==========================================================
     * GET CONTRIBUTION
     * ==========================================================
     */

    $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.member_id,
            c.round_id,
            c.expected_amount,
            c.paid_amount,
            c.status,
            m.full_name,
            m.group_id

        FROM contributions c

        INNER JOIN members m
            ON c.member_id = m.id

        WHERE c.id = ?

        LIMIT 1
    ");

    $stmt->execute([
        $contribution_id
    ]);

    $contribution =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$contribution) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Contribution not found."
        ]);

        exit;
    }


    /*
     * ==========================================================
     * MEMBER OWNERSHIP CHECK
     * ==========================================================
     *
     * A Member can only pay their own contribution.
     */

    if ($role === "member") {

        requireMemberAccount();

        $memberId =
            currentMemberId();

        if (
            (int) $contribution["member_id"]
            !== $memberId
        ) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" =>
                    "You can only make payments for your own contributions."
            ]);

            exit;
        }
    }


    /*
     * ==========================================================
     * TREASURER GROUP CHECK
     * ==========================================================
     *
     * Treasurer can only record payments
     * belonging to their own group.
     */

    if ($role === "treasurer") {

        requireMemberAccount();

        $groupId =
            getCurrentUserGroupId($pdo);

        if (
            (int) $contribution["group_id"]
            !== $groupId
        ) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" =>
                    "You cannot record payments for another Mchezo group."
            ]);

            exit;
        }
    }


    /*
     * ==========================================================
     * CALCULATE REMAINING AMOUNT
     * ==========================================================
     */

    $remaining =
        floatval($contribution["expected_amount"])
        -
        floatval($contribution["paid_amount"]);


    if ($remaining <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "This contribution has already been fully paid."
        ]);

        exit;
    }


    /*
     * ==========================================================
     * PREVENT OVERPAYMENT
     * ==========================================================
     */

    if ($amount > $remaining) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Payment amount cannot exceed the remaining contribution.",
            "remaining" => $remaining
        ]);

        exit;
    }


    /*
     * ==========================================================
     * START DATABASE TRANSACTION
     * ==========================================================
     */

    $pdo->beginTransaction();


    /*
     * ==========================================================
     * 1. INSERT PAYMENT
     * ==========================================================
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

        VALUES
        (
            ?, ?, ?, ?, ?, 'successful', CURDATE()
        )
    ");

    $stmt->execute([

        $contribution_id,

        $contribution["member_id"],

        $amount,

        $payment_method,

        $reference !== ""
            ? $reference
            : null
    ]);


    /*
     * ==========================================================
     * 2. UPDATE CONTRIBUTION
     * ==========================================================
     */

    $newPaidAmount =
        floatval($contribution["paid_amount"])
        +
        $amount;


    if (
        $newPaidAmount
        >=
        floatval($contribution["expected_amount"])
    ) {

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
     * ==========================================================
     * 3. UPDATE ROUND COLLECTION
     * ==========================================================
     */

    $stmt = $pdo->prepare("
        UPDATE rounds r

        INNER JOIN contributions c
            ON c.round_id = r.id

        SET r.collected_amount = (

            SELECT
                COALESCE(SUM(paid_amount), 0)

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

        currentUserId(),

        "Record Payment",

        "Recorded a "
        . $payment_method
        . " payment of "
        . $amount
        . " for member \""
        . $contribution["full_name"]
        . "\" (Contribution ID: "
        . $contribution_id
        . ")."
    );


    /*
     * ==========================================================
     * RESPONSE
     * ==========================================================
     */

    echo json_encode([

        "success" => true,

        "message" =>
            "Payment recorded successfully.",

        "payment_amount" =>
            $amount,

        "new_paid_amount" =>
            $newPaidAmount,

        "contribution_status" =>
            $newStatus
    ]);


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Add payment error: "
        . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to record payment."
    ]);
}