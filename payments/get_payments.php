<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "treasurer", "member"]);

header("Content-Type: application/json");

try {

    $contributionId = (int) ($_GET["contribution_id"] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | MEMBER
    |--------------------------------------------------------------------------
    | A member can only see payments that belong to their own member account.
    */

    if ($_SESSION["role"] === "member") {

        $memberId = $_SESSION["member_id"] ?? null;

        if (!$memberId) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" => "Your account is not linked to a member."
            ]);

            exit;
        }

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

        /*
        |--------------------------------------------------------------------------
        | If a specific contribution was selected,
        | make sure it also belongs to the logged-in member.
        |--------------------------------------------------------------------------
        */

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

    } else {

        /*
        |--------------------------------------------------------------------------
        | ADMIN / TREASURER
        |--------------------------------------------------------------------------
        | Keep the existing behavior.
        |--------------------------------------------------------------------------
        */

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

        if ($contributionId > 0) {

            $sql .= "
                WHERE p.contribution_id = ?
            ";

            $sql .= "
                ORDER BY p.payment_date DESC, p.id DESC
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $contributionId
            ]);

        } else {

            $sql .= "
                ORDER BY p.payment_date DESC, p.id DESC
            ";

            $stmt = $pdo->query($sql);
        }
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