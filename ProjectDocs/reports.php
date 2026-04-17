A<?php
require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/db_connection.php";

start_secure_session();
$pdo = create_database_connection();

// auth check
// if (!isset($_SESSION["USER_ID"])) {
//     header("Location: login.php");
//     exit();
// }

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function valid_date(?string $date): bool
{
    if (!$date) {
        return false;
    }

    $dt = DateTime::createFromFormat("Y-m-d", $date);
    return $dt && $dt->format("Y-m-d") === $date;
}

$date_from = $_GET["date_from"] ?? "";
$date_to   = $_GET["date_to"] ?? "";
$export    = $_GET["export"] ?? "";

if (!valid_date($date_from)) {
    $date_from = "";
}

if (!valid_date($date_to)) {
    $date_to = "";
}

if ($date_from !== "" && $date_to !== "" && $date_from > $date_to) {
    [$date_from, $date_to] = [$date_to, $date_from];
}

function buildDateFilter(string $column, string $from, string $to, array &$params): string
{
    $conditions = [];

    if ($from !== "") {
        $conditions[] = "$column >= :date_from";
        $params[":date_from"] = $from;
    }

    if ($to !== "") {
        $conditions[] = "$column <= :date_to";
        $params[":date_to"] = $to;
    }

    return $conditions ? " WHERE " . implode(" AND ", $conditions) : "";
}

function buildDateTimeFilter(string $column, string $from, string $to, array &$params): string
{
    $conditions = [];

    if ($from !== "") {
        $conditions[] = "$column >= :dt_from";
        $params[":dt_from"] = $from . " 00:00:00";
    }

    if ($to !== "") {
        $conditions[] = "$column <= :dt_to";
        $params[":dt_to"] = $to . " 23:59:59";
    }

    return $conditions ? " WHERE " . implode(" AND ", $conditions) : "";
}

