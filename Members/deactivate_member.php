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

    /*
     * Get the member.
     */
    $check = $pdo->prepare("
        SELECT
            id,
            full_name,
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

    /*
     * Check whether the member is already inactive.
     */
    if ($member["status"] === "inactive") {

        echo json_encode([
            "success" => false,
            "message" => "Member is already inactive."
        ]);

        exit;
    }

    /*
     * Start transaction.
     */
    $pdo->beginTransaction();


    /*
     * 1. Deactivate the member.
     */
    $stmt = $pdo->prepare("
        UPDATE members
        SET status = 'inactive'
        WHERE id = ?
    ");

    $stmt->execute([$id]);


    /*
     * 2. Deactivate any user account
     *    linked to this member.
     */
    $stmt = $pdo->prepare("
        UPDATE users
        SET status = 'inactive'
        WHERE member_id = ?
        AND status = 'active'
    ");

    $stmt->execute([$id]);

    $deactivatedUsers = $stmt->rowCount();


    /*
     * Commit both changes.
     */
    $pdo->commit();


    /*
     * Audit: Member deactivated.
     */
    logAudit(
        $_SESSION["user_id"],
        "Deactivate Member",
        "Deactivated member \"" .
        $member["full_name"] .
        "\" (ID: " .
        $id .
        ")."
    );


    /*
     * Audit: Linked user deactivated.
     */
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

    /*
     * Roll back if something failed.
     */
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