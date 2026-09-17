<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

try {

    /*
     * 1. TOTAL MEMBERS
     */
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM members
        WHERE status = 'active'
    ");

    $totalMembers = (int) $stmt->fetchColumn();


    /*
     * 2. ACTIVE Mchezo GROUPS
     */
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM mchezo_groups
        WHERE status = 'active'
    ");

    $totalGroups = (int) $stmt->fetchColumn();


    /*
     * 3. CURRENT / ACTIVE ROUND
     */
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.round_number,
            r.group_id,
            r.expected_amount,
            r.collected_amount,
            r.status,
            g.group_name,
            g.contribution_amount,
            g.cycle_length
        FROM rounds r
        INNER JOIN mchezo_groups g
            ON r.group_id = g.id
        WHERE r.status = 'active'
        ORDER BY r.id DESC
        LIMIT 1
    ");

    $currentRound = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
     * 4. TOTAL COLLECTED
     */
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(amount), 0)
        FROM payments
        WHERE status = 'successful'
    ");

    $totalCollected = (float) $stmt->fetchColumn();


    /*
     * 5. PENDING CONTRIBUTIONS
     */
    if ($currentRound) {

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM contributions
            WHERE round_id = ?
            AND status IN ('pending', 'partial', 'overdue')
        ");

        $stmt->execute([$currentRound["id"]]);

        $pendingContributions = (int) $stmt->fetchColumn();

    } else {

        $pendingContributions = 0;

    }


    /*
     * 6. CURRENT TURN
     */
    $currentTurn = null;

    if ($currentRound) {

        $stmt = $pdo->prepare("
            SELECT
                t.turn_number,
                t.status,
                m.full_name
            FROM turns t
            INNER JOIN members m
                ON t.member_id = m.id
            WHERE t.round_id = ?
            AND t.status = 'current'
            LIMIT 1
        ");

        $stmt->execute([$currentRound["id"]]);

        $currentTurn = $stmt->fetch(PDO::FETCH_ASSOC);

    }


    /*
     * 7. UPCOMING TURN
     */
    $upcomingTurn = null;

    if ($currentRound) {

        $stmt = $pdo->prepare("
            SELECT
                t.turn_number,
                t.status,
                m.full_name,
                r.round_number
            FROM turns t
            INNER JOIN members m
                ON t.member_id = m.id
            INNER JOIN rounds r
                ON t.round_id = r.id
            WHERE t.status = 'upcoming'
            AND r.group_id = ?
            ORDER BY t.turn_number ASC
            LIMIT 1
        ");

        $stmt->execute([$currentRound["group_id"]]);

        $upcomingTurn = $stmt->fetch(PDO::FETCH_ASSOC);

    }


    /*
     * 8. RECENT CONTRIBUTIONS
     */
    $recentContributions = [];

    if ($currentRound) {

        $stmt = $pdo->prepare("
            SELECT
                c.id,
                c.expected_amount,
                c.paid_amount,
                c.status,
                c.paid_date,
                m.full_name,
                r.round_number
            FROM contributions c
            INNER JOIN members m
                ON c.member_id = m.id
            INNER JOIN rounds r
                ON c.round_id = r.id
            WHERE c.round_id = ?
            ORDER BY c.id DESC
            LIMIT 5
        ");

        $stmt->execute([$currentRound["id"]]);

        $recentContributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    }


    /*
     * 9. RECENT PAYMENTS
     */
    $stmt = $pdo->query("
        SELECT
            p.id,
            p.amount,
            p.payment_method,
            p.status,
            p.payment_date,
            p.reference,
            m.full_name
        FROM payments p
        INNER JOIN members m
            ON p.member_id = m.id
        ORDER BY p.id DESC
        LIMIT 5
    ");

    $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);


    /*
     * RETURN DASHBOARD DATA
     */
    echo json_encode([
        "success" => true,

        "members" => [
            "total" => $totalMembers
        ],

        "groups" => [
            "total" => $totalGroups
        ],

        "current_round" => $currentRound,

        "total_collected" => $totalCollected,

        "pending_contributions" => $pendingContributions,

        "current_turn" => $currentTurn,

        "upcoming_turn" => $upcomingTurn,

        "recent_contributions" => $recentContributions,

        "recent_payments" => $recentPayments
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load dashboard data."
    ]);

}