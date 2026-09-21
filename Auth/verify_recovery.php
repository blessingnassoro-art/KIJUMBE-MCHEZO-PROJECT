<?php

require_once "../includes/db.php";

session_start();

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $recoveryCode = trim($_POST["recovery_code"] ?? "");

    if ($username === "" || $recoveryCode === "") {

        $message = "Please enter both username and recovery code.";
        $messageType = "error";

    } else {

        try {

            /*
             * Find the Admin account.
             */
            $stmt = $pdo->prepare("
                SELECT id, username, role, status
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([$username]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {

                $message = "Invalid username or recovery code.";
                $messageType = "error";

            } elseif ($user["role"] !== "admin") {

                $message = "Password recovery is available for Admin accounts only.";
                $messageType = "error";

            } elseif ($user["status"] !== "active") {

                $message = "This Admin account is inactive.";
                $messageType = "error";

            } else {

                /*
                 * Get the latest unused recovery code.
                 */
                $stmt = $pdo->prepare("
                    SELECT id, recovery_code_hash
                    FROM admin_recovery
                    WHERE user_id = ?
                    AND used = 0
                    ORDER BY id DESC
                    LIMIT 1
                ");

                $stmt->execute([$user["id"]]);

                $recovery = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$recovery) {

                    $message = "No valid recovery code was found. Please generate a new one.";
                    $messageType = "error";

                } elseif (
                    !password_verify(
                        $recoveryCode,
                        $recovery["recovery_code_hash"]
                    )
                ) {

                    $message = "Invalid username or recovery code.";
                    $messageType = "error";

                } else {

                    /*
                     * Recovery code is valid.
                     *
                     * Store the verified information
                     * temporarily in the session.
                     */
                    $_SESSION["password_reset_user_id"] = (int)$user["id"];
                    $_SESSION["password_reset_recovery_id"] = (int)$recovery["id"];

                    header("Location: reset_password.php");
                    exit;
                }
            }

        } catch (PDOException $e) {

            error_log(
                "Recovery verification error: "
                . $e->getMessage()
            );

            $message = "Unable to verify recovery code.";
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

    <title>Verify Recovery Code | KIJUMBE</title>

    <link
        rel="stylesheet"
        href="../stylelogin.css"
    >

</head>

<body>

<div class="wrapper">

    <section class="form-container">

        <h2>Verify Recovery Code</h2>

        <p>
            Enter your Admin username and recovery code.
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


        <form method="POST">

            <div class="input-group">

                <input
                    type="text"
                    name="username"
                    placeholder="Admin Username"
                    required
                >

            </div>


            <div class="input-group">

                <input
                    type="text"
                    name="recovery_code"
                    placeholder="Recovery Code"
                    required
                >

            </div>


            <button type="submit">
                Verify Recovery Code
            </button>

        </form>


        <p style="margin-top:15px;">

            <a href="forgot_password.php">
                Generate New Code
            </a>

            &nbsp; | &nbsp;

            <a href="../login.php">
                Back to Login
            </a>

        </p>

    </section>

</div>

</body>

</html>