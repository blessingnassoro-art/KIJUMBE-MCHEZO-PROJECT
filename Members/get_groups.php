<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "coordinator"]);

$sql = "SELECT id, group_name
        FROM mchezo_groups
        WHERE status = 'active'
        ORDER BY group_name ASC";

$stmt = $pdo->query($sql);

$groups = $stmt->fetchAll();

header("Content-Type: application/json");

echo json_encode($groups);