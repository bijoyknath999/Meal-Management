<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

requireLogin();

header('Content-Type: application/json');

$periodId = intval($_GET['period_id'] ?? 0);

if (!$periodId) {
    echo json_encode(['error' => 'Invalid period ID']);
    exit;
}

$db = getDB();

// Get all active members
$allMembers = [];
$result = $db->query("SELECT id, name FROM members WHERE is_active = 1 ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $allMembers[] = $row;
}

// Get members assigned to this period
$periodMembers = [];
$stmt = $db->prepare("SELECT member_id FROM period_members WHERE period_id = ?");
$stmt->bind_param("i", $periodId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $periodMembers[] = intval($row['member_id']);
}

echo json_encode([
    'allMembers' => $allMembers,
    'periodMembers' => $periodMembers
]);
?>
