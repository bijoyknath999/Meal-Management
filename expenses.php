<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

requireLogin();

$period = getActivePeriod();

if (!$period) {
    header('Location: periods.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $memberId = intval($_POST['member_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $date = $_POST['expense_date'] ?? '';
        $description = trim($_POST['description'] ?? '');
        
        if ($memberId && $amount > 0 && $date) {
            if (addExpense($period['id'], $memberId, $amount, $date, $description)) {
                calculateSettlements($period['id']);
                $_SESSION['success'] = "Expense added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add expense.";
            }
        }
        header('Location: expenses.php');
        exit();
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $memberId = intval($_POST['member_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $date = $_POST['expense_date'] ?? '';
        $description = trim($_POST['description'] ?? '');
        
        if ($id && $memberId && $amount > 0 && $date) {
            if (updateExpense($id, $memberId, $amount, $date, $description)) {
                calculateSettlements($period['id']);
                $_SESSION['success'] = "Expense updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update expense.";
            }
        }
        header('Location: expenses.php');
        exit();
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id) {
            if (deleteExpense($id)) {
                calculateSettlements($period['id']);
                $_SESSION['success'] = "Expense deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete expense.";
            }
        }
        header('Location: expenses.php');
        exit();
    }
}

// Retrieve flash messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$expenses = getExpensesForPeriod($period['id']);
$members = getPeriodMembers($period['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo APP_VERSION; ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Expense Management</h1>
            <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Expense</button>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="expense-summary">
            <h3>Period: <?php echo htmlspecialchars($period['period_name']); ?></h3>
            <p>Total Expense: ৳<?php echo formatCurrency($period['total_expense']); ?></p>
        </div>
        
        <div class="expenses-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Member</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No expenses recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?php echo formatDate($expense['expense_date']); ?></td>
                                <td><?php echo htmlspecialchars($expense['member_name']); ?></td>
                                <td class="amount">৳<?php echo formatCurrency($expense['amount']); ?></td>
                                <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                <td class="actions">
                                    <button class="btn btn-sm btn-secondary" onclick='editExpense(<?php echo json_encode($expense); ?>)'>Edit</button>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $expense['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Expense Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Expense</h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="member_id">Paid By *</label>
                    <select id="member_id" name="member_id" required>
                        <option value="">Select Member</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount">Amount (৳) *</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="expense_date">Date *</label>
                    <input type="date" id="expense_date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Expense</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Expense Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Expense</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_member_id">Paid By *</label>
                    <select id="edit_member_id" name="member_id" required>
                        <option value="">Select Member</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_amount">Amount (৳) *</label>
                    <input type="number" id="edit_amount" name="amount" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_expense_date">Date *</label>
                    <input type="date" id="edit_expense_date" name="expense_date" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Expense</button>
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
    
    function editExpense(expense) {
        document.getElementById('edit_id').value = expense.id;
        document.getElementById('edit_member_id').value = expense.member_id;
        document.getElementById('edit_amount').value = expense.amount;
        document.getElementById('edit_expense_date').value = expense.expense_date;
        document.getElementById('edit_description').value = expense.description || '';
        openModal('editModal');
    }
    
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>
</html>

