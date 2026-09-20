<?php

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/audit.php";

header("Content-Type: application/json");

try {

    requireLogin();

    $userId = currentUserId();

    $fullName = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");

    $currentPassword = $_POST["current_password"] ?? "";
    $newPassword = $_POST["new_password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";


    /*
     * Basic validation
     */

    if ($fullName === "") {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Full name is required."
        ]);

        exit;
    }


    /*
     * Validate email if provided
     */

    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Please enter a valid email address."
        ]);

        exit;
    }


    /*
     * Get current user
     */

    $stmt = $pdo->prepare("
        SELECT
            id,
            member_id,
            full_name,
            email,
            password
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
            "message" => "User account not found."
        ]);

        exit;
    }


    /*
     * Check whether the email is already used
     */

    if ($email !== "") {

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id <> ?
            LIMIT 1
        ");

        $stmt->execute([
            $email,
            $userId
        ]);

        if ($stmt->fetch()) {

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" => "This email is already being used by another account."
            ]);

            exit;
        }
    }


    /*
     * Password change
     */

    $changePassword = false;

    if (
        $currentPassword !== "" ||
        $newPassword !== "" ||
        $confirmPassword !== ""
    ) {

        if ($currentPassword === "") {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Current password is required to change your password."
            ]);

            exit;
        }


        if (!password_verify($currentPassword, $user["password"])) {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Current password is incorrect."
            ]);

            exit;
        }


        if ($newPassword === "") {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "New password is required."
            ]);

            exit;
        }


        if (strlen($newPassword) < 6) {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "New password must be at least 6 characters."
            ]);

            exit;
        }


        if ($newPassword !== $confirmPassword) {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "New passwords do not match."
            ]);

            exit;
        }

        $changePassword = true;
    }


    /*
     * Update user account
     */

    if ($changePassword) {

        $hashedPassword = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                full_name = ?,
                email = ?,
                password = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $fullName,
            $email !== "" ? $email : null,
            $hashedPassword,
            $userId
        ]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                full_name = ?,
                email = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $fullName,
            $email !== "" ? $email : null,
            $userId
        ]);
    }


    /*
     * Update member information if this account
     * is linked to a member.
     */

    if (!empty($user["member_id"])) {

        $stmt = $pdo->prepare("
            UPDATE members
            SET
                full_name = ?,
                phone = ?,
                email = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $fullName,
            $phone !== "" ? $phone : null,
            $email !== "" ? $email : null,
            $user["member_id"]
        ]);
    }


    /*
     * Keep session name updated.
     */

    $_SESSION["full_name"] = $fullName;


    /*
     * Audit
     */

    $details = $changePassword
        ? "Updated profile information and changed password."
        : "Updated profile information.";

    logAudit(
        $userId,
        "Update Profile",
        $details
    );


    echo json_encode([
        "success" => true,
        "message" => $changePassword
            ? "Profile and password updated successfully."
            : "Profile updated successfully."
    ]);

} catch (PDOException $e) {

    error_log("Update profile error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update profile."
    ]);
}