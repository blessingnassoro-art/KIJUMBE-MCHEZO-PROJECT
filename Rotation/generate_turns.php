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

    $group_id = intval($_POST["group_id"] ?? 0);

$stmt = $pdo->prepare("
    SELECT group_name
    FROM mchezo_groups
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$group_id]);

$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Mchezo group not found."
    ]);
    exit;
}

    // Get active members
    $stmt = $pdo->prepare("
        SELECT id, full_name
        FROM members
        WHERE group_id = ?
        AND status = 'active'
        ORDER BY id ASC
    ");

    $stmt->execute([$group_id]);

    $members = $stmt->fetchAll();

    if (count($members) === 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "No active members found in this group."
        ]);
        exit;
    }

    // Get rounds for this group
    $stmt = $pdo->prepare("
        SELECT id, round_number
        FROM rounds
        WHERE group_id = ?
        ORDER BY round_number ASC
    ");

    $stmt->execute([$group_id]);

    $rounds = $stmt->fetchAll();

    if (count($rounds) === 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "No rounds found for this group."
        ]);
        exit;
    }

    // Make sure number of rounds matches number of members
    if (count($rounds) !== count($members)) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "The number of rounds must match the number of active members.",
            "members" => count($members),
            "rounds" => count($rounds)
        ]);

        exit;
    }

    // Check whether turns already exist
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM turns t
        INNER JOIN rounds r
            ON t.round_id = r.id
        WHERE r.group_id = ?
    ");

    $stmt->execute([$group_id]);

    $existingTurns = $stmt->fetchColumn();

    if ($existingTurns > 0) {
        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Rotation has already been generated for this group."
        ]);

        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    $insert = $pdo->prepare("
        INSERT INTO turns
        (
            round_id,
            member_id,
            turn_number,
            status
        )
        VALUES (?, ?, ?, 'upcoming')
    ");

    foreach ($rounds as $index => $round) {

        $member = $members[$index];

        $turnNumber = $index + 1;

        $insert->execute([
            $round["id"],
            $member["id"],
            $turnNumber
        ]);
    }

$pdo->commit();

logAudit(
    $_SESSION["user_id"],
    "Generate Turns",
    "Generated " . count($rounds) .
    " rotation turn(s) for Mchezo group \"" .
    $group["group_name"] . "\"."
);

echo json_encode([
        "success" => true,
        "message" => "Rotation generated successfully.",
        "turns_created" => count($rounds)
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to generate rotation."
    ]);
}