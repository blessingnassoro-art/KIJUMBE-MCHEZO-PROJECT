<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/audit.php";

requireRole(["admin"]);

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

    $userId = intval($_POST["user_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    if ($userId <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid user ID."
        ]);

        exit;
    }

    if (!in_array($status, ["active", "inactive"], true)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid status."
        ]);


        exit;
    }

    /*
     * Prevent administrator from changing
     * their own account status.
     */
    if ($userId === (int) $_SESSION["user_id"]) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "You cannot deactivate or reactivate your own account."
        ]);

        exit;
    }

    /*
     * Get the user first.
     */
    $stmt = $pdo->prepare("
    SELECT
        id,
        member_id,
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


    if ($user["status"] === $status) {

        echo json_encode([
            "success" => true,
            "message" => "User is already " . $status . "."
        ]);

        exit;
    }

    /*
 * A non-admin user can only be reactivated
 * if their linked member is active.
 */
if (
    $status === "active" &&
    $user["role"] !== "admin"
) {

    if (empty($user["member_id"])) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "This user is not linked to a member."
        ]);

        exit;
    }

    $memberCheck = $pdo->prepare("
        SELECT status
        FROM members
        WHERE id = ?
        LIMIT 1
    ");

    $memberCheck->execute([
        $user["member_id"]
    ]);

    $memberStatus = $memberCheck->fetchColumn();

    if ($memberStatus !== "active") {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "This user's member account is inactive. " .
                "Reactivate the member first."
        ]);

        exit;
    }
}
    /*
     * Prevent deactivating the last active administrator.
     */
    if (
        $status === "inactive" &&
        $user["role"] === "admin"
    ) {

        $stmt = $pdo->query("
            SELECT COUNT(*) AS active_admins
            FROM users
            WHERE role = 'admin'
            AND status = 'active'
        ");

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ((int) $result["active_admins"] <= 1) {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "The last active Administrator cannot be deactivated."
            ]);

            exit;
        }
    }

    /*
     * Update status.
     */
    $stmt = $pdo->prepare("
        UPDATE users
        SET status = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $status,
        $userId
    ]);

    /*
     * Audit log.
     */
    $action = $status === "active"
        ? "Reactivate User"
        : "Deactivate User";

    $details = $status === "active"
        ? "Reactivated user account \"" .
          $user["username"] .
          "\" (" .
          $user["full_name"] .
          ")."
        : "Deactivated user account \"" .
          $user["username"] .
          "\" (" .
          $user["full_name"] .
          ").";

    logAudit(
        $_SESSION["user_id"],
        $action,
        $details
    );

    echo json_encode([
        "success" => true,
        "message" => $status === "active"
            ? "User account reactivated successfully."
            : "User account deactivated successfully."
    ]);

} catch (PDOException $e) {

    error_log(
        "Update user status error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update user status."
    ]);
}