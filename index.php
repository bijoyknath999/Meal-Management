<?php
require_once 'config.php';
require_once 'auth.php';
// Deployment trigger
require_once 'functions.php';

requireLogin();

$period = getActivePeriod();
$members = getPeriodMembers($period['id']);

// Get today's date
$today = date('Y-m-d');
$currentMonth = date('F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard">
            <h1>Dashboard</h1>
            
            <?php if ($period): ?>
                <div class="period-info">
                    <h2><?php echo htmlspecialchars($period['period_name']); ?></h2>
                    <p><?php echo formatDate($period['start_date']); ?> - <?php echo formatDate($period['end_date']); ?></p>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3><?php echo count($members); ?></h3>
                            <p>Active Members</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🍽️</div>
                        <div class="stat-info">
                            <h3><?php echo $period['total_meals']; ?></h3>
                            <p>Total Meals</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3>৳<?php echo formatCurrency($period['total_expense']); ?></h3>
                            <p>Total Expense</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <h3>৳<?php echo formatCurrency($period['meal_rate']); ?></h3>
                            <p>Meal Rate</p>
                        </div>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="action-grid">
                        <a href="meals.php" class="action-card">
                            <div class="action-icon">📝</div>
                            <h3>Daily Meals</h3>
                            <p>Add or update daily meal counts</p>
                        </a>
                        
                        <a href="expenses.php" class="action-card">
                            <div class="action-icon">💵</div>
                            <h3>Expenses</h3>
                            <p>Track grocery and shopping expenses</p>
                        </a>
                        
                        <a href="settlements.php" class="action-card">
                            <div class="action-icon">🧾</div>
                            <h3>Settlements</h3>
                            <p>View who owes and who gets money</p>
                        </a>
                        
                        <a href="report.php" class="action-card">
                            <div class="action-icon">📄</div>
                            <h3>Reports</h3>
                            <p>Generate and export reports</p>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <p>No active meal period found. Please create one to get started.</p>
                    <a href="periods.php" class="btn btn-primary">Create Period</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

