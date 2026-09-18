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
                "message" =>
                    "Access denied. You can only deactivate members in your assigned Mchezo group."
            ]);

            exit;
        }
    }



    if ($member["status"] === "inactive") {

        echo json_encode([
            "success" => false,
            "message" => "Member is already inactive."
        ]);

        exit;
    }




    $pdo->beginTransaction();



    $stmt = $pdo->prepare("
        UPDATE members
        SET status = 'inactive'
        WHERE id = ?
    ");

    $stmt->execute([$id]);


   

    $stmt = $pdo->prepare("
        UPDATE users
        SET status = 'inactive'
        WHERE member_id = ?
        AND status = 'active'
    ");

    $stmt->execute([$id]);

    $deactivatedUsers = $stmt->rowCount();



    $pdo->commit();



    logAudit(
        $_SESSION["user_id"],
        "Deactivate Member",
        "Deactivated member \"" .
        $member["full_name"] .
        "\" (ID: " .
        $id .
        ")."
    );




    if ($deactivatedUsers > 0) {

        logAudit(
            $_SESSION["user_id"],
            "Deactivate User",
            "Automatically deactivated the user account linked to member \"" .
            $member["full_name"] .
            "\" (Member ID: " .
            $id .
            ")."
        );
    }


  

    echo json_encode([
        "success" => true,
        "message" => $deactivatedUsers > 0
            ? "Member and linked user account deactivated successfully."
            : "Member deactivated successfully."
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Deactivate member error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to deactivate member."
    ]);
}