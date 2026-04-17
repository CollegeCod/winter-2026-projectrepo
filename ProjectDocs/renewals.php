<?php
// renewal.php
// Shows members with upcoming or past expiry dates and sends renewal reminder emails

require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/db_connection.php";

start_secure_session();
$pdo = create_database_connection();

// ── SMTP Config — fill in when client email is available ──────────────────
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'your-email@gmail.com');   // replace with client Gmail
define('SMTP_PASS',     'your-app-password');       // replace with Gmail app password
define('SMTP_FROM',     'your-email@gmail.com');   // replace with client Gmail
define('SMTP_FROM_NAME','Belle\'s Training Solutions');

// ── Filter ────────────────────────────────────────────────────────────────
$filter   = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$today    = date('Y-m-d');

switch ($filter) {
    case 'expired':
        $where = "END_DATE < :today";
        $params = [':today' => $today];
        $filter_label = 'Expired';
        break;
    case '5':
        $where = "END_DATE <= DATE_ADD(:today2, INTERVAL 5 DAY)";
        $params = [':today2' => $today];
        $filter_label = 'Expiring in 5 Days or Less';
        break;
    case '10':
        $where = "END_DATE <= DATE_ADD(:today2, INTERVAL 10 DAY)";
        $params = [':today2' => $today];
        $filter_label = 'Expiring in 10 Days or Less';
        break;
    case '15':
        $where = "END_DATE <= DATE_ADD(:today2, INTERVAL 15 DAY)";
        $params = [':today2' => $today];
        $filter_label = 'Expiring in 15 Days or Less';
        break;
    case 'all':
    default:
        $filter = 'all';
        $where = "1=1";
        $params = [];
        $filter_label = 'All Members';
        break;
}

$stmt = $pdo->prepare("
    SELECT m.MEMB_ID, m.MEMB_FNAME, m.MEMB_LNAME, m.MEMB_EMAIL,
           m.MEMB_PHONE, m.END_DATE, p.PACKAGE_NAME,
           DATEDIFF(m.END_DATE, :today_diff) AS days_remaining
    FROM member m
    LEFT JOIN packages p    ON m.PACKAGE_ID      = p.PACKAGE_ID
    LEFT JOIN memb_status s ON m.MEMB_STATUS_ID  = s.MEMB_STATUS_ID
    WHERE s.MEMB_STATUS_NAME = 'Active'
    AND $where
    ORDER BY m.END_DATE ASC, days_remaining ASC
");
$params[':today_diff'] = $today;
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Handle single send ────────────────────────────────────────────────────
$send_msg   = '';
$send_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_single'])) {
    $memb_id = (int)$_POST['memb_id'];
    $result  = sendReminderById($pdo, $memb_id);
    if ($result === true) {
        $send_msg = 'Reminder sent successfully.';
    } else {
        $send_error = 'Failed to send reminder: ' . $result;
    }
}

// ── Handle send all ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_all'])) {
    $ids     = array_map('intval', explode(',', $_POST['all_ids'] ?? ''));
    $success = 0;
    $failed  = 0;
    foreach ($ids as $id) {
        $result = sendReminderById($pdo, $id);
        $result === true ? $success++ : $failed++;
    }
    $send_msg = "Sent $success reminder(s).";
    if ($failed > 0) $send_error = "$failed reminder(s) failed to send.";
}

