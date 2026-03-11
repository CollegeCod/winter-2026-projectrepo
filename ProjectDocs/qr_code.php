<?php
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/db_connection.php";

start_secure_session();
$pdo = create_database_connection();

try {
    // 1. Fetch Summary Stats
    $total_qr = $pdo->query("SELECT COUNT(*) FROM qr_code")->fetchColumn();

    $linked_members = $pdo
        ->query("SELECT COUNT(DISTINCT MEMB_ID) FROM qr_code")
        ->fetchColumn();
    $total_rules = $pdo
        ->query("SELECT COUNT(DISTINCT RULE_ID) FROM qr_code")
        ->fetchColumn();

    // 2. Fetch QR Grid Data
    $stmt = $pdo->query("
        SELECT QR_ID, MEMB_ID, RULE_ID
        FROM qr_code
        ORDER BY QR_ID DESC
    ");
    $qr_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

echo '<link rel="stylesheet" href="css/qr_codes.css">';
include "includes/header.php";
?>

<div class="qr-management-wrapper">
    <div class="page-header">
        <div class="header-text">
            <h1>QR Code Management</h1>
            <p>Manage QR codes based on the current database schema</p>
        </div>
        <a href="https://unifi.ui.com/" target="_blank" class="btn-platform">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Open UNIFI
        </a>
    </div>

    <div class="qr-stats-grid">
        <div class="qr-stat-card">
            <div class="qr-stat-info">
                <span>Total QR Codes</span>
                <h2><?= $total_qr ?></h2>
            </div>
            <i class="fa-solid fa-qrcode blue-icon"></i>
        </div>

        <div class="qr-stat-card">
            <div class="qr-stat-info">
                <span>Linked Members</span>
                <h2><?= $linked_members ?></h2>
            </div>
            <i class="fa-solid fa-users green-icon"></i>
        </div>

        <div class="qr-stat-card">
            <div class="qr-stat-info">
                <span>Access Rules Used</span>
                <h2><?= $total_rules ?></h2>
            </div>
            <i class="fa-solid fa-shield-halved orange-icon"></i>
        </div>
    </div>

    <div class="search-container">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="qrSearch" placeholder="Search QR codes by QR ID, Member ID, or Rule ID...">
    </div>

    <div class="qr-grid">
        <?php foreach ($qr_codes as $qr): ?>
            <div class="qr-card">
                <div class="qr-visual">
                    <a href="view_qr.php?id=<?= urlencode(
                        $qr["QR_ID"],
                    ) ?>" class="qr-link-display">
                        <i class="fa-solid fa-qrcode"></i>
                        <span>Click to View QR</span>
                    </a>
                    <span class="status-indicator active">Available</span>
                </div>

                <div class="qr-details">
                    <h3>QR-<?= str_pad(
                        $qr["QR_ID"],
                        3,
                        "0",
                        STR_PAD_LEFT,
                    ) ?></h3>
                    <p class="member-name">Member #<?= htmlspecialchars(
                        $qr["MEMB_ID"],
                    ) ?></p>
                    <p class="member-id">Rule #<?= htmlspecialchars(
                        $qr["RULE_ID"],
                    ) ?></p>

                    <div class="qr-metadata">
                        <div class="meta-row">
                            <span>QR ID:</span>
                            <strong><?= htmlspecialchars(
                                $qr["QR_ID"],
                            ) ?></strong>
                        </div>
                        <div class="meta-row">
                            <span>Member ID:</span>
                            <strong><?= htmlspecialchars(
                                $qr["MEMB_ID"],
                            ) ?></strong>
                        </div>
                        <div class="meta-row">
                            <span>Rule ID:</span>
                            <strong><?= htmlspecialchars(
                                $qr["RULE_ID"],
                            ) ?></strong>

                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
      <p><a href="dashboard.php">Back To Dashboard</a></p>
</div>
