<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "coordinator"]);

$sql = "SELECT
            id,
            group_name,
            contribution_amount,
            frequency,
            cycle_length,
            start_date,
            status
        FROM mchezo_groups
        ORDER BY id DESC";

$stmt = $pdo->query($sql);

$groups = $stmt->fetchAll();

header("Content-Type: application/json");

echo json_encode($groups);