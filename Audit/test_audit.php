<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/audit.php";

requireLogin();

$userId = $_SESSION["user_id"] ?? null;

if (!$userId) {
    die("User is not logged in.");
}

$result = logAudit(
    $userId,
    "Test Audit",
    "Audit logging system test."
);

if ($result) {

    echo "Audit log created successfully.";

} else {

    echo "Failed to create audit log.";
}