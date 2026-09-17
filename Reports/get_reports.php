<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

try {

    /*
     * 1. Financial summary
     */

    $stmt = $pdo->query("
        SELECT
            COALESCE(SUM(collected_amount), 0) AS total_contributions
        FROM rounds
    ");

    $financial = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
     * 2. Total fines
     */

    $stmt = $pdo->query("
        SELECT
            COALESCE(SUM(amount), 0) AS total_fines
        FROM fines
    ");

    $fines = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
     * 3. Turn statistics
     */

    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total_turns,
            SUM(
                CASE
                    WHEN status = 'completed'
                    THEN 1
                    ELSE 0
                END
            ) AS completed_turns
        FROM turns
    ");

    $turns = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
     * 4. Collection performance by round
     */

    $stmt = $pdo->query("
        SELECT
            r.id,
            r.round_number,
            r.expected_amount,
            r.collected_amount,
            r.status,
            g.group_name
        FROM rounds r
        INNER JOIN mchezo_groups g
            ON r.group_id = g.id
        ORDER BY
            g.group_name ASC,
            r.round_number ASC
    ");

    $rounds = $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        "success" => true,

        "summary" => [
            "total_contributions" =>
                (float) ($financial["total_contributions"] ?? 0),

            "total_fines" =>
                (float) ($fines["total_fines"] ?? 0),

            "completed_turns" =>
                (int) ($turns["completed_turns"] ?? 0),

            "total_turns" =>
                (int) ($turns["total_turns"] ?? 0)
        ],

        "rounds" => $rounds
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load reports."
    ]);
}