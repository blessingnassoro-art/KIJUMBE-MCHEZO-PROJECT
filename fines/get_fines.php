<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

try {

    $sql = "
        SELECT
            f.id,
            f.member_id,
            m.full_name,
            m.phone,

            mg.group_name,

            f.meeting_id,
            mt.title AS meeting_title,

            f.amount,
            f.reason,
            f.status,
            f.paid_date,
            f.created_at

        FROM fines f

        INNER JOIN members m
            ON f.member_id = m.id

        INNER JOIN mchezo_groups mg
            ON m.group_id = mg.id

        LEFT JOIN meetings mt
            ON f.meeting_id = mt.id

        ORDER BY f.created_at DESC, f.id DESC
    ";

    $stmt = $pdo->query($sql);

    $fines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "fines" => $fines
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load fines."
    ]);
}