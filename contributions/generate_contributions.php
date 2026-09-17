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

$roundId = (int) ($_POST["round_id"] ?? 0);

if ($roundId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid round."
    ]);

    exit;
}

try {

    /*
     * Get round and group information
     */
$roundStmt = $pdo->prepare(
    "SELECT
        r.id,
        r.group_id,
        r.round_number,
        r.due_date,
        mg.group_name,
        mg.contribution_amount
     FROM rounds r
     INNER JOIN mchezo_groups mg
         ON r.group_id = mg.id
     WHERE r.id = ?
     LIMIT 1"
);

    $roundStmt->execute([$roundId]);

    $round = $roundStmt->fetch();

    if (!$round) {

        echo json_encode([
            "success" => false,
            "message" => "Round not found."
        ]);

        exit;
    }


    /*
     * Get all active members in this group
     */
    $memberStmt = $pdo->prepare(
        "SELECT id
         FROM members
         WHERE group_id = ?
         AND status = 'active'"
    );

    $memberStmt->execute([
        $round["group_id"]
    ]);

    $members = $memberStmt->fetchAll();


    if (count($members) === 0) {

        echo json_encode([
            "success" => false,
            "message" => "This group has no active members."
        ]);

        exit;
    }


    /*
     * Insert one contribution for each member.
     */
    $insertStmt = $pdo->prepare(
        "INSERT INTO contributions
        (
            round_id,
            member_id,
            expected_amount,
            paid_amount,
            status,
            due_date
        )
        VALUES (?, ?, ?, 0, 'pending', ?)"
    );


    $created = 0;

    foreach ($members as $member) {

        /*
         * Check if contribution already exists.
         */
        $checkStmt = $pdo->prepare(
            "SELECT id
             FROM contributions
             WHERE round_id = ?
             AND member_id = ?
             LIMIT 1"
        );

        $checkStmt->execute([
            $roundId,
            $member["id"]
        ]);


        if ($checkStmt->fetch()) {
            continue;
        }


        $insertStmt->execute([
            $roundId,
            $member["id"],
            $round["contribution_amount"],
            $round["due_date"]
        ]);

        $created++;
    }


logAudit(
    $_SESSION["user_id"],
    "Generate Contributions",
    "Generated " . $created .
    " contribution record(s) for Round " .
    $round["round_number"] .
    " of Mchezo group \"" .
    $round["group_name"] . "\"."
);

echo json_encode([
    "success" => true,
    "message" =>
        $created .
        " contribution record(s) generated successfully."
]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to generate contributions."
    ]);
}