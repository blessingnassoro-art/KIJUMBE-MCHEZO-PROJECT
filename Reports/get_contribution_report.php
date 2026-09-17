<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

try {

    $stmt = $pdo->query("
        SELECT
            c.id,
            m.full_name,
            g.group_name,
            r.round_number,
            c.expected_amount,
            c.paid_amount,
            c.status,
            c.due_date,
            c.paid_date
        FROM contributions c

        INNER JOIN members m
            ON c.member_id = m.id

        INNER JOIN rounds r
            ON c.round_id = r.id

        INNER JOIN mchezo_groups g
            ON r.group_id = g.id

        ORDER BY
            g.group_name ASC,
            r.round_number ASC,
            m.full_name ASC
    ");

    $contributions =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "contributions" => $contributions
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load contribution report."
    ]);
}