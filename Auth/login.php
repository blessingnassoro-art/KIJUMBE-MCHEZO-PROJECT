<?php

require_once "../includes/db.php";

session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        die("Username and password are required.");
    }

    $sql = "SELECT 
                id,
                member_id,
                full_name,
                username,
                email,
                password,
                role,
                status
            FROM users
            WHERE username = ?
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);

    $user = $stmt->fetch();

    if (!$user) {
        die("Invalid username or password.");
    }

    if ($user["status"] !== "active") {
        die("Your account is inactive.");
    }

    if (!password_verify($password, $user["password"])) {
        die("Invalid username or password.");
    }

    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["id"];
    $_SESSION["member_id"] = $user["member_id"];
    $_SESSION["full_name"] = $user["full_name"];
    $_SESSION["username"] = $user["username"];
    $_SESSION["email"] = $user["email"];
    $_SESSION["role"] = $user["role"];

    header("Location: ../index.php");
    exit;
}

header("Location: ../login.php");
exit;