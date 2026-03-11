<?php
// edit_customer.php
// Displays and saves all customer data fields for a single customer

require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/db_connection.php";
require_once __DIR__ . "/includes/csrf.php";
include __DIR__ . "/logout_modal.php";

start_secure_session();
$pdo = create_database_connection();

$table = "Member";

// ── Validate member id ─────────────────────────────────────────────────────
if (!isset($_GET["MEMB_ID"]) || trim($_GET["MEMB_ID"]) === "") {
    header("Location: customers.php");
    exit();
}

$member_id = trim($_GET["MEMB_ID"]);
$success_msg = "";
$error_msg = "";

// ── Handle form submission ─────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validate_csrf_token($_POST["csrf_token"] ?? null);

    $fields = [
        "MEMB_FNAME" => trim($_POST["MEMB_FNAME"] ?? ""),
        "MEMB_LNAME" => trim($_POST["MEMB_LNAME"] ?? ""),
        "PACKAGE_ID" => trim($_POST["PACKAGE_ID"] ?? ""),
        "MEMB_PHONE" => trim($_POST["MEMB_PHONE"] ?? ""),
        "MEMB_EMAIL" => trim($_POST["MEMB_EMAIL"] ?? ""),
        "START_DATE" => trim($_POST["START_DATE"] ?? ""),
        "END_DATE" => trim($_POST["END_DATE"] ?? ""),
        "NOTES" => trim($_POST["NOTES"] ?? ""),
    ];

    try {
        $stmt = $pdo->prepare("
            UPDATE $table SET
                MEMB_FNAME = :MEMB_FNAME,
                MEMB_LNAME = :MEMB_LNAME,
                PACKAGE_ID = :PACKAGE_ID,
                MEMB_PHONE = :MEMB_PHONE,
                MEMB_EMAIL = :MEMB_EMAIL,
                START_DATE = :START_DATE,
                END_DATE   = :END_DATE,
                NOTES      = :NOTES
            WHERE MEMB_ID = :MEMB_ID
        ");

        foreach ($fields as $key => $value) {
            $stmt->bindValue(":" . $key, $value);
        }

        $stmt->bindValue(":MEMB_ID", $member_id);
        $stmt->execute();

        $success_msg = "Customer updated successfully.";
    } catch (PDOException $e) {
        $error_msg = "Failed to save changes: " . $e->getMessage();
    }
}

// ── Fetch customer record ──────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM $table WHERE MEMB_ID = :MEMB_ID LIMIT 1");
$stmt->bindValue(":MEMB_ID", $member_id);
$stmt->execute();
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    die('Customer not found. <a href="customers.php">Back to customers</a>');
}

// ── Helper ─────────────────────────────────────────────────────────────────
function field_value($customer, $key)
{
    return htmlspecialchars($customer[$key] ?? "", ENT_QUOTES, "UTF-8");
}

