<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if ($name) {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO members (name, phone, email) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $phone, $email);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Member added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add member.";
            }
        }
        header('Location: members.php');
        exit();
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if ($id && $name) {
            $db = getDB();
            $stmt = $db->prepare("UPDATE members SET name = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $phone, $email, $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Member updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update member.";
            }
        }
        header('Location: members.php');
        exit();
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id) {
            $db = getDB();
            // Permanently delete member
            $stmt = $db->prepare("DELETE FROM members WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Member deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete member. Member may have associated records.";
            }
        }
        header('Location: members.php');
        exit();
    }
}

// Retrieve flash messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$members = getAllMembers(false); // Get all members including inactive
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo APP_VERSION; ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Members Management</h1>
            <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Member</button>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="members-grid">
            <?php foreach ($members as $member): ?>
                <div class="member-card <?php echo $member['is_active'] ? '' : 'inactive'; ?>">
                    <div class="member-icon">👤</div>
                    <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                    <?php if ($member['phone']): ?>
                        <p>📱 <?php echo htmlspecialchars($member['phone']); ?></p>
                    <?php endif; ?>
                    <?php if ($member['email']): ?>
                        <p>✉️ <?php echo htmlspecialchars($member['email']); ?></p>
                    <?php endif; ?>
                    <p class="member-status">
                        <?php echo $member['is_active'] ? '✓ Active' : '✗ Inactive'; ?>
                    </p>
                    <div class="member-actions">
                        <button class="btn btn-sm btn-secondary" onclick='editMember(<?php echo json_encode($member); ?>)'>Edit</button>
                        <button class="btn btn-sm btn-danger" onclick='deleteMember(<?php echo $member["id"]; ?>, "<?php echo htmlspecialchars($member["name"]); ?>")'>Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Add Member Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Member</h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Member Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Member</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_name">Name *</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_phone">Phone</label>
                    <input type="text" id="edit_phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Member</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    function editMember(member) {
        document.getElementById('edit_id').value = member.id;
        document.getElementById('edit_name').value = member.name;
        document.getElementById('edit_phone').value = member.phone || '';
        document.getElementById('edit_email').value = member.email || '';
        openModal('editModal');
    }
    
    function deleteMember(id, name) {
        if (confirm('Are you sure you want to permanently delete "' + name + '"?\n\nThis will remove all associated meal and expense records. This action cannot be undone!')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>
</html>

