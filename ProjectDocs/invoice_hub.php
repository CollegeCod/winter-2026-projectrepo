<?php
require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/db_connection.php";

start_secure_session();
$pdo = create_database_connection();

// ── Quick stats for the cards ──────────────────────────────────────────────
$unpaid_count = (int)$pdo->query("
    SELECT COUNT(*) FROM invoice i
    LEFT JOIN inv_status s ON i.INV_STAT_ID = s.INV_STAT_ID
    WHERE s.STAT_NAME = 'Unpaid'
")->fetchColumn();

$expiring_count = (int)$pdo->query("
    SELECT COUNT(*) FROM member
    WHERE END_DATE BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)
")->fetchColumn();

$expired_count = (int)$pdo->query("
    SELECT COUNT(*) FROM member WHERE END_DATE < CURDATE()
")->fetchColumn();

$total_invoices = (int)$pdo->query("SELECT COUNT(*) FROM invoice")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments & Renewals — Belle's Training Solutions</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/invoice_hub.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-images">
                <img src="images/Belle_Logo.png" alt="Belle's Training Solutions" class="logo-img">
                <img src="images/WSC_Logo.png" alt="WSC" class="logo-wsc">
            </div>
            <div class="logo-fallback">
                <div class="logo-circle">
                    <span>Belle's<br>Training<br>Solutions</span>
                </div>
                <span class="wsc-text">WSC</span>
            </div>
        </div>

    <nav class="sidebar-nav">
        <a href="Dashboard.php" class="nav-item">
            <i data-lucide="layout-dashboard" class="nav-icon"></i><span>Dashboard</span>
        </a>
        <a href="customers.php" class="nav-item">
            <i data-lucide="users" class="nav-icon"></i><span>Customers</span>
        </a>
        <a href="invoice_hub.php" class="nav-item active">
            <i data-lucide="credit-card" class="nav-icon"></i><span>Payments & invoices/ Renewals</span>
        </a>
        <a href="reports.php" class="nav-item">
            <i data-lucide="bar-chart-2" class="nav-icon"></i><span>Reports</span>
        </a>
        <a href="settings.php" class="nav-item">
            <i data-lucide="settings" class="nav-icon"></i><span>Settings</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <button data-logout class="nav-item logout">
            <i data-lucide="log-out" class="nav-icon"></i><span>Logout</span>
        </button>
    </div>
</aside>

<!-- ===== MAIN CONTENT ===== -->
<main class="main-content">

    <div class="page-header">
        <div>
            <h1>Payments &amp; Renewals</h1>
            <p class="page-subtitle">Manage invoices, track payments, and handle membership renewals.</p>
        </div>
        <a href="Dashboard.php" class="btn-back">
            <i data-lucide="arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- ── Quick stats ──────────────────────────────────────────────────── -->
    <div class="stats-row">
        <div class="stat-card">
            <i data-lucide="file-text" class="stat-icon"></i>
            <div class="stat-info">
                <span class="stat-value"><?php echo $total_invoices; ?></span>
                <span class="stat-label">Total Invoices</span>
            </div>
        </div>
        <div class="stat-card stat-warning">
            <i data-lucide="alert-circle" class="stat-icon"></i>
            <div class="stat-info">
                <span class="stat-value"><?php echo $unpaid_count; ?></span>
                <span class="stat-label">Unpaid Invoices</span>
            </div>
        </div>
        <div class="stat-card stat-caution">
            <i data-lucide="clock" class="stat-icon"></i>
            <div class="stat-info">
                <span class="stat-value"><?php echo $expiring_count; ?></span>
                <span class="stat-label">Expiring in 15 Days</span>
            </div>
        </div>
        <div class="stat-card stat-danger">
            <i data-lucide="x-circle" class="stat-icon"></i>
            <div class="stat-info">
                <span class="stat-value"><?php echo $expired_count; ?></span>
                <span class="stat-label">Expired Memberships</span>
            </div>
        </div>
    </div>

    <!-- ── Navigation cards ─────────────────────────────────────────────── -->
    <div class="hub-cards">

        <a href="invoices.php" class="hub-card">
            <div class="hub-card-icon icon-orange">
                <i data-lucide="credit-card"></i>
            </div>
            <div class="hub-card-body">
                <h2 class="hub-card-title">Invoices &amp; Payments</h2>
                <p class="hub-card-desc">Create invoices, link payments to member accounts, and view billing history. Connect to Square dashboard for payment processing.</p>
                <div class="hub-card-meta">
                    <span class="hub-meta-item">
                        <i data-lucide="file-text"></i>
                        <?php echo $total_invoices; ?> invoice(s)
                    </span>
                    <?php if ($unpaid_count > 0): ?>
                        <span class="hub-meta-item hub-meta-warn">
                            <i data-lucide="alert-circle"></i>
                            <?php echo $unpaid_count; ?> unpaid
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <i data-lucide="chevron-right" class="hub-card-arrow"></i>
        </a>

        <a href="renewals.php" class="hub-card">
            <div class="hub-card-icon icon-teal">
                <i data-lucide="refresh-cw"></i>
            </div>
            <div class="hub-card-body">
                <h2 class="hub-card-title">Membership Renewals</h2>
                <p class="hub-card-desc">View memberships expiring in the next 15 days or already expired. Send individual or bulk renewal reminder emails to members.</p>
                <div class="hub-card-meta">
                    <?php if ($expiring_count > 0): ?>
                        <span class="hub-meta-item hub-meta-warn">
                            <i data-lucide="clock"></i>
                            <?php echo $expiring_count; ?> expiring soon
                        </span>
                    <?php endif; ?>
                    <?php if ($expired_count > 0): ?>
                        <span class="hub-meta-item hub-meta-danger">
                            <i data-lucide="x-circle"></i>
                            <?php echo $expired_count; ?> expired
                        </span>
                    <?php else: ?>
                        <span class="hub-meta-item hub-meta-good">
                            <i data-lucide="check-circle"></i>
                            No expired memberships
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <i data-lucide="chevron-right" class="hub-card-arrow"></i>
        </a>

    </div>

</main>

<footer class="site-footer">
    <p><small>&copy; Windswept Student Consulting 2026</small></p>
</footer>

<?php include 'logout_modal.php'; ?>

<script>
lucide.createIcons();
</script>

</body>
</html>
<?php $pdo = null; ?>