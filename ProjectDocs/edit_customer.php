<?php
// edit_customer.php
// Displays and saves all customer data fields for a single customer

require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/db_connection.php";

start_secure_session();
$pdo = create_database_connection();

$table       = 'member';
$success_msg = '';
$error_msg   = '';

// ── Get member ID from URL ─────────────────────────────────────────────────
$memb_id = isset($_GET['MEMB_ID']) && is_numeric($_GET['MEMB_ID'])
    ? (int) $_GET['MEMB_ID']
    : null;

if (!$memb_id) {
    header('Location: customers.php');
    exit;
}

// ── Handle form submission ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'MEMB_FNAME'  => trim($_POST['MEMB_FNAME']  ?? ''),
        'MEMB_LNAME'  => trim($_POST['MEMB_LNAME']  ?? ''),
        'PACKAGE_ID'  => (int) trim($_POST['PACKAGE_ID'] ?? 0),
        'MEMB_PHONE'  => trim($_POST['MEMB_PHONE']  ?? ''),
        'MEMB_EMAIL'  => trim($_POST['MEMB_EMAIL']  ?? ''),
        'MEMB_DOB'    => trim($_POST['MEMB_DOB']    ?? ''),
        'START_DATE'  => trim($_POST['START_DATE']  ?? ''),
        'END_DATE'    => trim($_POST['END_DATE']    ?? ''),
        'NOTES'       => trim($_POST['NOTES']       ?? ''),
    ];

    // Server-side validation
    if ($fields['MEMB_FNAME'] === '' || $fields['MEMB_LNAME'] === '') {
        $error_msg = 'First name and last name are required.';
    } elseif ($fields['MEMB_EMAIL'] === '' || !filter_var($fields['MEMB_EMAIL'], FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
    } elseif ($fields['MEMB_PHONE'] === '' || !preg_match('/^\+1 [0-9]{3}-[0-9]{3}-[0-9]{4}$/', $fields['MEMB_PHONE'])) {
        $error_msg = 'Please enter a valid phone number (e.g. +1 555-123-4567).';
    } elseif ($fields['MEMB_DOB'] === '') {
        $error_msg = 'Date of birth is required.';
    } elseif ($fields['PACKAGE_ID'] === 0) {
        $error_msg = 'Please select a package.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE $table SET
                MEMB_FNAME  = :MEMB_FNAME,
                MEMB_LNAME  = :MEMB_LNAME,
                PACKAGE_ID  = :PACKAGE_ID,
                MEMB_PHONE  = :MEMB_PHONE,
                MEMB_EMAIL  = :MEMB_EMAIL,
                MEMB_DOB    = :MEMB_DOB,
                START_DATE  = :START_DATE,
                END_DATE    = :END_DATE,
                NOTES       = :NOTES
                WHERE MEMB_ID = :MEMB_ID");

            foreach ($fields as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->bindValue(':MEMB_ID', $memb_id, PDO::PARAM_INT);
            $stmt->execute();
            $success_msg = 'Customer updated successfully.';

        } catch (PDOException $e) {
            $error_msg = 'Failed to save changes: ' . $e->getMessage();
        }
    }
}

// ── Fetch the customer record ──────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM $table WHERE MEMB_ID = :MEMB_ID LIMIT 1");
$stmt->bindValue(':MEMB_ID', $memb_id, PDO::PARAM_INT);
$stmt->execute();
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    die('Customer not found. <a href="customers.php">Back to customers</a>');
}

function field_value($customer, $key) {
    return htmlspecialchars($customer[$key] ?? '');
}

