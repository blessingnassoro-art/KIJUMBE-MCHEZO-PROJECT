<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "coordinator"]);

header("Content-Type: application/json");

try {

    $role = currentRole();


    if ($role === "admin") {

        $sql = "
            SELECT
                id,
                group_name,
                contribution_amount,
                frequency,
                cycle_length,
                max_members,
                start_date,
                status
            FROM mchezo_groups
            ORDER BY id DESC
        ";

        $stmt = $pdo->query($sql);
    }



    else {

        requireMemberAccount();

        $groupId = getCurrentUserGroupId($pdo);

        $sql = "
            SELECT
                id,
                group_name,
                contribution_amount,
                frequency,
                cycle_length,
                max_members,
                start_date,
                status
            FROM mchezo_groups
            WHERE id = ?
            ORDER BY id DESC
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $groupId
        ]);
    }


    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($groups);

} catch (PDOException $e) {

    error_log("Get Mchezo groups error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load Mchezo groups."
    ]);
}