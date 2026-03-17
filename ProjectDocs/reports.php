
<?php
session_start();
require_once 'config.php';

$config = [
    'primary' => 'Square',
    'secondary' => '',
    'auto_verify' => 'Enabled'
];

// FETCH DATA FOR THE ON-SCREEN TABLE
try {
    $stmt = $pdo->query("SELECT * FROM `package_reports` ORDER BY `report_date` DESC");
    $reportData = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/report.css">

<div class="report-wrapper" id="printArea">
    <div class="page-header no-print">
        <div class="header-content">
            <h1>Package Report</h1>
            <p>View the earnings and customer details per package</p>
        </div>

        <button type="button" class="btn-export" onclick="window.print()">Print Report</button>
    </div>

    <h2 class="section-title">Recent Package Reports</h2>
    <div class="table-card">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Package Name</th>
                    <th>Total Earnings (in $)</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reportData)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;">No report data found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td class="id-col"><?= htmlspecialchars($row['package_name']) ?></td>
                            <td><strong>$<?= number_format($row['total_earnings'], 2) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($row['start_date'])) ?></td>
                            <td><?= date('M d, Y', strtotime($row['end_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/report.js"></script>

<?php include 'includes/footer.php'; ?>
