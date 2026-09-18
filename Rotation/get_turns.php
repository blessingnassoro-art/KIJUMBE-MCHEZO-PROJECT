<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "coordinator"]);

header("Content-Type: application/json");

try {

    $role = currentRole();

    /*
     * ADMIN
     * Can view rotation turns from all Mchezo groups.
     */
    if ($role === "admin") {

        $sql = "SELECT
                    t.id,
                    t.round_id,
                    t.member_id,
                    t.turn_number,
                    t.status,
                    t.received_date,
                    r.round_number,
                    r.due_date,
                    m.full_name,
                    mg.group_name
                FROM turns t

                INNER JOIN rounds r
                    ON t.round_id = r.id

                INNER JOIN members m
                    ON t.member_id = m.id

                INNER JOIN mchezo_groups mg
                    ON r.group_id = mg.id

                ORDER BY
                    mg.group_name ASC,
                    t.turn_number ASC";

        $stmt = $pdo->query($sql);

    }

    /*
     * COORDINATOR
     * Can only view turns from their own
     * Mchezo group.
     */
    else {

        requireMemberAccount();

        $groupId = getCurrentUserGroupId($pdo);

        $sql = "SELECT
                    t.id,
                    t.round_id,
                    t.member_id,
                    t.turn_number,
                    t.status,
                    t.received_date,
                    r.round_number,
                    r.due_date,
                    m.full_name,
                    mg.group_name
                FROM turns t

                INNER JOIN rounds r
                    ON t.round_id = r.id

                INNER JOIN members m
                    ON t.member_id = m.id

                INNER JOIN mchezo_groups mg
                    ON r.group_id = mg.id

                WHERE r.group_id = ?

                ORDER BY
                    t.turn_number ASC";

        $stmt = $pdo->prepare($sql);
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