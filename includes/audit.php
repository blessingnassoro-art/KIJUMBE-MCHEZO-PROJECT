<?php

function logAudit($userId, $action, $details = null)
{
    global $pdo;

    try {

        $stmt = $pdo->prepare("
            INSERT INTO audit_logs
            (
                user_id,
                action,
                details
            )
            VALUES
            (
                ?,
                ?,
                ?
            )
        ");

        $stmt->execute([
            $userId,
            $action,
            $details
        ]);

        return true;

    } catch (PDOException $e) {

        error_log(
            "Audit log error: " . $e->getMessage()
        );

        return false;
    }
}