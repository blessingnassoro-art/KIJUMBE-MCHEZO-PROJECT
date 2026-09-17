<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/audit.php";

requireLogin();

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

try {

    $groupId = $_POST["group_id"] ?? null;
    $title = trim($_POST["title"] ?? "");
    $meetingDate = $_POST["meeting_date"] ?? null;
    $location = trim($_POST["location"] ?? "");
    $agenda = trim($_POST["agenda"] ?? "");

    /*
     * Validate required fields
     */
    if (!$groupId || !$title || !$meetingDate) {

        echo json_encode([
            "success" => false,
            "message" => "Group, meeting title and meeting date are required."
        ]);

        exit;
    }


    /*
     * Make sure the selected group exists
     */
$stmt = $pdo->prepare("
    SELECT id, group_name
    FROM mchezo_groups
    WHERE id = ?
    LIMIT 1
");

    $stmt->execute([$groupId]);

$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {

        echo json_encode([
            "success" => false,
            "message" => "Selected Mchezo group does not exist."
        ]);

        exit;
    }


    /*
     * Get the logged-in user's ID
     */
    $createdBy = $_SESSION["user_id"] ?? null;


    /*
     * Insert meeting
     */
    $stmt = $pdo->prepare("
        INSERT INTO meetings
        (
            group_id,
            title,
            meeting_date,
            location,
            agenda,
            created_by
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([
        $groupId,
        $title,
        $meetingDate,
        $location ?: null,
        $agenda ?: null,
        $createdBy
    ]);

    logAudit(
        $_SESSION["user_id"],
        "Add Meeting",
        "Created meeting \"" . $title .
        "\" for Mchezo group \"" . $group["group_name"] .
        "\" on " . $meetingDate . "."
    );
    echo json_encode([
        "success" => true,
        "message" => "Meeting created successfully.",
        "meeting_id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to create meeting."
    ]);
}