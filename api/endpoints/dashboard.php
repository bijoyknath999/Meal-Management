<?php
/**
 * Dashboard API Endpoint
 * GET /api/dashboard - Get dashboard summary data
 */

$db = getDB();

if ($method !== 'GET') {
    sendError('Method not allowed.', 405);
}

// Get active period
$result = $db->query("SELECT * FROM meal_periods WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$period = $result->fetch_assoc();

if (!$period) {
    sendSuccess([
        'active_period' => null,
        'members' => [],
        'today_meals' => [],
        'recent_expenses' => [],
        'stats' => [
            'total_members' => 0,
            'total_meals_today' => 0,
            'total_expense' => 0,
            'meal_rate' => 0
        ]
    ]);
    return;
}

$periodId = $period['id'];

// Get period members
$stmt = $db->prepare("
    SELECT m.* FROM members m
    INNER JOIN period_members pm ON m.id = pm.member_id
    WHERE pm.period_id = ? AND m.is_active = 1
    ORDER BY m.name ASC
");
$stmt->bind_param("i", $periodId);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get today's meals
$today = date('Y-m-d');
$stmt = $db->prepare("
    SELECT dm.*, m.name as member_name 
    FROM daily_meals dm
    JOIN members m ON dm.member_id = m.id
    WHERE dm.period_id = ? AND dm.meal_date = ?
    ORDER BY m.name ASC
");
$stmt->bind_param("is", $periodId, $today);
$stmt->execute();
$todayMeals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent expenses (last 10)
$stmt = $db->prepare("
    SELECT e.*, COALESCE(m.name, 'Other') as member_name 
    FROM expenses e
    LEFT JOIN members m ON e.member_id = m.id
    WHERE e.period_id = ?
    ORDER BY e.expense_date DESC, e.id DESC
    LIMIT 10
");
$stmt->bind_param("i", $periodId);
$stmt->execute();
$recentExpenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Today's total meals
$totalMealsToday = 0;
foreach ($todayMeals as $meal) {
    $totalMealsToday += intval($meal['meal_count']);
}

sendSuccess([
    'active_period' => $period,
    'members' => $members,
    'today_meals' => $todayMeals,
    'recent_expenses' => $recentExpenses,
    'stats' => [
        'total_members' => count($members),
        'total_meals_today' => $totalMealsToday,
        'total_expense' => floatval($period['total_expense']),
        'meal_rate' => floatval($period['meal_rate']),
        'total_meals_period' => intval($period['total_meals'])
    ]
]);
