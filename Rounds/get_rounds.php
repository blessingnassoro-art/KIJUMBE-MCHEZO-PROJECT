<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "coordinator"]);

header("Content-Type: application/json");

try {

    $role = currentRole();

    /*
     * ADMIN
     * Can see rounds from all Mchezo groups.
     */
    if ($role === "admin") {

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

    }

    /*
     * COORDINATOR
     * Can only see rounds belonging to
     * their own Mchezo group.
     */
    else {

        requireMemberAccount();

        $groupId = getCurrentUserGroupId($pdo);

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
                WHERE r.group_id = ?
                ORDER BY r.round_number ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
    }

    $rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rounds);

} catch (PDOException $e) {

    error_log("Get rounds error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load rounds."
    ]);
}