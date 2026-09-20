<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/audit.php";

requireRole([
    "admin",
    "coordinator",
    "treasurer"
]);

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

$memberId = (int)($_POST["member_id"] ?? 0);
$meetingId = (int)($_POST["meeting_id"] ?? 0);
$amount = $_POST["amount"] ?? "";
$reason = trim($_POST["reason"] ?? "");


if ($memberId <= 0 || $amount === "" || $reason === "") {

    echo json_encode([
        "success" => false,
        "message" => "Member, amount and reason are required."
    ]);

    exit;
}


if (!is_numeric($amount) || $amount <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Fine amount must be greater than zero."
    ]);

    exit;
}


try {

    $role = currentRole();


    /*
     * Find the selected member
     * and their Mchezo group.
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            full_name,
            group_id
        FROM members
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$memberId]);

    $member = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$member) {

        echo json_encode([
            "success" => false,
            "message" => "Selected member does not exist."
        ]);

        exit;
    }


    /*
     * ADMIN
     * Can record fines for any group.
     *
     * TREASURER / COORDINATOR
     * Can only record fines for their own group.
     */
    if ($role !== "admin") {

        requireMemberAccount();

        $userGroupId =
            getCurrentUserGroupId($pdo);

        if ((int)$member["group_id"] !== $userGroupId) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Access denied. You can only record fines for your own Mchezo group."
            ]);

            exit;
        }
    }


    /*
     * Meeting is optional.
     */
    if ($meetingId > 0) {

        $stmt = $pdo->prepare("
            SELECT
                id,
                group_id,
                title
            FROM meetings
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$meetingId]);

        $meeting =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$meeting) {

            echo json_encode([
                "success" => false,
                "message" => "Selected meeting does not exist."
            ]);

            exit;
        }


        /*
         * Make sure the meeting belongs
         * to the same group as the member.
         */
        if (
            (int)$meeting["group_id"] !==
            (int)$member["group_id"]
        ) {

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" =>
                    "The selected meeting does not belong to the member's Mchezo group."
            ]);

            exit;
        }


        /*
         * Treasurer / Coordinator must also
         * be allowed to access that group.
         */
        if ($role !== "admin") {

            $userGroupId =
                getCurrentUserGroupId($pdo);

            if (
                (int)$meeting["group_id"] !==
                $userGroupId
            ) {

                http_response_code(403);

                echo json_encode([
                    "success" => false,
                    "message" =>
                        "Access denied. You cannot use a meeting from another Mchezo group."
                ]);

                exit;
            }
        }

    } else {

        $meetingId = null;
    }


    /*
     * Create the fine.
     */
    $stmt = $pdo->prepare("
        INSERT INTO fines
        (
            member_id,
            meeting_id,
            amount,
            reason,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'unpaid'
        )
    ");

    $stmt->execute([
        $memberId,
        $meetingId,
        $amount,
        $reason
    ]);


    /*
     * Audit log.
     */
    logAudit(
        $_SESSION["user_id"],
        "Record Fine",
        "Recorded a fine of " . $amount .
        " for member \"" . $member["full_name"] .
        "\". Reason: " . $reason . "."
    );


    echo json_encode([
        "success" => true,
        "message" => "Fine recorded successfully.",
        "fine_id" => $pdo->lastInsertId()
    ]);


} catch (PDOException $e) {

    error_log(
        "Add fine error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to record fine."
    ]);
}