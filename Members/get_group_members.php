<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireLogin();

header("Content-Type: application/json");

$groupId = (int) ($_GET["group_id"] ?? 0);

if ($groupId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid group ID."
    ]);

    exit;
}

try {

    $sql = "
        SELECT
            members.id,
            members.full_name,
            members.phone,
            members.email,
            members.join_date,
            members.status,
            mchezo_groups.group_name

        FROM members

        INNER JOIN mchezo_groups
            ON members.group_id = mchezo_groups.id

        WHERE members.group_id = ?

        ORDER BY members.full_name ASC
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$groupId]);

    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        "success" => true,
        "members" => $members
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load group members."
    ]);
}