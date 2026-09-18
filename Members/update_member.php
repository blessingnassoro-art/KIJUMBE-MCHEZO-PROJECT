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




$id = (int) ($_POST["id"] ?? 0);

$fullName = trim(
    $_POST["full_name"] ?? ""
);

$phone = trim(
    $_POST["phone"] ?? ""
);

$email = trim(
    $_POST["email"] ?? ""
);

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



    $check = $pdo->prepare("
        SELECT
            id,
            full_name,
            group_id,
            status
        FROM members
        WHERE id = ?
        LIMIT 1
    ");

    $check->execute([
        $id
    ]);

    $member = $check->fetch(PDO::FETCH_ASSOC);



    if (!$member) {

        echo json_encode([
            "success" => false,
            "message" => "Member not found."
        ]);

        exit;
    }



    if (currentRole() === "coordinator") {

        requireMemberAccount();

        $coordinatorGroupId = getCurrentUserGroupId($pdo);

        if ((int) $member["group_id"] !== $coordinatorGroupId) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Access denied. You can only edit members in your assigned Mchezo group."
            ]);

            exit;
        }
    }



    $sql = "
        UPDATE members

        SET
            full_name = ?,
            phone = ?,
            email = ?,
            join_date = ?,
            status = ?

        WHERE id = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $fullName,
        $phone,
        $email !== "" ? $email : null,
        $joinDate,
        $status,
        $id
    ]);


 

    if ($status === "inactive") {

        $stmt = $pdo->prepare("
            UPDATE users
            SET status = 'inactive'
            WHERE member_id = ?
            AND status = 'active'
        ");

        $stmt->execute([
            $id
        ]);

        $deactivatedUser = $stmt->rowCount();

        if ($deactivatedUser > 0) {

            logAudit(
                $_SESSION["user_id"],
                "Deactivate User",
                "Automatically deactivated the user account linked to member \"" .
                $fullName .
                "\" (Member ID: " .
                $id .
                ")."
            );
        }
    }




    logAudit(
        $_SESSION["user_id"],
        "Edit Member",
        "Updated member \"" .
        $fullName .
        "\" (ID: " .
        $id .
        ")."
    );



    echo json_encode([
        "success" => true,
        "message" => "Member updated successfully."
    ]);

} catch (PDOException $e) {

    error_log(
        "Update member error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update member."
    ]);
}