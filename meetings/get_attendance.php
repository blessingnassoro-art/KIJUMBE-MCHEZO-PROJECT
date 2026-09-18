<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireRole(["admin", "coordinator"]);

header("Content-Type: application/json");

$meetingId = (int) ($_GET["meeting_id"] ?? 0);

if ($meetingId <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Meeting ID is required."
    ]);

    exit;
}

try {

  
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

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Meeting not found."
        ]);

        exit;
    }



    if (currentRole() === "coordinator") {

        requireMemberAccount();

        $coordinatorGroupId = getCurrentUserGroupId($pdo);

        if ((int) $meeting["group_id"] !== $coordinatorGroupId) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" => "Access denied. You can only view attendance for your own Mchezo group."
            ]);

            exit;
        }
    }



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

    error_log(
        "Get attendance error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load attendance."
    ]);
}