<?php
// invoices.php
// Manual invoice creation and payment tracking

require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/db_connection.php";

start_secure_session();
$pdo = create_database_connection();

$success_msg = '';
$error_msg   = '';

if (isset($_GET['created'])) $success_msg = 'Invoice created successfully.';
if (isset($_GET['updated'])) $success_msg = 'Invoice updated successfully.';

// ── Handle new invoice submission ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $memb_id   = (int)($_POST['MEMB_ID']           ?? 0);
    $pkg_id    = (int)($_POST['PACKAGE_ID']         ?? 0);
    $stat_id   = (int)($_POST['INV_STAT_ID']        ?? 1);
    $price     = trim($_POST['INV_PACKAGE_PRICE']   ?? '');
    $start     = trim($_POST['INV_START_DATE']       ?? '');
    $end       = trim($_POST['INV_END_DATE']         ?? '');
    $inv_numb  = trim($_POST['INV_NUMB']             ?? '');
    $inv_date  = trim($_POST['INV_DATE']             ?? date('Y-m-d'));
    $paid_date = trim($_POST['PAID_DATE']            ?? '') ?: date('Y-m-d');

    if (!$memb_id)         $error_msg = 'Please select a member.';
    elseif (!$pkg_id)      $error_msg = 'Please select a package.';
    elseif ($price === '') $error_msg = 'Please enter a price.';
    elseif ($inv_numb === '') $error_msg = 'Please enter an invoice number.';
    else {
        try {
            // Get next INV_ID
            $max = $pdo->query("SELECT COALESCE(MAX(INV_ID), 0) + 1 FROM invoice")->fetchColumn();

            $stmt = $pdo->prepare("INSERT INTO invoice
                (INV_ID, MEMB_ID, PACKAGE_ID, INV_STAT_ID, INV_PACKAGE_PRICE,
                 INV_START_DATE, INV_END_DATE, INV_NUMB, INV_DATE, PAID_DATE)
                VALUES
                (:inv_id, :memb_id, :pkg_id, :stat_id, :price,
                 :start, :end, :inv_numb, :inv_date, :paid_date)");

            $stmt->bindValue(':inv_id',    (int)$max,   PDO::PARAM_INT);
            $stmt->bindValue(':memb_id',   $memb_id,    PDO::PARAM_INT);
            $stmt->bindValue(':pkg_id',    $pkg_id,     PDO::PARAM_INT);
            $stmt->bindValue(':stat_id',   $stat_id,    PDO::PARAM_INT);
            $stmt->bindValue(':price',     $price);
            $stmt->bindValue(':start',     $start ?: null);
            $stmt->bindValue(':end',       $end   ?: null);
            $stmt->bindValue(':inv_numb',  $inv_numb);
            $stmt->bindValue(':inv_date',  $inv_date);
            $stmt->bindValue(':paid_date', $paid_date);
            $stmt->execute();

            header('Location: invoices.php?created=1');
            exit;
        } catch (PDOException $e) {
            $error_msg = 'Failed to create invoice: ' . $e->getMessage();
        }
    }
}