// ── Send reminder function ────────────────────────────────────────────────
function sendReminderById($pdo, $memb_id) {
    $stmt = $pdo->prepare("
        SELECT m.MEMB_FNAME, m.MEMB_LNAME, m.MEMB_EMAIL, m.END_DATE, p.PACKAGE_NAME
        FROM member m
        LEFT JOIN packages p ON m.PACKAGE_ID = p.PACKAGE_ID
        WHERE m.MEMB_ID = :id
    ");
    $stmt->bindValue(':id', $memb_id, PDO::PARAM_INT);
    $stmt->execute();
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member || empty($member['MEMB_EMAIL'])) {
        return 'Member not found or has no email.';
    }

    $name       = $member['MEMB_FNAME'] . ' ' . $member['MEMB_LNAME'];
    $email      = $member['MEMB_EMAIL'];
    $end_date   = !empty($member['END_DATE']) ? date('F j, Y', strtotime($member['END_DATE'])) : 'soon';
    $package    = $member['PACKAGE_NAME'] ?? 'your current package';
    $days_left  = $member['END_DATE'] ? (int)((strtotime($member['END_DATE']) - time()) / 86400) : 0;

    if ($days_left < 0) {
        $urgency = "Your membership has expired.";
    } elseif ($days_left === 0) {
        $urgency = "Your membership expires today.";
    } else {
        $urgency = "Your membership expires in $days_left day(s) on $end_date.";
    }

    $subject = "Membership Renewal Reminder — Belle's Training Solutions";
    $body    = "Hi $name,\r\n\r\n"
             . "This is a friendly reminder from Belle's Training Solutions.\r\n\r\n"
             . "$urgency\r\n\r\n"
             . "Package: $package\r\n"
             . "Expiry Date: $end_date\r\n\r\n"
             . "To keep your membership active, please contact us to arrange your renewal.\r\n\r\n"
             . "Thank you for being a valued member.\r\n\r\n"
             . "— Belle's Training Solutions\r\n"
             . "   Windswept Student Consulting";

    return sendSmtpEmail($email, $name, $subject, $body);
}

// ── Basic SMTP send via socket ────────────────────────────────────────────
// ── SMTP helper functions (declared once at file scope) ───────────────────
function smtp_read($socket) {
    $data = '';
    while ($str = fgets($socket, 515)) {
        $data .= $str;
        if (substr($str, 3, 1) === ' ') break;
    }
    return $data;
}

function smtp_send($socket, $cmd) {
    fputs($socket, $cmd . "\r\n");
    return smtp_read($socket);
}

function sendSmtpEmail($to_email, $to_name, $subject, $body) {
    $host    = SMTP_HOST;
    $port    = SMTP_PORT;
    $user    = SMTP_USER;
    $pass    = SMTP_PASS;
    $from    = SMTP_FROM;
    $from_nm = SMTP_FROM_NAME;

    $socket = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$socket) return "Could not connect to SMTP server: $errstr ($errno)";

    smtp_read($socket); // greeting
    smtp_send($socket, "EHLO localhost");
    smtp_send($socket, "STARTTLS");

    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    smtp_send($socket, "EHLO localhost");
    smtp_send($socket, "AUTH LOGIN");
    smtp_send($socket, base64_encode($user));
    $auth = smtp_send($socket, base64_encode($pass));

    if (strpos($auth, '235') === false) {
        fclose($socket);
        return 'SMTP authentication failed. Check SMTP credentials.';
    }

    smtp_send($socket, "MAIL FROM:<$from>");
    smtp_send($socket, "RCPT TO:<$to_email>");
    smtp_send($socket, "DATA");

    $date    = date('r');
    $msg_id  = '<' . time() . rand(1000,9999) . '@bellestraining.com>';
    $headers = "Date: $date\r\n"
             . "From: $from_nm <$from>\r\n"
             . "To: $to_name <$to_email>\r\n"
             . "Subject: $subject\r\n"
             . "Message-ID: $msg_id\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n"
             . "Content-Transfer-Encoding: 7bit\r\n";

    $result = smtp_send($socket, $headers . "\r\n" . $body . "\r\n.");

    smtp_send($socket, "QUIT");
    fclose($socket);

    if (strpos($result, '250') !== false) return true;
    return 'SMTP error sending message: ' . trim($result);
}

