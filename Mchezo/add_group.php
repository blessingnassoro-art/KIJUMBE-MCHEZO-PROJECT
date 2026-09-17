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


/*
|--------------------------------------------------------------------------
| Get form data
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Validate required fields
|--------------------------------------------------------------------------
*/

if (
    $groupName === "" ||
    $contributionAmount === "" ||
    $frequency === "" ||
    $cycleLength <= 0 ||
    $maxMembers <= 0 ||
    $startDate === ""
) {
    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required fields."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate contribution amount
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

if (
    !in_array(
        $frequency,
        ["weekly", "monthly"],
        true
    )
) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid frequency."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate maximum members
|--------------------------------------------------------------------------
*/

if ($maxMembers < 1) {
    echo json_encode([
        "success" => false,
        "message" => "Maximum members must be at least 1."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Insert Mchezo group
|--------------------------------------------------------------------------
*/

try {

    $sql = "
        INSERT INTO mchezo_groups
        (
            group_name,
            contribution_amount,
            frequency,
            cycle_length,
            max_members,
            start_date,
            status
        )

        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $groupName,
        $contributionAmount,
        $frequency,
        $cycleLength,
        $maxMembers,
        $startDate
    ]);

logAudit(
    $_SESSION["user_id"],
    "Add Mchezo Group",
    "Added Mchezo group \"" . $groupName . "\" with contribution amount " .
    $contributionAmount . ", " . $frequency . " frequency and maximum " .
    $maxMembers . " members."
);

    echo json_encode([
        "success" => true,
        "message" => "Mchezo group added successfully."
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to add Mchezo group."
    ]);
}