function fetchRows(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function exportCsv(string $filename, array $headers, array $rows): void
{
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $output = fopen("php://output", "w");
    fputcsv($output, $headers);

    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

/*
 Members Report
*/
$memberParams = [];
$memberWhere = buildDateFilter("m.MEMB_JOINDATE", $date_from, $date_to, $memberParams);

$membersReport = fetchRows(
    $pdo,
    "SELECT
        m.MEMB_ID,
        m.MEMB_FNAME,
        m.MEMB_LNAME,
        m.MEMB_EMAIL,
        m.MEMB_PHONE,
        m.MEMB_JOINDATE,
        m.START_DATE,
        m.END_DATE,
        p.PACKAGE_NAME,
        ms.MEMB_STATUS_NAME
     FROM member m
     LEFT JOIN packages p ON p.PACKAGE_ID = m.PACKAGE_ID
     LEFT JOIN memb_status ms ON ms.MEMB_STATUS_ID = m.MEMB_STATUS_ID
     $memberWhere
     ORDER BY m.MEMB_ID DESC",
    $memberParams
);

/*
 Invoices Report
*/
$invoiceParams = [];
$invoiceWhere = buildDateFilter("i.INV_DATE", $date_from, $date_to, $invoiceParams);

$invoicesReport = fetchRows(
    $pdo,
    "SELECT
        i.INV_ID,
        i.INV_NUMB,
        i.INV_DATE,
        i.INV_START_DATE,
        i.INV_END_DATE,
        i.PAID_DATE,
        i.INV_PACKAGE_PRICE,
        s.STAT_NAME,
        m.MEMB_FNAME,
        m.MEMB_LNAME,
        p.PACKAGE_NAME
     FROM invoice i
     LEFT JOIN inv_status s ON s.INV_STAT_ID = i.INV_STAT_ID
     LEFT JOIN member m ON m.MEMB_ID = i.MEMB_ID
     LEFT JOIN packages p ON p.PACKAGE_ID = i.PACKAGE_ID
     $invoiceWhere
     ORDER BY i.INV_DATE DESC, i.INV_ID DESC",
    $invoiceParams
);

/*
 Payments Report
*/
$paymentParams = [];
$paymentWhere = buildDateTimeFilter("pt.PMT_DATE_TIME", $date_from, $date_to, $paymentParams);

$paymentsReport = fetchRows(
    $pdo,
    "SELECT
        pt.PMT_ID,
        pt.PMT_DATE_TIME,
        pt.PROC_CODE,
        pt.SUCCESS,
        pt.TOTAL,
        pt.FEE,
        i.INV_NUMB,
        m.MEMB_FNAME,
        m.MEMB_LNAME
     FROM payment_transaction pt
     LEFT JOIN invoice i ON i.INV_ID = pt.INV_ID
     LEFT JOIN member m ON m.MEMB_ID = i.MEMB_ID
     $paymentWhere
     ORDER BY pt.PMT_DATE_TIME DESC, pt.PMT_ID DESC",
    $paymentParams
);

/*
 Renewals Report
*/
$renewalParams = [];
$renewalConditions = [];

if ($date_from !== "") {
    $renewalConditions[] = "m.END_DATE >= :renewal_from";
    $renewalParams[":renewal_from"] = $date_from;
} else {
    $renewalConditions[] = "m.END_DATE >= CURDATE()";
}

if ($date_to !== "") {
    $renewalConditions[] = "m.END_DATE <= :renewal_to";
    $renewalParams[":renewal_to"] = $date_to;
} else {
    $renewalConditions[] = "m.END_DATE <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

$renewalWhere = " WHERE m.END_DATE IS NOT NULL AND " . implode(" AND ", $renewalConditions);

$renewalsReport = fetchRows(
    $pdo,
    "SELECT
        m.MEMB_ID,
        m.MEMB_FNAME,
        m.MEMB_LNAME,
        m.MEMB_EMAIL,
        m.MEMB_PHONE,
        p.PACKAGE_NAME,
        m.END_DATE
     FROM member m
     LEFT JOIN packages p ON p.PACKAGE_ID = m.PACKAGE_ID
     $renewalWhere
     ORDER BY m.END_DATE ASC, m.MEMB_LNAME ASC, m.MEMB_FNAME ASC",
    $renewalParams
);

/*
 QR Code / Access Rules Report
*/
$qrRulesReport = fetchRows(
    $pdo,
    "SELECT
        q.QR_ID,
        q.MEMB_ID,
        q.RULE_ID,
        m.MEMB_FNAME,
        m.MEMB_LNAME,
        ar.MIN_AGE,
        ar.OPEN_HOUR,
        ar.CLOSE_HOUR
     FROM qr_code q
     LEFT JOIN member m ON m.MEMB_ID = q.MEMB_ID
     LEFT JOIN access_rules ar ON ar.RULE_ID = q.RULE_ID
     ORDER BY q.QR_ID DESC"
);

/*
 Users / Permissions Report
*/
$usersReport = fetchRows(
    $pdo,
    "SELECT
        u.USER_ID,
        u.USER_NAME,
        u.USER_FNAME,
        u.USER_LNAME,
        u.USER_EMAIL,
        p.PERM_NAME
     FROM `user` u
     LEFT JOIN permissions p ON p.PERM_ID = u.PERM_ID
     ORDER BY u.USER_ID DESC"
);

/*
 CSV Export
*/
if ($export !== "") {
    switch ($export) {
        case "members":
            $rows = [];
            foreach ($membersReport as $row) {
                $rows[] = [
                    $row["MEMB_ID"] ?? "",
                    trim(($row["MEMB_FNAME"] ?? "") . " " . ($row["MEMB_LNAME"] ?? "")),
                    $row["MEMB_EMAIL"] ?? "",
                    $row["MEMB_PHONE"] ?? "",
                    $row["PACKAGE_NAME"] ?? "",
                    $row["MEMB_STATUS_NAME"] ?? "",
                    $row["MEMB_JOINDATE"] ?? "",
                    $row["START_DATE"] ?? "",
                    $row["END_DATE"] ?? "",
                ];
            }

            exportCsv(
                "members_report.csv",
                ["Member ID", "Member Name", "Email", "Phone", "Package", "Status", "Join Date", "Start Date", "End Date"],
                $rows
            );
            break;

        case "invoices":
            $rows = [];
            foreach ($invoicesReport as $row) {
                $rows[] = [
                    $row["INV_ID"] ?? "",
                    $row["INV_NUMB"] ?? "",
                    trim(($row["MEMB_FNAME"] ?? "") . " " . ($row["MEMB_LNAME"] ?? "")),
                    $row["PACKAGE_NAME"] ?? "",
                    $row["STAT_NAME"] ?? "",
                    $row["INV_PACKAGE_PRICE"] ?? "",
                    $row["INV_DATE"] ?? "",
                    $row["INV_START_DATE"] ?? "",
                    $row["INV_END_DATE"] ?? "",
                    $row["PAID_DATE"] ?? "",
                ];
            }

            exportCsv(
                "invoices_report.csv",
                ["Invoice ID", "Invoice Number", "Member", "Package", "Status", "Amount", "Invoice Date", "Start Date", "End Date", "Paid Date"],
                $rows
            );
            break;

        case "payments":
            $rows = [];
            foreach ($paymentsReport as $row) {
                $rows[] = [
                    $row["PMT_ID"] ?? "",
                    trim(($row["MEMB_FNAME"] ?? "") . " " . ($row["MEMB_LNAME"] ?? "")),
                    $row["INV_NUMB"] ?? "",
                    $row["PMT_DATE_TIME"] ?? "",
                    $row["PROC_CODE"] ?? "",
                    !empty($row["SUCCESS"]) ? "Success" : "Failed",
                    $row["TOTAL"] ?? "",
                    $row["FEE"] ?? "",
                ];
            }

            exportCsv(
                "payments_report.csv",
                ["Payment ID", "Member", "Invoice Number", "Payment Date/Time", "Processor Code", "Status", "Total", "Fee"],
                $rows
            );
            break;

        case "renewals":
            $rows = [];
            foreach ($renewalsReport as $row) {
                $rows[] = [
                    $row["MEMB_ID"] ?? "",
                    trim(($row["MEMB_FNAME"] ?? "") . " " . ($row["MEMB_LNAME"] ?? "")),
                    $row["MEMB_EMAIL"] ?? "",
                    $row["MEMB_PHONE"] ?? "",
                    $row["PACKAGE_NAME"] ?? "",
                    $row["END_DATE"] ?? "",
                ];
            }

            exportCsv(
                "renewals_report.csv",
                ["Member ID", "Member", "Email", "Phone", "Package", "Expiry Date"],
                $rows
            );
            break;

        case "qrrules":
            $rows = [];
            foreach ($qrRulesReport as $row) {
                $rows[] = [
                    $row["QR_ID"] ?? "",
                    $row["MEMB_ID"] ?? "",
                    trim(($row["MEMB_FNAME"] ?? "") . " " . ($row["MEMB_LNAME"] ?? "")),
                    $row["RULE_ID"] ?? "",
                    $row["MIN_AGE"] ?? "",
                    $row["OPEN_HOUR"] ?? "",
                    $row["CLOSE_HOUR"] ?? "",
                ];
            }

            exportCsv(
                "qr_access_rules_report.csv",
                ["QR ID", "Member ID", "Member", "Rule ID", "Minimum Age", "Open Hour", "Close Hour"],
                $rows
            );
            break;

        case "users":
            $rows = [];
            foreach ($usersReport as $row) {
                $rows[] = [
                    $row["USER_ID"] ?? "",
                    trim(($row["USER_FNAME"] ?? "") . " " . ($row["USER_LNAME"] ?? "")),
                    $row["USER_NAME"] ?? "",
                    $row["USER_EMAIL"] ?? "",
                    $row["PERM_NAME"] ?? "",
                ];
            }

            exportCsv(
                "users_permissions_report.csv",
                ["User ID", "Name", "Username", "Email", "Permission"],
                $rows
            );
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>

    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/reports.css">

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <style>
        .report-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .print-shell {
            font-family: Arial, sans-serif;
            color: #111;
            padding: 24px;
        }

        .print-shell h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .print-shell .print-date {
            margin: 0 0 20px;
            color: #555;
            font-size: 14px;
        }

        .print-shell table {
            width: 100%;
            border-collapse: collapse;
        }

        .print-shell th,
        .print-shell td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        .print-shell th {
            background: #f3f3f3;
        }

        @media print {
            .print-shell {
                padding: 0;
            }
        }
    </style>
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
        <a href="dashboard.php" class="nav-item">
            <i data-lucide="layout-dashboard" class="nav-icon"></i>
            <span>Dashboard</span>
        </a>
        <a href="customers.php" class="nav-item">
            <i data-lucide="users" class="nav-icon"></i>
            <span>Customers</span>
        </a>
        <a href="qr_code.php" class="nav-item">
            <i data-lucide="qr-code" class="nav-icon"></i>
            <span>QR Codes</span>
        </a>
        <a href="payments.php" class="nav-item">
            <i data-lucide="credit-card" class="nav-icon"></i>
            <span>Payments</span>
        </a>
        <a href="renewals.php" class="nav-item">
            <i data-lucide="refresh-cw" class="nav-icon"></i>
            <span>Renewals</span>
        </a>
        <a href="invoice.php" class="nav-item">
            <i data-lucide="file-text" class="nav-icon"></i>
            <span>Invoice</span>
        </a>
        <a href="reports.php" class="nav-item active">
            <i data-lucide="table-properties" class="nav-icon"></i>
            <span>Reports</span>
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

<main class="main-content">
    <div class="reports-page-header">
        <div>
            <h1>Reports</h1>
            <p>View and export system records in a clean table format.</p>
        </div>
    </div>

    <section class="reports-toolbar-card">
        <form method="GET" action="reports.php" class="reports-filter-form">
            <div class="filter-input">
                <label for="date_from">From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo h($date_from); ?>">
            </div>

            <div class="filter-input">
                <label for="date_to">To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo h($date_to); ?>">
            </div>

            <div class="filter-buttons">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="reports.php" class="btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="report-section" id="members-report">
        <div class="report-section-header">
            <h2>Members Report</h2>
            <div class="report-actions">
                <a href="#" class="btn-secondary print-report-link" data-print-target="members-report">Print Report</a>
                <a class="btn-secondary" href="reports.php?export=members<?php echo $date_from !== '' ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to !== '' ? '&date_to=' . urlencode($date_to) : ''; ?>">Download Report</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Member ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Package</th>
                        <th>Status</th>
                        <th>Join Date</th>
                        <th>End Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($membersReport)): ?>
                    <?php foreach ($membersReport as $row): ?>
                        <tr>
                            <td><?php echo h($row["MEMB_ID"]); ?></td>
                            <td><?php echo h(trim(($row["MEMB_FNAME"] ?? "") . " " . ($row["MEMB_LNAME"] ?? ""))); ?></td>
                            <td><?php echo h($row["MEMB_EMAIL"] ?? ""); ?></td>
                            <td><?php echo h($row["MEMB_PHONE"] ?? ""); ?></td>
                            <td><?php echo h($row["PACKAGE_NAME"] ?? ""); ?></td>
                            <td><?php echo h($row["MEMB_STATUS_NAME"] ?? ""); ?></td>
                            <td><?php echo !empty($row["MEMB_JOINDATE"]) ? h(date("M j, Y", strtotime($row["MEMB_JOINDATE"]))) : "—"; ?></td>
                            <td><?php echo !empty($row["END_DATE"]) ? h(date("M j, Y", strtotime($row["END_DATE"]))) : "—"; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty-cell">No member records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="report-section" id="invoices-report">
        <div class="report-section-header">
            <h2>Invoices Report</h2>
            <div class="report-actions">
                <a href="#" class="btn-secondary print-report-link" data-print-target="invoices-report">Print Report</a>
                <a class="btn-secondary" href="reports.php?export=invoices<?php echo $date_from !== '' ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to !== '' ? '&date_to=' . urlencode($date_to) : ''; ?>">Download Report</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Invoice #</th>
                        <th>Member</th>
                        <th>Package</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Invoice Date</th>
                        <th>Paid Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($invoicesReport)): ?>
                    <?php foreach ($invoicesReport as $row): ?>
                        <tr>
                            <td><?php echo h($row["INV_ID"]); ?></td>
                            <td><?php echo h($row["INV_NUMB"] ?? ""); ?></td>
                            <td><?php echo h(trim(($row["MEMB_FNAME"] ?? "") . " " . ($row["MEMB_LNAME"] ?? ""))); ?></td>
                            <td><?php echo h($row["PACKAGE_NAME"] ?? ""); ?></td>
                            <td><?php echo h($row["STAT_NAME"] ?? ""); ?></td>
                            <td>$<?php echo number_format((float) ($row["INV_PACKAGE_PRICE"] ?? 0), 2); ?></td>
                            <td><?php echo !empty($row["INV_DATE"]) ? h(date("M j, Y", strtotime($row["INV_DATE"]))) : "—"; ?></td>
                            <td><?php echo !empty($row["PAID_DATE"]) ? h(date("M j, Y", strtotime($row["PAID_DATE"]))) : "—"; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty-cell">No invoice records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="report-section" id="payments-report">
        <div class="report-section-header">
            <h2>Payments Report</h2>
            <div class="report-actions">
                <a href="#" class="btn-secondary print-report-link" data-print-target="payments-report">Print Report</a>
                <a class="btn-secondary" href="reports.php?export=payments<?php echo $date_from !== '' ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to !== '' ? '&date_to=' . urlencode($date_to) : ''; ?>">Download Report</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Member</th>
                        <th>Invoice #</th>
                        <th>Date / Time</th>
                        <th>Processor Code</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Fee</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($paymentsReport)): ?>
                    <?php foreach ($paymentsReport as $row): ?>
                        <tr>
                            <td><?php echo h($row["PMT_ID"]); ?></td>
                            <td><?php echo h(trim(($row["MEMB_FNAME"] ?? "") . " " . ($row["MEMB_LNAME"] ?? ""))); ?></td>
                            <td><?php echo h($row["INV_NUMB"] ?? ""); ?></td>
                            <td><?php echo !empty($row["PMT_DATE_TIME"]) ? h(date("M j, Y g:i A", strtotime($row["PMT_DATE_TIME"]))) : "—"; ?></td>
                            <td><?php echo h($row["PROC_CODE"] ?? ""); ?></td>
                            <td><?php echo !empty($row["SUCCESS"]) ? "Success" : "Failed"; ?></td>
                            <td>$<?php echo number_format((float) ($row["TOTAL"] ?? 0), 2); ?></td>
                            <td>$<?php echo number_format((float) ($row["FEE"] ?? 0), 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty-cell">No payment records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="report-section" id="renewals-report">
        <div class="report-section-header">
            <h2>Renewals Due</h2>
            <div class="report-actions">
                <a href="#" class="btn-secondary print-report-link" data-print-target="renewals-report">Print Report</a>
                <a class="btn-secondary" href="reports.php?export=renewals<?php echo $date_from !== '' ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to !== '' ? '&date_to=' . urlencode($date_to) : ''; ?>">Download Report</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Member ID</th>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Package</th>
                        <th>Expiry Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($renewalsReport)): ?>
                    <?php foreach ($renewalsReport as $row): ?>
                        <tr>
                            <td><?php echo h($row["MEMB_ID"]); ?></td>
                            <td><?php echo h(trim(($row["MEMB_FNAME"] ?? "") . " " . ($row["MEMB_LNAME"] ?? ""))); ?></td>
                            <td><?php echo h($row["MEMB_EMAIL"] ?? ""); ?></td>
                            <td><?php echo h($row["MEMB_PHONE"] ?? ""); ?></td>
                            <td><?php echo h($row["PACKAGE_NAME"] ?? ""); ?></td>
                            <td><?php echo !empty($row["END_DATE"]) ? h(date("M j, Y", strtotime($row["END_DATE"]))) : "—"; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="empty-cell">No renewals found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="report-section" id="qrrules-report">
        <div class="report-section-header">
            <h2>QR Code / Access Rules Report</h2>
            <div class="report-actions">
                <a href="#" class="btn-secondary print-report-link" data-print-target="qrrules-report">Print Report</a>
                <a class="btn-secondary" href="reports.php?export=qrrules">Download Report</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>QR ID</th>
                        <th>Member ID</th>
                        <th>Member</th>
                        <th>Rule ID</th>
                        <th>Minimum Age</th>
                        <th>Open Hour</th>
                        <th>Close Hour</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($qrRulesReport)): ?>
                    <?php foreach ($qrRulesReport as $row): ?>
                        <tr>
                            <td><?php echo h($row["QR_ID"]); ?></td>
                            <td><?php echo h($row["MEMB_ID"]); ?></td>
                            <td><?php echo h(trim(($row["MEMB_FNAME"] ?? "") . " " . ($row["MEMB_LNAME"] ?? ""))); ?></td>
                            <td><?php echo h($row["RULE_ID"] ?? ""); ?></td>
                            <td><?php echo h($row["MIN_AGE"] ?? ""); ?></td>
                            <td><?php echo !empty($row["OPEN_HOUR"]) ? h(date("g:i A", strtotime($row["OPEN_HOUR"]))) : "—"; ?></td>
                            <td><?php echo !empty($row["CLOSE_HOUR"]) ? h(date("g:i A", strtotime($row["CLOSE_HOUR"]))) : "—"; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="empty-cell">No QR / access rule records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="report-section" id="users-report">
        <div class="report-section-header">
            <h2>Users / Permissions Report</h2>
            <div class="report-actions">
                <a href="#" class="btn-secondary print-report-link" data-print-target="users-report">Print Report</a>
                <a class="btn-secondary" href="reports.php?export=users">Download Report</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Permission</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($usersReport)): ?>
                    <?php foreach ($usersReport as $row): ?>
                        <tr>
                            <td><?php echo h($row["USER_ID"]); ?></td>
                            <td><?php echo h(trim(($row["USER_FNAME"] ?? "") . " " . ($row["USER_LNAME"] ?? ""))); ?></td>
                            <td><?php echo h($row["USER_NAME"] ?? ""); ?></td>
                            <td><?php echo h($row["USER_EMAIL"] ?? ""); ?></td>
                            <td><?php echo h($row["PERM_NAME"] ?? ""); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty-cell">No user records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<footer class="site-footer">
    <a href="about.php" id="aboutfooter">About WSC</a>
    <p><small>&copy; Windswept Student Consulting 2026</small></p>
