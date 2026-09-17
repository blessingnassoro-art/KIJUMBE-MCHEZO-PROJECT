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

$groupId = (int) ($_POST["group_id"] ?? 0);
$roundNumber = (int) ($_POST["round_number"] ?? 0);
$dueDate = $_POST["due_date"] ?? "";

if (
    $groupId <= 0 ||
    $roundNumber <= 0 ||
    $dueDate === ""
) {

    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required fields."
    ]);

    exit;
}

try {

    /*
     * Get Mchezo group information
     */
$groupStmt = $pdo->prepare(
    "SELECT
        group_name,
        contribution_amount,
        cycle_length
     FROM mchezo_groups
     WHERE id = ?
     LIMIT 1"
);

    $groupStmt->execute([$groupId]);

    $group = $groupStmt->fetch();

    if (!$group) {

        echo json_encode([
            "success" => false,
            "message" => "Mchezo group not found."
        ]);

        exit;
    }


    /*
     * Check that round number does not exceed
     * the group's cycle length.
     */
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
    $checkStmt = $pdo->prepare(
        "SELECT id
         FROM rounds
         WHERE group_id = ?
         AND round_number = ?
         LIMIT 1"
    );

    $checkStmt->execute([
        $groupId,
        $roundNumber
    ]);

    if ($checkStmt->fetch()) {

        echo json_encode([
            "success" => false,
            "message" => "This round already exists for the selected group."
        ]);

        exit;
    }


    /*
     * Count active members
     */
    $memberStmt = $pdo->prepare(
        "SELECT COUNT(*) AS total_members
         FROM members
         WHERE group_id = ?
         AND status = 'active'"
    );

    $memberStmt->execute([$groupId]);

    $memberData = $memberStmt->fetch();

    $totalMembers = (int) $memberData["total_members"];


    /*
     * A round needs at least one active member.
     */
    if ($totalMembers <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "The selected group has no active members."
        ]);

        exit;
    }


    /*
     * Calculate expected collection.
     */
    $expectedAmount =
        $group["contribution_amount"] * $totalMembers;


    /*
     * Insert round.
     */
    $sql = "INSERT INTO rounds
            (
                group_id,
                round_number,
                due_date,
                expected_amount,
                collected_amount,
                status
            )
            VALUES (?, ?, ?, ?, 0, 'upcoming')";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $groupId,
        $roundNumber,
        $dueDate,
        $expectedAmount
    ]);

logAudit(
    $_SESSION["user_id"],
    "Add Round",
    "Created Round " . $roundNumber .
    " for Mchezo group \"" . $group["group_name"] .
    "\" with expected amount " . $expectedAmount .
    " and due date " . $dueDate . "."
    );

    echo json_encode([
        "success" => true,
        "message" => "Round created successfully."
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to create round."
    ]);
}