$full_name = trim(
    field_value($customer, "MEMB_FNAME") .
        " " .
        field_value($customer, "MEMB_LNAME"),
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer — <?php echo $full_name !== ""
        ? $full_name
        : "Member"; ?></title>

    <!-- Adjust these paths if needed -->
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/customers.css">

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
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
        <a href="Dashboard.php" class="nav-item">
            <i data-lucide="layout-dashboard" class="nav-icon"></i><span>Dashboard</span>
        </a>
        <a href="customers.php" class="nav-item active">
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

    <div class="page-header-row">
        <div>
            <div class="breadcrumb">
                <a href="customers.php">Customers</a>
                <i data-lucide="chevron-right" class="breadcrumb-sep"></i>
                <span>Edit Customer</span>
            </div>

            <h1>Edit Customer</h1>

            <p class="page-subtitle">
                Member ID: <strong><?php echo field_value(
                    $customer,
                    "MEMB_ID",
                ); ?></strong>
                <?php if ($full_name !== ""): ?>
                    &mdash; <?php echo $full_name; ?>
                <?php endif; ?>
            </p>
        </div>

        <a href="customers.php" class="btn-secondary">
            <i data-lucide="arrow-left"></i> Back to Customers
        </a>
    </div>

    <?php if ($success_msg !== ""): ?>
        <div class="alert alert-success">
            <i data-lucide="check-circle"></i>
            <?php echo htmlspecialchars($success_msg, ENT_QUOTES, "UTF-8"); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_msg !== ""): ?>
        <div class="alert alert-error">
            <i data-lucide="alert-circle"></i>
            <?php echo htmlspecialchars($error_msg, ENT_QUOTES, "UTF-8"); ?>
        </div>
    <?php endif; ?>

    <div class="table-card edit-card">
        <form method="POST" action="edit_customer.php?MEMB_ID=<?php echo urlencode(
            $member_id,
        ); ?>">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars(
                    get_csrf_token(),
                    ENT_QUOTES,
                    "UTF-8",
                ); ?>"
            >

            <!-- Section: Personal Info -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <i data-lucide="user"></i> Personal Information
                </h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="MEMB_FNAME">First Name</label>
                        <input
                            type="text"
                            id="MEMB_FNAME"
                            name="MEMB_FNAME"
                            value="<?php echo field_value(
                                $customer,
                                "MEMB_FNAME",
                            ); ?>"
                            placeholder="First name"
                        >
                    </div>

                    <div class="form-group">
                        <label for="MEMB_LNAME">Last Name</label>
                        <input
                            type="text"
                            id="MEMB_LNAME"
                            name="MEMB_LNAME"
                            value="<?php echo field_value(
                                $customer,
                                "MEMB_LNAME",
                            ); ?>"
                            placeholder="Last name"
                        >
                    </div>

                    <div class="form-group">
                        <label for="MEMB_EMAIL">Email</label>
                        <input
                            type="email"
                            id="MEMB_EMAIL"
                            name="MEMB_EMAIL"
                            value="<?php echo field_value(
                                $customer,
                                "MEMB_EMAIL",
                            ); ?>"
                            placeholder="email@example.com"
                        >
                    </div>

                    <div class="form-group">
                        <label for="MEMB_PHONE">Phone Number</label>
                        <input
                            type="text"
                            id="MEMB_PHONE"
                            name="MEMB_PHONE"
                            value="<?php echo field_value(
                                $customer,
                                "MEMB_PHONE",
                            ); ?>"
                            placeholder="+1 000-000-0000"
                        >
                    </div>
                </div>
            </div>

            <div class="form-divider"></div>

            <!-- Section: Membership Info -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <i data-lucide="credit-card"></i> Membership Details
                </h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="PACKAGE_ID">Package ID</label>
                        <input
                            type="text"
                            id="PACKAGE_ID"
                            name="PACKAGE_ID"
                            value="<?php echo field_value(
                                $customer,
                                "PACKAGE_ID",
                            ); ?>"
                            placeholder="Package ID"
                        >
                    </div>

                    <!-- Uncomment these later once QR_ID and RULE_ID are fully wired in -->
                    <!--
                    <div class="form-group">
                        <label for="QR_ID">QR ID</label>
                        <input
                            type="text"
                            id="QR_ID"
                            name="QR_ID"
                            value="<?php echo field_value(
                                $customer,
                                "QR_ID",
                            ); ?>"
                            placeholder="QR ID"
                        >
                    </div>

                    <div class="form-group">
                        <label for="RULE_ID">Rule ID</label>
                        <input
                            type="text"
                            id="RULE_ID"
                            name="RULE_ID"
                            value="<?php echo field_value(
                                $customer,
                                "RULE_ID",
                            ); ?>"
                            placeholder="Rule ID"
                        >
                    </div>
                    -->

                    <div class="form-group">
                        <label for="START_DATE">Start Date</label>
                        <input
                            type="date"
                            id="START_DATE"
                            name="START_DATE"
                            value="<?php echo field_value(
                                $customer,
                                "START_DATE",
                            ); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="END_DATE">End Date</label>
                        <input
                            type="date"
                            id="END_DATE"
                            name="END_DATE"
                            value="<?php echo field_value(
                                $customer,
                                "END_DATE",
                            ); ?>"
                        >
                    </div>
                </div>
            </div>

            <div class="form-divider"></div>

            <!-- Section: Notes -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <i data-lucide="file-text"></i> Notes
                </h2>

                <div class="form-group full-width">
                    <label for="NOTES">Notes</label>
                    <textarea
                        id="NOTES"
                        name="NOTES"
                        rows="4"
                        placeholder="Any additional notes about this member..."
                    ><?php echo field_value($customer, "NOTES"); ?></textarea>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="form-actions">
                <a href="customers.php" class="btn-secondary">
                    <i data-lucide="x"></i> Cancel
                </a>

                <button type="submit" class="btn-primary">
                    <i data-lucide="save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</main>

<footer class="site-footer">
    <p><small>&copy; Windswept Student Consulting 2026</small></p>
</footer>

<script>
    lucide.createIcons();

    function openLogoutModal() {
        const modal = document.getElementById("logoutModal");
        if (modal) {
            modal.style.display = "flex";
        }
    }
</script>

</body>
</html>
