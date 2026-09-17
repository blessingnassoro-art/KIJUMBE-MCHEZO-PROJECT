<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION["user_id"]);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: loginForm.php");
        exit;
    }
}

function requireRole($allowedRoles)
{
    requireLogin();

    if (!in_array($_SESSION["role"], $allowedRoles)) {
        http_response_code(403);
        die("Access denied. You do not have permission to access this page.");
    }
}