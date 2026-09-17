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

    /*
    |--------------------------------------------------------------------------
    | Get submitted data
    |--------------------------------------------------------------------------
    */

    $userId = intval($_POST["user_id"] ?? 0);
    $fullName = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $memberId = $_POST["member_id"] ?? "";
    $role = $_POST["role"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | Validate user ID
    |--------------------------------------------------------------------------
    */

    if ($userId <= 0) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid user ID."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Prevent Admin from editing their own account through this endpoint
    |--------------------------------------------------------------------------
    */

    if ($userId === (int) $_SESSION["user_id"]) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "You cannot edit your own account here."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate required fields
    |--------------------------------------------------------------------------
    */

    if ($fullName === "") {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Full name is required."
        ]);

        exit;
    }

    if ($username === "") {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Username is required."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate role
    |--------------------------------------------------------------------------
    */

    $allowedRoles = [
        "admin",
        "treasurer",
        "coordinator",
        "member"
    ];

    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid role selected."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Get existing user
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            member_id,
            full_name,
            username,
            email,
            role,
            status
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);

    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingUser) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "User not found."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Check username uniqueness
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE username = ?
        AND id != ?
        LIMIT 1
    ");

    $stmt->execute([
        $username,
        $userId
    ]);

    if ($stmt->fetch()) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Username is already in use."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Check email uniqueness if provided
    |--------------------------------------------------------------------------
    */

    if ($email !== "") {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Please enter a valid email address."
            ]);

            exit;
        }

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $email,
            $userId
        ]);

        if ($stmt->fetch()) {
            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Email is already in use."
            ]);

            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Admin does not require a member
    |--------------------------------------------------------------------------
    */

    if ($role === "admin") {

        $memberId = null;

    } else {

        if (empty($memberId)) {
            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "A member must be selected for this role."
            ]);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Verify member exists
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT id, full_name
            FROM members
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$memberId]);

        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Selected member was not found."
            ]);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent one member from having multiple accounts
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE member_id = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $memberId,
            $userId
        ]);

        if ($stmt->fetch()) {
            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "This member already has another user account."
            ]);

            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update user
    |--------------------------------------------------------------------------
    */

$stmt = $pdo->prepare("
    UPDATE users
    SET
        member_id = ?,
        full_name = ?,
        username = ?,
        email = ?,
        role = ?
    WHERE id = ?
");

    $stmt->execute([
        $memberId,
        $fullName,
        $username,
        $email !== "" ? $email : null,
        $role,
        $userId
    ]);

    /*
    |--------------------------------------------------------------------------
    | Audit log
    |--------------------------------------------------------------------------
    */

    logAudit(
        $_SESSION["user_id"],
        "Edit User",
        "Updated user account \"" .
        $username .
        "\" (" .
        $fullName .
        "). New role: " .
        $role .
        "."
    );

    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "message" => "User account updated successfully."
    ]);

} catch (PDOException $e) {

    error_log("Update user error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update user account."
    ]);
}