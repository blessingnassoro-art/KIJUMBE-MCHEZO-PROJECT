<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/audit.php";

session_start();

$message = "";
$messageType = "";


if (
    empty($_SESSION["password_reset_user_id"]) ||
    empty($_SESSION["password_reset_recovery_id"])
) {
    header("Location: forgot_password.php");
    exit;
}

$userId = (int) $_SESSION["password_reset_user_id"];
$recoveryId = (int) $_SESSION["password_reset_recovery_id"];


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $newPassword = $_POST["new_password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    /*
     * Validate password.
     */
    if ($newPassword === "" || $confirmPassword === "") {

        $message = "Please fill in both password fields.";
        $messageType = "error";

    } elseif (strlen($newPassword) < 6) {

        $message = "Password must contain at least 6 characters.";
        $messageType = "error";

    } elseif ($newPassword !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } else {

        try {

            /*
             * Confirm that the recovery code is still valid.
             */
            $stmt = $pdo->prepare("
                SELECT id, user_id, used
                FROM admin_recovery
                WHERE id = ?
                AND user_id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $recoveryId,
                $userId
            ]);

            $recovery = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$recovery) {

                $message = "Recovery request is invalid.";
                $messageType = "error";

            } elseif ((int)$recovery["used"] === 1) {

                $message = "This recovery code has already been used.";
                $messageType = "error";

            } else {

                /*
                 * Confirm the account is still an Admin.
                 */
                $stmt = $pdo->prepare("
                    SELECT id, username, role, status
                    FROM users
                    WHERE id = ?
                    LIMIT 1
                ");

                $stmt->execute([$userId]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {

                    $message = "Admin account not found.";
                    $messageType = "error";

                } elseif ($user["role"] !== "admin") {

                    $message = "This account is not an Admin account.";
                    $messageType = "error";

                } elseif ($user["status"] !== "active") {

                    $message = "This Admin account is inactive.";
                    $messageType = "error";

                } else {

                    /*
                     * Hash the new password.
                     */
                    $passwordHash = password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    );

                    /*
                     * Update password and mark recovery code
                     * as used inside a transaction.
                     */
                    $pdo->beginTransaction();

                    $updateUser = $pdo->prepare("
                        UPDATE users
                        SET password = ?
                        WHERE id = ?
                    ");

                    $updateUser->execute([
                        $passwordHash,
                        $userId
                    ]);

                    $updateRecovery = $pdo->prepare("
                        UPDATE admin_recovery
                        SET used = 1
                        WHERE id = ?
                        AND user_id = ?
                    ");

                    $updateRecovery->execute([
                        $recoveryId,
                        $userId
                    ]);

                    /*
                     * Record the password reset in audit logs.
                     */
                    logAudit(
                        $userId,
                        "Admin Password Reset",
                        "Admin password was reset using recovery code."
                    );

                    $pdo->commit();

                    /*
                     * Remove password-reset session information.
                     */
                    unset($_SESSION["password_reset_user_id"]);
                    unset($_SESSION["password_reset_recovery_id"]);

                    /*
                     * Redirect to login with success message.
                     */
                    header("Location: ../loginForm.php?reset=success");
                    exit;
                }
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                "Password reset error: " . $e->getMessage()
            );

            $message = "Unable to reset password. Please try again.";
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

    <title>Reset Password | KIJUMBE</title>

    <link
        rel="stylesheet"
        href="../stylelogin.css"
    >

</head>

<body>

<div class="wrapper">

    <section class="form-container">

        <h2>Reset Password</h2>

        <p>
            Create a new password for your Admin account.
        </p>


        <?php if ($message !== ""): ?>

            <div
                style="
                    padding:10px;
                    border-radius:6px;
                    margin-bottom:15px;
                    background:#fdeaea;
                    color:#c62828;
                "
            >
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="input-group">

                <i class="fa-solid fa-lock"></i>

                <input
                    type="password"
                    name="new_password"
                    id="new_password"
                    placeholder="New Password"
                    minlength="6"
                    required
                >

            </div>


            <small
                id="passwordRequirement"
                style="
                    display:block;
                    margin-top:5px;
                    margin-bottom:15px;
                "
            >
                Password must contain at least 6 characters.
            </small>


            <div class="input-group">

                <i class="fa-solid fa-lock"></i>

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Confirm New Password"
                    minlength="6"
                    required
                >

            </div>


            <button type="submit">
                Reset Password
            </button>

        </form>


        <p style="margin-top:15px;">

            <a href="../login.php">
                Back to Login
            </a>

        </p>

    </section>

</div>


<script>

const newPassword = document.getElementById("new_password");
const passwordRequirement =
    document.getElementById("passwordRequirement");

if (newPassword && passwordRequirement) {

    newPassword.addEventListener("input", function () {

        const length = newPassword.value.length;

        if (length === 0) {

            passwordRequirement.textContent =
                "Password must contain at least 6 characters.";

            passwordRequirement.style.color = "";

        } else if (length < 6) {

            passwordRequirement.textContent =
                "Password must contain at least 6 characters. "
                + (6 - length)
                + " more needed.";

            passwordRequirement.style.color = "#c62828";

        } else {

            passwordRequirement.textContent =
                "Password length is valid.";

            passwordRequirement.style.color = "#2e7d32";
        }

    });

}

</script>

</body>

</html>