<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireRole([
    "admin",
    "treasurer",
    "coordinator",
    "member"
]);

header("Content-Type: application/json");

try {

    $role = currentRole();

    /*
     * ADMIN
     * Can view fines from all groups.
     */
    if ($role === "admin") {

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

            ORDER BY
                f.created_at DESC,
                f.id DESC
        ";

        $stmt = $pdo->query($sql);
    }


    /*
     * TREASURER / COORDINATOR
     * Can only view fines belonging
     * to their own Mchezo group.
     */
    elseif (
        $role === "treasurer" ||
        $role === "coordinator"
    ) {

        requireMemberAccount();

        $groupId =
            getCurrentUserGroupId($pdo);

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

            WHERE m.group_id = ?

            ORDER BY
                f.created_at DESC,
                f.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId]);
    }


    /*
     * MEMBER
     * Can only view their own fines.
     */
    else {

        requireMemberAccount();

        $memberId =
            currentMemberId();

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

            WHERE f.member_id = ?

            ORDER BY
                f.created_at DESC,
                f.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$memberId]);
    }


    $fines =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        "success" => true,
        "fines" => $fines
    ]);


} catch (PDOException $e) {

    error_log(
        "Get fines error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load fines."
    ]);
}