// ── Handle edit invoice submission ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_invoice'])) {
    $inv_id    = (int)($_POST['edit_INV_ID']            ?? 0);
    $pkg_id    = (int)($_POST['edit_PACKAGE_ID']         ?? 0);
    $stat_id   = (int)($_POST['edit_INV_STAT_ID']        ?? 1);
    $price     = trim($_POST['edit_INV_PACKAGE_PRICE']   ?? '');
    $start     = trim($_POST['edit_INV_START_DATE']       ?? '');
    $end       = trim($_POST['edit_INV_END_DATE']         ?? '');
    $inv_numb  = trim($_POST['edit_INV_NUMB']             ?? '');
    $inv_date  = trim($_POST['edit_INV_DATE']             ?? date('Y-m-d'));
    $paid_date = trim($_POST['edit_PAID_DATE']            ?? '') ?: date('Y-m-d');

    if (!$inv_id)          $error_msg = 'Invalid invoice.';
    elseif ($price === '') $error_msg = 'Please enter a price.';
    elseif ($inv_numb === '') $error_msg = 'Please enter an invoice number.';
    else {
        try {
            $stmt = $pdo->prepare("UPDATE invoice SET
                PACKAGE_ID          = :pkg_id,
                INV_STAT_ID         = :stat_id,
                INV_PACKAGE_PRICE   = :price,
                INV_START_DATE      = :start,
                INV_END_DATE        = :end,
                INV_NUMB            = :inv_numb,
                INV_DATE            = :inv_date,
                PAID_DATE           = :paid_date
                WHERE INV_ID        = :inv_id");

            $stmt->bindValue(':pkg_id',    $pkg_id,     PDO::PARAM_INT);
            $stmt->bindValue(':stat_id',   $stat_id,    PDO::PARAM_INT);
            $stmt->bindValue(':price',     $price);
            $stmt->bindValue(':start',     $start ?: null);
            $stmt->bindValue(':end',       $end   ?: null);
            $stmt->bindValue(':inv_numb',  $inv_numb);
            $stmt->bindValue(':inv_date',  $inv_date);
            $stmt->bindValue(':paid_date', $paid_date);
            $stmt->bindValue(':inv_id',    $inv_id,     PDO::PARAM_INT);
            $stmt->execute();

            header('Location: invoices.php?updated=1');
            exit;
        } catch (PDOException $e) {
            $error_msg = 'Failed to update invoice: ' . $e->getMessage();
        }
    }
}

