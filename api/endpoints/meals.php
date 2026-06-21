<?php
/**
 * Meals API Endpoint - Security Hardened
 * GET  /api/meals?period_id=&date=   - Get meals for date or period
 * POST /api/meals                     - Save daily meals (bulk)
 */

$db = getDB();

// Helper: get or validate active period ID
function getActivePeriodId() {
    $db = getDB();
    $result = $db->query("SELECT id FROM meal_periods WHERE is_active = 1 LIMIT 1");
    $activePeriod = $result->fetch_assoc();
    if (!$activePeriod) sendError('No active period found.', 404);
    return intval($activePeriod['id']);
}

switch ($method) {
    case 'GET':
        $periodId = Security::validateId($_GET['period_id'] ?? 0);
        $date = Security::validateDate($_GET['date'] ?? '');
        
        if (!$periodId) {
            $periodId = getActivePeriodId();
        }
        
        if ($date) {
            $stmt = $db->prepare("
                SELECT m.id as member_id, m.name as member_name,
                       COALESCE(dm.meal_count, 0) as meal_count,
                       dm.id as meal_id
                FROM period_members pm
                JOIN members m ON pm.member_id = m.id AND m.is_active = 1
                LEFT JOIN daily_meals dm ON dm.member_id = m.id AND dm.period_id = pm.period_id AND dm.meal_date = ?
                WHERE pm.period_id = ?
                ORDER BY m.name ASC
            ");
            $stmt->bind_param("si", $date, $periodId);
            $stmt->execute();
            $memberMeals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            sendSuccess([
                'period_id' => $periodId,
                'date' => $date,
                'meals' => $memberMeals
            ]);
        } else {
            $stmt = $db->prepare("
                SELECT dm.id, dm.period_id, dm.member_id, dm.meal_date, dm.meal_count,
                       m.name as member_name 
                FROM daily_meals dm
                JOIN members m ON dm.member_id = m.id
                WHERE dm.period_id = ?
                ORDER BY dm.meal_date ASC, m.name ASC
            ");
            $stmt->bind_param("i", $periodId);
            $stmt->execute();
            $meals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $grouped = [];
            foreach ($meals as $meal) {
                $d = $meal['meal_date'];
                if (!isset($grouped[$d])) $grouped[$d] = [];
                $grouped[$d][] = $meal;
            }
            
            sendSuccess([
                'period_id' => $periodId,
                'meals' => $grouped,
                'total_records' => count($meals)
            ]);
        }
        break;

    case 'POST':
        $input = getJsonInput();
        
        $periodId = Security::validateId($input['period_id'] ?? 0);
        if (!$periodId) {
            $periodId = getActivePeriodId();
        }
        
        $date = Security::validateDate($input['date'] ?? '');
        $meals = $input['meals'] ?? [];
        
        if (!$date) sendError('Invalid or missing date. Use YYYY-MM-DD format.');
        if (empty($meals) || !is_array($meals)) sendError('Meals data is required and must be an array.');
        
        // Limit batch size to prevent abuse
        if (count($meals) > 50) sendError('Too many meals in one request. Maximum 50.');
        
        // Verify period exists
        $stmt = $db->prepare("SELECT id FROM meal_periods WHERE id = ?");
        $stmt->bind_param("i", $periodId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) sendError('Period not found.', 404);
        
        $saved = 0;
        $stmt = $db->prepare("
            INSERT INTO daily_meals (period_id, member_id, meal_date, meal_count)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE meal_count = ?
        ");
        
        $db->begin_transaction();
        try {
            foreach ($meals as $meal) {
                $memberId = Security::validateId($meal['member_id'] ?? 0);
                $mealCount = Security::validateMealCount($meal['meal_count'] ?? 0);
                
                if ($memberId && $mealCount !== null) {
                    $stmt->bind_param("iisii", $periodId, $memberId, $date, $mealCount, $mealCount);
                    if ($stmt->execute()) {
                        $saved++;
                    }
                }
            }
            
            recalculateSettlementsMeals($periodId);
            $db->commit();
            
            Security::logActivity('Meals saved', "Period: $periodId, Date: $date, Count: $saved");
            
            sendSuccess([
                'period_id' => $periodId,
                'date' => $date,
                'saved_count' => $saved
            ], "Meals saved successfully. {$saved} records updated.");
            
        } catch (Exception $e) {
            $db->rollback();
            Security::logActivity('Meals save failed', $e->getMessage(), 'ERROR');
            sendError('Failed to save meals.', 500);
        }
        break;

    default:
        sendError('Method not allowed.', 405);
}

/**
 * Recalculate settlements for a period
 */
function recalculateSettlementsMeals($periodId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT member_id, SUM(meal_count) as total_meals FROM daily_meals WHERE period_id = ? GROUP BY member_id");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    $mealsByMember = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $mealsByMember[$r['member_id']] = $r['total_meals'];
    
    $stmt = $db->prepare("SELECT member_id, SUM(amount) as total_expense FROM expenses WHERE period_id = ? AND member_id IS NOT NULL GROUP BY member_id");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    $expensesByMember = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $expensesByMember[$r['member_id']] = $r['total_expense'];
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as t FROM expenses WHERE period_id = ? AND member_id IS NULL");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    $totalOther = floatval($stmt->get_result()->fetch_assoc()['t']);
    
    $totalMeals = array_sum($mealsByMember);
    $totalMemberExp = array_sum($expensesByMember);
    $totalExpense = $totalMemberExp + $totalOther;
    $mealRate = $totalMeals > 0 ? $totalMemberExp / $totalMeals : 0;
    
    $stmt = $db->prepare("UPDATE meal_periods SET total_meals = ?, total_expense = ?, meal_rate = ? WHERE id = ?");
    $stmt->bind_param("iddi", $totalMeals, $totalExpense, $mealRate, $periodId);
    $stmt->execute();
    
    $stmt = $db->prepare("SELECT m.id FROM members m INNER JOIN period_members pm ON m.id = pm.member_id WHERE pm.period_id = ? AND m.is_active = 1");
    $stmt->bind_param("i", $periodId);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $mc = count($members);
    $opm = $mc > 0 ? $totalOther / $mc : 0;
    
    $stmt = $db->prepare("INSERT INTO settlements (period_id, member_id, total_meals, total_expense, meal_cost, balance, status) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE total_meals=?, total_expense=?, meal_cost=?, balance=?, status=?");
    
    foreach ($members as $m) {
        $mid = $m['id'];
        $mm = $mealsByMember[$mid] ?? 0;
        $me = $expensesByMember[$mid] ?? 0;
        $mealCost = $mm * $mealRate;
        $bal = round($me - $mealCost - $opm);
        $status = 'settled';
        if ($bal > 0) $status = 'credit';
        elseif ($bal < 0) $status = 'due';
        $stmt->bind_param("iiidddsiddds", $periodId, $mid, $mm, $me, $mealCost, $bal, $status, $mm, $me, $mealCost, $bal, $status);
        $stmt->execute();
    }
}
