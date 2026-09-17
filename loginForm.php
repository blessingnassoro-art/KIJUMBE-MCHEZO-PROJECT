<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | KMS</title>
    <link rel="stylesheet" href="stylelogin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <section class="form-container" id="login">
            <h2 data-i18n="welcome">Welcome to Kijumbe Management System</h2>
            <p data-i18n="loginPrompt">Please login to continue.</p>
            <?php if (isset($_GET['error'])): ?>
            <p style="color: #d33; background: #fdeaea; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;">
                <?php
                    if ($_GET['error'] === 'empty_fields') echo "Please fill in all required fields.";
                    elseif ($_GET['error'] === 'database_unavailable') echo "Database connection is not configured.";
                    else echo "Invalid email address or password.";
                    ?>
            </p>
            <?php endif; ?>
            <form action="auth/login.php" method="post">
                <div class="input-group">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                  <div class="show-password-row">
                        <label class="show-password-control">
                            <input type="checkbox" name="show_password" id="show_password">
                            <span data-i18n="showPassword">Show Password</span>
                        </label>
                    </div>
                <button type="submit">Login</button>
            </form>
            <p>Don't have an account? <a href="./register.php">Register here</a>.</p>
        </section>
    </div>
    <script src="kijumbe.js?v=3"></script>
</body>
</html>