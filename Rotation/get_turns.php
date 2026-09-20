<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "coordinator", "member"]);

header("Content-Type: application/json");

try {

$role = currentRole();

if ($role === "admin") {

    // Admin can view all groups
    $stmt = $pdo->query("
        SELECT
            t.id,
            t.turn_number,
            t.status,
            t.received_date,
            r.round_number,
            m.full_name,
            g.group_name
        FROM turns t
        INNER JOIN rounds r
            ON t.round_id = r.id
        INNER JOIN members m
            ON t.member_id = m.id
        INNER JOIN mchezo_groups g
            ON r.group_id = g.id
        ORDER BY
            g.group_name,
            t.turn_number
    ");

} else {

    requireMemberAccount();

    $groupId = getCurrentUserGroupId($pdo);

    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.turn_number,
            t.status,
            t.received_date,
            r.round_number,
            m.full_name,
            g.group_name
        FROM turns t
        INNER JOIN rounds r
            ON t.round_id = r.id
        INNER JOIN members m
            ON t.member_id = m.id
        INNER JOIN mchezo_groups g
            ON r.group_id = g.id
        WHERE r.group_id = ?
        ORDER BY
            t.turn_number
    ");

    $stmt->execute([$groupId]);
}

    $turns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($turns);

} catch (PDOException $e) {

    error_log(
        "Get rotation turns error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load rotation turns."
    ]);
}