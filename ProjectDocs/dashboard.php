<?php
// session_start();
// if (!isset($_SESSION['USER_ID'])) { header('Location: login.php'); exit(); }
// Use dashboard.css to style (not look like a random website from the 80's)
//
require_once __DIR__ . "/logout_modal.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belle's Training Solutions - Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>

<!-- ===== SIDEBAR NAV BAR ===== -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="bellestraining.jpeg" alt="Belle's Training Solutions" class="logo-img" onerror="this.style.display='none'">
        <img src="WSC.jpg" alt="WSC" class="logo-wsc" onerror="this.style.display='none'">
        <div class="logo-fallback">
            <div class="logo-circle">
                <span>Belle's<br>Training<br>Solutions</span>
            </div>
            <span class="wsc-text">WSC</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="Dashboard.php" class="nav-item active">
            <i data-lucide="layout-dashboard" class="nav-icon"></i><span>Dashboard</span>
        </a>
        <a href="customers.php" class="nav-item">
            <i data-lucide="users" class="nav-icon"></i><span>Customers</span>
        </a>
        <a href="qr_code.php" class="nav-item">
            <i data-lucide="qr-code" class="nav-icon"></i><span>QR Codes</span>
        </a>
        <a href="invoice.php" class="nav-item">
            <i data-lucide="credit-card" class="nav-icon"></i><span>Payments & Invoices</span>
        </a>
        <a href="renewals.php" class="nav-item">
            <i data-lucide="refresh-cw" class="nav-icon"></i><span>Renewals</span>
        </a>
        <a href="sys_settings.php" class="nav-item">
            <i data-lucide="settings" class="nav-icon"></i><span>Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <button type="button" class="nav-item logout" data-logout>
            <i data-lucide="log-out" class="nav-icon"></i>
            <span>Logout</span>
        </button>
    </div>

</aside>

<!-- ===== MAIN CONTENT ===== -->
<main class="main-content">

    <div class="page-header">
        <h1>Dashboard</h1>
        <p class="page-subtitle">Welcome back! Where would you like to go?</p>
    </div>

    <!-- ===== MAIN NAV CARDS ===== -->
    <div class="nav-cards-grid">

        <a href="customers.php" class="nav-card">
            <div class="nav-card-icon icon-blue">
                <i data-lucide="users"></i>
            </div>
            <div class="nav-card-text">
                <span class="nav-card-title">Customer Management</span>
                <span class="nav-card-desc">Add, edit, and manage gym members</span>
            </div>
            <i data-lucide="chevron-right" class="nav-card-arrow"></i>
        </a>

        <a href="qr_code.php" class="nav-card">
            <div class="nav-card-icon icon-purple">
                <i data-lucide="qr-code"></i>
            </div>
            <div class="nav-card-text">
                <span class="nav-card-title">QR Code Management</span>
                <span class="nav-card-desc">View QR codes, and send expiry reminders</span>
            </div>
            <i data-lucide="chevron-right" class="nav-card-arrow"></i>
        </a>

        <a href="invoice.php" class="nav-card">
            <div class="nav-card-icon icon-orange">
                <i data-lucide="credit-card"></i>
            </div>
            <div class="nav-card-text">
                <span class="nav-card-title">Payments &amp; Invoices</span>
                <span class="nav-card-desc">Track payments and manage billing invoices</span>
            </div>
            <i data-lucide="chevron-right" class="nav-card-arrow"></i>
        </a>

        <a href="reports.php" class="nav-card">
            <div class="nav-card-icon icon-indigo">
                <i data-lucide="bar-chart-2"></i>
            </div>
            <div class="nav-card-text">
                <span class="nav-card-title">Generate Report</span>
                <span class="nav-card-desc">Create and export system reports</span>
            </div>
            <i data-lucide="chevron-right" class="nav-card-arrow"></i>
        </a>

        <a href="sys_settings.php" class="nav-card">
            <div class="nav-card-icon icon-gray">
                <i data-lucide="settings"></i>
            </div>
            <div class="nav-card-text">
                <span class="nav-card-title">Settings</span>
                <span class="nav-card-desc">Configure system and account preferences</span>
            </div>
            <i data-lucide="chevron-right" class="nav-card-arrow"></i>
        </a>

        <a href="renewals.php" class="nav-card">
            <div class="nav-card-icon icon-green">
                <i data-lucide="chevron-up"></i>
            </div>
            <div class="nav-card-text">
                <span class="nav-card-title">Renewals</span>
                <span class="nav-card-desc">Renewals outlined through the invoice section</span>
            </div>
            <i data-lucide="chevron-right" class="nav-card-arrow"></i>
        </a>

    </div>

</main>

<?php if (file_exists("logout_modal.php")) {
    /* include 'logout_modal.php'; */
} ?>

<footer class="site-footer">
	<a href="sys_about.php" id="aboutfooter">About WSC</a>
    <p><small>&copy; Windswept Student Consulting 2026</small></p>
</footer>

<script>lucide.createIcons();</script>
</body>
</html>
