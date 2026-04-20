<?php
// customers.php
// Displays a paginated, searchable table of customer data

// ── Database connection ────────────────────────────────────────────────────
require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/db_connection.php";

start_secure_session();
$pdo = create_database_connection();

// ── Pagination and search settings ────────────────────────────────────────
$rows_per_page = 10;
$page =
    isset($_GET["page"]) && is_numeric($_GET["page"]) && (int) $_GET["page"] > 0
        ? (int) $_GET["page"]
        : 1;
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$offset = ($page - 1) * $rows_per_page;

// ── Table name ────────────────────────────────────────────────────────────
$table = "member";

// ── Search / data queries ────────────────────────────────────────────────
if ($search !== "") {
    $like = "%" . $search . "%";

    $count_sql = "SELECT COUNT(*) FROM $table m
        LEFT JOIN packages p ON m.PACKAGE_ID = p.PACKAGE_ID
        WHERE
        m.MEMB_FNAME LIKE :like OR
        m.MEMB_LNAME LIKE :like OR
        m.MEMB_ID LIKE :like OR
        p.PACKAGE_NAME LIKE :like OR
        m.MEMB_PHONE LIKE :like OR
        m.MEMB_EMAIL LIKE :like OR
        m.START_DATE LIKE :like OR
        m.END_DATE LIKE :like OR
        m.NOTES LIKE :like";

    $data_sql = "SELECT
        m.MEMB_ID,
        p.PACKAGE_NAME,
        m.MEMB_FNAME,
        m.MEMB_LNAME,
        m.MEMB_PHONE,
        m.MEMB_EMAIL,
        m.START_DATE,
        m.END_DATE,
        m.NOTES
        FROM $table m
        LEFT JOIN packages p ON m.PACKAGE_ID = p.PACKAGE_ID
        WHERE
        m.MEMB_FNAME LIKE :like OR
        m.MEMB_LNAME LIKE :like OR
        m.MEMB_ID LIKE :like OR
        p.PACKAGE_NAME LIKE :like OR
        m.MEMB_PHONE LIKE :like OR
        m.MEMB_EMAIL LIKE :like OR
        m.START_DATE LIKE :like OR
        m.END_DATE LIKE :like OR
        m.NOTES LIKE :like
        ORDER BY m.MEMB_LNAME ASC, m.MEMB_FNAME ASC
        LIMIT :limit OFFSET :offset";

    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->bindValue(":like", $like);
    $count_stmt->execute();

    $data_stmt = $pdo->prepare($data_sql);
    $data_stmt->bindValue(":like", $like);
    $data_stmt->bindValue(":limit", $rows_per_page, PDO::PARAM_INT);
    $data_stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $data_stmt->execute();
} else {
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM $table");

    $data_stmt = $pdo->prepare("SELECT
        m.MEMB_ID,
        p.PACKAGE_NAME,
        m.MEMB_FNAME,
        m.MEMB_LNAME,
        m.MEMB_PHONE,
        m.MEMB_EMAIL,
        m.START_DATE,
        m.END_DATE,
        m.NOTES
        FROM $table m
        LEFT JOIN packages p ON m.PACKAGE_ID = p.PACKAGE_ID
        ORDER BY m.MEMB_LNAME ASC, m.MEMB_FNAME ASC
        LIMIT :limit OFFSET :offset");

    $data_stmt->bindValue(":limit", $rows_per_page, PDO::PARAM_INT);
    $data_stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $data_stmt->execute();
}

$total_rows = (int) $count_stmt->fetchColumn();
$total_pages = (int) ceil($total_rows / $rows_per_page);
$customers = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management</title>

    <!-- Update these paths to match your project -->
    <link rel="stylesheet" href="css/customers.css">
	<link rel="stylesheet" href="css/dashboard.css">
	

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
        <a href="dashboard.php" class="nav-item">
            <i data-lucide="layout-dashboard" class="nav-icon"></i>
            <span>Dashboard</span>
        </a>
        <a href="customers.php" class="nav-item active">
            <i data-lucide="users" class="nav-icon"></i>
            <span>Customers</span>
        </a>
        <a href="invoices.php" class="nav-item">
            <i data-lucide="credit-card" class="nav-icon"></i>
            <span>Payments</span>
        </a>
        <a href="renewal.php" class="nav-item">
            <i data-lucide="refresh-cw" class="nav-icon"></i>
            <span>Renewals</span>
        </a>
        <a href="invoice.php" class="nav-item">
            <i data-lucide="file-text" class="nav-icon"></i>
            <span>Invoice</span>
        </a>
        <a href="sys_settings.php" class="nav-item">
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

    <div class="page-header-row">
        <div>
            <h1>Customer Management</h1>
            <p class="page-subtitle">Manage customer information and memberships</p>
        </div>

    </div>

    <div class="search-bar-row">
        <a href="new_customer.php" class="btn-add-customer">
            <i data-lucide="plus"></i> Add Customer
        </a>

        <form method="GET" action="customers.php" id="search_form">
            <div class="search-wrap">
                <i data-lucide="search" class="search-icon"></i>
                <input
                    type="text"
                    name="search"
                    id="search_input"
                    class="search-input"
                    placeholder="Search customers..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    autocomplete="off"
                >
                <?php if ($search !== ""): ?>
                    <a href="customers.php" class="search-clear" title="Clear search">
                        <i data-lucide="x"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <p class="results-summary">
        <?php if ($search !== ""): ?>
            <?php echo $total_rows; ?> result(s) for
            "<strong><?php echo htmlspecialchars($search); ?></strong>"
            &mdash; Page <?php echo $page; ?> of <?php echo max(
     $total_pages,
     1,
 ); ?>
        <?php else: ?>
            <?php echo $total_rows; ?> total customers
            &mdash; Page <?php echo $page; ?> of <?php echo max(
     $total_pages,
     1,
 ); ?>
        <?php endif; ?>
    </p>

    <div class="table-card">
        <div class="table-scroll">
        <?php if (count($customers) > 0): ?>
            <table class="customer-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Package</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr data-edit-url="edit_customer.php?MEMB_ID=<?php echo urlencode($customer['MEMB_ID']); ?>" class="clickable-row">
                            <td class="col-name">
                                <?php echo htmlspecialchars(
                                    $customer["MEMB_FNAME"] .
                                        " " .
                                        $customer["MEMB_LNAME"],
                                ); ?>
                            </td>

                            <td class="col-contact">
                                <a href="https://mail.google.com/mail/?view=cm&to=<?php echo urlencode($customer['MEMB_EMAIL']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($customer["MEMB_EMAIL"]); ?>
                                </a>
                            </td>

                            <td class="col-contact">
                                <?php echo htmlspecialchars($customer["MEMB_PHONE"]); ?>
                            </td>

                            <td>
                                <span class="badge badge-package">
                                    <?php echo htmlspecialchars(
                                        $customer["PACKAGE_NAME"] ?? '—',
                                    ); ?>
                                </span>
                            </td>

                            <td class="col-date">
                                <?php
                                $start_date = !empty($customer["START_DATE"])
                                    ? date(
                                        "M j, Y",
                                        strtotime($customer["START_DATE"]),
                                    )
                                    : "—";
                                echo htmlspecialchars($start_date);
                                ?>
                            </td>

                            <td class="col-date">
                                <?php
                                $end_date = !empty($customer["END_DATE"])
                                    ? date(
                                        "M j, Y",
                                        strtotime($customer["END_DATE"]),
                                    )
                                    : "—";
                                echo htmlspecialchars($end_date);
                                ?>
                            </td>

                            <td class="col-notes">
                                <?php echo htmlspecialchars(
                                    $customer["NOTES"] ?? "",
                                ); ?>
                            </td>

                            <td class="col-actions">
                                <div class="actions-menu">
                                    <button class="actions-toggle" onclick="toggleMenu(this)" title="Actions">
                                        <i data-lucide="more-vertical"></i>
                                    </button>
                                    <div class="actions-dropdown">
                                        <a class="action-item" href="edit_customer.php?MEMB_ID=<?php echo urlencode($customer['MEMB_ID']); ?>">
                                            <i data-lucide="pencil"></i> Edit
                                        </a>
                                        <button class="action-item action-delete" onclick="confirmDelete(<?php echo (int)$customer['MEMB_ID']; ?>, '<?php echo htmlspecialchars($customer['MEMB_FNAME'] . ' ' . $customer['MEMB_LNAME'], ENT_QUOTES); ?>')">
                                            <i data-lucide="trash-2"></i> Delete
                                        </button>
                                        <!-- Add back once UniFi Access API is connected -->
                                        <button class="action-item action-disabled" disabled title="Available once UniFi Access is connected">
                                            <i data-lucide="qr-code"></i> Resend QR
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a class="page-btn" href="customers.php?page=<?php echo $page -
                            1; ?>&search=<?php echo urlencode($search); ?>">
                            <i data-lucide="chevron-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="page-btn disabled">
                            <i data-lucide="chevron-left"></i>
                        </span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a
                            class="page-btn <?php echo $i === $page
                                ? "active"
                                : ""; ?>"
                            href="customers.php?page=<?php echo $i; ?>&search=<?php echo urlencode(
    $search,
); ?>"
                        >
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a class="page-btn" href="customers.php?page=<?php echo $page +
                            1; ?>&search=<?php echo urlencode($search); ?>">
                            <i data-lucide="chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="page-btn disabled">
                            <i data-lucide="chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <i data-lucide="users" class="empty-icon"></i>
                <p>No customers found<?php echo $search !== ""
                    ? " matching your search."
                    : "."; ?></p>
                <?php if ($search !== ""): ?>
                    <a href="customers.php" class="btn-secondary">Clear Search</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </div><!-- /.table-scroll -->
    </div>

