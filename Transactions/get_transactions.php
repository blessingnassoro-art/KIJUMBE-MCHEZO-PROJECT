<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

try {

    $sql = "
        SELECT
            t.id,
            t.group_id,
            g.group_name,
            t.type,
            t.amount,
            t.description,
            t.transaction_date,
            t.recorded_by,
            u.full_name AS recorded_by_name,
            t.created_at

        FROM transactions t

        INNER JOIN mchezo_groups g
            ON t.group_id = g.id

        LEFT JOIN users u
            ON t.recorded_by = u.id

        ORDER BY t.transaction_date DESC, t.id DESC
    ";

    $stmt = $pdo->query($sql);

    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "transactions" => $transactions
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load transactions."
    ]);
}