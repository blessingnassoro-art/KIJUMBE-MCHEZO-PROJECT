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

$meetingId = $_POST["meeting_id"] ?? null;
$attendance = $_POST["attendance"] ?? null;

if (!$meetingId || !$attendance) {
    echo json_encode([
        "success" => false,
        "message" => "Meeting ID and attendance data are required."
    ]);
    exit;
}

$attendanceData = json_decode($attendance, true);

if (!is_array($attendanceData)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid attendance data."
    ]);
    exit;
}

try {

    // Confirm meeting exists
$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.title,
        m.meeting_date,
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
        echo json_encode([
            "success" => false,
            "message" => "Meeting not found."
        ]);
        exit;
    }

$pdo->beginTransaction();

$presentCount = 0;
$absentCount = 0;

foreach ($attendanceData as $record) {

        $memberId = $record["member_id"] ?? null;
        $status = $record["status"] ?? null;
        $remarks = trim($record["remarks"] ?? "");

        if (!$memberId || !in_array($status, ["present", "absent"])) {
            continue;
        }

        if ($status === "present") {
            $presentCount++;
        } else {
            $absentCount++;
        }

        // Check whether attendance already exists
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

            // Update existing attendance
            $stmt = $pdo->prepare("
                UPDATE attendance
                SET status = ?, remarks = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $status,
                $remarks ?: null,
                $existing["id"]
            ]);

        } else {

            // Create new attendance
            $stmt = $pdo->prepare("
                INSERT INTO attendance
                (meeting_id, member_id, status, remarks)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $meetingId,
                $memberId,
                $status,
                $remarks ?: null
            ]);
        }
    }

    $presentCount = 0;
$absentCount = 0;

foreach ($attendanceData as $record) {
    if (($record["status"] ?? "") === "present") {
        $presentCount++;
    } elseif (($record["status"] ?? "") === "absent") {
        $absentCount++;
    }
}
$pdo->commit();

logAudit(
    $_SESSION["user_id"],
    "Save Attendance",
    "Saved attendance for meeting \"" . $meeting["title"] .
    "\" (" . $meeting["meeting_date"] .
    ") in Mchezo group \"" . $meeting["group_name"] .
    "\". Present: " . $presentCount .
    ", Absent: " . $absentCount . "."
);

echo json_encode([
    
        "success" => true,
        "message" => "Attendance saved successfully."
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to save attendance."
    ]);
}