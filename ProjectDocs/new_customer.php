<?php
// new_customer.php
// Creates a new customer record

require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/db_connection.php";

start_secure_session();
$pdo = create_database_connection();

$table       = 'member';
$success_msg = '';
$error_msg   = '';

// ── Handle form submission ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'MEMB_FNAME'     => trim($_POST['MEMB_FNAME']   ?? ''),
        'MEMB_LNAME'     => trim($_POST['MEMB_LNAME']   ?? ''),
        'PACKAGE_ID'     => trim($_POST['PACKAGE_ID']   ?? ''),
        'MEMB_STATUS_ID' => '1', // Default: Active
        'MEMB_PHONE'     => trim($_POST['MEMB_PHONE']   ?? ''),
        'MEMB_EMAIL'     => trim($_POST['MEMB_EMAIL']   ?? ''),
        'MEMB_DOB'       => trim($_POST['MEMB_DOB']     ?? ''),
        'MEMB_JOINDATE'  => date('Y-m-d'),
        'START_DATE'     => trim($_POST['START_DATE']   ?? ''),
        'END_DATE'       => trim($_POST['END_DATE']     ?? ''),
        'NOTES'          => trim($_POST['NOTES']        ?? ''),
    ];

    // Basic server-side validation
    if ($fields['MEMB_FNAME'] === '' || $fields['MEMB_LNAME'] === '') {
        $error_msg = 'First name and last name are required.';
    } elseif ($fields['MEMB_EMAIL'] === '' || !filter_var($fields['MEMB_EMAIL'], FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
    } elseif ($fields['MEMB_PHONE'] === '' || !preg_match('/^\([0-9]{3}\) [0-9]{3}-[0-9]{4}$/', $fields['MEMB_PHONE'])) {
        $error_msg = 'Please enter a valid phone number (e.g. (123) 456-7890).';
    } elseif ($fields['MEMB_DOB'] === '') {
        $error_msg = 'Date of birth is required.';
    } elseif ($fields['PACKAGE_ID'] === '') {
        $error_msg = 'Please select a package.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO $table
                (MEMB_FNAME, MEMB_LNAME, PACKAGE_ID, MEMB_STATUS_ID,
                 MEMB_PHONE, MEMB_EMAIL, MEMB_DOB, MEMB_JOINDATE,
                 START_DATE, END_DATE, NOTES)
                VALUES
                (:MEMB_FNAME, :MEMB_LNAME, :PACKAGE_ID, :MEMB_STATUS_ID,
                 :MEMB_PHONE, :MEMB_EMAIL, :MEMB_DOB, :MEMB_JOINDATE,
                 :START_DATE, :END_DATE, :NOTES)");

            foreach ($fields as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();

            header('Location: customers.php?added=1');
            exit;

        } catch (PDOException $e) {
            $error_msg = 'Failed to create customer: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/customers.css">
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
        <a href="qr_codes.php" class="nav-item">
            <i data-lucide="qr-code" class="nav-icon"></i><span>QR Codes</span>
        </a>
        <a href="Invoices.php" class="nav-item">
            <i data-lucide="credit-card" class="nav-icon"></i><span>Payments</span>
        </a>
        <a href="renewal.php" class="nav-item">
            <i data-lucide="refresh-cw" class="nav-icon"></i><span>Renewals</span>
        </a>
        <a href="invoice.php" class="nav-item">
            <i data-lucide="file-text" class="nav-icon"></i><span>Invoice</span>
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

    <div class="page-header-row">
        <div>
            <div class="breadcrumb">
                <a href="customers.php">Customers</a>
                <i data-lucide="chevron-right" class="breadcrumb-sep"></i>
                <span>Add Customer</span>
            </div>
            <h1>Add Customer</h1>
            <p class="page-subtitle">Fill in the details below to create a new customer record.</p>
        </div>
        <a href="customers.php" class="btn-secondary">
            <i data-lucide="arrow-left"></i> Back to Customers
        </a>
    </div>

    <?php if ($error_msg !== ''): ?>
        <div class="alert alert-error">
            <i data-lucide="alert-circle"></i>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <div class="table-card edit-card">
        <form method="POST" action="new_customer.php">

            <div class="form-sections-row">

                <!-- Personal Info -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i data-lucide="user"></i> Personal Information
                    </h2>
                    <div class="form-grid">

                        <div class="form-group">
                            <label for="MEMB_FNAME">First Name</label>
                            <input type="text" id="MEMB_FNAME" name="MEMB_FNAME"
                                value="<?php echo htmlspecialchars($_POST['MEMB_FNAME'] ?? ''); ?>"
                                placeholder="First name"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="MEMB_LNAME">Last Name</label>
                            <input type="text" id="MEMB_LNAME" name="MEMB_LNAME"
                                value="<?php echo htmlspecialchars($_POST['MEMB_LNAME'] ?? ''); ?>"
                                placeholder="Last name"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="MEMB_DOB">Date of Birth</label>
                            <input type="date" id="MEMB_DOB" name="MEMB_DOB"
                                value="<?php echo htmlspecialchars($_POST['MEMB_DOB'] ?? ''); ?>"
                                min="1900-01-01"
                                max="<?php echo date('Y-m-d'); ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="MEMB_EMAIL">Email</label>
                            <input type="email" id="MEMB_EMAIL" name="MEMB_EMAIL"
                                value="<?php echo htmlspecialchars($_POST['MEMB_EMAIL'] ?? ''); ?>"
                                placeholder="email@example.com"
                                pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
                                title="Please enter a valid email address (e.g. name@example.com)"
                                required>
                            <span class="field-hint">e.g. name@example.com</span>
                        </div>

                        <div class="form-group">
                            <label for="MEMB_PHONE">Phone Number</label>
                            <input type="tel" id="MEMB_PHONE" name="MEMB_PHONE"
                                value="<?php echo htmlspecialchars($_POST['MEMB_PHONE'] ?? ''); ?>"
                                placeholder="(123) 456-7890"
                                pattern="^\([0-9]{3}\) [0-9]{3}-[0-9]{4}$"
                                title="Please enter a valid phone number in the format (123) 456-7890"
                                maxlength="14"
                                required>
                            <span class="field-hint">Format: (123) 456-7890</span>
                        </div>

                    </div>
                </div>

                <div class="form-sections-divider"></div>

                <!-- Membership Details -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i data-lucide="credit-card"></i> Membership Details
                    </h2>
                    <div class="form-grid">

                        <div class="form-group">
                            <label for="PACKAGE_ID">Package</label>
                            <select id="PACKAGE_ID" name="PACKAGE_ID" required>
                                <option value="">— Select a package —</option>
                                <optgroup label="Basic">
                                    <option value="1" data-days="30" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '1'  ? 'selected' : ''; ?>>Basic — 1 Month</option>
                                    <option value="2" data-days="90" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '2'  ? 'selected' : ''; ?>>Basic — 3 Month</option>
                                    <option value="3" data-days="180" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '3'  ? 'selected' : ''; ?>>Basic — 6 Month</option>
                                    <option value="4" data-days="365" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '4'  ? 'selected' : ''; ?>>Basic — 1 Year</option>
                                </optgroup>
                                <optgroup label="Intermediate">
                                    <option value="5" data-days="30" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '5'  ? 'selected' : ''; ?>>Intermediate — 1 Month</option>
                                    <option value="6" data-days="90" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '6'  ? 'selected' : ''; ?>>Intermediate — 3 Month</option>
                                    <option value="7" data-days="180" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '7'  ? 'selected' : ''; ?>>Intermediate — 6 Month</option>
                                    <option value="8" data-days="365" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '8'  ? 'selected' : ''; ?>>Intermediate — 1 Year</option>
                                </optgroup>
                                <optgroup label="Ultimate">
                                    <option value="9" data-days="30" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '9'  ? 'selected' : ''; ?>>Ultimate — 1 Month</option>
                                    <option value="10" data-days="90" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '10' ? 'selected' : ''; ?>>Ultimate — 3 Month</option>
                                    <option value="11" data-days="180" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '11' ? 'selected' : ''; ?>>Ultimate — 6 Month</option>
                                    <option value="12" data-days="365" <?php echo ($_POST['PACKAGE_ID'] ?? '') === '12' ? 'selected' : ''; ?>>Ultimate — 1 Year</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="START_DATE">Start Date</label>
                            <input type="date" id="START_DATE" name="START_DATE"
                                value="<?php echo htmlspecialchars($_POST['START_DATE'] ?? date('Y-m-d')); ?>">
                        </div>

                        <div class="form-group">
                            <label for="END_DATE">Expiry Date</label>
                            <input type="date" id="END_DATE" name="END_DATE"
                                value="<?php echo htmlspecialchars($_POST['END_DATE'] ?? ''); ?>">
                        </div>

                    </div>
                </div>

            </div>

            <div class="form-divider"></div>

            <!-- Notes -->
            <div class="form-section">
                <h2 class="form-section-title">
                    <i data-lucide="file-text"></i> Notes
                </h2>
                <div class="form-group full-width">
                    <label for="NOTES">Notes</label>
                    <textarea id="NOTES" name="NOTES" rows="4"
                        placeholder="Any additional notes about this member..."
                    ><?php echo htmlspecialchars($_POST['NOTES'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="customers.php" class="btn-secondary">
                    <i data-lucide="x"></i> Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i data-lucide="user-plus"></i> Add Customer
                </button>
            </div>

        </form>
    </div>

</main>

<footer class="site-footer">
    <p><small>&copy; Windswept Student Consulting 2026</small></p>
</footer>

<?php include 'logout_modal.php'; ?>

<script>
lucide.createIcons();

// ── Auto-fill End Date based on package + start date ─────────────────────
function updateEndDate() {
    var pkg_select = document.getElementById('PACKAGE_ID');
    var start_input = document.getElementById('START_DATE');
    var end_input   = document.getElementById('END_DATE');
    if (!pkg_select || !start_input || !end_input) return;

    var selected = pkg_select.options[pkg_select.selectedIndex];
    var days = selected ? parseInt(selected.dataset.days) : 0;
    if (!days || !start_input.value) return;

    var start = new Date(start_input.value + 'T00:00:00');
    start.setDate(start.getDate() + days);
    var yyyy = start.getFullYear();
    var mm   = String(start.getMonth() + 1).padStart(2, '0');
    var dd   = String(start.getDate()).padStart(2, '0');
    end_input.value = yyyy + '-' + mm + '-' + dd;
}

var pkg_select = document.getElementById('PACKAGE_ID');
var start_input = document.getElementById('START_DATE');
if (pkg_select)  pkg_select.addEventListener('change', updateEndDate);
if (start_input) start_input.addEventListener('change', updateEndDate);

// ── Phone number auto-formatter: (123) 456-7890 ──────────────────────────
var phone_input = document.getElementById('MEMB_PHONE');
if (phone_input) {
    phone_input.addEventListener('input', function () {
        var digits = this.value.replace(/[^\d]/g, '').slice(0, 10);
        var formatted = '';
        if (digits.length === 0) {
            formatted = '';
        } else if (digits.length <= 3) {
            formatted = '(' + digits;
        } else if (digits.length <= 6) {
            formatted = '(' + digits.slice(0, 3) + ') ' + digits.slice(3);
        } else {
            formatted = '(' + digits.slice(0, 3) + ') ' + digits.slice(3, 6) + '-' + digits.slice(6, 10);
        }
        this.value = formatted;
    });
}

// ── Inline validation feedback ────────────────────────────────────────────
function validate_field(input) {
    if (!input.checkValidity()) {
        input.classList.add('input-invalid');
        input.classList.remove('input-valid');
    } else {
        input.classList.remove('input-invalid');
        input.classList.add('input-valid');
    }
}

document.getElementById('MEMB_EMAIL').addEventListener('blur', function () { validate_field(this); });
document.getElementById('MEMB_PHONE').addEventListener('blur', function () { validate_field(this); });
</script>
</body>
</html>