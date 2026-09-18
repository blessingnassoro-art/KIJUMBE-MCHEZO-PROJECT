<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/audit.php";

requireRole(["admin", "coordinator"]);

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$id = (int) ($_POST["id"] ?? 0);
$roundNumber = (int) ($_POST["round_number"] ?? 0);
$dueDate = $_POST["due_date"] ?? "";
$status = $_POST["status"] ?? "";

if (
    $id <= 0 ||
    $roundNumber <= 0 ||
    $dueDate === "" ||
    $status === ""
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required fields."
    ]);

    exit;
}

if (!in_array(
    $status,
    ["upcoming", "active", "completed"],
    true
)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid round status."
    ]);

    exit;
}

try {

    /*
     * Get the existing round
     */
    $check = $pdo->prepare(
        "SELECT
            r.group_id,
            r.round_number AS old_round_number,
            r.due_date AS old_due_date,
            r.status AS old_status,
            g.group_name
         FROM rounds r
         INNER JOIN mchezo_groups g
            ON r.group_id = g.id
         WHERE r.id = ?
         LIMIT 1"
    );

    $check->execute([$id]);

    $round = $check->fetch(PDO::FETCH_ASSOC);

    if (!$round) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Round not found."
        ]);

        exit;
    }

    $groupId = (int) $round["group_id"];


    if (currentRole() === "coordinator") {

        requireMemberAccount();

        $coordinatorGroupId = getCurrentUserGroupId($pdo);

        if ($groupId !== $coordinatorGroupId) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" => "Access denied. You can only edit rounds in your own Mchezo group."
            ]);

            exit;
        }
    }



    $groupStmt = $pdo->prepare(
        "SELECT cycle_length
         FROM mchezo_groups
         WHERE id = ?
         LIMIT 1"
    );

    $groupStmt->execute([$groupId]);

    $group = $groupStmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Mchezo group not found."
        ]);

        exit;
    }

    if ($roundNumber > (int) $group["cycle_length"]) {

        echo json_encode([
            "success" => false,
            "message" => "Round number cannot exceed the group's cycle length."
        ]);

        exit;
    }


    /*
     * Check for duplicate round number
     */
    $duplicate = $pdo->prepare(
        "SELECT id
         FROM rounds
         WHERE group_id = ?
         AND round_number = ?
         AND id != ?
         LIMIT 1"
    );

    $duplicate->execute([
        $groupId,
        $roundNumber,
        $id
    ]);

    if ($duplicate->fetch()) {

        echo json_encode([
            "success" => false,
            "message" => "This round number already exists for this group."
        ]);

        exit;
    }


    /*
     * Update round
     */
    $sql = "UPDATE rounds
            SET round_number = ?,
                due_date = ?,
                status = ?
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $roundNumber,
        $dueDate,
        $status,
        $id
    ]);


   
    logAudit(
        $_SESSION["user_id"],
        "Edit Round",
        "Updated Round " . $roundNumber .
        " for Mchezo group \"" . $round["group_name"] .
        "\" (ID: " . $id . "). Status: " . $status .
        ", due date: " . $dueDate . "."
    );


    echo json_encode([
        "success" => true,
        "message" => "Round updated successfully."
    ]);

} catch (PDOException $e) {

    error_log(
        "Update round error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update round."
    ]);
}