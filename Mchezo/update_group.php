<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";
require_once "../includes/audit.php";

requireRole(["admin", "coordinator"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$id = (int) ($_POST["id"] ?? 0);

$groupName = trim($_POST["group_name"] ?? "");

$contributionAmount =
    $_POST["contribution_amount"] ?? "";

$frequency =
    $_POST["frequency"] ?? "";

$cycleLength =
    (int) ($_POST["cycle_length"] ?? 0);

$maxMembers =
    (int) ($_POST["max_members"] ?? 0);

$startDate =
    $_POST["start_date"] ?? "";

$status =
    $_POST["status"] ?? "";


/*
|--------------------------------------------------------------------------
| Validate required fields
|--------------------------------------------------------------------------
*/

if (
    $id <= 0 ||
    $groupName === "" ||
    $contributionAmount === "" ||
    $frequency === "" ||
    $cycleLength <= 0 ||
    $maxMembers <= 0 ||
    $startDate === "" ||
    $status === ""
) {

    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required fields."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate amount
|--------------------------------------------------------------------------
*/

if (
    !is_numeric($contributionAmount) ||
    $contributionAmount <= 0
) {

    echo json_encode([
        "success" => false,
        "message" => "Contribution amount must be greater than zero."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate frequency
|--------------------------------------------------------------------------
*/

if (!in_array($frequency, ["weekly", "monthly"], true)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid frequency."
    ]);

    exit;
}

if ($maxMembers < 1) {
    echo json_encode([
        "success" => false,
        "message" => "Maximum members must be at least 1."
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Validate status
|--------------------------------------------------------------------------
*/

if (!in_array(
    $status,
    ["active", "inactive", "completed"],
    true
)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid group status."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check group exists
|--------------------------------------------------------------------------
*/

$check = $pdo->prepare(
    "SELECT id, group_name
     FROM mchezo_groups
     WHERE id = ?
     LIMIT 1"
);

$check->execute([$id]);

$group = $check->fetch(PDO::FETCH_ASSOC);

if (!$group) {

    echo json_encode([
        "success" => false,
        "message" => "Mchezo group not found."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Update group
|--------------------------------------------------------------------------
*/

$sql = "UPDATE mchezo_groups
        SET group_name = ?,
            contribution_amount = ?,
            frequency = ?,
            cycle_length = ?,
            max_members = ?,
            start_date = ?,
            status = ?
        WHERE id = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $groupName,
    $contributionAmount,
    $frequency,
    $cycleLength,
    $maxMembers,
    $startDate,
    $status,
    $id
]);

if ($status === "inactive") {

    logAudit(
        $_SESSION["user_id"],
        "Deactivate Mchezo Group",
        "Deactivated Mchezo group \"" . $groupName . "\" (ID: " . $id . ")."
    );

} else {

    logAudit(
        $_SESSION["user_id"],
        "Edit Mchezo Group",
        "Updated Mchezo group \"" . $groupName . "\" (ID: " . $id . ")."
    );
}

echo json_encode([
    "success" => true,
    "message" => "Mchezo group updated successfully."
]);