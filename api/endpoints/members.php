<?php
/**
 * Members API Endpoint - Security Hardened
 * GET    /api/members          - List all members
 * GET    /api/members/{id}     - Get single member
 * POST   /api/members          - Create member
 * PUT    /api/members/{id}     - Update member
 * DELETE /api/members/{id}     - Delete member
 */

$db = getDB();

switch ($method) {
    case 'GET':
        if ($id) {
            $id = Security::validateId($id);
            if (!$id) sendError('Invalid member ID.');
            
            $stmt = $db->prepare("SELECT id, name, phone, email, is_active, created_at FROM members WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $member = $stmt->get_result()->fetch_assoc();
            
            if (!$member) sendError('Member not found.', 404);
            $member['id'] = intval($member['id']);
            $member['is_active'] = intval($member['is_active']);
            sendSuccess($member);
        } else {
            $activeOnly = isset($_GET['active']) ? intval($_GET['active']) : null;
            
            $sql = "SELECT id, name, phone, email, is_active, created_at FROM members";
            if ($activeOnly !== null) {
                $sql .= " WHERE is_active = " . ($activeOnly ? '1' : '0');
            }
            $sql .= " ORDER BY name ASC";
            
            $result = $db->query($sql);
            $members = [];
            while ($row = $result->fetch_assoc()) {
                $row['id'] = intval($row['id']);
                $row['is_active'] = intval($row['is_active']);
                $members[] = $row;
            }
            sendSuccess($members);
        }
        break;

    case 'POST':
        $input = getJsonInput();
        validateRequired($input, ['name']);
        
        $name = Security::sanitizeString($input['name'], 100);
        $phone = Security::sanitizePhone($input['phone'] ?? '');
        $email = Security::sanitizeEmail($input['email'] ?? '');
        
        if (empty($name)) sendError('Name cannot be empty after sanitization.');
        
        // Validate email format if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendError('Invalid email format.');
        }
        
        $stmt = $db->prepare("INSERT INTO members (name, phone, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $phone, $email);
        
        if ($stmt->execute()) {
            $newId = $db->insert_id;
            Security::logActivity('Member created', "ID: $newId, Name: $name");
            
            $stmt2 = $db->prepare("SELECT id, name, phone, email, is_active, created_at FROM members WHERE id = ?");
            $stmt2->bind_param("i", $newId);
            $stmt2->execute();
            $member = $stmt2->get_result()->fetch_assoc();
            $member['id'] = intval($member['id']);
            $member['is_active'] = intval($member['is_active']);
            sendSuccess($member, 'Member created successfully.');
        }
        
        sendError('Failed to create member.', 500);
        break;

    case 'PUT':
        $id = Security::validateId($id);
        if (!$id) sendError('Invalid member ID.');
        
        $input = getJsonInput();
        
        $stmt = $db->prepare("SELECT id, name, phone, email, is_active FROM members WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if (!$existing) sendError('Member not found.', 404);
        
        $name = Security::sanitizeString($input['name'] ?? $existing['name'], 100);
        $phone = Security::sanitizePhone($input['phone'] ?? $existing['phone']);
        $email = Security::sanitizeEmail($input['email'] ?? $existing['email']);
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : intval($existing['is_active']);
        
        // Clamp is_active to 0 or 1
        $isActive = $isActive ? 1 : 0;
        
        if (empty($name)) sendError('Name cannot be empty.');
        
        $stmt = $db->prepare("UPDATE members SET name = ?, phone = ?, email = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sssii", $name, $phone, $email, $isActive, $id);
        
        if ($stmt->execute()) {
            Security::logActivity('Member updated', "ID: $id");
            
            $stmt2 = $db->prepare("SELECT id, name, phone, email, is_active, created_at FROM members WHERE id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $member = $stmt2->get_result()->fetch_assoc();
            $member['id'] = intval($member['id']);
            $member['is_active'] = intval($member['is_active']);
            sendSuccess($member, 'Member updated successfully.');
        }
        
        sendError('Failed to update member.', 500);
        break;

    case 'DELETE':
        $id = Security::validateId($id);
        if (!$id) sendError('Invalid member ID.');
        
        $stmt = $db->prepare("SELECT id, name FROM members WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if (!$existing) sendError('Member not found.', 404);
        
        // Use transaction to handle cascading deletes safely
        $db->begin_transaction();
        try {
            $stmt = $db->prepare("DELETE FROM members WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $db->commit();
                Security::logActivity('Member deleted', "ID: $id, Name: " . $existing['name']);
                sendSuccess(null, 'Member deleted successfully.');
            } else {
                $db->rollback();
                sendError('Failed to delete member. Member may have associated records.', 500);
            }
        } catch (Exception $e) {
            $db->rollback();
            Security::logActivity('Member delete failed', "ID: $id, Error: " . $e->getMessage(), 'ERROR');
            sendError('Failed to delete member. Member may have associated records.', 500);
        }
        break;

    default:
        sendError('Method not allowed.', 405);
}
