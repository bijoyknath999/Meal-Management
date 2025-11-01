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
$expenses = getExpensesForPeriod($period['id']);
$meals = getAllMealsForPeriod($period['id']);

// Group meals by date
$mealsByDate = [];
foreach ($meals as $meal) {
    $date = $meal['meal_date'];
    if (!isset($mealsByDate[$date])) {
        $mealsByDate[$date] = [];
    }
    $mealsByDate[$date][] = $meal;
}
ksort($mealsByDate);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo APP_VERSION; ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Detailed Report</h1>
            <div class="report-actions">
                <button class="btn btn-primary" onclick="window.print()">🖨️ Print</button>
                <button class="btn btn-secondary" onclick="downloadReport()">💾 Download</button>
            </div>
        </div>
        
        <div class="report-container" id="reportContent">
            <!-- Report Header -->
            <div class="report-header">
                <h2><?php echo htmlspecialchars($period['period_name']); ?></h2>
                <p><?php echo formatDate($period['start_date']); ?> - <?php echo formatDate($period['end_date']); ?></p>
                <p class="report-date">Generated: <?php echo date('d M Y, h:i A'); ?></p>
            </div>
            
            <!-- Summary Section -->
            <div class="report-section">
                <h3>📊 Summary</h3>
                <div class="report-stats">
                    <div class="stat-item">
                        <span>Total Meals:</span>
                        <strong><?php echo $period['total_meals']; ?></strong>
                    </div>
                    <div class="stat-item">
                        <span>Total Expense:</span>
                        <strong>৳<?php echo formatCurrency($period['total_expense']); ?></strong>
                    </div>
                    <div class="stat-item">
                        <span>Meal Rate:</span>
                        <strong>৳<?php echo formatCurrency($period['meal_rate']); ?></strong>
                    </div>
                </div>
            </div>
            
            <!-- Settlements Section -->
            <div class="report-section">
                <h3>💰 Settlement Details</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Meals</th>
                            <th>Meal Cost</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($settlements as $settlement): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($settlement['member_name']); ?></td>
                                <td><?php echo $settlement['total_meals']; ?></td>
                                <td>৳<?php echo formatCurrency($settlement['meal_cost']); ?></td>
                                <td>৳<?php echo formatCurrency($settlement['total_expense']); ?></td>
                                <td class="<?php echo $settlement['status']; ?>">
                                    ৳<?php echo formatCurrency(abs($settlement['balance'])); ?>
                                </td>
                                <td>
                                    <?php if ($settlement['status'] === 'credit'): ?>
                                        <span class="badge badge-success">Will Take</span>
                                    <?php elseif ($settlement['status'] === 'due'): ?>
                                        <span class="badge badge-danger">Will Give</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Settled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Expenses Section -->
            <div class="report-section">
                <h3>🛒 Expenses</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Paid By</th>
                            <th>Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?php echo formatDate($expense['expense_date']); ?></td>
                                <td><?php echo htmlspecialchars($expense['member_name']); ?></td>
                                <td>৳<?php echo formatCurrency($expense['amount']); ?></td>
                                <td><?php echo htmlspecialchars($expense['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Daily Meals Section -->
            <div class="report-section">
                <h3>🍽️ Daily Meal Summary</h3>
                <div class="meals-summary">
                    <?php if (empty($mealsByDate)): ?>
                        <p>No meal records found.</p>
                    <?php else: ?>
                        <?php foreach ($mealsByDate as $date => $dateMeals): ?>
                            <div class="date-meals">
                                <h4><?php echo formatDate($date); ?></h4>
                                <div class="meals-list">
                                    <?php foreach ($dateMeals as $meal): ?>
                                        <?php if ($meal['meal_count'] > 0): ?>
                                            <span class="meal-badge">
                                                <?php echo htmlspecialchars($meal['member_name']); ?>: 
                                                <strong><?php echo $meal['meal_count']; ?></strong>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="share-section">
            <h3>📤 Share Report</h3>
            <p>To share this report in WhatsApp:</p>
            <ol>
                <li>Click the "Print" button and save as PDF, or</li>
                <li>Take a screenshot of the report</li>
                <li>Share in your WhatsApp group</li>
            </ol>
            <p>Public View Link: <a href="<?php echo BASE_URL; ?>/view.php" target="_blank"><?php echo BASE_URL; ?>/view.php</a></p>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    function downloadReport() {
        window.print();
    }
    </script>
</body>
</html>

