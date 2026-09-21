<?php

require_once "includes/auth.php";

requireLogin();

$fullName = $_SESSION["full_name"];
$role = $_SESSION["role"];

$permissions = [
"admin" => [
    "dashboard",
    "mchezo",
    "members",
    "rounds",
    "contributions",
    "rotation",
    "payments",
    "meetings",
    "fines",
    "transactions",
    "reports",
    "auditLogs",
    "settings",
    "profile"
],

"treasurer" => [
    "dashboard",
    "contributions",
    "payments",
    "fines",
    "transactions",
    "reports",
    "profile"
],

"coordinator" => [
    "dashboard",
    "mchezo",
    "members",
    "rounds",
    "rotation",
    "meetings",
    "reports",
    "profile"
],

"member" => [
    "dashboard",
    "contributions",
    "payments",
    "meetings",
    "rotation",
    "profile"
]
];

$userPermissions = $permissions[$role] ?? ["dashboard"];


function canAccess($page)
{
    global $userPermissions;

    return in_array($page, $userPermissions);
}

$initials = strtoupper(
    implode(
        "",
        array_map(
            fn($name) => $name[0],
            array_slice(explode(" ", trim($fullName)), 0, 2)
        )
    )
);

?>

<script>
    const currentUser = {
        name: <?= json_encode($fullName) ?>,
        role: <?= json_encode($role) ?>
    };
</script>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KIJUMBE Management System</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="app">
    <aside id="sidebar" class="sidebar">
      <div class="brand">
        <div class="brand-mark">K</div>
        <div>
          <h1>KIJUMBE</h1>
          <span>Mchezo Management</span>
        </div>
      </div>

      <nav class="nav">

    <p class="nav-label">MAIN</p>

    <?php if (canAccess("dashboard")): ?>
        <button class="nav-item active" data-page="dashboard">
            <span>⌂</span>Dashboard
        </button>
    <?php endif; ?>


    <?php if (canAccess("mchezo")): ?>
        <button class="nav-item" data-page="mchezo">
            <span>◎</span>Mchezo
        </button>
    <?php endif; ?>


    <?php if (canAccess("members")): ?>
        <button class="nav-item" data-page="members">
            <span>♙</span>Members
        </button>
    <?php endif; ?>


    <?php if (canAccess("rounds")): ?>
        <button class="nav-item" data-page="rounds">
            <span>◷</span>Rounds
        </button>
    <?php endif; ?>


    <?php if (canAccess("contributions")): ?>
        <button class="nav-item" data-page="contributions">
            <span>+</span>Contributions
        </button>
    <?php endif; ?>


    <?php if (canAccess("rotation")): ?>
        <button class="nav-item" data-page="rotation">
            <span>↻</span>Rotation
        </button>
    <?php endif; ?>


    <?php if (canAccess("payments")): ?>
        <button class="nav-item" data-page="payments">
            <span>▣</span>Payments
        </button>
    <?php endif; ?>

    <?php if (canAccess("profile")): ?>
        <button class="nav-item" data-page="profile">
            <span>👤</span>My Profile
        </button>
    <?php endif; ?>


    <?php if (
        canAccess("meetings") ||
        canAccess("fines") ||
        canAccess("transactions") ||
        canAccess("reports") ||
        canAccess("auditLogs") ||
        canAccess("settings") ||
        canAccess("profile")
    ): ?>

        <p class="nav-label">MANAGEMENT</p>

        <?php if (canAccess("meetings")): ?>
            <button class="nav-item" data-page="meetings">
                <span>□</span>Meetings
            </button>
        <?php endif; ?>


        <?php if (canAccess("fines")): ?>
            <button class="nav-item" data-page="fines">
                <span>!</span>Fines
            </button>
        <?php endif; ?>


        <?php if (canAccess("transactions")): ?>
            <button class="nav-item" data-page="transactions">
                <span>↕</span>Transactions
            </button>
        <?php endif; ?>


        <?php if (canAccess("reports")): ?>
            <button class="nav-item" data-page="reports">
                <span>▤</span>Reports
            </button>
        <?php endif; ?>

        <?php if (canAccess("auditLogs")): ?>
        <button class="nav-item" data-page="auditLogs">
            <span>▥</span>Audit Logs
        </button>
       <?php endif; ?>

        <?php if (canAccess("settings")): ?>
            <button class="nav-item" data-page="settings">
                <span>⚙</span>Settings
            </button>
        <?php endif; ?>

    <?php endif; ?>




</nav>

      <div class="sidebar-bottom">
        <div class="profile-mini">
          <!-- <div class="avatar">AM</div> -->
          <div>
            <?= htmlspecialchars($fullName) ?>
            <small><?= htmlspecialchars(ucfirst($role)) ?></small>
          </div>
        </div>
        <button id="logoutBtn" class="logout">Logout</button>
      </div>
    </aside>

    <div id="overlay" class="overlay">hello</div>

    <main class="main">
      <header class="topbar">
        <button id="menuBtn" class="icon-btn menu-btn" aria-label="Menu">☰</button>
        <div class="topbar-title">
          <h2 id="pageTitle">Dashboard</h2>
          <span id="pageSubtitle">Overview of your Mchezo group</span>
        </div>
            <button id="themeToggle" class="theme-toggle">Dark Mode</button>
        <div class="topbar-actions">
          <!-- <button title="Notifications"><i class="fa-solid fa-bell"></i></button> -->

          <div class="top-profile">
            <div class="avatar small"><?= htmlspecialchars($initials) ?></div>
            <?= htmlspecialchars($fullName) ?>
          </div>
        </div>
      </header>

      <section id="content" class="content"></section>
    </main>
  </div>

  <div id="modal" class="modal-backdrop hidden">
    <div class="modal">
      <button id="modalClose" class="modal-close">×</button>
      <h3 id="modalTitle">Add Member</h3>
      <div id="modalBody"></div>
    </div>
  </div>
<script>
    window.currentUserId = <?= (int) $_SESSION["user_id"] ?>;
    window.currentUserRole = <?= json_encode($role) ?>;
</script>

  <script src="js/app.js"></script>
</body>
</html>