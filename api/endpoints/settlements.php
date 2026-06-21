<?php
/**
 * Settlements API Endpoint
 * GET /api/settlements?period_id=  - Get settlements for a period
 * POST /api/settlements/recalculate - Force recalculate settlements
 */

$db = getDB();

switch ($method) {
    case 'GET':
        $periodId = Security::validateId($_GET['period_id'] ?? 0);
        
        if (!$periodId) {
            $result = $db->query("SELECT id FROM meal_periods WHERE is_active = 1 LIMIT 1");
            $activePeriod = $result->fetch_assoc();
            if ($activePeriod) {
                $periodId = intval($activePeriod['id']);
            } else {
                sendError('No active period found.', 404);
            }
        }
        
        $stmt = $db->prepare("
            SELECT s.*, m.name as member_name 
            FROM settlements s
            JOIN members m ON s.member_id = m.id
            JOIN period_members pm ON m.id = pm.member_id AND pm.period_id = s.period_id
            WHERE s.period_id = ?
            ORDER BY s.balance DESC
        ");
        $stmt->bind_param("i", $periodId);
        $stmt->execute();
        $settlements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Summary
        $totalCredit = 0;
        $totalDue = 0;
        foreach ($settlements as $s) {
            if ($s['status'] === 'credit') $totalCredit += floatval($s['balance']);
            elseif ($s['status'] === 'due') $totalDue += abs(floatval($s['balance']));
        }
        
        sendSuccess([
            'period_id' => $periodId,
            'settlements' => $settlements,
            'summary' => [
                'total_credit' => $totalCredit,
                'total_due' => $totalDue,
                'balance' => round($totalCredit - $totalDue, 2)
            ]
        ]);
        break;

    case 'POST':
        $input = getJsonInput();
        $periodId = intval($input['period_id'] ?? $_GET['period_id'] ?? 0);
        
        if (!$periodId) {
            $result = $db->query("SELECT id FROM meal_periods WHERE is_active = 1 LIMIT 1");
            $activePeriod = $result->fetch_assoc();
            if ($activePeriod) $periodId = $activePeriod['id'];
            else sendError('No active period found.', 404);
        }
        
        // Inline recalculation
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
        
        $stmt = $db->prepare("SELECT m.id FROM members m INNER JOIN period_members pm ON m.id = pm.member_id WHERE pm.period_id = ?");
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
        
        sendSuccess(null, 'Settlements recalculated.');
        break;

    default:
        sendError('Method not allowed.', 405);
}
