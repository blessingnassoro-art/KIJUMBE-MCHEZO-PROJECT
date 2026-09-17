<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

$meetingId = $_GET["meeting_id"] ?? null;

if (!$meetingId) {
    echo json_encode([
        "success" => false,
        "message" => "Meeting ID is required."
    ]);
    exit;
}

try {

    // Get meeting and its Mchezo group
    $stmt = $pdo->prepare("
        SELECT
            m.id,
            m.title,
            m.meeting_date,
            m.group_id,
            g.group_name
        FROM meetings m
        INNER JOIN mchezo_groups g
            ON m.group_id = g.id
        WHERE m.id = ?
        LIMIT 1
    ");

    $stmt->execute([$meetingId]);

    $meeting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$meeting) {
        echo json_encode([
            "success" => false,
            "message" => "Meeting not found."
        ]);
        exit;
    }


    // Get ALL members belonging to the meeting's Mchezo group
    // Existing attendance is loaded if already recorded.
    $stmt = $pdo->prepare("
        SELECT
            m.id AS member_id,
            m.full_name,
            m.phone,
            a.status,
            a.remarks

        FROM members m

        LEFT JOIN attendance a
            ON a.member_id = m.id
            AND a.meeting_id = ?

        WHERE m.group_id = ?

        ORDER BY m.full_name ASC
    ");

    $stmt->execute([
        $meetingId,
        $meeting["group_id"]
    ]);

    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        "success" => true,
        "meeting" => $meeting,
        "members" => $members
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load attendance."
    ]);
}