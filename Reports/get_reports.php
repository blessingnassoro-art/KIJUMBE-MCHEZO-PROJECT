<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

try {

    $role = currentRole();

    /*
     * Admin
     * -----
     * Can view reports for all groups.
     *
     * Treasurer
     * ---------
     * Can view financial reports for their group.
     *
     * Coordinator
     * -----------
     * Can view operational reports for their group.
     *
     * Member
     * ------
     * Can view only their own personal report.
     */

    $groupId = null;

    if (in_array($role, ["treasurer", "coordinator", "member"])) {
        $groupId = getCurrentUserGroupId($pdo);
    }




    if ($role === "admin") {

        $stmt = $pdo->query("
            SELECT
                COALESCE(SUM(paid_amount), 0) AS total_contributions
            FROM contributions
        ");

    } elseif ($role === "member") {

        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(paid_amount), 0) AS total_contributions
            FROM contributions
            WHERE member_id = ?
        ");

        $stmt->execute([
            currentMemberId()
        ]);

    } else {

        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(c.paid_amount), 0) AS total_contributions

            FROM contributions c

            INNER JOIN rounds r
                ON c.round_id = r.id

            WHERE r.group_id = ?
        ");

        $stmt->execute([$groupId]);
    }

    $financial = $stmt->fetch(PDO::FETCH_ASSOC);



    if ($role === "admin") {

        $stmt = $pdo->query("
            SELECT
                COALESCE(SUM(amount), 0) AS total_fines
            FROM fines
        ");

    } elseif ($role === "member") {

        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(amount), 0) AS total_fines
            FROM fines
            WHERE member_id = ?
        ");

        $stmt->execute([
            currentMemberId()
        ]);

    } else {

        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(f.amount), 0) AS total_fines

            FROM fines f

            INNER JOIN members m
                ON f.member_id = m.id

            WHERE m.group_id = ?
        ");

        $stmt->execute([$groupId]);
    }

    $fines = $stmt->fetch(PDO::FETCH_ASSOC);


 

    if ($role === "admin") {

        $stmt = $pdo->query("
            SELECT
                COUNT(*) AS total_turns,

                SUM(
                    CASE
                        WHEN status = 'completed'
                        THEN 1
                        ELSE 0
                    END
                ) AS completed_turns

            FROM turns
        ");

    } elseif ($role === "member") {

        /*
         * A member should only see their own turns.
         */

        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) AS total_turns,

                SUM(
                    CASE
                        WHEN t.status = 'completed'
                        THEN 1
                        ELSE 0
                    END
                ) AS completed_turns

            FROM turns t

            INNER JOIN rounds r
                ON t.round_id = r.id

            WHERE t.member_id = ?
        ");

        $stmt->execute([
            currentMemberId()
        ]);

    } else {

        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) AS total_turns,

                SUM(
                    CASE
                        WHEN t.status = 'completed'
                        THEN 1
                        ELSE 0
                    END
                ) AS completed_turns

            FROM turns t

            INNER JOIN rounds r
                ON t.round_id = r.id

            WHERE r.group_id = ?
        ");

        $stmt->execute([$groupId]);
    }

    $turns = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($role === "admin") {

        $stmt = $pdo->query("
            SELECT
                r.id,
                r.round_number,
                r.expected_amount,
                r.collected_amount,
                r.status,
                g.group_name

            FROM rounds r

            INNER JOIN mchezo_groups g
                ON r.group_id = g.id

            ORDER BY
                g.group_name ASC,
                r.round_number ASC
        ");

        $rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($role === "member") {



        $rounds = [];

    } else {

        $stmt = $pdo->prepare("
            SELECT
                r.id,
                r.round_number,
                r.expected_amount,
                r.collected_amount,
                r.status,
                g.group_name

            FROM rounds r

            INNER JOIN mchezo_groups g
                ON r.group_id = g.id

            WHERE r.group_id = ?

            ORDER BY
                r.round_number ASC
        ");

        $stmt->execute([$groupId]);

        $rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    $memberContributions = [];

    if ($role === "member") {

        $stmt = $pdo->prepare("
            SELECT
                c.id,
                r.round_number,
                c.expected_amount,
                c.paid_amount,
                c.status,
                c.due_date,
                c.paid_date

            FROM contributions c

            INNER JOIN rounds r
                ON c.round_id = r.id

            WHERE c.member_id = ?

            ORDER BY
                r.round_number ASC
        ");

        $stmt->execute([
            currentMemberId()
        ]);

        $memberContributions =
            $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    echo json_encode([

        "success" => true,

        "summary" => [

            "total_contributions" =>
                (float) ($financial["total_contributions"] ?? 0),

            "total_fines" =>
                (float) ($fines["total_fines"] ?? 0),

            "completed_turns" =>
                (int) ($turns["completed_turns"] ?? 0),

            "total_turns" =>
                (int) ($turns["total_turns"] ?? 0)
        ],

        "rounds" => $rounds,

        "member_contributions" =>
            $memberContributions

    ]);

} catch (PDOException $e) {

    error_log(
        "Reports error: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load reports."
    ]);
}