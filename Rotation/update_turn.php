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

    $turn_id = intval($_POST["turn_id"] ?? 0);
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

    if (!in_array($status, $allowedStatuses)) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid turn status."
        ]);

        exit;
    }

    // Get the turn
$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.round_id,
        t.member_id,
        t.turn_number,
        m.full_name,
        r.round_number,
        g.group_name
    FROM turns t
    INNER JOIN members m
        ON t.member_id = m.id
    INNER JOIN rounds r
        ON t.round_id = r.id
    INNER JOIN mchezo_groups g
        ON r.group_id = g.id
    WHERE t.id = ?
");

    $stmt->execute([$turn_id]);

    $turn = $stmt->fetch();

    if (!$turn) {
        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Turn not found."
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
            INNER JOIN rounds selected_round
                ON selected_round.id = ?
            WHERE r.group_id = selected_round.group_id
            AND t.status = 'current'
            AND t.id != ?
        ");

        $stmt->execute([
            $turn["round_id"],
            $turn_id
        ]);

        $currentTurn = $stmt->fetchColumn();

        if ($currentTurn > 0) {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Another turn is already current for this group."
            ]);

            exit;
        }
    }

    // Set received date when completed
    if ($status === "completed") {

        $stmt = $pdo->prepare("
            UPDATE turns
            SET status = ?, received_date = CURDATE()
            WHERE id = ?
        ");

    } else {

        $stmt = $pdo->prepare("
            UPDATE turns
            SET status = ?, received_date = NULL
            WHERE id = ?
        ");
    }

$stmt->execute([
    $status,
    $turn_id
]);

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

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update turn status."
    ]);
}