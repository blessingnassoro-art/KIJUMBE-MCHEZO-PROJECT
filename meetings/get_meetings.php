<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

try {

    $stmt = $pdo->query("
        SELECT
            m.id,
            m.group_id,
            m.title,
            m.meeting_date,
            m.location,
            m.agenda,
            m.created_by,
            m.created_at,
            g.group_name
        FROM meetings m
        INNER JOIN mchezo_groups g
            ON m.group_id = g.id
        ORDER BY m.meeting_date DESC, m.id DESC
    ");

    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "meetings" => $meetings
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load meetings."
    ]);
}