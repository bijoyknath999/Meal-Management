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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['meal_date'] ?? '';
    
    if ($date) {
        $members = getPeriodMembers($period['id']);
        foreach ($members as $member) {
            $mealCount = intval($_POST['meal_' . $member['id']] ?? 0);
            saveDailyMeal($period['id'], $member['id'], $date, $mealCount);
        }
        
        // Recalculate settlements
        calculateSettlements($period['id']);
        
        $success = "Meals saved successfully!";
    }
}

// Get selected date (default to today)
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$meals = getMealsForDate($period['id'], $selectedDate);

// Create a map of meal counts by member ID
$mealsByMember = [];
foreach ($meals as $meal) {
    $mealsByMember[$meal['member_id']] = $meal['meal_count'];
}

$members = getPeriodMembers($period['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Meals - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Daily Meals</h1>
            <h3><?php echo htmlspecialchars($period['period_name']); ?></h3>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="date-selector">
            <label for="meal_date">Select Date:</label>
            <input type="date" id="meal_date" value="<?php echo $selectedDate; ?>" 
                   min="<?php echo $period['start_date']; ?>" 
                   max="<?php echo $period['end_date']; ?>"
                   onchange="location.href='meals.php?date=' + this.value">
        </div>
        
        <form method="POST" action="" class="meals-form">
            <input type="hidden" name="meal_date" value="<?php echo $selectedDate; ?>">
            
            <div class="meals-grid">
                <?php foreach ($members as $member): ?>
                    <div class="meal-card">
                        <div class="meal-header">
                            <span class="member-icon">👤</span>
                            <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                        </div>
                        <div class="meal-counter">
                            <button type="button" class="counter-btn" onclick="decrementMeal(<?php echo $member['id']; ?>)">-</button>
                            <input type="number" 
                                   id="meal_<?php echo $member['id']; ?>" 
                                   name="meal_<?php echo $member['id']; ?>" 
                                   value="<?php echo $mealsByMember[$member['id']] ?? 0; ?>" 
                                   min="0" 
                                   max="10"
                                   class="meal-input">
                            <button type="button" class="counter-btn" onclick="incrementMeal(<?php echo $member['id']; ?>)">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">💾 Save Meals</button>
            </div>
        </form>
        
        <div class="quick-actions-buttons">
            <button class="btn btn-secondary" onclick="setAllMeals(1)">Set All to 1</button>
            <button class="btn btn-secondary" onclick="setAllMeals(2)">Set All to 2</button>
            <button class="btn btn-secondary" onclick="setAllMeals(3)">Set All to 3</button>
            <button class="btn btn-secondary" onclick="setAllMeals(0)">Clear All</button>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    function incrementMeal(memberId) {
        const input = document.getElementById('meal_' + memberId);
        const currentValue = parseInt(input.value) || 0;
        if (currentValue < 10) {
            input.value = currentValue + 1;
        }
    }
    
    function decrementMeal(memberId) {
        const input = document.getElementById('meal_' + memberId);
        const currentValue = parseInt(input.value) || 0;
        if (currentValue > 0) {
            input.value = currentValue - 1;
        }
    }
    
    function setAllMeals(value) {
        const inputs = document.querySelectorAll('.meal-input');
        inputs.forEach(input => {
            input.value = value;
        });
    }
    </script>
</body>
</html>

