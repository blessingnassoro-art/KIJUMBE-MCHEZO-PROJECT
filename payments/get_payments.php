<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "treasurer", "coordinator", "member"]);

header("Content-Type: application/json");

try {

    $role = currentRole();
    $contributionId = (int) ($_GET["contribution_id"] ?? 0);


    if ($role === "admin") {

        $sql = "
            SELECT
                p.id,
                p.contribution_id,
                p.member_id,
                m.full_name,
                r.round_number,
                mg.group_name,
                p.amount,
                p.payment_method,
                p.reference,
                p.status,
                p.payment_date,
                p.created_at

            FROM payments p

            INNER JOIN members m
                ON p.member_id = m.id

            INNER JOIN contributions c
                ON p.contribution_id = c.id

            INNER JOIN rounds r
                ON c.round_id = r.id

            INNER JOIN mchezo_groups mg
                ON r.group_id = mg.id
        ";

        $params = [];

        if ($contributionId > 0) {

            $sql .= "
                WHERE p.contribution_id = ?
            ";

            $params[] = $contributionId;
        }

        $sql .= "
            ORDER BY p.payment_date DESC, p.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }



    elseif ($role === "treasurer" || $role === "coordinator") {

        requireMemberAccount();

        $groupId = getCurrentUserGroupId($pdo);

        $sql = "
            SELECT
                p.id,
                p.contribution_id,
                p.member_id,
                m.full_name,
                r.round_number,
                mg.group_name,
                p.amount,
                p.payment_method,
                p.reference,
                p.status,
                p.payment_date,
                p.created_at

            FROM payments p

            INNER JOIN members m
                ON p.member_id = m.id

            INNER JOIN contributions c
                ON p.contribution_id = c.id

            INNER JOIN rounds r
                ON c.round_id = r.id

            INNER JOIN mchezo_groups mg
                ON r.group_id = mg.id

            WHERE r.group_id = ?
        ";

        $params = [$groupId];

        if ($contributionId > 0) {

            $sql .= "
                AND p.contribution_id = ?
            ";

            $params[] = $contributionId;
        }

        $sql .= "
            ORDER BY p.payment_date DESC, p.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }




    else {

        requireMemberAccount();

        $memberId = currentMemberId();

        $sql = "
            SELECT
                p.id,
                p.contribution_id,
                p.member_id,
                m.full_name,
                r.round_number,
                mg.group_name,
                p.amount,
                p.payment_method,
                p.reference,
                p.status,
                p.payment_date,
                p.created_at

            FROM payments p

            INNER JOIN members m
                ON p.member_id = m.id

            INNER JOIN contributions c
                ON p.contribution_id = c.id

            INNER JOIN rounds r
                ON c.round_id = r.id

            INNER JOIN mchezo_groups mg
                ON r.group_id = mg.id

            WHERE p.member_id = ?
        ";

        $params = [$memberId];

        if ($contributionId > 0) {

            $sql .= "
                AND p.contribution_id = ?
            ";

            $params[] = $contributionId;
        }

        $sql .= "
            ORDER BY p.payment_date DESC, p.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }


    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($payments);

} catch (PDOException $e) {

    error_log("Get payments error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load payments."
    ]);
}