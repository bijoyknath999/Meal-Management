<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; }
        .success { color: #27ae60; padding: 10px; background: #d4edda; border-radius: 5px; margin: 10px 0; }
        .error { color: #c0392b; padding: 10px; background: #f8d7da; border-radius: 5px; margin: 10px 0; }
        .info { color: #2980b9; padding: 10px; background: #d1ecf1; border-radius: 5px; margin: 10px 0; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        code {
            background: #f8f9fa;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Period Members Migration</h1>
        <p>This migration adds support for period-based member management.</p>
        
        <?php
        require_once 'config.php';
        require_once 'db.php';
        
        try {
            $db = getDB();
            
            // Check if table already exists
            $result = $db->query("SHOW TABLES LIKE 'period_members'");
            if ($result->num_rows > 0) {
                echo '<div class="info">ℹ️ <strong>period_members</strong> table already exists!</div>';
            } else {
                // Create period_members table
                $sql = "CREATE TABLE IF NOT EXISTS period_members (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    period_id INT NOT NULL,
                    member_id INT NOT NULL,
                    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (period_id) REFERENCES meal_periods(id) ON DELETE CASCADE,
                    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_period_member (period_id, member_id),
                    INDEX idx_period (period_id),
                    INDEX idx_member (member_id)
                )";
                
                if ($db->query($sql)) {
                    echo '<div class="success">✅ <strong>period_members</strong> table created successfully!</div>';
                } else {
                    throw new Exception("Failed to create table: " . $db->error);
                }
            }
            
            // Check if data already migrated
            $result = $db->query("SELECT COUNT(*) as count FROM period_members");
            $count = $result->fetch_assoc()['count'];
            
            if ($count > 0) {
                echo '<div class="info">ℹ️ Data already migrated! Found <strong>' . $count . '</strong> period-member associations.</div>';
            } else {
                // Migrate existing data: Add all current members to existing periods
                $sql = "INSERT INTO period_members (period_id, member_id)
                        SELECT DISTINCT p.id, m.id
                        FROM meal_periods p
                        CROSS JOIN members m
                        WHERE m.is_active = 1
                        ON DUPLICATE KEY UPDATE period_id = period_id";
                
                if ($db->query($sql)) {
                    $count = $db->affected_rows;
                    echo '<div class="success">✅ Migration completed! Added <strong>' . $count . '</strong> member-period associations!</div>';
                    echo '<div class="info">ℹ️ All active members have been added to all existing periods for backward compatibility.</div>';
                } else {
                    throw new Exception("Failed to migrate data: " . $db->error);
                }
            }
            
            echo '<h3>✅ Migration Successful!</h3>';
            echo '<p>You can now:</p>';
            echo '<ul>';
            echo '<li>Create periods with specific members</li>';
            echo '<li>Manage members per period (add/remove)</li>';
            echo '<li>When someone leaves, remove them from future periods only</li>';
            echo '</ul>';
            
            echo '<div style="margin-top: 20px;">';
            echo '<a href="periods.php" class="btn btn-success">Go to Periods</a>';
            echo '<a href="index.php" class="btn">Go to Dashboard</a>';
            echo '</div>';
            
            echo '<hr style="margin: 30px 0;">';
            echo '<p><small>💡 <strong>Tip:</strong> You can safely delete <code>migrate_period_members.php</code> after running this migration.</small></p>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<p>Please check your database connection and try again.</p>';
        }
        ?>
    </div>
</body>
</html>

