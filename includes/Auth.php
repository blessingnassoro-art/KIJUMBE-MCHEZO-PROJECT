<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}




function isLoggedIn()
{
    return isset($_SESSION["user_id"]);
}


function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: loginForm.php");
        exit;
    }
}



function requireRole($allowedRoles)
{
    requireLogin();

    if (!in_array($_SESSION["role"], $allowedRoles, true)) {
        http_response_code(403);

        die("Access denied. You do not have permission to access this page.");
    }
}


function currentUserId()
{
    return (int) ($_SESSION["user_id"] ?? 0);
}


function currentMemberId()
{
    return (int) ($_SESSION["member_id"] ?? 0);
}


function currentRole()
{
    return $_SESSION["role"] ?? null;
}



function requireMemberAccount()
{
    requireLogin();

    if (empty($_SESSION["member_id"])) {
        http_response_code(403);

        die("Your user account is not linked to a member.");
    }
}


function getCurrentUserGroupId($pdo)
{
    requireMemberAccount();

    $stmt = $pdo->prepare("
        SELECT group_id
        FROM members
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $_SESSION["member_id"]
    ]);

    $groupId = $stmt->fetchColumn();

    if (!$groupId) {
        http_response_code(403);

        die("Your member account is not assigned to a Mchezo group.");
    }

    return (int) $groupId;
}



function requireGroupAccess($pdo, $groupId)
{
    requireLogin();

    $groupId = (int) $groupId;

    if ($groupId <= 0) {
        http_response_code(400);

        die("Invalid Mchezo group.");
    }




    if (currentRole() === "admin") {
        return true;
    }



    requireMemberAccount();


    $userGroupId = getCurrentUserGroupId($pdo);


    if ($userGroupId !== $groupId) {
        http_response_code(403);

        die("Access denied. You do not have permission to access this Mchezo group.");
    }

    return true;
}