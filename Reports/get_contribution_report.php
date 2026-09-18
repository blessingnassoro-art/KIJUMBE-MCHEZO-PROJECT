<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

try {

    $role = currentRole();



    if ($role === "admin") {

        $stmt = $pdo->query("
            SELECT
                c.id,
                m.full_name,
                g.group_name,
                r.round_number,
                c.expected_amount,
                c.paid_amount,
                c.status,
                c.due_date,
                c.paid_date

            FROM contributions c

            INNER JOIN members m
                ON c.member_id = m.id

            INNER JOIN rounds r
                ON c.round_id = r.id

            INNER JOIN mchezo_groups g
                ON r.group_id = g.id

            ORDER BY
                g.group_name ASC,
                r.round_number ASC,
                m.full_name ASC
        ");

    }



    elseif ($role === "member") {

        $stmt = $pdo->prepare("
            SELECT
                c.id,
                m.full_name,
                g.group_name,
                r.round_number,
                c.expected_amount,
                c.paid_amount,
                c.status,
                c.due_date,
                c.paid_date

            FROM contributions c

            INNER JOIN members m
                ON c.member_id = m.id

            INNER JOIN rounds r
                ON c.round_id = r.id

            INNER JOIN mchezo_groups g
                ON r.group_id = g.id

            WHERE c.member_id = ?

            ORDER BY
                r.round_number ASC
        ");

        $stmt->execute([
            currentMemberId()
        ]);
    }


    elseif (in_array($role, ["treasurer", "coordinator"])) {

        $groupId = getCurrentUserGroupId($pdo);

        $stmt = $pdo->prepare("
            SELECT
                c.id,
                m.full_name,
                g.group_name,
                r.round_number,
                c.expected_amount,
                c.paid_amount,
                c.status,
                c.due_date,
                c.paid_date

            FROM contributions c

            INNER JOIN members m
                ON c.member_id = m.id

            INNER JOIN rounds r
                ON c.round_id = r.id

            INNER JOIN mchezo_groups g
                ON r.group_id = g.id

            WHERE r.group_id = ?

            ORDER BY
                r.round_number ASC,
                m.full_name ASC
        ");

        $stmt->execute([
            $groupId
        ]);
    }


    else {

        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" => "You do not have permission to view contribution reports."
        ]);

        exit;
    }



    $contributions =
        $stmt->fetchAll(PDO::FETCH_ASSOC);



    $totalExpected = 0;
    $totalPaid = 0;

    foreach ($contributions as $contribution) {

        $totalExpected +=
            (float) $contribution["expected_amount"];

        $totalPaid +=
            (float) $contribution["paid_amount"];
    }

    $outstanding =
        max(0, $totalExpected - $totalPaid);



    echo json_encode([

        "success" => true,

        "summary" => [

            "total_expected" =>
                $totalExpected,

            "total_paid" =>
                $totalPaid,

            "outstanding" =>
                $outstanding
        ],

        "contributions" =>
            $contributions

    ]);

} catch (PDOException $e) {

    error_log(
        "Contribution report error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to load contribution report."
    ]);
}