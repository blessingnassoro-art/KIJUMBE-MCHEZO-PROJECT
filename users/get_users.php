<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireRole(["admin"]);

header("Content-Type: application/json");

try {

    $stmt = $pdo->query("
        SELECT
            u.id,
            u.member_id,
            u.full_name,
            u.username,
            u.email,
            u.role,
            u.status,
            u.created_at,
            m.full_name AS member_name
        FROM users u
        LEFT JOIN members m
            ON u.member_id = m.id
        ORDER BY u.id DESC
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "users" => $users
    ]);

} catch (PDOException $e) {

    error_log("Get users error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load users."
    ]);
}