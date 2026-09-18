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

$meetingId = (int) ($_POST["meeting_id"] ?? 0);
$attendance = $_POST["attendance"] ?? null;

if ($meetingId <= 0 || !$attendance) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Meeting ID and attendance data are required."
    ]);

    exit;
}

$attendanceData = json_decode($attendance, true);

if (!is_array($attendanceData)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid attendance data."
    ]);

    exit;
}

try {

    /*
     * Get meeting and its Mchezo group.
     */
    $stmt = $pdo->prepare("
        SELECT
            m.id,
            m.title,
            m.meeting_date,
            m.group_id,
            g.group_name
        FROM meetings m
        INNER JOIN mchezo_groups g
            ON m.group_id = g.id
        WHERE m.id = ?
        LIMIT 1
    ");

    $stmt->execute([$meetingId]);

    $meeting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$meeting) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Meeting not found."
        ]);

        exit;
    }


    /*
     * COORDINATOR GROUP SECURITY
     *
     * Admin can save attendance for any group.
     * Coordinator can only save attendance
     * for meetings belonging to their own group.
     */
    if (currentRole() === "coordinator") {

        requireMemberAccount();

        $coordinatorGroupId = getCurrentUserGroupId($pdo);

        if ((int) $meeting["group_id"] !== $coordinatorGroupId) {

            http_response_code(403);

            echo json_encode([
                "success" => false,
                "message" => "Access denied. You can only save attendance for your own Mchezo group."
            ]);

            exit;
        }
    }


    /*
     * Start database transaction.
     */
    $pdo->beginTransaction();

    $presentCount = 0;
    $absentCount = 0;


    /*
     * Process each attendance record.
     */
    foreach ($attendanceData as $record) {

        $memberId = (int) ($record["member_id"] ?? 0);
        $status = $record["status"] ?? "";
        $remarks = trim($record["remarks"] ?? "");


        /*
         * Validate attendance record.
         */
        if (
            $memberId <= 0 ||
            !in_array($status, ["present", "absent"], true)
        ) {
            continue;
        }


        /*
         * IMPORTANT SECURITY CHECK
         *
         * Make sure the member actually belongs
         * to the meeting's Mchezo group.
         */
        $memberCheck = $pdo->prepare("
            SELECT id
            FROM members
            WHERE id = ?
            AND group_id = ?
            LIMIT 1
        ");

        $memberCheck->execute([
            $memberId,
            $meeting["group_id"]
        ]);

        $validMember = $memberCheck->fetchColumn();

        if (!$validMember) {

            $pdo->rollBack();

            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "One or more selected members do not belong to this meeting's Mchezo group."
            ]);

            exit;
        }


        /*
         * Count attendance.
         */
        if ($status === "present") {
            $presentCount++;
        } else {
            $absentCount++;
        }


        /*
         * Check whether attendance already exists.
         */
        $stmt = $pdo->prepare("
            SELECT id
            FROM attendance
            WHERE meeting_id = ?
            AND member_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $meetingId,
            $memberId
        ]);

        $existing = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($existing) {

            /*
             * Update existing attendance.
             */
            $stmt = $pdo->prepare("
                UPDATE attendance
                SET status = ?,
                    remarks = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $status,
                $remarks !== "" ? $remarks : null,
                $existing["id"]
            ]);

        } else {

            /*
             * Create new attendance.
             */
            $stmt = $pdo->prepare("
                INSERT INTO attendance
                (
                    meeting_id,
                    member_id,
                    status,
                    remarks
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $meetingId,
                $memberId,
                $status,
                $remarks !== "" ? $remarks : null
            ]);
        }
    }


    /*
     * Commit all attendance changes.
     */
    $pdo->commit();


    /*
     * Audit log.
     */
    logAudit(
        $_SESSION["user_id"],
        "Save Attendance",
        "Saved attendance for meeting \"" .
        $meeting["title"] .
        "\" (" .
        $meeting["meeting_date"] .
        ") in Mchezo group \"" .
        $meeting["group_name"] .
        "\". Present: " .
        $presentCount .
        ", Absent: " .
        $absentCount .
        "."
    );


    echo json_encode([
        "success" => true,
        "message" => "Attendance saved successfully."
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Save attendance error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to save attendance."
    ]);
}