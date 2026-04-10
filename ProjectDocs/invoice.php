<?php
	/* "invoice.php" - Main page for managing customer invoices, with a section for payments.
	// Page will act as both a display and interface between managing a invoice and creatng new ones.
	Page created by Malcolm Ng, using some adapted code by Ronan Kelly
	--- Windswept Student Consulting ---
		
		Pseudocode & Planning
	- 	
		
	*/
/*---- Database & Connections ----*/
require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/db_connection.php";

//start_secure_session();
//$pdo = create_database_connection();

	// Requirement & Includes

	// include __DIR__ . "/logout_modal.php";
	
	
?>
<?php ?>
<!DOCTYPE html>
<html lang="en">
	<!-- HEAD SECTION-->
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Invoice & Payment Managemenet</title>

		<!-- Update these paths to match your project -->
		<link rel="stylesheet" href="css/dashboard.css">
		<link rel="stylesheet" href="css/invoice_hub.css">

		<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
		<?php include "logout_modal.php";?>
	</head>
	<body>
<!-- The sidebar component. Provides navigation between site sections.
	For the sake of maintainability, we may need to investigate including the sidebar instead.-->
	<aside class="sidebar">
		<div class="sidebar-logo">
			<img src="bellestraining.jpeg" alt="Belle's Training Solutions" class="logo-img" onerror="this.style.display='none'">
			<img src="WSC.jpg" alt="WSC" class="logo-wsc" onerror="this.style.display='none'">
			<div class="logo-fallback">
				<div class="logo-circle"><span>Belle's<br>Training<br>Solutions</span></div>
				<span class="wsc-text">WSC</span>
			</div>
		</div>

		<nav class="sidebar-nav">
			<a href="dashboard.php" class="nav-item">
				<i data-lucide="layout-dashboard" class="nav-icon"></i><span>Dashboard</span>
			</a>
			<a href="customers.php" class="nav-item">
				<i data-lucide="users" class="nav-icon"></i><span>Customers</span>
			</a>
						<!-- You are here -->
			<a href="invoice.php" class="nav-item active">
				<i data-lucide="file-text" class="nav-icon"></i><span>Invoices</span>
			</a>
			<a href="qr_code.php" class="nav-item">
				<i data-lucide="qr-code" class="nav-icon"></i><span>QR Codes</span>
			</a>
			<!-- THIS ITEM APPEARED TO BE INCORRECTLY PATHED. MAY NEED FIXING FROM "invoices.php" to "payments.php".
				It is unclear if this item should be combined with invoices into "Invoices & Payments". -->
			<!--<a href="payments.php" class="nav-item">
				<i data-lucide="credit-card" class="nav-icon"></i>
				<span>Payments</span>
			</a>-->
			<a href="renewals.php" class="nav-item">
				<i data-lucide="refresh-cw" class="nav-icon"></i>
				<span>Renewals</span>
			</a>
			<a href="settings.php" class="nav-item">
				<i data-lucide="settings" class="nav-icon"></i>
				<span>Settings</span>
			</a>
		</nav>

		<div class="sidebar-footer">
			<button data-logout class="nav-item logout">
				<i data-lucide="log-out" class="nav-icon"></i>
				<span>Logout</span>
			</button>
		</div>
	</aside>
<!-- Content Display -->
	<main class="main-content">
		<!-- Content Header -->
		<div class="page-header">
			<div>
				<h1>Invoice Management</h1>
				<p class="page-subtitle">Manage and monitor invoices and pending payments.</p> <!-- Subtitle may need revising.-->
			</div>
		</div>
		<!-- Main content goes here.-->
		<div class="test-box">
			<h2>Invoicing Functionality still in Development!</h2>
			<br>
			<h3>The invoice management page would allow for the viewing, search, editing and manual creation of invoices.</h3>
			<br>
			<h3>The system would interface with Square's API to enable creation and subsequent request for payments by customers.</h3>
		</div>
	</main>
	
	<?php if (file_exists('logout_modal.php')) { /* include 'logout_modal.php'; */ } ?>
	<!-- Footer goes here -->
	<!-- We can probably improve the footer by having it in a single file, and simply including it where needed. -->
	<footer class="site-footer">
		<a href="about.php" id="aboutfooter">About WSC</a>
		<p><small>&copy; Windswept Student Consulting 2026</small></p>
	</footer>
	
	<script>
		lucide.createIcons();
	</script>
</html>