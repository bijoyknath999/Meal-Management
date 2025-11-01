<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();

$period = getActivePeriod();

if (!$period) {
    header('Location: periods.php');
    exit();
}

// Recalculate settlements
calculateSettlements($period['id']);
$settlements = getSettlements($period['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlements - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo APP_VERSION; ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Settlement Summary</h1>
            <h3><?php echo htmlspecialchars($period['period_name']); ?></h3>
        </div>
        
        <div class="period-summary">
            <div class="summary-card">
                <h3>Period Overview</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span>Total Meals:</span>
                        <strong><?php echo $period['total_meals']; ?></strong>
                    </div>
                    <div class="summary-item">
                        <span>Total Expense:</span>
                        <strong>৳<?php echo formatCurrency($period['total_expense']); ?></strong>
                    </div>
                    <div class="summary-item">
                        <span>Meal Rate:</span>
                        <strong>৳<?php echo formatCurrency($period['meal_rate']); ?></strong>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="settlements-container">
            <h2>Member Settlements</h2>
            
            <div class="settlements-grid">
                <?php foreach ($settlements as $settlement): ?>
                    <div class="settlement-card <?php echo $settlement['status']; ?>">
                        <div class="settlement-header">
                            <h3><?php echo htmlspecialchars($settlement['member_name']); ?></h3>
                            <?php if ($settlement['status'] === 'credit'): ?>
                                <span class="badge badge-success">Will Take</span>
                            <?php elseif ($settlement['status'] === 'due'): ?>
                                <span class="badge badge-danger">Will Give</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Settled</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="settlement-details">
                            <div class="detail-row">
                                <span>Total Meals:</span>
                                <strong><?php echo $settlement['total_meals']; ?></strong>
                            </div>
                            <div class="detail-row">
                                <span>Meal Cost:</span>
                                <strong>৳<?php echo formatCurrency($settlement['meal_cost']); ?></strong>
                            </div>
                            <div class="detail-row">
                                <span>Total Paid:</span>
                                <strong>৳<?php echo formatCurrency($settlement['total_expense']); ?></strong>
                            </div>
                            <div class="detail-row balance-row">
                                <span>Balance:</span>
                                <strong class="balance-amount <?php echo $settlement['status']; ?>">
                                    ৳<?php echo formatCurrency(abs($settlement['balance'])); ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="report.php" class="btn btn-primary">📄 Generate Report</a>
            <a href="view.php" target="_blank" class="btn btn-secondary">👁️ Public View</a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

