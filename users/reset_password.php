<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/audit.php";

requireRole(["admin"]);

header("Content-Type: application/json");

try {

    /*
     * Only POST requests are allowed.
     */
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        http_response_code(405);

        echo json_encode([
            "success" => false,
            "message" => "Only POST requests are allowed."
        ]);

        exit;
    }


    /*
     * Get user ID.
     */
    $userId = intval($_POST["user_id"] ?? 0);


    if ($userId <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid user ID."
        ]);

        exit;
    }


    /*
     * Prevent Admin from resetting
     * their own password here.
     */
    if ($userId === (int) $_SESSION["user_id"]) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "You cannot reset your own password here."
        ]);

        exit;
    }


    /*
     * Find user.
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            full_name,
            username,
            role,
            status
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$user) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "User not found."
        ]);

        exit;
    }


    /*
     * Generate a new temporary password.
     *
     * Example:
     * Kijumbe@4821
     */
    $temporaryPassword =
        "Kijumbe@" .
        random_int(1000, 9999);


    /*
     * Hash password before storing.
     */
    $passwordHash = password_hash(
        $temporaryPassword,
        PASSWORD_DEFAULT
    );


    /*
     * Update password.
     */
    $stmt = $pdo->prepare("
        UPDATE users
        SET password = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $passwordHash,
        $userId
    ]);


    /*
     * Audit log.
     *
     * IMPORTANT:
     * Never put the actual password
     * into the audit log.
     */
    logAudit(
        $_SESSION["user_id"],
        "Reset User Password",
        "Reset password for user account \"" .
        $user["username"] .
        "\" (" .
        $user["full_name"] .
        ")."
    );


    /*
     * Return temporary password once.
     */
    echo json_encode([
        "success" => true,
        "message" =>
            "Password reset successfully.",
        "credentials" => [
            "full_name" => $user["full_name"],
            "username" => $user["username"],
            "password" => $temporaryPassword
        ]
    ]);

} catch (PDOException $e) {

    error_log(
        "Reset password error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to reset password."
    ]);
}