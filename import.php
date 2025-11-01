<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();

$success = '';
$error = '';
$importData = [];

// Handle CSV import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    $period = getActivePeriod();
    
    if (!$period) {
        $error = "No active period found. Please create a period first.";
    } else {
        // Read the CSV file
        $csvFile = 'Meals_List_2025 - October 2025.csv';
        
        if (file_exists($csvFile)) {
            $handle = fopen($csvFile, 'r');
            $data = [];
            $lineNumber = 0;
            
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;
                $data[] = $row;
            }
            fclose($handle);
            
            // Parse member names from row 4 (index 3)
            $memberNames = array_slice($data[3], 1, 10); // Columns B to K
            
            // Get or create members
            $members = getAllMembers();
            $memberMap = [];
            
            foreach ($memberNames as $index => $name) {
                $name = trim($name);
                if (empty($name)) continue;
                
                // Find member by name
                $found = false;
                foreach ($members as $member) {
                    if (strtolower($member['name']) === strtolower($name)) {
                        $memberMap[$index + 1] = $member['id']; // Column index to member ID
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    // Create new member
                    $db = getDB();
                    $stmt = $db->prepare("INSERT INTO members (name) VALUES (?)");
                    $stmt->bind_param("s", $name);
                    if ($stmt->execute()) {
                        $memberMap[$index + 1] = $db->insert_id;
                    }
                }
            }
            
            // Import daily meals (rows 5-36, dates 1-31)
            $importedMeals = 0;
            $importedExpenses = 0;
            
            for ($i = 4; $i < 36 && $i < count($data); $i++) {
                $row = $data[$i];
                $day = intval($row[0] ?? 0);
                
                if ($day > 0 && $day <= 31) {
                    $date = date('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
                    
                    // Check if date is within period
                    if ($date >= $period['start_date'] && $date <= $period['end_date']) {
                        // Import meals for each member
                        foreach ($memberMap as $colIndex => $memberId) {
                            $mealCount = intval($row[$colIndex] ?? 0);
                            if (saveDailyMeal($period['id'], $memberId, $date, $mealCount)) {
                                $importedMeals++;
                            }
                        }
                        
                        // Import expense if exists (column L - index 11)
                        $expensePayer = trim($row[11] ?? '');
                        $expenseAmount = floatval($row[12] ?? 0);
                        
                        if (!empty($expensePayer) && $expenseAmount > 0) {
                            // Find member ID by name
                            foreach ($members as $member) {
                                if (strtolower($member['name']) === strtolower($expensePayer)) {
                                    addExpense($period['id'], $member['id'], $expenseAmount, $date, 'Imported from CSV');
                                    $importedExpenses++;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            
            // Recalculate settlements
            calculateSettlements($period['id']);
            
            $success = "Import completed! Imported $importedMeals meal entries and $importedExpenses expenses.";
        } else {
            $error = "CSV file not found: $csvFile";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import CSV Data - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Import CSV Data</h1>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="report-container">
            <h2>Import from CSV File</h2>
            <p>This will import data from: <strong>Meals_List_2025 - October 2025.csv</strong></p>
            
            <div class="alert alert-warning">
                <h3>⚠️ Important Notes:</h3>
                <ul>
                    <li>Make sure you have created an active meal period first</li>
                    <li>The CSV file must be in the same directory as this script</li>
                    <li>Members will be automatically created if they don't exist</li>
                    <li>Existing data for the same dates will be updated</li>
                    <li>This process may take a few seconds</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="import" value="1">
                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary btn-large" onclick="return confirm('Are you sure you want to import the CSV data? This will update existing records.')">
                        📥 Import CSV Data
                    </button>
                </div>
            </form>
            
            <div style="margin-top: 3rem;">
                <h3>CSV File Format Expected:</h3>
                <ul>
                    <li>Row 4: Member names in columns B-K</li>
                    <li>Rows 5-36: Daily data (dates 1-31)</li>
                    <li>Column A: Date (day of month)</li>
                    <li>Columns B-K: Meal counts for each member</li>
                    <li>Column L: Expense payer name</li>
                    <li>Column M: Expense amount</li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

