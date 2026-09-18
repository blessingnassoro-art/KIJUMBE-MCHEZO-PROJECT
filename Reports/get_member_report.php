<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

try {

    $role = currentRole();

    /*
     * ==========================================================
     * ADMIN
     * ==========================================================
     *
     * Admin can view members from all Mchezo groups.
     */

    if ($role === "admin") {

        $stmt = $pdo->query("
            SELECT
                m.id,
                m.full_name,
                m.phone,
                m.email,
                m.join_date,
                m.status,
                g.group_name

            FROM members m

            LEFT JOIN mchezo_groups g
                ON m.group_id = g.id

            ORDER BY
                g.group_name ASC,
                m.full_name ASC
        ");
    }


    /*
     * ==========================================================
     * COORDINATOR / TREASURER
     * ==========================================================
     *
     * They can only view members belonging
     * to their own Mchezo group.
     */

    elseif (in_array($role, ["coordinator", "treasurer"])) {

        $groupId = getCurrentUserGroupId($pdo);

        $stmt = $pdo->prepare("
            SELECT
                m.id,
                m.full_name,
                m.phone,
                m.email,
                m.join_date,
                m.status,
                g.group_name

            FROM members m

            INNER JOIN mchezo_groups g
                ON m.group_id = g.id

            WHERE m.group_id = ?

            ORDER BY
                m.full_name ASC
        ");

        $stmt->execute([
            $groupId
        ]);
    }


    /*
     * ==========================================================
     * MEMBER
     * ==========================================================
     *
     * A normal member can only view their own
     * member record.
     */

    elseif ($role === "member") {

        $stmt = $pdo->prepare("
            SELECT
                m.id,
                m.full_name,
                m.phone,
                m.email,
                m.join_date,
                m.status,
                g.group_name

            FROM members m

            LEFT JOIN mchezo_groups g
                ON m.group_id = g.id

            WHERE m.id = ?

            LIMIT 1
        ");

        $stmt->execute([
            currentMemberId()
        ]);
    }


    /*
     * ==========================================================
     * UNKNOWN ROLE
     * ==========================================================
     */

    else {

        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" =>
                "You do not have permission to view member reports."
        ]);

        exit;
    }


    /*
     * ==========================================================
     * FETCH RESULTS
     * ==========================================================
     */

    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        "success" => true,
        "members" => $members
    ]);

} catch (PDOException $e) {

    error_log(
        "Member report error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to load member report."
    ]);
}