// ── Fetch data ────────────────────────────────────────────────────────────
$invoices = $pdo->query("
    SELECT i.*, m.MEMB_FNAME, m.MEMB_LNAME, p.PACKAGE_NAME, s.STAT_NAME
    FROM invoice i
    LEFT JOIN member m     ON i.MEMB_ID      = m.MEMB_ID
    LEFT JOIN packages p   ON i.PACKAGE_ID   = p.PACKAGE_ID
    LEFT JOIN inv_status s ON i.INV_STAT_ID  = s.INV_STAT_ID
    ORDER BY i.INV_DATE DESC
")->fetchAll(PDO::FETCH_ASSOC);

$members = $pdo->query("
    SELECT MEMB_ID, MEMB_FNAME, MEMB_LNAME, MEMB_EMAIL
    FROM member
    ORDER BY MEMB_LNAME ASC, MEMB_FNAME ASC
")->fetchAll(PDO::FETCH_ASSOC);

$packages = $pdo->query("
    SELECT PACKAGE_ID, PACKAGE_NAME, PACKAGE_PRICE
    FROM packages
    ORDER BY PACKAGE_ID ASC
")->fetchAll(PDO::FETCH_ASSOC);

$inv_statuses = $pdo->query("
    SELECT INV_STAT_ID, STAT_NAME FROM inv_status ORDER BY INV_STAT_ID ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Build members JSON for recommendation search
$members_json = json_encode(array_map(function($m) {
    return [
        'id'    => $m['MEMB_ID'],
        'name'  => $m['MEMB_FNAME'] . ' ' . $m['MEMB_LNAME'],
        'email' => $m['MEMB_EMAIL'],
    ];
}, $members));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices & Payments — Belle's Training Solutions</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/invoices.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>

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
        <a href="customers.php" class="nav-item">
            <i data-lucide="users" class="nav-icon"></i><span>Customers</span>
        </a>
        <a href="invoices.php" class="nav-item active">
            <i data-lucide="credit-card" class="nav-icon"></i><span>Payments</span>
        </a>
        <a href="renewal.php" class="nav-item">
            <i data-lucide="refresh-cw" class="nav-icon"></i><span>Renewals</span>
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

<main class="main-content">

    <div class="page-header-row">
        <div>
            <h1>Invoices &amp; Payments</h1>
            <p class="page-subtitle">Create invoices, track payments, and manage member billing.</p>
        </div>
        <div class="header-actions">
            <a href="invoice_hub.php" class="btn-back">
                <i data-lucide="arrow-left"></i> Back
            </a>
            <button id="btn_import_square" class="btn-import-square" onclick="importFromSquare()">
                <i data-lucide="download"></i> Import from Square
            </button>
            <a href="https://squareup.com/dashboard" target="_blank" class="btn-square">
                <i data-lucide="external-link"></i> Square Dashboard
            </a>
        </div>
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

    <!-- ── Create Invoice ───────────────────────────────────────────────── -->
    <div class="table-card form-card">
        <div class="table-card-header">
            <span class="table-card-title">Create Invoice</span>
        </div>
        <div class="form-body">
            <form method="POST" action="invoices.php">

                <div class="form-sections-row">

                    <!-- Member & Package -->
                    <div class="form-section">
                        <h2 class="form-section-title">
                            <i data-lucide="user"></i> Member &amp; Package
                        </h2>
                        <div class="form-grid">

                            <!-- Member search with recommendations -->
                            <div class="form-group full-width">
                                <label for="member_search">Member</label>
                                <input type="text" id="member_search"
                                    placeholder="Type a name to search..."
                                    autocomplete="off">
                                <div id="member_suggestions" class="suggestions-box"></div>
                                <input type="hidden" id="MEMB_ID" name="MEMB_ID">
                                <span id="member_selected" class="selected-member"></span>
                            </div>

                            <div class="form-group">
                                <label for="PACKAGE_ID">Package</label>
                                <select id="PACKAGE_ID" name="PACKAGE_ID" required>
                                    <option value="">— Select package —</option>
                                    <?php foreach ($packages as $pkg): ?>
                                        <option value="<?php echo $pkg['PACKAGE_ID']; ?>"
                                            data-price="<?php echo $pkg['PACKAGE_PRICE']; ?>">
                                            <?php echo htmlspecialchars($pkg['PACKAGE_NAME']); ?>
                                            — $<?php echo number_format($pkg['PACKAGE_PRICE'], 2); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="INV_STAT_ID">Status</label>
                                <select id="INV_STAT_ID" name="INV_STAT_ID" required>
                                    <?php foreach ($inv_statuses as $s): ?>
                                        <option value="<?php echo $s['INV_STAT_ID']; ?>">
                                            <?php echo htmlspecialchars($s['STAT_NAME']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="form-sections-divider"></div>

                    <!-- Invoice Details -->
                    <div class="form-section">
                        <h2 class="form-section-title">
                            <i data-lucide="file-text"></i> Invoice Details
                        </h2>
                        <div class="form-grid">

                            <div class="form-group">
                                <label for="INV_NUMB">Invoice Number</label>
                                <input type="text" id="INV_NUMB" name="INV_NUMB"
                                    placeholder="e.g. INV-00001"
                                    maxlength="9" required>
                            </div>

                            <div class="form-group">
                                <label for="INV_PACKAGE_PRICE">Amount ($)</label>
                                <input type="number" id="INV_PACKAGE_PRICE" name="INV_PACKAGE_PRICE"
                                    step="0.01" min="0" placeholder="0.00" required>
                            </div>

                            <div class="form-group">
                                <label for="INV_DATE">Invoice Date</label>
                                <input type="date" id="INV_DATE" name="INV_DATE"
                                    value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="PAID_DATE">Paid Date <span class="field-hint">(leave blank if unpaid)</span></label>
                                <input type="date" id="PAID_DATE" name="PAID_DATE">
                            </div>

                            <div class="form-group">
                                <label for="INV_START_DATE">Coverage Start</label>
                                <input type="date" id="INV_START_DATE" name="INV_START_DATE">
                            </div>

                            <div class="form-group">
                                <label for="INV_END_DATE">Coverage End</label>
                                <input type="date" id="INV_END_DATE" name="INV_END_DATE">
                            </div>

                        </div>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="submit" name="create_invoice" class="btn-primary">
                        <i data-lucide="plus-circle"></i> Create Invoice
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- ── Square Import Panel ──────────────────────────────────────────── -->
    <div id="square_panel" style="display:none;" class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">
                <i data-lucide="download" style="width:15px;height:15px;display:inline;"></i>
                Square Payment Import
            </span>
            <button onclick="closeSquarePanel()" class="btn-close-panel">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div id="square_panel_body" style="padding:20px 24px;">
            <p style="color:#6b7280; font-size:13.5px;">Loading Square payments...</p>
        </div>
    </div>

    <!-- ── Invoice History ──────────────────────────────────────────────── -->
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Invoice History</span>
            <span class="table-card-count"><?php echo count($invoices); ?> invoice(s)</span>
        </div>
        <div class="table-scroll">
            <?php if (count($invoices) > 0): ?>
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Member</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Invoice Date</th>
                            <th>Paid Date</th>
                            <th>Coverage</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td class="col-inv-numb">
                                    <?php echo htmlspecialchars($inv['INV_NUMB']); ?>
                                </td>
                                <td class="col-name">
                                    <?php echo htmlspecialchars($inv['MEMB_FNAME'] . ' ' . $inv['MEMB_LNAME']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($inv['PACKAGE_NAME'] ?? '—'); ?></td>
                                <td>$<?php echo number_format($inv['INV_PACKAGE_PRICE'], 2); ?></td>
                                <td>
                                    <?php
                                    $stat = strtolower($inv['STAT_NAME'] ?? '');
                                    $stat_class = match($stat) {
                                        'paid'      => 'status-paid',
                                        'unpaid'    => 'status-unpaid',
                                        'overdue'   => 'status-overdue',
                                        'cancelled' => 'status-cancelled',
                                        default     => 'status-unpaid',
                                    };
                                    ?>
                                    <span class="status-badge <?php echo $stat_class; ?>">
                                        <?php echo htmlspecialchars($inv['STAT_NAME'] ?? '—'); ?>
                                    </span>
                                </td>
                                <td class="col-date">
                                    <?php echo !empty($inv['INV_DATE']) ? date('M j, Y', strtotime($inv['INV_DATE'])) : '—'; ?>
                                </td>
                                <td class="col-date">
                                    <?php echo !empty($inv['PAID_DATE']) ? date('M j, Y', strtotime($inv['PAID_DATE'])) : '—'; ?>
                                </td>
                                <td class="col-date" style="white-space:nowrap;">
                                    <?php
                                    $cs = !empty($inv['INV_START_DATE']) ? date('M j, Y', strtotime($inv['INV_START_DATE'])) : '—';
                                    $ce = !empty($inv['INV_END_DATE'])   ? date('M j, Y', strtotime($inv['INV_END_DATE']))   : '—';
                                    echo htmlspecialchars("$cs → $ce");
                                    ?>
                                </td>
                                <td class="col-actions">
                                    <button class="btn-action-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($inv)); ?>)">
                                        <i data-lucide="pencil"></i> Edit
                                    </button>
                                    <button class="btn-action-delete" onclick="confirmDeleteInvoice(<?php echo (int)$inv['INV_ID']; ?>, '<?php echo htmlspecialchars($inv['INV_NUMB'], ENT_QUOTES); ?>')">
                                        <i data-lucide="trash-2"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i data-lucide="file-text" class="empty-icon"></i>
                    <p>No invoices yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="site-footer">
        <p><small>&copy; Windswept Student Consulting 2026</small></p>
    </footer>

</main>

<?php include 'logout_modal.php'; ?>

<!-- ── Edit Invoice Modal ──────────────────────────────────────────────── -->
<div id="edit_overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
    <div id="edit_modal" style="background:#fff; border-radius:14px; padding:32px; width:640px; max-width:95vw; max-height:90vh; overflow-y:auto; box-shadow:0 8px 32px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:18px; font-weight:700; color:#0077BE;">Edit Invoice</h2>
            <button onclick="closeEditModal()" style="background:none; border:none; cursor:pointer; color:#6b7280;">
                <i data-lucide="x" style="width:20px;height:20px;"></i>
            </button>
        </div>
        <form method="POST" action="invoices.php">
            <input type="hidden" name="edit_invoice" value="1">
            <input type="hidden" name="edit_INV_ID" id="edit_INV_ID">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px 20px;">

                <div class="form-group">
                    <label for="edit_INV_NUMB">Invoice Number</label>
                    <input type="text" id="edit_INV_NUMB" name="edit_INV_NUMB" maxlength="9" required>
                </div>

                <div class="form-group">
                    <label for="edit_PACKAGE_ID">Package</label>
                    <select id="edit_PACKAGE_ID" name="edit_PACKAGE_ID" required>
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?php echo $pkg['PACKAGE_ID']; ?>"
                                data-price="<?php echo $pkg['PACKAGE_PRICE']; ?>">
                                <?php echo htmlspecialchars($pkg['PACKAGE_NAME']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_INV_STAT_ID">Status</label>
                    <select id="edit_INV_STAT_ID" name="edit_INV_STAT_ID" required>
                        <?php foreach ($inv_statuses as $s): ?>
                            <option value="<?php echo $s['INV_STAT_ID']; ?>">
                                <?php echo htmlspecialchars($s['STAT_NAME']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_INV_PACKAGE_PRICE">Amount ($)</label>
                    <input type="number" id="edit_INV_PACKAGE_PRICE" name="edit_INV_PACKAGE_PRICE" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="edit_INV_DATE">Invoice Date</label>
                    <input type="date" id="edit_INV_DATE" name="edit_INV_DATE">
                </div>

                <div class="form-group">
                    <label for="edit_PAID_DATE">Paid Date</label>
                    <input type="date" id="edit_PAID_DATE" name="edit_PAID_DATE">
                </div>

                <div class="form-group">
                    <label for="edit_INV_START_DATE">Coverage Start</label>
                    <input type="date" id="edit_INV_START_DATE" name="edit_INV_START_DATE">
                </div>

                <div class="form-group">
                    <label for="edit_INV_END_DATE">Coverage End</label>
                    <input type="date" id="edit_INV_END_DATE" name="edit_INV_END_DATE">
                </div>

            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="closeEditModal()" class="btn-secondary-modal">Cancel</button>
                <button type="submit" class="btn-primary">
                    <i data-lucide="save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Square import ─────────────────────────────────────────────────────────
function importFromSquare() {
    var panel = document.getElementById('square_panel');
    var body  = document.getElementById('square_panel_body');
    panel.style.display = 'block';
    body.innerHTML = '<p style="color:#6b7280;font-size:13.5px;">Fetching Square payments...</p>';
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'square_import.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        try {
            var res = JSON.parse(xhr.responseText);
            renderSquarePanel(res);
        } catch (e) {
            body.innerHTML = '<p style="color:#ef4444;">Unexpected response from server.</p>';
        }
    };
    xhr.onerror = function () {
        body.innerHTML = '<p style="color:#ef4444;">Network error. Please try again.</p>';
    };
    xhr.send();
}

function closeSquarePanel() {
    document.getElementById('square_panel').style.display = 'none';
}

function renderSquarePanel(res) {
    var body = document.getElementById('square_panel_body');

    if (!res.success && res.not_configured) {
        body.innerHTML = '<div style="padding:16px;background:#fef3c7;border-radius:8px;border:1px solid #fcd34d;">'
            + '<p style="font-weight:600;color:#92400e;margin-bottom:6px;">Square API Not Configured</p>'
            + '<p style="color:#78350f;font-size:13px;">' + res.message + '</p>'
            + '</div>';
        return;
    }

    if (!res.success) {
        body.innerHTML = '<p style="color:#ef4444;">' + res.message + '</p>';
        return;
    }

    if (!res.payments || res.payments.length === 0) {
        body.innerHTML = '<p style="color:#6b7280;font-size:13.5px;">No new Square payments to import.</p>';
        return;
    }

    var packages_data = <?php echo json_encode(array_map(function($p) {
        return ['id' => $p['PACKAGE_ID'], 'name' => $p['PACKAGE_NAME']];
    }, $packages)); ?>;

    var html = '<p style="font-size:13px;color:#6b7280;margin-bottom:16px;">'
        + res.count + ' unimported payment(s) found. Review and link each to a member account.</p>';

    html += '<div style="display:flex;flex-direction:column;gap:16px;">';

    res.payments.forEach(function (pmt) {
        var auto = pmt.auto_match;
        var suggestions = pmt.suggestions;

        var pkg_options = '<option value="">— No package —</option>';
        packages_data.forEach(function (p) {
            pkg_options += '<option value="' + p.id + '">' + p.name + '</option>';
        });

        var suggestions_html = '<option value="">— Select a member —</option>';
        suggestions.forEach(function (s) {
            suggestions_html += '<option value="' + s.memb_id + '"'
                + (auto && auto.MEMB_ID == s.memb_id ? ' selected' : '') + '>'
                + s.name + ' (' + s.email + ')'
                + (s.score >= 80 ? ' ✓' : '') + '</option>';
        });

        var status_color = pmt.status === 'COMPLETED' ? '#065f46' : '#92400e';
        var status_bg    = pmt.status === 'COMPLETED' ? '#d1fae5' : '#fef3c7';

        html += '<div style="background:#f8fbfd;border:1px solid #E5E7EB;border-radius:10px;padding:16px;">'
            + '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">'
            +   '<div>'
            +     '<span style="font-weight:700;font-size:15px;color:#1e3a5f;">$' + pmt.amount.toFixed(2) + ' ' + pmt.currency + '</span>'
            +     '<span style="margin-left:10px;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:' + status_bg + ';color:' + status_color + ';">' + pmt.status + '</span>'
            +   '</div>'
            +   '<span style="font-size:12px;color:#6b7280;">' + pmt.date + '</span>'
            + '</div>'
            + (pmt.buyer_name  ? '<p style="font-size:13px;color:#6b7280;margin-bottom:4px;">Buyer: ' + pmt.buyer_name + '</p>' : '')
            + (pmt.buyer_email ? '<p style="font-size:13px;color:#6b7280;margin-bottom:12px;">Email: ' + pmt.buyer_email + '</p>' : '')
            + '<div style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end;">'
            +   '<div>'
            +     '<label style="font-size:12px;font-weight:600;color:#0077BE;display:block;margin-bottom:4px;">Link to Member</label>'
            +     '<select id="member_' + pmt.square_payment_id + '" style="width:100%;padding:8px 10px;border:1px solid #E5E7EB;border-radius:8px;font-size:13px;">'
            +     suggestions_html
            +     '</select>'
            +   '</div>'
            +   '<div>'
            +     '<label style="font-size:12px;font-weight:600;color:#0077BE;display:block;margin-bottom:4px;">Package</label>'
            +     '<select id="pkg_' + pmt.square_payment_id + '" style="width:100%;padding:8px 10px;border:1px solid #E5E7EB;border-radius:8px;font-size:13px;">'
            +     pkg_options
            +     '</select>'
            +   '</div>'
            +   '<button onclick="linkSquarePayment(\'' + pmt.square_payment_id + '\', ' + pmt.amount + ', \'' + pmt.date + '\')" '
            +     'style="padding:8px 16px;background:#0077BE;color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:13px;white-space:nowrap;">'
            +     'Link &amp; Import'
            +   '</button>'
            + '</div>'
            + '</div>';
    });

    html += '</div>';
    body.innerHTML = html;
    lucide.createIcons();
}

function linkSquarePayment(sq_id, amount, date) {
    var memb_id = document.getElementById('member_' + sq_id).value;
    var pkg_id  = document.getElementById('pkg_' + sq_id).value;

    if (!memb_id) {
        alert('Please select a member to link this payment to.');
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'square_link.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        try {
            var res = JSON.parse(xhr.responseText);
            if (res.success) {
                alert('Payment imported as invoice ' + res.inv_numb);
                window.location.reload();
            } else {
                alert('Failed to import: ' + res.message);
            }
        } catch (e) { alert('Unexpected server response.'); }
    };
    xhr.onerror = function () { alert('Network error. Please try again.'); };
    xhr.send(
        'MEMB_ID='             + encodeURIComponent(memb_id)
        + '&PACKAGE_ID='       + encodeURIComponent(pkg_id)
        + '&amount='           + encodeURIComponent(amount)
        + '&pmt_date='         + encodeURIComponent(date)
        + '&square_payment_id='+ encodeURIComponent(sq_id)
    );
}

// ── Functions defined first so onclick handlers always find them ──────────
function openEditModal(inv) {
    document.getElementById('edit_INV_ID').value            = inv.INV_ID;
    document.getElementById('edit_INV_NUMB').value          = inv.INV_NUMB;
    document.getElementById('edit_INV_PACKAGE_PRICE').value = parseFloat(inv.INV_PACKAGE_PRICE).toFixed(2);
    document.getElementById('edit_INV_DATE').value          = inv.INV_DATE || '';
    document.getElementById('edit_PAID_DATE').value         = inv.PAID_DATE || '';
    document.getElementById('edit_INV_START_DATE').value    = inv.INV_START_DATE || '';
    document.getElementById('edit_INV_END_DATE').value      = inv.INV_END_DATE || '';
    var pkg_select  = document.getElementById('edit_PACKAGE_ID');
    var stat_select = document.getElementById('edit_INV_STAT_ID');
    for (var i = 0; i < pkg_select.options.length; i++) {
        pkg_select.options[i].selected = (pkg_select.options[i].value == inv.PACKAGE_ID);
    }
    for (var j = 0; j < stat_select.options.length; j++) {
        stat_select.options[j].selected = (stat_select.options[j].value == inv.INV_STAT_ID);
    }
    document.getElementById('edit_overlay').style.display = 'flex';
    document.addEventListener('keydown', handleEditKey);
}

function closeEditModal() {
    document.getElementById('edit_overlay').style.display = 'none';
    document.removeEventListener('keydown', handleEditKey);
}

function handleEditKey(e) {
    if (e.key === 'Escape') closeEditModal();
}

function confirmDeleteInvoice(invId, invNumb) {
    if (!confirm('Are you sure you want to delete invoice ' + invNumb + '? This cannot be undone.')) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'delete_invoice.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) { window.location.reload(); }
                else { alert('Failed to delete: ' + (res.message || 'Unknown error.')); }
            } catch (e) { alert('Unexpected server response.'); }
        } else { alert('Server error (' + xhr.status + '). Please try again.'); }
    };
    xhr.onerror = function () { alert('Network error. Please try again.'); };
    xhr.send('INV_ID=' + encodeURIComponent(invId));
}

// ── Init after DOM ready ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();

    var overlay = document.getElementById('edit_overlay');
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) closeEditModal();
        });
    }

    var pkg_select = document.getElementById('PACKAGE_ID');
    if (pkg_select) {
        pkg_select.addEventListener('change', function () {
            var price = this.options[this.selectedIndex].dataset.price;
            if (price) document.getElementById('INV_PACKAGE_PRICE').value = parseFloat(price).toFixed(2);
        });
    }

    var members_data  = <?php echo $members_json; ?>;
    var search_input  = document.getElementById('member_search');
    var suggestions   = document.getElementById('member_suggestions');
    var memb_id_input = document.getElementById('MEMB_ID');
    var selected_span = document.getElementById('member_selected');

    if (search_input) {
        search_input.addEventListener('input', function () {
            var query = this.value.trim().toLowerCase();
            suggestions.innerHTML = '';
            suggestions.style.display = 'none';
            if (query.length < 2) return;
            var matches = members_data.filter(function (m) {
                return m.name.toLowerCase().includes(query) || m.email.toLowerCase().includes(query);
            }).slice(0, 6);
            if (matches.length === 0) return;
            matches.forEach(function (m) {
                var item = document.createElement('div');
                item.className = 'suggestion-item';
                item.innerHTML = '<strong>' + m.name + '</strong><span>' + m.email + '</span>';
                item.addEventListener('click', function () {
                    search_input.value        = m.name;
                    memb_id_input.value       = m.id;
                    selected_span.textContent = 'Selected: ' + m.name;
                    suggestions.innerHTML     = '';
                    suggestions.style.display = 'none';
                });
                suggestions.appendChild(item);
            });
            suggestions.style.display = 'block';
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#member_search') && !e.target.closest('#member_suggestions')) {
                suggestions.style.display = 'none';
            }
        });
    }
});
</script>

</body>
</html>
<?php $pdo = null; ?>