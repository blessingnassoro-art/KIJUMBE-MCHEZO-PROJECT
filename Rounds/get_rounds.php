<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "coordinator"]);

header("Content-Type: application/json");

try {

    $sql = "SELECT
                r.id,
                r.group_id,
                mg.group_name,
                r.round_number,
                r.due_date,
                r.expected_amount,
                r.collected_amount,
                r.status
            FROM rounds r
            INNER JOIN mchezo_groups mg
                ON r.group_id = mg.id
            ORDER BY r.group_id ASC, r.round_number ASC";

    $stmt = $pdo->query($sql);

    $rounds = $stmt->fetchAll();

    echo json_encode($rounds);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load rounds."
    ]);
}