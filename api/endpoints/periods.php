<?php
/**
 * Periods API Endpoint
 * GET    /api/periods              - List all periods
 * GET    /api/periods/{id}         - Get single period with members
 * GET    /api/periods/active       - Get active period
 * POST   /api/periods              - Create period
 * PUT    /api/periods/{id}         - Update period
 * PUT    /api/periods/{id}/activate - Activate a period
 * PUT    /api/periods/{id}/members - Update period members
 * DELETE /api/periods/{id}         - Delete period
 */

$db = getDB();

switch ($method) {
    case 'GET':
        $action = $segments[1] ?? $id ?? null;
        
        if ($action === 'active') {
            // Get active period
            $result = $db->query("SELECT * FROM meal_periods WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
            $period = $result->fetch_assoc();
            
            if (!$period) {
                sendError('No active period found.', 404);
            }
            
            // Include members
            $stmt = $db->prepare("
                SELECT m.* FROM members m
                INNER JOIN period_members pm ON m.id = pm.member_id
                WHERE pm.period_id = ? AND m.is_active = 1
                ORDER BY m.name ASC
            ");
            $stmt->bind_param("i", $period['id']);
            $stmt->execute();
            $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $period['members'] = $members;
            $period['id'] = intval($period['id']);
            $period['is_active'] = intval($period['is_active']);
            
            sendSuccess($period);
        }
        
        if ($id && is_numeric($id)) {
            // Get single period
            $stmt = $db->prepare("SELECT * FROM meal_periods WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $period = $stmt->get_result()->fetch_assoc();
            
            if (!$period) {
                sendError('Period not found.', 404);
            }
            
            // Include members
            $stmt2 = $db->prepare("
                SELECT m.* FROM members m
                INNER JOIN period_members pm ON m.id = pm.member_id
                WHERE pm.period_id = ?
                ORDER BY m.name ASC
            ");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $period['members'] = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            
            sendSuccess($period);
        }
        
        // List all periods
        $result = $db->query("SELECT * FROM meal_periods ORDER BY year DESC, month DESC");
        $periods = [];
        while ($row = $result->fetch_assoc()) {
            $row['id'] = intval($row['id']);
            $row['is_active'] = intval($row['is_active']);
            $periods[] = $row;
        }
        
        sendSuccess($periods);
        break;

    case 'POST':
        $input = getJsonInput();
        validateRequired($input, ['period_name', 'month', 'year', 'start_date', 'end_date']);
        
        $periodName = Security::sanitizeString($input['period_name'], 100);
        $month = intval($input['month']);
        $year = intval($input['year']);
        $startDate = Security::validateDate($input['start_date']);
        $endDate = Security::validateDate($input['end_date']);
        $members = $input['members'] ?? [];
        $activate = $input['activate'] ?? true;
        
        if (!$startDate || !$endDate) sendError('Invalid date format. Use YYYY-MM-DD.');
        if ($month < 1 || $month > 12) sendError('Invalid month.');
        if ($year < 2020 || $year > 2050) sendError('Invalid year.');
        if (!is_array($members)) sendError('Members must be an array.');
        if (count($members) > 100) sendError('Too many members.');
        if (empty($periodName)) sendError('Period name cannot be empty.');
        
        // Deactivate other periods if activating
        if ($activate) {
            $db->query("UPDATE meal_periods SET is_active = 0");
        }
        
        $stmt = $db->prepare("INSERT INTO meal_periods (period_name, month, year, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $isActive = $activate ? 1 : 0;
        $stmt->bind_param("siissi", $periodName, $month, $year, $startDate, $endDate, $isActive);
        
        if ($stmt->execute()) {
            $periodId = $db->insert_id;
            
            // Add members to period
            if (!empty($members)) {
                $stmt2 = $db->prepare("INSERT INTO period_members (period_id, member_id) VALUES (?, ?)");
                foreach ($members as $memberId) {
                    $memberId = Security::validateId($memberId);
                    if ($memberId) {
                        $stmt2->bind_param("ii", $periodId, $memberId);
                        $stmt2->execute();
                    }
                }
            }
            
            // Fetch and return the created period
            $stmt3 = $db->prepare("SELECT * FROM meal_periods WHERE id = ?");
            $stmt3->bind_param("i", $periodId);
            $stmt3->execute();
            $period = $stmt3->get_result()->fetch_assoc();
            $period['members'] = $members;
            
            sendSuccess($period, 'Period created successfully.');
        }
        
        sendError('Failed to create period.', 500);
        break;

    case 'PUT':
        $action = $segments[1] ?? null;
        
        // PUT /api/periods/{id}/activate
        if ($segments[2] ?? '' === 'activate' || ($id && ($segments[1] ?? '') === 'activate')) {
            $periodId = is_numeric($id) ? $id : ($segments[0] ?? null);
            // Re-parse: if URL is /periods/5/activate
            if (($segments[1] ?? '') === 'activate') {
                // This means $resource=periods, $id=null, segments[1]=activate
                sendError('Use PUT /api/periods/{id}/activate format.');
            }
        }
        
        $id = Security::validateId($id);
        if (!$id) sendError('Invalid period ID.');
        
        $subAction = $segments[2] ?? null;
        
        if ($subAction === 'activate') {
            // Activate period
            $db->query("UPDATE meal_periods SET is_active = 0");
            $stmt = $db->prepare("UPDATE meal_periods SET is_active = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                sendSuccess(null, 'Period activated successfully.');
            }
            sendError('Failed to activate period.', 500);
        }
        
        if ($subAction === 'members') {
            // Update period members
            $input = getJsonInput();
            $members = $input['members'] ?? [];
            if (!is_array($members)) sendError('Members must be an array.');
            
            // Remove all current members
            $stmt = $db->prepare("DELETE FROM period_members WHERE period_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Add new members
            if (!empty($members)) {
                $stmt2 = $db->prepare("INSERT INTO period_members (period_id, member_id) VALUES (?, ?)");
                foreach ($members as $memberId) {
                    $memberId = Security::validateId($memberId);
                    if ($memberId) {
                        $stmt2->bind_param("ii", $id, $memberId);
                        $stmt2->execute();
                    }
                }
            }
            
            Security::logActivity('Period members updated', "Period: $id");
            sendSuccess(null, 'Period members updated successfully.');
        }
        
        // Update period details
        $input = getJsonInput();
        
        // Check if period exists
        $stmt = $db->prepare("SELECT * FROM meal_periods WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if (!$existing) {
            sendError('Period not found.', 404);
        }
        
        $periodName = Security::sanitizeString($input['period_name'] ?? $existing['period_name'], 100);
        $month = intval($input['month'] ?? $existing['month']);
        $year = intval($input['year'] ?? $existing['year']);
        $startDate = Security::validateDate($input['start_date'] ?? $existing['start_date']);
        $endDate = Security::validateDate($input['end_date'] ?? $existing['end_date']);
        
        if (!$startDate || !$endDate) sendError('Invalid date format.');
        if (empty($periodName)) sendError('Period name cannot be empty.');
        
        $stmt = $db->prepare("UPDATE meal_periods SET period_name = ?, month = ?, year = ?, start_date = ?, end_date = ? WHERE id = ?");
        $stmt->bind_param("siissi", $periodName, $month, $year, $startDate, $endDate, $id);
        
        if ($stmt->execute()) {
            $stmt2 = $db->prepare("SELECT * FROM meal_periods WHERE id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $period = $stmt2->get_result()->fetch_assoc();
            sendSuccess($period, 'Period updated successfully.');
        }
        
        sendError('Failed to update period.', 500);
        break;

    case 'DELETE':
        $id = Security::validateId($id);
        if (!$id) sendError('Invalid period ID.');
        
        // Check if it's the active period
        $stmt = $db->prepare("SELECT is_active FROM meal_periods WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            sendError('Period not found.', 404);
        }
        
        if ($result['is_active'] == 1) {
            sendError('Cannot delete active period. Please activate another period first.');
        }
        
        $stmt = $db->prepare("DELETE FROM meal_periods WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            sendSuccess(null, 'Period deleted successfully.');
        }
        
        sendError('Failed to delete period.', 500);
        break;

    default:
        sendError('Method not allowed.', 405);
}
