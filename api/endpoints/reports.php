<?php
/**
 * Reports API Endpoint
 * GET /api/reports?period_id=  - Full report for a period
 */

$db = getDB();

if ($method !== 'GET') {
    sendError('Method not allowed.', 405);
}

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

// Get period info
$stmt = $db->prepare("SELECT * FROM meal_periods WHERE id = ?");
$stmt->bind_param("i", $periodId);
$stmt->execute();
$period = $stmt->get_result()->fetch_assoc();

if (!$period) {
    sendError('Period not found.', 404);
}

// Get settlements
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

// Get expenses
$stmt = $db->prepare("
    SELECT e.*, COALESCE(m.name, 'Other') as member_name 
    FROM expenses e
    LEFT JOIN members m ON e.member_id = m.id
    WHERE e.period_id = ?
    ORDER BY e.expense_date DESC
");
$stmt->bind_param("i", $periodId);
$stmt->execute();
$expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get daily meals grouped by date
$stmt = $db->prepare("
    SELECT dm.*, m.name as member_name 
    FROM daily_meals dm
    JOIN members m ON dm.member_id = m.id
    WHERE dm.period_id = ?
    ORDER BY dm.meal_date ASC, m.name ASC
");
$stmt->bind_param("i", $periodId);
$stmt->execute();
$meals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$mealsByDate = [];
foreach ($meals as $meal) {
    $date = $meal['meal_date'];
    if (!isset($mealsByDate[$date])) {
        $mealsByDate[$date] = [];
    }
    $mealsByDate[$date][] = $meal;
}
ksort($mealsByDate);

// Get period members
$stmt = $db->prepare("
    SELECT m.* FROM members m
    INNER JOIN period_members pm ON m.id = pm.member_id
    WHERE pm.period_id = ?
    ORDER BY m.name ASC
");
$stmt->bind_param("i", $periodId);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate other/needs expense per member
$totalOtherExpense = 0;
foreach ($expenses as $expense) {
    if (!$expense['member_id']) {
        $totalOtherExpense += floatval($expense['amount']);
    }
}
$memberCount = count($members);
$otherPerMember = $memberCount > 0 ? $totalOtherExpense / $memberCount : 0;

sendSuccess([
    'period' => $period,
    'summary' => [
        'total_meals' => intval($period['total_meals']),
        'total_expense' => floatval($period['total_expense']),
        'meal_rate' => floatval($period['meal_rate']),
        'member_count' => $memberCount,
        'other_expense_total' => $totalOtherExpense,
        'other_expense_per_member' => round($otherPerMember, 2)
    ],
    'settlements' => $settlements,
    'expenses' => $expenses,
    'daily_meals' => $mealsByDate,
    'members' => $members
]);
