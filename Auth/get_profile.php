<?php

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

header("Content-Type: application/json");

try {

    requireLogin();

    $userId = currentUserId();

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.member_id,
            u.full_name,
            u.username,
            u.email,
            u.role,
            u.status,
            m.phone,
            m.group_id,
            g.group_name
        FROM users u

        LEFT JOIN members m
            ON u.member_id = m.id

        LEFT JOIN mchezo_groups g
            ON m.group_id = g.id

        WHERE u.id = ?

        LIMIT 1
    ");

    $stmt->execute([$userId]);

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Profile not found."
        ]);

        exit;
    }

    echo json_encode([
        "success" => true,
        "profile" => $profile
    ]);

} catch (PDOException $e) {

    error_log("Get profile error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load profile."
    ]);
}