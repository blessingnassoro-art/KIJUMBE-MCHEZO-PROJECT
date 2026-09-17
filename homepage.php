
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kijumbe Management System</title>
    <link rel="stylesheet" href="stylelogin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="wrapper">
        <nav>
            <div class="logo">
                <h2>KMS</h2>
            </div>
            <ul>
                <li><a href="#home" data-i18n="home">Home</a></li>
                <li><a href="#about" data-i18n="about">About</a></li>
                <li><a href="#features" data-i18n="features">Features</a></li>
                <li><a href="#contact" data-i18n="contact">Contact</a></li>
                <li><a href="loginForm.php" data-i18n="login">Login</a></li>
                <li><a href="register.php" data-i18n="register">Register</a></li>
            </ul>
            <div class="lang-switch">
                <select name="language" id="language">
                    <option value="en">English</option>
                    <option value="sw">Swahili</option>
                </select>
            </div>
            <button class="theme-toggle" type="button" aria-label="Switch to dark mode" aria-pressed="false">
                <i class="fa-solid fa-moon" aria-hidden="true"></i>
                <span>Dark mode</span>
            </button>
        </nav>
        <div class="hero-login">
            <main id="home">
                <div class="slogan-wrap">
                    <h1 id="slogan"></h1>
                </div>
            </main>
            <section class="hero-image">
                <img src="images/Stocks1.jpg" alt="Kijumbe financial management dashboard">
            </section>
        </div>

        <section class="About reveal" id="about">
                <h2 data-i18n="aboutTitle">About Kijumbe Management System</h2>
                <div class="card">
                <p data-i18n="aboutText">Kijumbe Management System is a comprehensive solution for managing digital financial transactions with
                    ease and security.</p>
                <p data-i18n="aboutText2">Our platform offers a user-friendly interface, real-time tracking, and robust security features to ensure
                    your digital finances are well-managed.</p>
                </div>
        </section>

        <section class="features reveal" id="features">
                <h2 data-i18n="features">Features</h2>
                <div class="card">
                    <h3 data-i18n="manageTransactions">Manage Transactions</h3>
                    <p data-i18n="manageTransactionsText">Effortlessly manage your digital financial transactions with our intuitive interface.</p>
                </div>
                <div class="card">
                    <h3 data-i18n="realTimeTracking">Real-time Tracking</h3>
                    <p data-i18n="realTimeTrackingText">Monitor your financial activities in real-time with our advanced tracking capabilities.</p>
                </div>
                <div class="card">
                    <h3 data-i18n="securePlatform">Secure Platform</h3>
                    <p data-i18n="securePlatformText">Rest assured that your digital finances are protected with our robust security measures.</p>
                </div>
                <div class="card">
                    <h3 data-i18n="userFriendly">User-friendly Interface</h3>
                    <p data-i18n="userFriendlyText">Navigate through our platform with ease, thanks to our user-friendly interface.</p>
                </div>
                <div class="card">
                    <h3 data-i18n="paymentProcessing">Payment Processing</h3>
                    <p data-i18n="paymentProcessingText">Effortlessly process payments with our streamlined payment gateway.</p>
                </div>
                <div class="card">
                    <h3 data-i18n="analyticsReporting">Analytics &amp; Reporting</h3>
                    <p data-i18n="analyticsReportingText">Gain insights into your financial activities with our comprehensive analytics and reporting tools.
                    </p>
                </div>
                <div class="card">
                    <h3 data-i18n="manageMeetings">Manage Meetings</h3>
                    <p data-i18n="manageMeetingsText">Effortlessly schedule and manage your meetings with our integrated calendar and scheduling tools.</p>
                </div>
                <div class="card">
                    <h3 data-i18n="fines">Fines</h3>
                    <p data-i18n="finesText">Manage and track fines associated with your financial activities.</p>
                </div>
                <div class="card">
                    <h3 data-i18n="settings">Settings</h3>
                    <p data-i18n="settingsText">Customize your experience and manage your account preferences.</p>
                </div>
        </section>

        <section class="contact reveal" id="contact">
                <h2 data-i18n="contactTitle">Contact Us</h2>
                <p data-i18n="contactText">If you have any questions or need assistance, please reach out to us.</p>
    
                <form id="contact-form" data-admin-number="255719016625" action="#" method="post">
                    <div class="input-group">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" name="name" placeholder="Your Name" data-i18n-placeholder="namePlaceholder" required>
                    </div>
    
                    <div class="input-group">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" placeholder="Your Email" data-i18n-placeholder="emailPlaceholder" required>
                    </div>
    
                    <div class="input-group textarea-group">
                        <i class="fa-solid fa-message"></i>
                        <textarea name="message" placeholder="Your Message" data-i18n-placeholder="messagePlaceholder" required></textarea>
                    </div>
    
                    <button type="submit" data-i18n="sendMessage">Send Message</button>
                </form>
        </section>
        <footer>
            <p>&copy; 2026 Kijumbe Management System. All rights reserved.</p>
            <ul>
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </footer>
    </div>
    <script src="js/kijumbe.js?v=3"></script>
</body>
</html>