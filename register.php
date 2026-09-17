
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | KMS</title>
    <link rel="stylesheet" href="stylelogin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <section class="form-container" id="register">
             <?php if (isset($_GET['error'])): ?>
                    <p style="color: #d33; background: #fdeaea; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;">
                        <?php
                            if ($_GET['error'] === 'email_exists') echo "Email is already registered.";
                            elseif ($_GET['error'] === 'empty_fields') echo "Please fill in all required fields.";
                            elseif ($_GET['error'] === 'invalid_email') echo "Please enter a valid email address.";
                            elseif ($_GET['error'] === 'weak_password') echo "Password must be at least 8 characters long.";
                            elseif ($_GET['error'] === 'database_unavailable') echo "Database connection is not configured.";
                            elseif ($_GET['error'] === 'admin_exists')
                            echo "An Administrator account already exists. Please login.";

                            elseif ($_GET['error'] === 'username_exists')
                            echo "Username is already registered.";
                            else echo "Registration failed. Try again.";
                        ?>
                    </p>
                <?php endif; ?>
        <h1>Create your admin account</h1>
        <form action="register_process.php" method="post">

    <div class="input-group">
        <i class="fa-regular fa-user" aria-hidden="true"></i>

        <input
            type="text"
            name="full_name"
            placeholder="Full Name"
            required
        >
    </div>


    <div class="input-group">
        <i class="fa-solid fa-at" aria-hidden="true"></i>

        <input
            type="text"
            name="username"
            placeholder="Username"
            required
        >
    </div>


    <div class="input-group">
        <i class="fa-solid fa-envelope" aria-hidden="true"></i>

        <input
            type="email"
            name="email"
            placeholder="Email"
            required
        >
    </div>


    <div class="input-group">
        <i class="fa-solid fa-phone" aria-hidden="true"></i>

        <input
            type="tel"
            name="phone"
            placeholder="Phone Number"
            required
        >
    </div>


    <div class="input-group">
        <i class="fa-solid fa-lock" aria-hidden="true"></i>

        <input
            type="password"
            name="password"
            id="password"
            placeholder="Password"
            required
        >
    </div>


    <div class="show-password-row">

        <label class="show-password-control">

            <input
                type="checkbox"
                id="show_password"
            >

            <span>Show Password</span>

        </label>

    </div>


    <button type="submit">
        Register
    </button>

</form>
        <p>Already have an account? <a href="./loginForm.php#login">Login here</a>.</p>
         </section>
    </div>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Kijumbe Management System. All rights reserved.</p>
    </footer>
    <script src="js/kijumbe.js?v=3"></script>
</body>
</html>