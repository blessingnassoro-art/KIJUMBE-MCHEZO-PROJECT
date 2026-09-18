<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireRole(["admin", "coordinator"]);

header("Content-Type: application/json");

try {

    $role = currentRole();

    /*
     * ADMIN
     * Can view meetings from all Mchezo groups.
     */
    if ($role === "admin") {

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
            ORDER BY
                m.meeting_date DESC,
                m.id DESC
        ");

    }

    /*
     * COORDINATOR
     * Can only view meetings belonging
     * to their own Mchezo group.
     */
    else {

        requireMemberAccount();

        $groupId = getCurrentUserGroupId($pdo);

        $stmt = $pdo->prepare("
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
            WHERE m.group_id = ?
            ORDER BY
                m.meeting_date DESC,
                m.id DESC
        ");

        $stmt->execute([$groupId]);
    }


    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "meetings" => $meetings
    ]);

} catch (PDOException $e) {

    error_log(
        "Get meetings error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load meetings."
    ]);
}