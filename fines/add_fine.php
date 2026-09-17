<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/audit.php";

requireRole(["admin", "coordinator"]);

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

$memberId = (int)($_POST["member_id"] ?? 0);
$meetingId = (int)($_POST["meeting_id"] ?? 0);
$amount = $_POST["amount"] ?? "";
$reason = trim($_POST["reason"] ?? "");


if ($memberId <= 0 || $amount === "" || $reason === "") {

    echo json_encode([
        "success" => false,
        "message" => "Member, amount and reason are required."
    ]);

    exit;
}


if (!is_numeric($amount) || $amount <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Fine amount must be greater than zero."
    ]);

    exit;
}


try {

    // Make sure member exists
$stmt = $pdo->prepare("
    SELECT id, full_name
    FROM members
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$memberId]);

$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {

        echo json_encode([
            "success" => false,
            "message" => "Selected member does not exist."
        ]);

        exit;
    }


    // Meeting is optional
    if ($meetingId > 0) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM meetings
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$meetingId]);

        if (!$stmt->fetch()) {

            echo json_encode([
                "success" => false,
                "message" => "Selected meeting does not exist."
            ]);

            exit;
        }

    } else {

        $meetingId = null;
    }


    // Create the fine
    $stmt = $pdo->prepare("
        INSERT INTO fines
        (
            member_id,
            meeting_id,
            amount,
            reason,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'unpaid'
        )
    ");

    $stmt->execute([
        $memberId,
        $meetingId,
        $amount,
        $reason
    ]);

logAudit(
    $_SESSION["user_id"],
    "Record Fine",
    "Recorded a fine of " . $amount .
    " for member \"" . $member["full_name"] .
    "\". Reason: " . $reason . "."
);

    echo json_encode([
        "success" => true,
        "message" => "Fine recorded successfully.",
        "fine_id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to record fine."
    ]);
}