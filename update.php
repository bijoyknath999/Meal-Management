<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'db.php';

requireLogin();

$db = getDB();
$results = [];
$errors = [];

// Migration: Make member_id nullable in expenses table
$migrations = [
    [
        'name' => 'Make expenses.member_id nullable',
        'check' => "SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expenses' AND COLUMN_NAME = 'member_id'",
        'check_value' => 'YES',
        'sql' => "ALTER TABLE expenses MODIFY COLUMN member_id INT NULL"
    ],
    [
        'name' => 'Update foreign key to SET NULL on delete',
        'check' => "SELECT DELETE_RULE FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'expenses' AND REFERENCED_TABLE_NAME = 'members'",
        'check_value' => 'SET NULL',
        'sql_steps' => [
            "SET @fk_name = (SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'expenses' AND REFERENCED_TABLE_NAME = 'members' LIMIT 1)",
            "SET @drop_sql = CONCAT('ALTER TABLE expenses DROP FOREIGN KEY ', @fk_name)",
            "PREPARE stmt FROM @drop_sql",
            "EXECUTE stmt",
            "DEALLOCATE PREPARE stmt",
            "ALTER TABLE expenses ADD CONSTRAINT fk_expenses_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL"
        ]
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    foreach ($migrations as $migration) {
        // Check if already applied
        $checkResult = $db->query($migration['check']);
        if ($checkResult) {
            $row = $checkResult->fetch_row();
            if ($row && $row[0] === $migration['check_value']) {
                $results[] = "✅ '{$migration['name']}' — Already applied, skipped.";
                continue;
            }
        }

        // Run migration
        try {
            if (isset($migration['sql'])) {
                if ($db->query($migration['sql'])) {
                    $results[] = "✅ '{$migration['name']}' — Applied successfully!";
                } else {
                    $errors[] = "❌ '{$migration['name']}' — Error: " . $db->error;
                }
            } elseif (isset($migration['sql_steps'])) {
                $success = true;
                foreach ($migration['sql_steps'] as $step) {
                    if (!$db->query($step)) {
                        $errors[] = "❌ '{$migration['name']}' — Error at step: " . $db->error;
                        $success = false;
                        break;
                    }
                }
                if ($success) {
                    $results[] = "✅ '{$migration['name']}' — Applied successfully!";
                }
            }
        } catch (Exception $e) {
            $errors[] = "❌ '{$migration['name']}' — Exception: " . $e->getMessage();
        }
    }
}

// Check current status
$statusChecks = [];

// Check member_id nullable
$check1 = $db->query("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'expenses' AND COLUMN_NAME = 'member_id'");
$row1 = $check1 ? $check1->fetch_row() : null;
$statusChecks['member_id_nullable'] = ($row1 && $row1[0] === 'YES');

// Check foreign key
$check2 = $db->query("SELECT DELETE_RULE FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'expenses' AND REFERENCED_TABLE_NAME = 'members'");
$row2 = $check2 ? $check2->fetch_row() : null;
$statusChecks['fk_set_null'] = ($row2 && $row2[0] === 'SET NULL');

$allApplied = $statusChecks['member_id_nullable'] && $statusChecks['fk_set_null'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo APP_VERSION; ?>">
    <style>
        .update-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
        }
        .update-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .update-card h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-badge.applied {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-badge.pending {
            background: #fff3e0;
            color: #e65100;
        }
        .result-item {
            padding: 10px 15px;
            margin: 8px 0;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .result-item.success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .result-item.error {
            background: #ffebee;
            color: #c62828;
        }
        .btn-update {
            display: block;
            width: 100%;
            padding: 14px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-update:hover {
            background: #388E3C;
        }
        .btn-update:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .all-done {
            text-align: center;
            padding: 20px;
            color: #2e7d32;
            font-size: 1.1rem;
        }
        .all-done .icon {
            font-size: 3rem;
            display: block;
            margin-bottom: 10px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="update-container">
        <div class="update-card">
            <h2>🔄 Database Update</h2>
            <p style="color: #666; margin-bottom: 20px;">This will update your database to support "Needs" expenses (expenses without a member).</p>

            <!-- Migration Results -->
            <?php if (!empty($results) || !empty($errors)): ?>
                <div style="margin-bottom: 20px;">
                    <?php foreach ($results as $result): ?>
                        <div class="result-item success"><?php echo $result; ?></div>
                    <?php endforeach; ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="result-item error"><?php echo $error; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Current Status -->
            <h3 style="margin-bottom: 10px;">Migration Status</h3>
            <div class="status-item">
                <span>expenses.member_id nullable</span>
                <span class="status-badge <?php echo $statusChecks['member_id_nullable'] ? 'applied' : 'pending'; ?>">
                    <?php echo $statusChecks['member_id_nullable'] ? '✅ Applied' : '⏳ Pending'; ?>
                </span>
            </div>
            <div class="status-item">
                <span>Foreign key ON DELETE SET NULL</span>
                <span class="status-badge <?php echo $statusChecks['fk_set_null'] ? 'applied' : 'pending'; ?>">
                    <?php echo $statusChecks['fk_set_null'] ? '✅ Applied' : '⏳ Pending'; ?>
                </span>
            </div>

            <!-- Run Button -->
            <div style="margin-top: 25px;">
                <?php if ($allApplied): ?>
                    <div class="all-done">
                        <span class="icon">✅</span>
                        All migrations are up to date!
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="run_migration" value="1">
                        <button type="submit" class="btn-update" onclick="return confirm('Run database migration? This will modify the expenses table.')">
                            🚀 Run Migration
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-link">
            <a href="index.php">← Back to Dashboard</a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
