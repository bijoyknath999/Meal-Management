<?php
// Helper functions for Meal Management System
require_once 'config.php';
require_once 'db.php';

// Get all members
function getAllMembers($activeOnly = true) {
    $db = getDB();
    $sql = "SELECT * FROM members";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name ASC";
    
    $result = $db->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get members for a specific period
function getPeriodMembers($periodId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.* 
        FROM members m
        INNER JOIN period_members pm ON m.id = pm.member_id
        WHERE pm.period_id = ? AND m.is_active = 1
        ORDER BY m.name ASC
    ");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get active meal period
function getActivePeriod() {
    $db = getDB();
    $result = $db->query("SELECT * FROM meal_periods WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    return $result->fetch_assoc();
}

// Get all meal periods
function getAllPeriods() {
    $db = getDB();
    $result = $db->query("SELECT * FROM meal_periods ORDER BY year DESC, month DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get meals for a specific date and period
function getMealsForDate($periodId, $date) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT dm.*, m.name as member_name 
        FROM daily_meals dm
        JOIN members m ON dm.member_id = m.id
        WHERE dm.period_id = ? AND dm.meal_date = ?
        ORDER BY m.name ASC
    ");
    $stmt->bind_param("is", $periodId, $date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get all meals for a period
function getAllMealsForPeriod($periodId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT dm.*, m.name as member_name 
        FROM daily_meals dm
        JOIN members m ON dm.member_id = m.id
        WHERE dm.period_id = ?
        ORDER BY dm.meal_date ASC, m.name ASC
    ");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Add or update daily meal
function saveDailyMeal($periodId, $memberId, $date, $mealCount) {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO daily_meals (period_id, member_id, meal_date, meal_count)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE meal_count = ?
    ");
    $stmt->bind_param("iisii", $periodId, $memberId, $date, $mealCount, $mealCount);
    
    return $stmt->execute();
}

// Get expenses for a period
function getExpensesForPeriod($periodId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT e.*, m.name as member_name 
        FROM expenses e
        JOIN members m ON e.member_id = m.id
        WHERE e.period_id = ?
        ORDER BY e.expense_date DESC, e.id DESC
    ");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Add expense
function addExpense($periodId, $memberId, $amount, $date, $description = '') {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO expenses (period_id, member_id, amount, expense_date, description, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $createdBy = $_SESSION['admin_username'] ?? 'system';
    $stmt->bind_param("iidsss", $periodId, $memberId, $amount, $date, $description, $createdBy);
    
    return $stmt->execute();
}

// Update expense
function updateExpense($id, $memberId, $amount, $date, $description = '') {
    $db = getDB();
    
    $stmt = $db->prepare("
        UPDATE expenses 
        SET member_id = ?, amount = ?, expense_date = ?, description = ?
        WHERE id = ?
    ");
    $stmt->bind_param("idssi", $memberId, $amount, $date, $description, $id);
    
    return $stmt->execute();
}

// Delete expense
function deleteExpense($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Calculate settlements for a period
function calculateSettlements($periodId) {
    $db = getDB();
    
    // Get total meals per member
    $mealQuery = "
        SELECT member_id, SUM(meal_count) as total_meals
        FROM daily_meals
        WHERE period_id = ?
        GROUP BY member_id
    ";
    $stmt = $db->prepare($mealQuery);
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    $mealResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $mealsByMember = [];
    foreach ($mealResults as $row) {
        $mealsByMember[$row['member_id']] = $row['total_meals'];
    }
    
    // Get total expenses per member
    $expenseQuery = "
        SELECT member_id, SUM(amount) as total_expense
        FROM expenses
        WHERE period_id = ?
        GROUP BY member_id
    ";
    $stmt = $db->prepare($expenseQuery);
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    $expenseResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $expensesByMember = [];
    foreach ($expenseResults as $row) {
        $expensesByMember[$row['member_id']] = $row['total_expense'];
    }
    
    // Calculate totals
    $totalMeals = array_sum($mealsByMember);
    $totalExpense = array_sum($expensesByMember);
    
    $mealRate = $totalMeals > 0 ? $totalExpense / $totalMeals : 0;
    
    // Update period totals
    $stmt = $db->prepare("
        UPDATE meal_periods 
        SET total_meals = ?, total_expense = ?, meal_rate = ?
        WHERE id = ?
    ");
    $stmt->bind_param("iddi", $totalMeals, $totalExpense, $mealRate, $periodId);
    $stmt->execute();
    
    // Calculate settlements for each member in this period
    $members = getPeriodMembers($periodId);
    foreach ($members as $member) {
        $memberId = $member['id'];
        $memberMeals = $mealsByMember[$memberId] ?? 0;
        $memberExpense = $expensesByMember[$memberId] ?? 0;
        
        $mealCost = $memberMeals * $mealRate;
        $balance = $memberExpense - $mealCost;
        
        $status = 'settled';
        if ($balance > 0.01) {
            $status = 'credit'; // Member should get money back
        } elseif ($balance < -0.01) {
            $status = 'due'; // Member owes money
        }
        
        // Insert or update settlement
        $stmt = $db->prepare("
            INSERT INTO settlements (period_id, member_id, total_meals, total_expense, meal_cost, balance, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                total_meals = ?, 
                total_expense = ?, 
                meal_cost = ?, 
                balance = ?, 
                status = ?
        ");
        $stmt->bind_param("iiidddsiddds", 
            $periodId, $memberId, $memberMeals, $memberExpense, $mealCost, $balance, $status,
            $memberMeals, $memberExpense, $mealCost, $balance, $status
        );
        $stmt->execute();
    }
    
    return true;
}

// Get settlements for a period
function getSettlements($periodId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.*, m.name as member_name 
        FROM settlements s
        JOIN members m ON s.member_id = m.id
        WHERE s.period_id = ?
        ORDER BY s.balance DESC
    ");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Format currency
function formatCurrency($amount) {
    return number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}
?>

