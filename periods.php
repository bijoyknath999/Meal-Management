<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $periodName = trim($_POST['period_name'] ?? '');
        $month = intval($_POST['month'] ?? 0);
        $year = intval($_POST['year'] ?? 0);
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        
        if ($periodName && $month && $year && $startDate && $endDate) {
            $db = getDB();
            
            // Deactivate other periods
            $db->query("UPDATE meal_periods SET is_active = 0");
            
            $stmt = $db->prepare("INSERT INTO meal_periods (period_name, month, year, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("siiss", $periodName, $month, $year, $startDate, $endDate);
            
            if ($stmt->execute()) {
                $success = "Period created successfully!";
            } else {
                $error = "Failed to create period.";
            }
        }
    } elseif ($action === 'activate') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id) {
            $db = getDB();
            $db->query("UPDATE meal_periods SET is_active = 0");
            $stmt = $db->prepare("UPDATE meal_periods SET is_active = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = "Period activated successfully!";
            }
        }
    }
}

$periods = getAllPeriods();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Periods - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Meal Periods</h1>
            <button class="btn btn-primary" onclick="openModal('addModal')">+ New Period</button>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="periods-list">
            <?php foreach ($periods as $period): ?>
                <div class="period-card <?php echo $period['is_active'] ? 'active' : ''; ?>">
                    <div class="period-header">
                        <h3><?php echo htmlspecialchars($period['period_name']); ?></h3>
                        <?php if ($period['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php endif; ?>
                    </div>
                    <div class="period-details">
                        <p>📅 <?php echo formatDate($period['start_date']); ?> - <?php echo formatDate($period['end_date']); ?></p>
                        <p>🍽️ Total Meals: <?php echo $period['total_meals']; ?></p>
                        <p>💰 Total Expense: ৳<?php echo formatCurrency($period['total_expense']); ?></p>
                        <p>📊 Meal Rate: ৳<?php echo formatCurrency($period['meal_rate']); ?></p>
                    </div>
                    <?php if (!$period['is_active']): ?>
                        <form method="POST" action="" style="margin-top: 10px;">
                            <input type="hidden" name="action" value="activate">
                            <input type="hidden" name="id" value="<?php echo $period['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-primary">Activate</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Add Period Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Period</h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="period_name">Period Name *</label>
                    <input type="text" id="period_name" name="period_name" placeholder="e.g., November 2025" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="month">Month *</label>
                        <select id="month" name="month" required>
                            <option value="">Select Month</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>"><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year">Year *</label>
                        <input type="number" id="year" name="year" value="2025" min="2020" max="2030" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date *</label>
                        <input type="date" id="end_date" name="end_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Period</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>
</html>