$all_ids = implode(',', array_column($members, 'MEMB_ID'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renewals — Belle's Training Solutions</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/renewals.css">
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
        <a href="invoices.php" class="nav-item">
            <i data-lucide="credit-card" class="nav-icon"></i><span>Payments</span>
        </a>
        <a href="renewals.php" class="nav-item active">
            <i data-lucide="refresh-cw" class="nav-icon"></i><span>Renewals</span>
        </a>
        <a href="reports.php" class="nav-item">
            <i data-lucide="file-bar-chart" class="nav-icon"></i><span>Reports</span>
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
            <h1>Renewals</h1>
            <p class="page-subtitle">Track upcoming expirations and send renewal reminders.</p>
        </div>
        <div class="header-actions">
            <a href="Invoice_hub.php" class="btn-back">
                <i data-lucide="arrow-left"></i> Back
            </a>
            <?php if (count($members) > 0): ?>
                <form method="POST" action="renewals.php?filter=<?php echo urlencode($filter); ?>">
                    <input type="hidden" name="send_all" value="1">
                    <input type="hidden" name="all_ids" value="<?php echo htmlspecialchars($all_ids); ?>">
                    <button type="submit" class="btn-send-all">
                        <i data-lucide="send"></i> Send All Reminders
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($send_msg !== ''): ?>
        <div class="alert alert-success">
            <i data-lucide="check-circle"></i>
            <?php echo htmlspecialchars($send_msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($send_error !== ''): ?>
        <div class="alert alert-error">
            <i data-lucide="alert-circle"></i>
            <?php echo htmlspecialchars($send_error); ?>
        </div>
    <?php endif; ?>

    <!-- ── Filter tabs ──────────────────────────────────────────────────── -->
    <div class="filter-tabs">
        <a href="renewals.php?filter=all"
           class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
            <i data-lucide="list"></i> All
        </a>
        <a href="renewals.php?filter=15"
           class="filter-tab <?php echo $filter === '15' ? 'active' : ''; ?>">
            <i data-lucide="clock"></i> 15 Days or Less
        </a>
        <a href="renewals.php?filter=10"
           class="filter-tab <?php echo $filter === '10' ? 'active' : ''; ?>">
            <i data-lucide="clock"></i> 10 Days or Less
        </a>
        <a href="renewals.php?filter=5"
           class="filter-tab <?php echo $filter === '5' ? 'active' : ''; ?>">
            <i data-lucide="clock"></i> 5 Days or Less
        </a>
        <a href="renewals.php?filter=expired"
           class="filter-tab filter-tab-danger <?php echo $filter === 'expired' ? 'active' : ''; ?>">
            <i data-lucide="x-circle"></i> Expired
        </a>
        <?php if ($filter !== 'all'): ?>
            <a href="renewals.php" class="filter-tab filter-tab-reset">
                <i data-lucide="rotate-ccw"></i> Reset
            </a>
        <?php endif; ?>
    </div>

    <!-- ── Results ─────────────────────────────────────────────────────── -->
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title"><?php echo $filter_label; ?></span>
            <span class="table-card-count"><?php echo count($members); ?> member(s)</span>
        </div>
        <div class="table-scroll">
            <?php if (count($members) > 0): ?>
                <table class="renewal-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Package</th>
                            <th>Expiry Date</th>
                            <th>Days</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td class="col-name">
                                    <?php echo htmlspecialchars($m['MEMB_FNAME'] . ' ' . $m['MEMB_LNAME']); ?>
                                </td>
                                <td>
                                    <a href="https://mail.google.com/mail/?view=cm&to=<?php echo urlencode($m['MEMB_EMAIL']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($m['MEMB_EMAIL']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($m['MEMB_PHONE'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($m['PACKAGE_NAME'] ?? '—'); ?></td>
                                <td class="col-date">
                                    <?php echo !empty($m['END_DATE']) ? date('M j, Y', strtotime($m['END_DATE'])) : '—'; ?>
                                </td>
                                <td>
                                    <?php
                                    $days = (int)$m['days_remaining'];
                                    if ($days < 0): ?>
                                        <span class="days-badge days-expired">Expired <?php echo abs($days); ?>d ago</span>
                                    <?php elseif ($days === 0): ?>
                                        <span class="days-badge days-today">Today</span>
                                    <?php else: ?>
                                        <span class="days-badge days-soon"><?php echo $days; ?> day(s)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="renewals.php?filter=<?php echo urlencode($filter); ?>">
                                        <input type="hidden" name="send_single" value="1">
                                        <input type="hidden" name="memb_id" value="<?php echo (int)$m['MEMB_ID']; ?>">
                                        <button type="submit" class="btn-send-single">
                                            <i data-lucide="send"></i> Send
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i data-lucide="check-circle" class="empty-icon"></i>
                    <p>No members in this category.</p>
                </div>
            <?php endif; ?>
        </div>
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