<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

try {

    $stmt = $pdo->query("
        SELECT
            m.id,
            m.full_name,
            m.phone,
            m.email,
            m.join_date,
            m.status,
            g.group_name
        FROM members m
        LEFT JOIN mchezo_groups g
            ON m.group_id = g.id
        ORDER BY
            g.group_name ASC,
            m.full_name ASC
    ");

    $members =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "members" => $members
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load member report."
    ]);
}