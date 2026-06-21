<?php
/**
 * Expenses API Endpoint - Security Hardened
 * GET    /api/expenses?period_id=    - List expenses for period
 * GET    /api/expenses/{id}          - Get single expense
 * POST   /api/expenses               - Create expense
 * PUT    /api/expenses/{id}          - Update expense
 * DELETE /api/expenses/{id}          - Delete expense
 */

$db = getDB();

// Helper: recalculate settlements
function recalculateSettlementsForExpense($periodId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT member_id, SUM(meal_count) as total_meals FROM daily_meals WHERE period_id = ? GROUP BY member_id");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    $mealResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $mealsByMember = [];
    foreach ($mealResults as $row) $mealsByMember[$row['member_id']] = $row['total_meals'];
    
    $stmt = $db->prepare("SELECT member_id, SUM(amount) as total_expense FROM expenses WHERE period_id = ? AND member_id IS NOT NULL GROUP BY member_id");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    $expenseResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $expensesByMember = [];
    foreach ($expenseResults as $row) $expensesByMember[$row['member_id']] = $row['total_expense'];
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total_other FROM expenses WHERE period_id = ? AND member_id IS NULL");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    $totalOtherExpense = floatval($stmt->get_result()->fetch_assoc()['total_other']);
    
    $totalMeals = array_sum($mealsByMember);
    $totalMemberExpense = array_sum($expensesByMember);
    $totalExpense = $totalMemberExpense + $totalOtherExpense;
    $mealRate = $totalMeals > 0 ? $totalMemberExpense / $totalMeals : 0;
    
    $stmt = $db->prepare("UPDATE meal_periods SET total_meals = ?, total_expense = ?, meal_rate = ? WHERE id = ?");
    $stmt->bind_param("iddi", $totalMeals, $totalExpense, $mealRate, $periodId);
    $stmt->execute();
    
    $stmt = $db->prepare("SELECT m.id FROM members m INNER JOIN period_members pm ON m.id = pm.member_id WHERE pm.period_id = ? AND m.is_active = 1");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $memberCount = count($members);
    $otherPerMember = $memberCount > 0 ? $totalOtherExpense / $memberCount : 0;
    
    $stmt = $db->prepare("INSERT INTO settlements (period_id, member_id, total_meals, total_expense, meal_cost, balance, status) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE total_meals = ?, total_expense = ?, meal_cost = ?, balance = ?, status = ?");
    
    foreach ($members as $member) {
        $mid = $member['id'];
        $mm = $mealsByMember[$mid] ?? 0;
        $me = $expensesByMember[$mid] ?? 0;
        $mc = $mm * $mealRate;
        $bal = round($me - $mc - $otherPerMember);
        $status = 'settled';
        if ($bal > 0) $status = 'credit';
        elseif ($bal < 0) $status = 'due';
        $stmt->bind_param("iiidddsiddds", $periodId, $mid, $mm, $me, $mc, $bal, $status, $mm, $me, $mc, $bal, $status);
        $stmt->execute();
    }
}

// Helper: get or validate active period ID
function getValidPeriodId($inputPeriodId = 0) {
    $db = getDB();
    $periodId = Security::validateId($inputPeriodId);
    
    if (!$periodId) {
        $result = $db->query("SELECT id FROM meal_periods WHERE is_active = 1 LIMIT 1");
        $activePeriod = $result->fetch_assoc();
        if ($activePeriod) {
            $periodId = intval($activePeriod['id']);
        } else {
            sendError('No active period found.', 404);
        }
    }
    return $periodId;
}

