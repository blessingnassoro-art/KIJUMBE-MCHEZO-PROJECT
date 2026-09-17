<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "treasurer", "coordinator", "member"]);

header("Content-Type: application/json");

try {

    /*
     * Members should only see their own contributions.
     * Admin, Treasurer and Coordinator can see all contributions
     * according to their existing permissions.
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

        $sql = "SELECT
                    c.id,
                    c.round_id,
                    c.member_id,
                    m.full_name,
                    r.round_number,
                    mg.group_name,
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

                INNER JOIN mchezo_groups mg
                    ON r.group_id = mg.id

                WHERE c.member_id = ?

                ORDER BY
                    r.round_number ASC,
                    c.due_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$memberId]);

    } else {

        /*
         * Admin / Treasurer / Coordinator
         * Keep the existing behavior.
         */

        $sql = "SELECT
                    c.id,
                    c.round_id,
                    c.member_id,
                    m.full_name,
                    r.round_number,
                    mg.group_name,
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

                INNER JOIN mchezo_groups mg
                    ON r.group_id = mg.id

                ORDER BY
                    r.round_number ASC,
                    m.full_name ASC";

        $stmt = $pdo->query($sql);
    }

    $contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($contributions);

} catch (PDOException $e) {

    error_log("Get contributions error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load contributions."
    ]);
}