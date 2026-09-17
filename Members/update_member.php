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
$fullName = trim($_POST["full_name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$email = trim($_POST["email"] ?? "");
$joinDate = $_POST["join_date"] ?? "";
$status = $_POST["status"] ?? "";

if (
    $id <= 0 ||
    $fullName === "" ||
    $phone === "" ||
    $joinDate === "" ||
    !in_array($status, ["active", "inactive"], true)
) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid member information."
    ]);
    exit;
}

if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email address."
    ]);
    exit;
}

$check = $pdo->prepare(
    "SELECT id FROM members WHERE id = ? LIMIT 1"
);

$check->execute([$id]);

if (!$check->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "Member not found."
    ]);
    exit;
}

$sql = "UPDATE members
        SET full_name = ?,
            phone = ?,
            email = ?,
            join_date = ?,
            status = ?
        WHERE id = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $fullName,
    $phone,
    $email !== "" ? $email : null,
    $joinDate,
    $status,
    $id
]);

logAudit(
    $_SESSION["user_id"],
    "Edit Member",
    "Updated member \"" . $fullName . "\" (ID: " . $id . ")."
);

echo json_encode([
    "success" => true,
    "message" => "Member updated successfully."
]);