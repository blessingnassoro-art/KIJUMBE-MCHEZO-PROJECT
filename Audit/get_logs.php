<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireRole(["admin"]);

header("Content-Type: application/json");

try {

    $stmt = $pdo->query("
        SELECT
            a.id,
            a.user_id,
            u.full_name AS user_name,
            u.role,
            a.action,
            a.details,
            a.created_at
        FROM audit_logs a
        LEFT JOIN users u
            ON a.user_id = u.id
        ORDER BY
            a.created_at DESC,
            a.id DESC
    ");

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "logs" => $logs
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load audit logs."
    ]);
}