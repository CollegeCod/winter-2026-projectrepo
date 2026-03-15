<?php
require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/db_connection.php";

start_secure_session();
$pdo = create_database_connection();

// Optional permission check
// if (!isset($_SESSION["USER_ID"]) || (int)($_SESSION["PERM_ID"] ?? 0) !== 1) {
//     header("Location: Dashboard.php");
//     exit();
// }

$access_rules = [];
$page_error = $_GET["error"] ?? "";
$page_success = $_GET["success"] ?? "";

try {
    $stmt = $pdo->query("
        SELECT
            RULE_ID,
            RULE_NAME,
            MIN_AGE,
            OPEN_HOUR,
            CLOSE_HOUR
        FROM access_rules
        ORDER BY RULE_ID ASC
    ");
    $access_rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $access_rules = [];
}

function settings_message($page_error, $page_success)
{
    if ($page_success === "user_created") {
        return ["success", "User account created successfully."];
    }

    if ($page_success === "rule_created") {
        return ["success", "Access rule created successfully."];
    }

    if ($page_success === "rule_deleted") {
        return ["success", "Access rule deleted successfully."];
    }

    $messages = [
        "missing_fields_user" => "Please fill in all user account fields.",
        "bad_email" => "Please enter a valid email address.",
        "password_mismatch" => "The password fields do not match.",
        "password_short" => "Password must be at least 8 characters long.",
        "user_exists" => "That email already exists.",
        "user_create_failed" => "Unable to create the user account.",
        "missing_fields_rule" => "Please fill in all access rule fields.",
        "bad_min_age" => "Minimum age must be between 0 and 120.",
        "bad_time_range" => "Close hour must be later than open hour.",
        "rule_create_failed" => "Unable to create the access rule.",
        "rule_delete_failed" => "Unable to delete the access rule.",
        "db_error" => "A database error occurred.",
    ];

    if (isset($messages[$page_error])) {
        return ["error", $messages[$page_error]];
    }

    return [null, ""];
}

[$message_type, $message_text] = settings_message($page_error, $page_success);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>

    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/settings.css">

    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

    <!-- ===== SIDEBAR NAV BAR ===== -->
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
                <i data-lucide="layout-dashboard" class="nav-icon"></i>
                <span>Dashboard</span>
            </a>

            <a href="customers.php" class="nav-item">
                <i data-lucide="users" class="nav-icon"></i>
                <span>Customers</span>
            </a>

            <a href="Invoice.php" class="nav-item">
                <i data-lucide="credit-card" class="nav-icon"></i>
                <span>Payments &amp; Invoices</span>
            </a>

            <a href="reports.php" class="nav-item">
                <i data-lucide="bar-chart-2" class="nav-icon"></i>
                <span>Reports</span>
            </a>

            <a href="settings.php" class="nav-item active">
                <i data-lucide="settings" class="nav-icon"></i>
                <span>Settings</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <button type="button" data-logout class="nav-item logout">
                <i data-lucide="log-out" class="nav-icon"></i>
                <span>Logout</span>
            </button>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">
        <div class="page-header">
            <h1>Settings</h1>
            <p class="page-subtitle">Manage your gym management system settings</p>
        </div>

        <?php if ($message_type !== null): ?>
            <div class="page-message <?php echo $message_type === "success"
                ? "message-success"
                : "message-error"; ?>">
                <?php echo htmlspecialchars($message_text); ?>
            </div>
        <?php endif; ?>

        <div class="settings-layout">

            <!-- LEFT COLUMN -->
            <div class="settings-column">
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-icon icon-blue">
                            <i data-lucide="user-plus"></i>
                        </div>
                        <h2>Account Creation</h2>
                    </div>

                    <div class="settings-card-body">
                        <form action="process_create_user.php" method="post" class="settings-form">
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?php echo htmlspecialchars(
                                    get_csrf_token(),
                                ); ?>"
                            >

                            <div class="form-group">
                                <label for="USER_FNAME">First Name</label>
                                <input
                                    type="text"
                                    id="USER_FNAME"
                                    name="USER_FNAME"
                                    placeholder="Enter first name"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="USER_LNAME">Last Name</label>
                                <input
                                    type="text"
                                    id="USER_LNAME"
                                    name="USER_LNAME"
                                    placeholder="Enter last name"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="USER_EMAIL">Email</label>
                                <input
                                    type="email"
                                    id="USER_EMAIL"
                                    name="USER_EMAIL"
                                    placeholder="Enter email"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="PASSWORD">Password</label>
                                <input
                                    type="password"
                                    id="PASSWORD"
                                    name="PASSWORD"
                                    placeholder="Enter password"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="CONFIRM_PASSWORD">Confirm Password</label>
                                <input
                                    type="password"
                                    id="CONFIRM_PASSWORD"
                                    name="CONFIRM_PASSWORD"
                                    placeholder="Re-enter password"
                                    required
                                >
                            </div>

                            <button type="submit" class="settings-btn-primary">
                                Create Account
                            </button>
                        </form>
                    </div>
                </section>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="settings-column">
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-icon icon-indigo">
                            <i data-lucide="credit-card"></i>
                        </div>
                        <h2>Payment Integration</h2>
                    </div>

                    <div class="settings-card-body">
                        <div class="stub-box">
                            <p>This section is reserved for future payment integration settings.</p>
                        </div>
                    </div>
                </section>

                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-icon icon-blue">
                            <i data-lucide="settings"></i>
                        </div>
                        <h2>UNIFI Integration</h2>
                    </div>

                    <div class="settings-card-body">
                        <div class="stub-box">
                            <p>This section is reserved for future UNIFI integration settings.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- FULL WIDTH -->
            <section class="settings-card settings-card-full">
                <div class="settings-card-header rules-header">
                    <div class="rules-title-wrap">
                        <div class="settings-icon icon-teal">
                            <i data-lucide="shield"></i>
                        </div>
                        <h2>Access Rules</h2>
                    </div>
                </div>

                <div class="settings-card-body">
                    <form action="process_create_rule.php" method="post" class="settings-form rule-create-form">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars(
                                get_csrf_token(),
                            ); ?>"
                        >

                        <div class="rule-form-grid">
                            <div class="form-group">
                                <label for="RULE_NAME">Rule Name</label>
                                <input
                                    type="text"
                                    id="RULE_NAME"
                                    name="RULE_NAME"
                                    placeholder="Enter rule name"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="MIN_AGE">Minimum Age</label>
                                <input
                                    type="number"
                                    id="MIN_AGE"
                                    name="MIN_AGE"
                                    min="0"
                                    max="120"
                                    placeholder="e.g. 18"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="OPEN_HOUR">Open Hour</label>
                                <input
                                    type="time"
                                    id="OPEN_HOUR"
                                    name="OPEN_HOUR"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="CLOSE_HOUR">Close Hour</label>
                                <input
                                    type="time"
                                    id="CLOSE_HOUR"
                                    name="CLOSE_HOUR"
                                    required
                                >
                            </div>
                        </div>

                        <div class="rule-create-actions">
                            <button type="submit" class="settings-btn-primary">
                                Save Rule
                            </button>
                        </div>
                    </form>

                    <div class="rules-list">
                        <?php if (!empty($access_rules)): ?>
                            <?php foreach ($access_rules as $rule): ?>
                                <div class="rule-item">
                                    <div class="rule-main">
                                        <h3><?php echo htmlspecialchars(
                                            $rule["RULE_NAME"] ??
                                                "Unnamed Rule",
                                        ); ?></h3>

                                        <p class="rule-meta">
                                            <span>Min Age: <?php echo htmlspecialchars(
                                                $rule["MIN_AGE"] ?? "N/A",
                                            ); ?></span>
                                            <span>•</span>
                                            <span>
                                                <?php
                                                $open =
                                                    $rule["OPEN_HOUR"] ?? null;
                                                $close =
                                                    $rule["CLOSE_HOUR"] ?? null;

                                                if ($open && $close) {
                                                    echo htmlspecialchars(
                                                        substr($open, 0, 5),
                                                    ) .
                                                        " - " .
                                                        htmlspecialchars(
                                                            substr(
                                                                $close,
                                                                0,
                                                                5,
                                                            ),
                                                        );
                                                } else {
                                                    echo "No hours set";
                                                }
                                                ?>
                                            </span>
                                        </p>
                                    </div>

                                    <div class="rule-actions">
                                        <span class="rule-status">Active</span>

                                        <form
                                            action="process_delete_rule.php"
                                            method="post"
                                            class="delete-rule-form"
                                            onsubmit="return confirm('Delete this access rule?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo htmlspecialchars(
                                                    get_csrf_token(),
                                                ); ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="RULE_ID"
                                                value="<?php echo htmlspecialchars(
                                                    $rule["RULE_ID"],
                                                ); ?>"
                                            >
                                            <button type="submit" class="rule-btn danger-btn">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                No access rules found yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

        </div>
    </main>

<script>
    lucide.createIcons();
</script>

<?php include "logout_modal.php"; ?>
</body>
</html>
