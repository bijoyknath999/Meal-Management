<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $periodName = trim($_POST['period_name'] ?? '');
        $month = intval($_POST['month'] ?? 0);
        $year = intval($_POST['year'] ?? 0);
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $selectedMembers = $_POST['members'] ?? [];
        
        if ($periodName && $month && $year && $startDate && $endDate) {
            $db = getDB();
            
            // Deactivate other periods
            $db->query("UPDATE meal_periods SET is_active = 0");
            
            $stmt = $db->prepare("INSERT INTO meal_periods (period_name, month, year, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("siiss", $periodName, $month, $year, $startDate, $endDate);
            
            if ($stmt->execute()) {
                $periodId = $db->insert_id;
                
                // Add selected members to this period
                if (!empty($selectedMembers)) {
                    $stmt = $db->prepare("INSERT INTO period_members (period_id, member_id) VALUES (?, ?)");
                    foreach ($selectedMembers as $memberId) {
                        $memberId = intval($memberId);
                        $stmt->bind_param("ii", $periodId, $memberId);
                        $stmt->execute();
                    }
                }
                
                $_SESSION['success'] = "Period created successfully with " . count($selectedMembers) . " members!";
            } else {
                $_SESSION['error'] = "Failed to create period.";
            }
        }
        header('Location: periods.php');
        exit();
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $periodName = trim($_POST['period_name'] ?? '');
        $month = intval($_POST['month'] ?? 0);
        $year = intval($_POST['year'] ?? 0);
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        
        if ($id && $periodName && $month && $year && $startDate && $endDate) {
            $db = getDB();
            $stmt = $db->prepare("UPDATE meal_periods SET period_name = ?, month = ?, year = ?, start_date = ?, end_date = ? WHERE id = ?");
            $stmt->bind_param("siiisi", $periodName, $month, $year, $startDate, $endDate, $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Period updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update period.";
            }
        }
        header('Location: periods.php');
        exit();
    } elseif ($action === 'edit_members') {
        $periodId = intval($_POST['period_id'] ?? 0);
        $selectedMembers = $_POST['members'] ?? [];
        
        if ($periodId) {
            $db = getDB();
            
            // Remove all current members
            $stmt = $db->prepare("DELETE FROM period_members WHERE period_id = ?");
            $stmt->bind_param("i", $periodId);
            $stmt->execute();
            
            // Add new members
            if (!empty($selectedMembers)) {
                $stmt = $db->prepare("INSERT INTO period_members (period_id, member_id) VALUES (?, ?)");
                foreach ($selectedMembers as $memberId) {
                    $memberId = intval($memberId);
                    $stmt->bind_param("ii", $periodId, $memberId);
                    $stmt->execute();
                }
            }
            
            $_SESSION['success'] = "Period members updated successfully!";
        }
        header('Location: periods.php');
        exit();
    } elseif ($action === 'activate') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id) {
            $db = getDB();
            $db->query("UPDATE meal_periods SET is_active = 0");
            $stmt = $db->prepare("UPDATE meal_periods SET is_active = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Period activated successfully!";
            }
        }
        header('Location: periods.php');
        exit();
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id) {
            $db = getDB();
            // Check if it's the active period
            $stmt = $db->prepare("SELECT is_active FROM meal_periods WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result && $result['is_active'] == 1) {
                $_SESSION['error'] = "Cannot delete active period! Please activate another period first.";
            } else {
                // Permanently delete period (cascade will delete related records)
                $stmt = $db->prepare("DELETE FROM meal_periods WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Period deleted successfully!";
                } else {
                    $_SESSION['error'] = "Failed to delete period.";
                }
            }
        }
        header('Location: periods.php');
        exit();
    }
}

// Retrieve flash messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Get all members for the selection list
$db = getDB();
$allMembers = [];
$result = $db->query("SELECT * FROM members WHERE is_active = 1 ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $allMembers[] = $row;
}

