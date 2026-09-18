<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/audit.php";

requireRole(["admin", "coordinator"]);

header("Content-Type: application/json");

try {

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        http_response_code(405);

        echo json_encode([
            "success" => false,
            "message" => "Only POST requests are allowed."
        ]);

        exit;
    }

    $turn_id = (int) ($_POST["turn_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    if ($turn_id <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid turn ID."
        ]);

        exit;
    }

    $allowedStatuses = [
        "upcoming",
        "current",
        "completed"
    ];

    if (!in_array($status, $allowedStatuses, true)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid turn status."
        ]);

        exit;
    }


    /*
     * Get the turn and its Mchezo group.
     */
    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.round_id,
            t.member_id,
            t.turn_number,
            t.status AS old_status,
            m.full_name,
            r.round_number,
            r.group_id,
            g.group_name
        FROM turns t
        INNER JOIN members m
            ON t.member_id = m.id
        INNER JOIN rounds r
            ON t.round_id = r.id
        INNER JOIN mchezo_groups g
            ON r.group_id = g.id
        WHERE t.id = ?
        LIMIT 1
    ");

    $stmt->execute([$turn_id]);

    $turn = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$turn) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Turn not found."
        ]);

        exit;
    }


    /*
     * COORDINATOR GROUP SECURITY
     *
     * Admin can manage turns from any group.
     * Coordinator can only manage turns
     * belonging to their own Mchezo group.
     */
    if (currentRole() === "coordinator") {

        requireMemberAccount();

        $coordinatorGroupId = getCurrentUserGroupId($pdo);

        if ((int) $turn["group_id"] !== $coordinatorGroupId) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" => "Access denied. You can only manage turns in your own Mchezo group."
            ]);

            exit;
        }
    }


    /*
     * Prevent unnecessary status update.
     */
    if ($turn["old_status"] === $status) {

        echo json_encode([
            "success" => false,
            "message" => "Turn is already " . $status . "."
        ]);

        exit;
    }


    /*
     * If this turn becomes CURRENT,
     * make sure another turn in the same group
     * is not already current.
     */
    if ($status === "current") {

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM turns t
            INNER JOIN rounds r
                ON t.round_id = r.id
            WHERE r.group_id = ?
            AND t.status = 'current'
            AND t.id != ?
        ");

        $stmt->execute([
            $turn["group_id"],
            $turn_id
        ]);

        $currentTurn = (int) $stmt->fetchColumn();

        if ($currentTurn > 0) {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Another turn is already current for this group."
            ]);

            exit;
        }
    }


    /*
     * Set received date when completed.
     */
    if ($status === "completed") {

        $stmt = $pdo->prepare("
            UPDATE turns
            SET status = ?,
                received_date = CURDATE()
            WHERE id = ?
        ");

    } else {

        $stmt = $pdo->prepare("
            UPDATE turns
            SET status = ?,
                received_date = NULL
            WHERE id = ?
        ");
    }

    $stmt->execute([
        $status,
        $turn_id
    ]);


    /*
     * Audit log
     */
    if ($status === "current") {

        logAudit(
            $_SESSION["user_id"],
            "Start Turn",
            "Started Turn " . $turn["turn_number"] .
            " for member \"" . $turn["full_name"] .
            "\" in Round " . $turn["round_number"] .
            " of Mchezo group \"" . $turn["group_name"] . "\"."
        );

    } elseif ($status === "completed") {

        logAudit(
            $_SESSION["user_id"],
            "Complete Turn",
            "Completed Turn " . $turn["turn_number"] .
            " for member \"" . $turn["full_name"] .
            "\" in Round " . $turn["round_number"] .
            " of Mchezo group \"" . $turn["group_name"] . "\"."
        );
    }


    echo json_encode([
        "success" => true,
        "message" => "Turn status updated successfully."
    ]);

} catch (PDOException $e) {

    error_log(
        "Update turn error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update turn status."
    ]);
}