switch ($method) {
    case 'GET':
        if ($id) {
            $id = Security::validateId($id);
            if (!$id) sendError('Invalid expense ID.');
            
            $stmt = $db->prepare("
                SELECT e.id, e.period_id, e.member_id, e.amount, e.expense_date, e.description, e.created_by, e.created_at,
                       COALESCE(m.name, 'Other') as member_name 
                FROM expenses e
                LEFT JOIN members m ON e.member_id = m.id
                WHERE e.id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $expense = $stmt->get_result()->fetch_assoc();
            
            if (!$expense) sendError('Expense not found.', 404);
            sendSuccess($expense);
        } else {
            $periodId = getValidPeriodId(intval($_GET['period_id'] ?? 0));
            
            $stmt = $db->prepare("
                SELECT e.id, e.period_id, e.member_id, e.amount, e.expense_date, e.description, e.created_by, e.created_at,
                       COALESCE(m.name, 'Other') as member_name 
                FROM expenses e
                LEFT JOIN members m ON e.member_id = m.id
                WHERE e.period_id = ?
                ORDER BY e.expense_date DESC, e.id DESC
            ");
            $stmt->bind_param("i", $periodId);
            $stmt->execute();
            $expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $total = 0;
            foreach ($expenses as $exp) {
                $total += floatval($exp['amount']);
            }
            
            sendSuccess([
                'period_id' => $periodId,
                'expenses' => $expenses,
                'total' => round($total, 2),
                'count' => count($expenses)
            ]);
        }
        break;

    case 'POST':
        $input = getJsonInput();
        validateRequired($input, ['amount', 'expense_date']);
        
        $periodId = getValidPeriodId(intval($input['period_id'] ?? 0));
        $memberId = Security::validateId($input['member_id'] ?? 0);
        $amount = Security::validateAmount($input['amount']);
        $date = Security::validateDate($input['expense_date']);
        $description = Security::sanitizeDescription($input['description'] ?? '');
        $createdBy = Security::sanitizeString($input['created_by'] ?? 'api', 100);
        
        if ($amount === null || $amount <= 0) sendError('Invalid amount. Must be a positive number.');
        if ($amount > 999999.99) sendError('Amount too large.');
        if (!$date) sendError('Invalid date format. Use YYYY-MM-DD.');
        
        // Verify member exists if provided
        if ($memberId) {
            $stmt = $db->prepare("SELECT id FROM members WHERE id = ?");
            $stmt->bind_param("i", $memberId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                sendError('Member not found.');
            }
        }
        
        if ($memberId) {
            $stmt = $db->prepare("INSERT INTO expenses (period_id, member_id, amount, expense_date, description, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iidsss", $periodId, $memberId, $amount, $date, $description, $createdBy);
        } else {
            $stmt = $db->prepare("INSERT INTO expenses (period_id, member_id, amount, expense_date, description, created_by) VALUES (?, NULL, ?, ?, ?, ?)");
            $stmt->bind_param("idsss", $periodId, $amount, $date, $description, $createdBy);
        }
        
        if ($stmt->execute()) {
            $newId = $db->insert_id;
            recalculateSettlementsForExpense($periodId);
            Security::logActivity('Expense created', "ID: $newId, Amount: $amount");
            
            $stmt2 = $db->prepare("SELECT e.*, COALESCE(m.name, 'Other') as member_name FROM expenses e LEFT JOIN members m ON e.member_id = m.id WHERE e.id = ?");
            $stmt2->bind_param("i", $newId);
            $stmt2->execute();
            $expense = $stmt2->get_result()->fetch_assoc();
            sendSuccess($expense, 'Expense created successfully.');
        }
        
        sendError('Failed to create expense.', 500);
        break;

    case 'PUT':
        $id = Security::validateId($id);
        if (!$id) sendError('Invalid expense ID.');
        
        $input = getJsonInput();
        
        $stmt = $db->prepare("SELECT * FROM expenses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if (!$existing) sendError('Expense not found.', 404);
        
        $memberId = isset($input['member_id']) ? Security::validateId($input['member_id']) : $existing['member_id'];
        $amount = isset($input['amount']) ? Security::validateAmount($input['amount']) : floatval($existing['amount']);
        $date = isset($input['expense_date']) ? Security::validateDate($input['expense_date']) : $existing['expense_date'];
        $description = isset($input['description']) ? Security::sanitizeDescription($input['description']) : $existing['description'];
        
        if ($amount === null || $amount <= 0) sendError('Invalid amount.');
        if (!$date) sendError('Invalid date format.');
        
        // Verify member exists if provided
        if ($memberId) {
            $stmt = $db->prepare("SELECT id FROM members WHERE id = ?");
            $stmt->bind_param("i", $memberId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                sendError('Member not found.');
            }
        }
        
        if ($memberId) {
            $stmt = $db->prepare("UPDATE expenses SET member_id = ?, amount = ?, expense_date = ?, description = ? WHERE id = ?");
            $stmt->bind_param("idssi", $memberId, $amount, $date, $description, $id);
        } else {
            $stmt = $db->prepare("UPDATE expenses SET member_id = NULL, amount = ?, expense_date = ?, description = ? WHERE id = ?");
            $stmt->bind_param("dssi", $amount, $date, $description, $id);
        }
        
        if ($stmt->execute()) {
            recalculateSettlementsForExpense($existing['period_id']);
            Security::logActivity('Expense updated', "ID: $id");
            
            $stmt2 = $db->prepare("SELECT e.*, COALESCE(m.name, 'Other') as member_name FROM expenses e LEFT JOIN members m ON e.member_id = m.id WHERE e.id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $expense = $stmt2->get_result()->fetch_assoc();
            sendSuccess($expense, 'Expense updated successfully.');
        }
        
        sendError('Failed to update expense.', 500);
        break;

    case 'DELETE':
        $id = Security::validateId($id);
        if (!$id) sendError('Invalid expense ID.');
        
        $stmt = $db->prepare("SELECT id, period_id FROM expenses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if (!$existing) sendError('Expense not found.', 404);
        
        $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            recalculateSettlementsForExpense($existing['period_id']);
            Security::logActivity('Expense deleted', "ID: $id");
            sendSuccess(null, 'Expense deleted successfully.');
        }
        
        sendError('Failed to delete expense.', 500);
        break;

    default:
        sendError('Method not allowed.', 405);
}
