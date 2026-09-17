<?php

require_once "../includes/auth.php";
require_once "../includes/db.php";

requireLogin();

header("Content-Type: application/json");

try {

    $role = currentRole();



    $groupId = null;
    $memberId = null;

    if ($role !== "admin") {

        requireMemberAccount();

        $memberId = currentMemberId();

        $groupId = getCurrentUserGroupId($pdo);
    }




    if ($role === "admin") {

        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM members
            WHERE status = 'active'
        ");

        $totalMembers = (int) $stmt->fetchColumn();

    } else {

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM members
            WHERE group_id = ?
            AND status = 'active'
        ");

        $stmt->execute([$groupId]);

        $totalMembers = (int) $stmt->fetchColumn();
    }




    if ($role === "admin") {

        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM mchezo_groups
            WHERE status = 'active'
        ");

        $totalGroups = (int) $stmt->fetchColumn();

    } else {



        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM mchezo_groups
            WHERE id = ?
            AND status = 'active'
        ");

        $stmt->execute([$groupId]);

        $totalGroups = (int) $stmt->fetchColumn();
    }




    if ($role === "admin") {

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

    } else {

        $stmt = $pdo->prepare("
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
            AND r.group_id = ?
            ORDER BY r.id DESC
            LIMIT 1
        ");

        $stmt->execute([$groupId]);
    }

    $currentRound = $stmt->fetch(PDO::FETCH_ASSOC);




    if ($role === "admin") {

        $stmt = $pdo->query("
            SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE status = 'successful'
        ");

        $totalCollected = (float) $stmt->fetchColumn();

    } elseif ($role === "member") {

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(p.amount), 0)
            FROM payments p
            WHERE p.member_id = ?
            AND p.status = 'successful'
        ");

        $stmt->execute([$memberId]);

        $totalCollected = (float) $stmt->fetchColumn();

    } else {

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(p.amount), 0)
            FROM payments p
            INNER JOIN contributions c
                ON p.contribution_id = c.id
            INNER JOIN rounds r
                ON c.round_id = r.id
            WHERE r.group_id = ?
            AND p.status = 'successful'
        ");

        $stmt->execute([$groupId]);

        $totalCollected = (float) $stmt->fetchColumn();
    }




    $pendingContributions = 0;

    if ($role === "member") {



        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM contributions c
            WHERE c.member_id = ?
            AND c.status IN ('pending', 'partial', 'overdue')
        ");

        $stmt->execute([$memberId]);

        $pendingContributions = (int) $stmt->fetchColumn();

    } elseif ($currentRound) {



        if ($role === "admin") {

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM contributions
                WHERE round_id = ?
                AND status IN ('pending', 'partial', 'overdue')
            ");

            $stmt->execute([
                $currentRound["id"]
            ]);

        } else {

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM contributions c
                INNER JOIN rounds r
                    ON c.round_id = r.id
                WHERE r.group_id = ?
                AND c.status IN ('pending', 'partial', 'overdue')
            ");

            $stmt->execute([
                $groupId
            ]);
        }

        $pendingContributions = (int) $stmt->fetchColumn();
    }




    $currentTurn = null;

    if ($currentRound) {

        if ($role === "member") {

            /*
             * Member should only see their own current turn.
             */

            $stmt = $pdo->prepare("
                SELECT
                    t.turn_number,
                    t.status,
                    m.full_name
                FROM turns t
                INNER JOIN members m
                    ON t.member_id = m.id
                WHERE t.round_id = ?
                AND t.member_id = ?
                AND t.status = 'current'
                LIMIT 1
            ");

            $stmt->execute([
                $currentRound["id"],
                $memberId
            ]);

        } else {

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

            $stmt->execute([
                $currentRound["id"]
            ]);
        }

        $currentTurn = $stmt->fetch(PDO::FETCH_ASSOC);
    }




    $upcomingTurn = null;

    if ($currentRound) {

        if ($role === "member") {



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
                AND t.member_id = ?
                ORDER BY t.turn_number ASC
                LIMIT 1
            ");

            $stmt->execute([
                $groupId,
                $memberId
            ]);

        } else {

            /*
             * Treasurer / Coordinator / Admin:
             * appropriate group scope.
             */

            if ($role === "admin") {

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

                $stmt->execute([
                    $currentRound["group_id"]
                ]);

            } else {

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

                $stmt->execute([
                    $groupId
                ]);
            }
        }

        $upcomingTurn = $stmt->fetch(PDO::FETCH_ASSOC);
    }




    $recentContributions = [];

    if ($role === "admin") {

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

            $stmt->execute([
                $currentRound["id"]
            ]);

            $recentContributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } elseif ($role === "member") {



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
            WHERE c.member_id = ?
            ORDER BY c.id DESC
            LIMIT 5
        ");

        $stmt->execute([
            $memberId
        ]);

        $recentContributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {



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
            WHERE r.group_id = ?
            ORDER BY c.id DESC
            LIMIT 5
        ");

        $stmt->execute([
            $groupId
        ]);

        $recentContributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    if ($role === "admin") {



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

    } elseif ($role === "member") {



        $stmt = $pdo->prepare("
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
            WHERE p.member_id = ?
            ORDER BY p.id DESC
            LIMIT 5
        ");

        $stmt->execute([
            $memberId
        ]);

    } else {


        $stmt = $pdo->prepare("
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
            INNER JOIN contributions c
                ON p.contribution_id = c.id
            INNER JOIN rounds r
                ON c.round_id = r.id
            WHERE r.group_id = ?
            ORDER BY p.id DESC
            LIMIT 5
        ");

        $stmt->execute([
            $groupId
        ]);
    }

    $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);




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

    error_log("Get dashboard error: " . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load dashboard data."
    ]);
}