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


$fullName = trim($_POST["full_name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$email = trim($_POST["email"] ?? "");
$groupId = (int) ($_POST["group_id"] ?? 0);
$joinDate = $_POST["join_date"] ?? "";



if (
    $fullName === "" ||
    $phone === "" ||
    $groupId <= 0 ||
    $joinDate === ""
) {

    echo json_encode([
        "success" => false,
        "message" => "Please fill in all required fields."
    ]);

    exit;
}




if (
    $email !== "" &&
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid email address."
    ]);

    exit;
}


try {



    $checkGroup = $pdo->prepare("
        SELECT
            g.id,
            g.group_name,
            g.max_members,
            COUNT(m.id) AS current_members

        FROM mchezo_groups g

        LEFT JOIN members m
            ON m.group_id = g.id
            AND m.status = 'active'

        WHERE g.id = ?

        GROUP BY
            g.id,
            g.group_name,
            g.max_members

        LIMIT 1
    ");

    $checkGroup->execute([
        $groupId
    ]);

    $group = $checkGroup->fetch(PDO::FETCH_ASSOC);




    if (!$group) {

        echo json_encode([
            "success" => false,
            "message" => "Selected Mchezo group does not exist."
        ]);

        exit;
    }




    if (currentRole() === "coordinator") {

        requireMemberAccount();

        $coordinatorGroupId = getCurrentUserGroupId($pdo);

        if ($groupId !== $coordinatorGroupId) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Access denied. You can only add members to your assigned Mchezo group."
            ]);

            exit;
        }
    }



    $currentMembers = (int) $group["current_members"];
    $maxMembers = (int) $group["max_members"];


    if ($currentMembers >= $maxMembers) {

        echo json_encode([
            "success" => false,
            "message" =>
                "This Mchezo group is full. " .
                "Maximum members allowed: " .
                $maxMembers . "."
        ]);

        exit;
    }




    $sql = "
        INSERT INTO members
        (
            group_id,
            full_name,
            phone,
            email,
            join_date,
            status
        )

        VALUES (?, ?, ?, ?, ?, 'active')
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $groupId,
        $fullName,
        $phone,
        $email !== "" ? $email : null,
        $joinDate
    ]);



    logAudit(
        $_SESSION["user_id"],
        "Add Member",
        "Added member \"" .
        $fullName .
        "\" to " .
        $group["group_name"] .
        " Mchezo Group."
    );




    echo json_encode([
        "success" => true,
        "message" => "Member added successfully."
    ]);

} catch (PDOException $e) {

    error_log("Add member error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to add member."
    ]);
}