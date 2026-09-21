<?php

require_once "../includes/db.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");

    if ($username === "") {
        $message = "Please enter your username.";
        $messageType = "error";
    } else {

        try {

            // Find the user
            $stmt = $pdo->prepare("
                SELECT id, username, role, status
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([$username]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {

                $message = "Username not found.";
                $messageType = "error";

            } elseif ($user["role"] !== "admin") {

                $message = "Password recovery is currently available for Admin accounts only.";
                $messageType = "error";

            } elseif ($user["status"] !== "active") {

                $message = "This Admin account is inactive.";
                $messageType = "error";

            } else {

                /*
                 * Generate a secure recovery code.
                 */
                $part1 = strtoupper(bin2hex(random_bytes(2)));
                $part2 = strtoupper(bin2hex(random_bytes(2)));

                $recoveryCode = "KJMB-" . $part1 . "-" . $part2;

                /*
                 * Hash the recovery code before storing it.
                 */
                $recoveryCodeHash = password_hash(
                    $recoveryCode,
                    PASSWORD_DEFAULT
                );

                /*
                 * Remove previous unused recovery codes
                 * for this Admin.
                 */
                $delete = $pdo->prepare("
                    DELETE FROM admin_recovery
                    WHERE user_id = ?
                    AND used = 0
                ");

                $delete->execute([$user["id"]]);

                /*
                 * Store the new recovery-code hash.
                 */
                $insert = $pdo->prepare("
                    INSERT INTO admin_recovery
                    (
                        user_id,
                        recovery_code_hash,
                        used
                    )
                    VALUES (?, ?, 0)
                ");

                $insert->execute([
                    $user["id"],
                    $recoveryCodeHash
                ]);

                /*
                 * For the local academic project,
                 * display the recovery code.
                 *
                 * In a real production system this would
                 * normally be delivered through a secure
                 * recovery channel such as email.
                 */
                $message = "Recovery code generated.";
                $messageType = "success";

            }

        } catch (PDOException $e) {

            error_log("Forgot password error: " . $e->getMessage());

            $message = "Unable to process password recovery.";
            $messageType = "error";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Forgot Password | KIJUMBE</title>

    <link
        rel="stylesheet"
        href="../stylelogin.css"
    >

</head>

<body>

<div class="wrapper">

    <section class="form-container">

        <h2 style="color: #176b55;">Forgot Password</h2>

        <p>
            Enter your Admin username to begin password recovery.
        </p>

        <?php if ($message !== ""): ?>

            <div
                style="
                    padding:10px;
                    border-radius:6px;
                    margin-bottom:15px;
                    background:
                    <?= $messageType === "success"
                        ? "#e8f5e9"
                        : "#fdeaea" ?>;
                    color:
                    <?= $messageType === "success"
                        ? "#2e7d32"
                        : "#c62828" ?>;
                "
            >
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <?php if (
            $messageType === "success"
            && isset($recoveryCode)
        ): ?>

            <div
                style="
                    background:#f5f5f5;
                    padding:15px;
                    border-radius:8px;
                    margin-bottom:20px;
                    text-align:center;
                "
            >

                <strong>Your Admin Recovery Code</strong>

                <div
                    style="
                        font-size:22px;
                        font-weight:bold;
                        letter-spacing:2px;
                        margin-top:10px;
                    "
                >
                    <?= htmlspecialchars($recoveryCode) ?>
                </div>

                <p
                    style="
                        font-size:13px;
                        margin-top:10px;
                    "
                >
                    Keep this code safe. It will be required
                    to reset the Admin password.
                </p>

                <a
                    href="verify_recovery.php"
                    style="
                        display:inline-block;
                        margin-top:10px;
                        text-decoration:none;
                    "
                >
                    Continue to Password Reset
                </a>

            </div>

        <?php else: ?>

            <form method="POST">

                <div class="input-group">

                    <input
                        type="text"
                        name="username"
                        placeholder="Admin Username"
                        required
                    >

                </div>

                <button type="submit">
                    Generate Recovery Code
                </button>

            </form>

        <?php endif; ?>


        <p style="margin-top:15px;">

            <a href="../login.php">
                Back to Login
            </a>

        </p>

    </section>

</div>

</body>

</html>