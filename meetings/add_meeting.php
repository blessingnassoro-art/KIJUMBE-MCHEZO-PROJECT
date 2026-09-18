<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/audit.php";

requireRole(["admin", "coordinator"]);

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

    $groupId = (int) ($_POST["group_id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $meetingDate = $_POST["meeting_date"] ?? "";
    $location = trim($_POST["location"] ?? "");
    $agenda = trim($_POST["agenda"] ?? "");


    /*
     * Validate required fields
     */
    if (
        $groupId <= 0 ||
        $title === "" ||
        $meetingDate === ""
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Group, meeting title and meeting date are required."
        ]);

        exit;
    }


    /*
     * COORDINATOR GROUP SECURITY
     *
     * Admin can create meetings for any group.
     * Coordinator can only create meetings
     * for their own Mchezo group.
     */
    if (currentRole() === "coordinator") {

        requireMemberAccount();

        $coordinatorGroupId = getCurrentUserGroupId($pdo);

        if ($groupId !== $coordinatorGroupId) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" => "Access denied. You can only create meetings for your own Mchezo group."
            ]);

            exit;
        }
    }


    /*
     * Make sure the selected group exists
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            group_name
        FROM mchezo_groups
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$groupId]);

    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Selected Mchezo group does not exist."
        ]);

        exit;
    }



    $createdBy = currentUserId();

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
        $location !== "" ? $location : null,
        $agenda !== "" ? $agenda : null,
        $createdBy
    ]);


    /*
     * Audit log
     */
    logAudit(
        $_SESSION["user_id"],
        "Add Meeting",
        "Created meeting \"" .
        $title .
        "\" for Mchezo group \"" .
        $group["group_name"] .
        "\" on " .
        $meetingDate .
        "."
    );


    echo json_encode([
        "success" => true,
        "message" => "Meeting created successfully.",
        "meeting_id" => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {

    error_log(
        "Add meeting error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to create meeting."
    ]);
}