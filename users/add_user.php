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

    $fullName = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $memberId = $_POST["member_id"] ?? "";
    $role = $_POST["role"] ?? "";

    /*
     * Validate full name
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
     * Validate role
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
     * Validate optional email
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
            LIMIT 1
        ");

        $stmt->execute([$email]);

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
     * ADMIN
     *
     * Admin accounts do not require
     * a member.
     */
    if ($role === "admin") {

        $memberId = null;

    } else {

        /*
         * Treasurer, Coordinator and Member
         * must be linked to a member.
         */
        if (empty($memberId)) {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "A member must be selected for this role."
            ]);

            exit;
        }

        /*
         * Check that member exists.
         */
        $stmt = $pdo->prepare("
            SELECT
                id,
                full_name
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
         * One member can have only one
         * user account.
         */
        $stmt = $pdo->prepare("
            SELECT
                id,
                username,
                status
            FROM users
            WHERE member_id = ?
            LIMIT 1
        ");

        $stmt->execute([$memberId]);

        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "This member already has a user account."
            ]);

            exit;
        }
    }

    /*
     * ------------------------------------------------
     * GENERATE USERNAME
     * ------------------------------------------------
     *
     * Example:
     *
     * John Peter
     *      ↓
     * john01
     *
     * Mary Joseph
     *      ↓
     * mary01
     */

    $nameSource = $role === "admin"
        ? $fullName
        : ($member["full_name"] ?? $fullName);

    $nameSource = strtolower($nameSource);

    /*
     * Remove anything that is not a letter.
     */
    $nameSource = preg_replace(
        "/[^a-z]/",
        "",
        $nameSource
    );

    /*
     * Use first 8 characters.
     */
    $baseUsername = substr($nameSource, 0, 8);

    /*
     * Fallback in case name contains
     * no usable letters.
     */
    if ($baseUsername === "") {
        $baseUsername = "user";
    }

    /*
     * Find an available username.
     *
     * john01
     * john02
     * john03
     * ...
     */
    $username = "";

    for ($number = 1; $number <= 9999; $number++) {

        $candidate = $baseUsername .
            str_pad(
                $number,
                2,
                "0",
                STR_PAD_LEFT
            );

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->execute([$candidate]);

        if (!$stmt->fetch()) {

            $username = $candidate;

            break;
        }
    }

    if ($username === "") {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to generate a unique username."
        ]);

        exit;
    }

    /*
     * ------------------------------------------------
     * GENERATE PASSWORD
     * ------------------------------------------------
     *
     * Example:
     *
     * Kijumbe@4821
     */

    $password = "Kijumbe@" .
        random_int(1000, 9999);

    /*
     * Hash password before storing.
     */
    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    /*
     * ------------------------------------------------
     * INSERT USER
     * ------------------------------------------------
     */

    $stmt = $pdo->prepare("
        INSERT INTO users
        (
            member_id,
            full_name,
            username,
            email,
            password,
            role,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ");

    $stmt->execute([
        $memberId,
        $fullName,
        $username,
        $email !== "" ? $email : null,
        $passwordHash,
        $role
    ]);

    $newUserId = $pdo->lastInsertId();

    /*
     * ------------------------------------------------
     * AUDIT LOG
     * ------------------------------------------------
     */

    logAudit(
        $_SESSION["user_id"],
        "Create User",
        "Created user account \"" .
        $username .
        "\" (" .
        $fullName .
        "). Role: " .
        $role .
        "."
    );

    /*
     * ------------------------------------------------
     * RESPONSE
     * ------------------------------------------------
     *
     * Password is returned ONLY at account creation
     * so the Administrator can give it to the user.
     */

    echo json_encode([
        "success" => true,
        "message" => "User account created successfully.",
        "credentials" => [
            "user_id" => $newUserId,
            "full_name" => $fullName,
            "username" => $username,
            "password" => $password
        ]
    ]);

} catch (PDOException $e) {

    error_log(
        "Add user error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to create user account."
    ]);
}