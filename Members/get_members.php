<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "treasurer", "coordinator", "member"]);

header("Content-Type: application/json");

try {

    $role = currentRole();


    if ($role === "admin") {

        $sql = "
            SELECT
                members.id,
                members.group_id,
                members.full_name,
                members.phone,
                members.email,
                members.join_date,
                members.status,
                mchezo_groups.group_name,
                mchezo_groups.max_members,

                (
                    SELECT COUNT(*)
                    FROM members AS group_members
                    WHERE group_members.group_id = members.group_id
                    AND group_members.status = 'active'
                ) AS current_members

            FROM members

            INNER JOIN mchezo_groups
                ON members.group_id = mchezo_groups.id

            ORDER BY members.id DESC
        ";

        $stmt = $pdo->query($sql);
    }




    elseif ($role === "treasurer" || $role === "coordinator") {

        requireMemberAccount();

        $groupId = getCurrentUserGroupId($pdo);

        $sql = "
            SELECT
                members.id,
                members.group_id,
                members.full_name,
                members.phone,
                members.email,
                members.join_date,
                members.status,
                mchezo_groups.group_name,
                mchezo_groups.max_members,

                (
                    SELECT COUNT(*)
                    FROM members AS group_members
                    WHERE group_members.group_id = members.group_id
                    AND group_members.status = 'active'
                ) AS current_members

            FROM members

            INNER JOIN mchezo_groups
                ON members.group_id = mchezo_groups.id

            WHERE members.group_id = ?

            ORDER BY members.id DESC
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $groupId
        ]);
    }



    else {

        requireMemberAccount();

        $memberId = currentMemberId();

        $sql = "
            SELECT
                members.id,
                members.group_id,
                members.full_name,
                members.phone,
                members.email,
                members.join_date,
                members.status,
                mchezo_groups.group_name,
                mchezo_groups.max_members,

                (
                    SELECT COUNT(*)
                    FROM members AS group_members
                    WHERE group_members.group_id = members.group_id
                    AND group_members.status = 'active'
                ) AS current_members

            FROM members

            INNER JOIN mchezo_groups
                ON members.group_id = mchezo_groups.id

            WHERE members.id = ?

            ORDER BY members.id DESC
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $memberId
        ]);
    }


    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($members);

} catch (PDOException $e) {

    error_log("Get members error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load members."
    ]);
}