$periods = getAllPeriods();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Periods - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo APP_VERSION; ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Meal Periods</h1>
            <button class="btn btn-primary" onclick="openModal('addModal')">+ New Period</button>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="periods-list">
            <?php foreach ($periods as $period): 
                // Get members for this period
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM period_members WHERE period_id = ?");
                $stmt->bind_param("i", $period['id']);
                $stmt->execute();
                $memberCount = $stmt->get_result()->fetch_assoc()['count'];
            ?>
                <div class="period-card <?php echo $period['is_active'] ? 'active' : ''; ?>">
                    <div class="period-header">
                        <h3><?php echo htmlspecialchars($period['period_name']); ?></h3>
                        <?php if ($period['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php endif; ?>
                    </div>
                    <div class="period-details">
                        <p>📅 <?php echo formatDate($period['start_date']); ?> - <?php echo formatDate($period['end_date']); ?></p>
                        <p>👥 Members: <?php echo $memberCount; ?></p>
                        <p>🍽️ Total Meals: <?php echo $period['total_meals']; ?></p>
                        <p>💰 Total Expense: ৳<?php echo formatCurrency($period['total_expense']); ?></p>
                        <p>📊 Meal Rate: ৳<?php echo formatCurrency($period['meal_rate']); ?></p>
                    </div>
                    <div style="margin-top: 10px; display: flex; gap: 5px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="openEditPeriodModal(<?php echo $period['id']; ?>)">✏️ Edit</button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="openEditMembersModal(<?php echo $period['id']; ?>, '<?php echo htmlspecialchars($period['period_name']); ?>')">👥 Manage Members</button>
                        <?php if (!$period['is_active']): ?>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="action" value="activate">
                                <input type="hidden" name="id" value="<?php echo $period['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Activate</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger" onclick='deletePeriod(<?php echo $period["id"]; ?>, "<?php echo htmlspecialchars($period["period_name"]); ?>")'>🗑️ Delete</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Add Period Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Period</h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="period_name">Period Name *</label>
                    <input type="text" id="period_name" name="period_name" placeholder="e.g., November 2025" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="month">Month *</label>
                        <select id="month" name="month" required>
                            <option value="">Select Month</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>"><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year">Year *</label>
                        <input type="number" id="year" name="year" value="2025" min="2020" max="2030" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date *</label>
                        <input type="date" id="end_date" name="end_date" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Select Members * <span class="member-count-badge" id="selectedCount">10 selected</span></label>
                    <div class="members-selection-box">
                        <div class="select-all-item">
                            <label>
                                <input type="checkbox" id="selectAll" onclick="toggleAllMembers(this)" checked> Select All Members
                            </label>
                        </div>
                        <?php foreach ($allMembers as $member): ?>
                            <div class="member-checkbox-item">
                                <label>
                                    <input type="checkbox" name="members[]" value="<?php echo $member['id']; ?>" class="member-checkbox" checked onchange="updateMemberCount()">
                                    <?php echo htmlspecialchars($member['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Period</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Period Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Period</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_period_name">Period Name *</label>
                    <input type="text" id="edit_period_name" name="period_name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_month">Month *</label>
                        <select id="edit_month" name="month" required>
                            <option value="">Select Month</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>"><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_year">Year *</label>
                        <input type="number" id="edit_year" name="year" min="2020" max="2030" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_start_date">Start Date *</label>
                        <input type="date" id="edit_start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_end_date">End Date *</label>
                        <input type="date" id="edit_end_date" name="end_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Period</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Members Modal -->
    <div id="editMembersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="editMembersPeriodName">Manage Period Members</h2>
                <span class="close" onclick="closeModal('editMembersModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_members">
                <input type="hidden" name="period_id" id="edit_period_id">
                <div class="form-group">
                    <label>Select Members <span class="member-count-badge" id="selectedCountEdit">0 selected</span></label>
                    <div class="members-selection-box">
                        <div class="select-all-item">
                            <label>
                                <input type="checkbox" id="selectAllEdit" onclick="toggleAllMembersEdit(this)"> Select All Members
                            </label>
                        </div>
                        <div id="editMembersList">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editMembersModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Members</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    const periodsData = <?php echo json_encode($periods); ?>;
    
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    function updateMemberCount() {
        const checkboxes = document.querySelectorAll('.member-checkbox:checked');
        const count = checkboxes.length;
        document.getElementById('selectedCount').textContent = count + ' selected';
    }
    
    function updateMemberCountEdit() {
        const checkboxes = document.querySelectorAll('.member-checkbox-edit:checked');
        const count = checkboxes.length;
        document.getElementById('selectedCountEdit').textContent = count + ' selected';
    }
    
    function toggleAllMembers(checkbox) {
        const checkboxes = document.querySelectorAll('.member-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
        updateMemberCount();
    }
    
    function toggleAllMembersEdit(checkbox) {
        const checkboxes = document.querySelectorAll('.member-checkbox-edit');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
        updateMemberCountEdit();
    }
    
    function openEditPeriodModal(periodId) {
        const period = periodsData.find(p => p.id == periodId);
        if (!period) return;
        
        document.getElementById('edit_id').value = period.id;
        document.getElementById('edit_period_name').value = period.period_name;
        document.getElementById('edit_month').value = period.month;
        document.getElementById('edit_year').value = period.year;
        document.getElementById('edit_start_date').value = period.start_date;
        document.getElementById('edit_end_date').value = period.end_date;
        
        openModal('editModal');
    }
    
    function openEditMembersModal(periodId, periodName) {
        document.getElementById('edit_period_id').value = periodId;
        document.getElementById('editMembersPeriodName').textContent = 'Manage Members - ' + periodName;
        
        // Fetch current members for this period
        fetch('get_period_members.php?period_id=' + periodId)
            .then(response => response.json())
            .then(data => {
                const membersList = document.getElementById('editMembersList');
                membersList.innerHTML = '';
                
                // Build the members list with pre-selected ones checked
                data.allMembers.forEach(member => {
                    const isChecked = data.periodMembers.includes(parseInt(member.id));
                    membersList.innerHTML += `
                        <div class="member-checkbox-item">
                            <label>
                                <input type="checkbox" name="members[]" value="${member.id}" class="member-checkbox-edit" ${isChecked ? 'checked' : ''} onchange="updateMemberCountEdit()">
                                ${member.name}
                            </label>
                        </div>
                    `;
                });
                
                // Update the member count badge
                updateMemberCountEdit();
                
                // Update "Select All" checkbox state based on selected members
                const totalMembers = data.allMembers.length;
                const selectedMembers = data.periodMembers.length;
                const selectAllCheckbox = document.getElementById('selectAllEdit');
                selectAllCheckbox.checked = (selectedMembers === totalMembers);
                
                openModal('editMembersModal');
            });
    }
    
    function deletePeriod(id, name) {
        if (confirm('Are you sure you want to permanently delete period "' + name + '"?\n\nThis will remove ALL meals, expenses, and settlement records for this period.\n\nThis action CANNOT be undone!')) {
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
    
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>
</html>
