<?php

require_once __DIR__ . '/includes/db.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: register.php");

    exit();
}



$fullName = trim($_POST["full_name"] ?? "");
$username = trim($_POST["username"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$password = $_POST["password"] ?? "";


/*
 * Validate required fields
 */

if (
    $fullName === "" ||
    $username === "" ||
    $email === "" ||
    $password === ""
) {

    header(
        "Location: register.php?error=empty_fields"
    );

    exit();
}




if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    header(
        "Location: register.php?error=invalid_email"
    );

    exit();
}



if (strlen($password) < 8) {

    header(
        "Location: register.php?error=weak_password"
    );

    exit();
}


try {



    $stmt = $pdo->query("
        SELECT id
        FROM users
        WHERE role = 'admin'
        LIMIT 1
    ");

    $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($existingAdmin) {

        header(
            "Location: register.php?error=admin_exists"
        );

        exit();
    }



    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE username = ?
        LIMIT 1
    ");

    $stmt->execute([
        $username
    ]);


    if ($stmt->fetch()) {

        header(
            "Location: register.php?error=username_exists"
        );

        exit();
    }




    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([
        $email
    ]);


    if ($stmt->fetch()) {

        header(
            "Location: register.php?error=email_exists"
        );

        exit();
    }



    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


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
        VALUES
        (
            NULL,
            ?,
            ?,
            ?,
            ?,
            'admin',
            'active'
        )
    ");


    $stmt->execute([
        $fullName,
        $username,
        $email,
        $hashedPassword
    ]);


    /*
     * Registration successful
     */

    header(
        "Location: ../login.php?registered=1"
    );

    exit();


} catch (PDOException $e) {

    error_log(
        "Admin registration error: " .
        $e->getMessage()
    );

    header(
        "Location: register.php?error=registration_failed"
    );

    exit();
}