</footer>

<?php
if (file_exists(__DIR__ . "/logout_modal.php")) {
    include __DIR__ . "/logout_modal.php";
}
?>

<script>
lucide.createIcons();

(function () {
    const images = document.querySelectorAll('.logo-images img');
    const wrapper = document.querySelector('.logo-images');
    const fallback = document.querySelector('.logo-fallback');

    if (!images.length || !wrapper || !fallback) {
        return;
    }

    let failedCount = 0;

    images.forEach(function (img) {
        function markFailed() {
            failedCount++;
            if (failedCount >= images.length) {
                wrapper.style.display = 'none';
                fallback.style.display = 'flex';
            }
        }

        if (img.complete && img.naturalWidth === 0) {
            markFailed();
        } else {
            img.addEventListener('error', markFailed);
        }
    });
})();

(function () {
    const printLinks = document.querySelectorAll('.print-report-link');

    function openPrintWindow(title, tableHtml) {
        const printWindow = window.open('', '_blank', 'width=1000,height=700');

        if (!printWindow) {
            alert('Please allow pop-ups for this site to print reports.');
            return;
        }

        const now = new Date();
        const printedAt = now.toLocaleString();

        const html = `
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>${title}</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 24px;
                        color: #111;
                    }

                    h1 {
                        margin: 0 0 8px;
                        font-size: 28px;
                    }

                    .meta {
                        margin: 0 0 20px;
                        color: #555;
                        font-size: 14px;
                    }

                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }

                    th, td {
                        border: 1px solid #ccc;
                        padding: 10px;
                        text-align: left;
                        vertical-align: top;
                    }

                    th {
                        background: #f3f3f3;
                    }

                    @media print {
                        body {
                            margin: 0;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="print-shell">
                    <h1>${title}</h1>
                    <p class="meta">Printed: ${printedAt}</p>
                    ${tableHtml}
                </div>
            </body>
            </html>
        `;

        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();

        printWindow.onload = function () {
            printWindow.focus();
            printWindow.print();
        };
    }

    printLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();

            const targetId = this.getAttribute('data-print-target');
            const section = document.getElementById(targetId);

            if (!section) {
                return;
            }

            const titleElement = section.querySelector('h2');
            const tableElement = section.querySelector('table');

            if (!titleElement || !tableElement) {
                return;
            }

            openPrintWindow(titleElement.textContent.trim(), tableElement.outerHTML);
        });
    });
})();
</script>
</body>
</html>
