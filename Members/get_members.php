<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

requireRole(["admin", "coordinator"]);
 
$sql = "SELECT 
            members.id,
            members.group_id,
            members.full_name,
            members.phone,
            members.email,
            members.join_date,
            members.status,
            mchezo_groups.group_name,
            mchezo_groups.max_members,

            (
                SELECT COUNT(*)
                FROM members AS group_members
                WHERE group_members.group_id = members.group_id
                AND group_members.status = 'active'
            ) AS current_members

        FROM members

        INNER JOIN mchezo_groups
            ON members.group_id = mchezo_groups.id

        ORDER BY members.id DESC";

$stmt = $pdo->query($sql);

$members = $stmt->fetchAll();

header("Content-Type: application/json");

echo json_encode($members);