$full_name       = trim(field_value($customer, 'MEMB_FNAME') . ' ' . field_value($customer, 'MEMB_LNAME'));
$current_package = (string)($customer['PACKAGE_ID'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer — <?php echo $full_name; ?></title>
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
        <a href="invoices_hub.php" class="nav-item">
            <i data-lucide="credit-card" class="nav-icon"></i><span>Payments, Invoices & Renewals</span>
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

    <div class="page-header-row">
        <div>
            <div class="breadcrumb">
                <a href="customers.php">Customers</a>
                <i data-lucide="chevron-right" class="breadcrumb-sep"></i>
                <span>Edit Customer</span>
            </div>
            <h1>Edit Customer</h1>
            <p class="page-subtitle">
                Member ID: <strong><?php echo htmlspecialchars($memb_id); ?></strong>
                &mdash; <?php echo $full_name; ?>
            </p>
        </div>
        <a href="customers.php" class="btn-secondary">
            <i data-lucide="arrow-left"></i> Back to Customers
        </a>
    </div>

    <?php if ($success_msg !== ''): ?>
        <div class="alert alert-success">
            <i data-lucide="check-circle"></i>
            <?php echo htmlspecialchars($success_msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_msg !== ''): ?>
        <div class="alert alert-error">
            <i data-lucide="alert-circle"></i>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <div class="table-card edit-card">
        <form method="POST" action="edit_customer.php?MEMB_ID=<?php echo urlencode($memb_id); ?>">

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
                                value="<?php echo field_value($customer, 'MEMB_FNAME'); ?>"
                                placeholder="First name" required>
                        </div>

                        <div class="form-group">
                            <label for="MEMB_LNAME">Last Name</label>
                            <input type="text" id="MEMB_LNAME" name="MEMB_LNAME"
                                value="<?php echo field_value($customer, 'MEMB_LNAME'); ?>"
                                placeholder="Last name" required>
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
                                value="<?php echo field_value($customer, 'MEMB_EMAIL'); ?>"
                                placeholder="email@example.com"
                                pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}"
                                title="Please enter a valid email address (e.g. name@example.com)"
                                required>
                            <span class="field-hint">e.g. name@example.com</span>
                        </div>

                        <div class="form-group">
                            <label for="MEMB_PHONE">Phone Number</label>
                            <input type="tel" id="MEMB_PHONE" name="MEMB_PHONE"
                                value="<?php echo field_value($customer, 'MEMB_PHONE'); ?>"
                                placeholder="+1 000-000-0000"
                                pattern="^\+1 [0-9]{3}-[0-9]{3}-[0-9]{4}$"
                                title="Please enter a valid phone number (e.g. +1 555-123-4567)"
                                maxlength="15"
                                required>
                            <span class="field-hint">e.g. +1 555-123-4567</span>
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
                                    <option value="1"  <?php echo $current_package === '1'  ? 'selected' : ''; ?>>Basic — 1 Month</option>
                                    <option value="2"  <?php echo $current_package === '2'  ? 'selected' : ''; ?>>Basic — 3 Month</option>
                                    <option value="3"  <?php echo $current_package === '3'  ? 'selected' : ''; ?>>Basic — 6 Month</option>
                                    <option value="4"  <?php echo $current_package === '4'  ? 'selected' : ''; ?>>Basic — 1 Year</option>
                                </optgroup>
                                <optgroup label="Intermediate">
                                    <option value="5"  <?php echo $current_package === '5'  ? 'selected' : ''; ?>>Intermediate — 1 Month</option>
                                    <option value="6"  <?php echo $current_package === '6'  ? 'selected' : ''; ?>>Intermediate — 3 Month</option>
                                    <option value="7"  <?php echo $current_package === '7'  ? 'selected' : ''; ?>>Intermediate — 6 Month</option>
                                    <option value="8"  <?php echo $current_package === '8'  ? 'selected' : ''; ?>>Intermediate — 1 Year</option>
                                </optgroup>
                                <optgroup label="Ultimate">
                                    <option value="9"  <?php echo $current_package === '9'  ? 'selected' : ''; ?>>Ultimate — 1 Month</option>
                                    <option value="10" <?php echo $current_package === '10' ? 'selected' : ''; ?>>Ultimate — 3 Month</option>
                                    <option value="11" <?php echo $current_package === '11' ? 'selected' : ''; ?>>Ultimate — 6 Month</option>
                                    <option value="12" <?php echo $current_package === '12' ? 'selected' : ''; ?>>Ultimate — 1 Year</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="START_DATE">Start Date</label>
                            <input type="date" id="START_DATE" name="START_DATE"
                                value="<?php echo field_value($customer, 'START_DATE'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="END_DATE">Expiry Date</label>
                            <input type="date" id="END_DATE" name="END_DATE"
                                value="<?php echo field_value($customer, 'END_DATE'); ?>">
                        </div>

                        <!-- QR ID = :QR ID, and Rule ID = :Rule ID, — add back in later -->
                        <!--
                        <div class="form-group">
                            <label for="QR_ID">QR ID</label>
                            <input type="text" id="QR_ID" name="QR_ID"
                                value="<?php //echo field_value($customer, 'QR_ID'); ?>"
                                placeholder="QR ID">
                        </div>

                        <div class="form-group">
                            <label for="RULE_ID">Rule ID</label>
                            <input type="text" id="RULE_ID" name="RULE_ID"
                                value="<?php //echo field_value($customer, 'RULE_ID'); ?>"
                                placeholder="Rule ID">
                        </div>
                        -->

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
                    ><?php echo field_value($customer, 'NOTES'); ?></textarea>
                </div>
            </div>

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

<?php include 'logout_modal.php'; ?>

<script>
lucide.createIcons();

// ── Phone number auto-formatter ───────────────────────────────────────────
var phone_input = document.getElementById('MEMB_PHONE');

phone_input.addEventListener('input', function () {
    var digits = this.value.replace(/^\+1\s?/, '').replace(/[^\d]/g, '').slice(0, 10);
    var formatted = '';
    if (digits.length === 0) {
        formatted = '';
    } else if (digits.length <= 3) {
        formatted = '+1 ' + digits;
    } else if (digits.length <= 6) {
        formatted = '+1 ' + digits.slice(0, 3) + '-' + digits.slice(3);
    } else {
        formatted = '+1 ' + digits.slice(0, 3) + '-' + digits.slice(3, 6) + '-' + digits.slice(6, 10);
    }
    this.value = formatted;
});

phone_input.addEventListener('keydown', function (e) {
    if ((e.key === 'Backspace' || e.key === 'Delete') && this.value === '+1 ') {
        e.preventDefault();
        this.value = '';
    }
});

// ── DOB auto-formatter ────────────────────────────────────────────
document.getElementById('MEMB_DOB').addEventListener('blur', function () {
    var val = this.value;
    if (val) {
        var year = val.split('-')[0];
        if (year.length !== 4 || parseInt(year) < 1900 || parseInt(year) > <?php echo date('Y'); ?>) {
            this.value = '';
            alert('Please enter a valid date of birth.');
        }
    }
});

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