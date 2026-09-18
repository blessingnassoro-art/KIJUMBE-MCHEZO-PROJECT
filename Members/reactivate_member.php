<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/audit.php";

requireRole(["admin", "coordinator"]);

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$id = (int) ($_POST["id"] ?? 0);

if ($id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid member ID."
    ]);

    exit;
}

try {


    $check = $pdo->prepare("
        SELECT
            id,
            full_name,
            group_id,
            status
        FROM members
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([$id]);

    $member = $check->fetch(PDO::FETCH_ASSOC);

    if (!$member) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Member not found."
        ]);

        exit;
    }

 
    if (currentRole() === "coordinator") {

        requireMemberAccount();

        $coordinatorGroupId = getCurrentUserGroupId($pdo);

        if ((int) $member["group_id"] !== $coordinatorGroupId) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" => "Access denied. You can only manage members in your own Mchezo group."
            ]);

            exit;
        }
    }

    /*
     * Check if already active
     */
    if ($member["status"] === "active") {

        echo json_encode([
            "success" => false,
            "message" => "Member is already active."
        ]);

        exit;
    }


    $stmt = $pdo->prepare("
        UPDATE members
        SET status = 'active'
        WHERE id = ?
    ");

    $stmt->execute([$id]);

  
    logAudit(
        $_SESSION["user_id"],
        "Reactivate Member",
        "Reactivated member \"" .
        $member["full_name"] .
        "\" (ID: " .
        $id .
        ")."
    );

    echo json_encode([
        "success" => true,
        "message" => "Member reactivated successfully."
    ]);

} catch (PDOException $e) {

    error_log(
        "Reactivate member error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to reactivate member."
    ]);
}