</main>

<footer class="site-footer">
    <p><small>&copy; Windswept Student Consulting 2026</small></p>
</footer>


<?php include 'logout_modal.php'; ?>

<script>
lucide.createIcons();

// ── Live search on every key press ────────────────────────────────────────
var search_input = document.getElementById('search_input');
var search_timer = null;

search_input.addEventListener('input', function () {
    clearTimeout(search_timer);
    search_timer = setTimeout(function () {
        document.getElementById('search_form').submit();
    }, 3000);
});

// ── Double-click row to edit customer ────────────────────────────────────
document.querySelectorAll('tr.clickable-row').forEach(function (row) {
    row.addEventListener('dblclick', function () {
        window.location.href = this.dataset.editUrl;
    });
});

// ── Actions hamburger menu ────────────────────────────────────────────────
// Portal the dropdown to <body> so overflow clipping on table/scroll containers
// can never cut it off

var _active_dropdown = null;

function toggleMenu(btn) {
    // If clicking the same button, close and bail
    if (_active_dropdown && _active_dropdown._btn === btn) {
        closeActiveDropdown();
        return;
    }

    closeActiveDropdown();

    var template = btn.nextElementSibling; // the .actions-dropdown in the row
    var clone     = template.cloneNode(true);
    clone.classList.add('open');
    clone.style.position = 'fixed';
    clone.style.zIndex   = '9999';
    clone.style.display  = 'block';

    // Position relative to button
    var rect = btn.getBoundingClientRect();
    document.body.appendChild(clone);

    var ch = clone.offsetHeight;
    var cw = clone.offsetWidth;

    // Flip up if not enough room below
    if (rect.bottom + ch > window.innerHeight) {
        clone.style.top  = (rect.top - ch) + 'px';
    } else {
        clone.style.top  = (rect.bottom + 4) + 'px';
    }
    clone.style.left = (rect.right - cw) + 'px';

    _active_dropdown = clone;
    _active_dropdown._btn = btn;
}

function closeActiveDropdown() {
    if (_active_dropdown) {
        _active_dropdown.remove();
        _active_dropdown = null;
    }
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('.actions-toggle') && !e.target.closest('.actions-dropdown')) {
        closeActiveDropdown();
    }
});

// ── Delete customer ───────────────────────────────────────────────────────
function confirmDelete(membId, fullName) {
    if (!confirm('Are you sure you want to delete ' + fullName + '? This cannot be undone.')) return;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'delete_customer.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Failed to delete customer: ' + (response.message || 'Unknown error.'));
                }
            } catch (e) {
                alert('Unexpected server response.');
            }
        } else {
            alert('Server error (' + xhr.status + '). Please try again.');
        }
    };
    xhr.onerror = function () {
        alert('Network error. Please try again.');
    };
    xhr.send('MEMB_ID=' + encodeURIComponent(membId));
}
</script>

</body>
</html>