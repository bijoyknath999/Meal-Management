<?php
// Public view page - no authentication required
require_once 'config.php';
require_once 'functions.php';

$period = getActivePeriod();
$members = getAllMembers();

if (!$period) {
    die('No active period found');
}

// Get language preference (default: Bangla)
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'bn';

// Language translations
$translations = [
    'bn' => [
        'title' => 'খাবার ব্যবস্থাপনা',
        'filter_member' => 'সদস্য অনুসারে ফিল্টার করুন',
        'show_all' => 'সকল সদস্য দেখান',
        'viewing' => 'দেখছেন',
        'total_meals' => 'মোট খাবার',
        'total_expense' => 'মোট খরচ',
        'meal_rate' => 'খাবারের দর',
        'settlements' => 'হিসাব',
        'daily_meals' => 'দৈনিক খাবার',
        'expenses' => 'খরচ',
        'settlement_summary' => 'হিসাবের সারাংশ',
        'member' => 'সদস্য',
        'meals' => 'খাবার',
        'will_take' => 'পাবে',
        'will_give' => 'দিবে',
        'meal_cost' => 'খাবারের খরচ',
        'balance' => 'ব্যালেন্স',
        'daily_meal_records' => 'দৈনিক খাবারের রেকর্ড',
        'no_data' => 'কোন তথ্য পাওয়া যায়নি',
        'date' => 'তারিখ',
        'paid_by' => 'পরিশোধ করেছেন',
        'amount' => 'পরিমাণ',
        'description' => 'বিবরণ',
        'total' => 'মোট',
        'updated' => 'আপডেট',
        'print' => 'প্রিন্ট করুন',
        'expense_records' => 'খরচের রেকর্ড',
        'switch_to_english' => 'Switch to English',
        'switch_to_bangla' => 'বাংলায় দেখুন',
        'meal' => 'খাবার',
        'day_of_week' => [
            'Saturday' => 'শনিবার',
            'Sunday' => 'রবিবার',
            'Monday' => 'সোমবার',
            'Tuesday' => 'মঙ্গলবার',
            'Wednesday' => 'বুধবার',
            'Thursday' => 'বৃহস্পতিবার',
            'Friday' => 'শুক্রবার'
        ],
        'months' => [
            'Jan' => 'জানুয়ারি',
            'Feb' => 'ফেব্রুয়ারি',
            'Mar' => 'মার্চ',
            'Apr' => 'এপ্রিল',
            'May' => 'মে',
            'Jun' => 'জুন',
            'Jul' => 'জুলাই',
            'Aug' => 'আগস্ট',
            'Sep' => 'সেপ্টেম্বর',
            'Oct' => 'অক্টোবর',
            'Nov' => 'নভেম্বর',
            'Dec' => 'ডিসেম্বর'
        ]
    ],
    'en' => [
        'title' => 'Meal Management',
        'filter_member' => 'Filter by Member',
        'show_all' => 'Show All Members',
        'viewing' => 'Viewing',
        'total_meals' => 'Total Meals',
        'total_expense' => 'Total Expense',
        'meal_rate' => 'Meal Rate',
        'settlements' => 'Settlements',
        'daily_meals' => 'Daily Meals',
        'expenses' => 'Expenses',
        'settlement_summary' => 'Settlement Summary',
        'member' => 'Member',
        'meals' => 'Meals',
        'will_take' => 'Will Take',
        'will_give' => 'Will Give',
        'meal_cost' => 'Meal Cost',
        'balance' => 'Balance',
        'daily_meal_records' => 'Daily Meal Records',
        'no_data' => 'No data available',
        'date' => 'Date',
        'paid_by' => 'Paid By',
        'amount' => 'Amount',
        'description' => 'Description',
        'total' => 'TOTAL',
        'updated' => 'Updated',
        'print' => 'Print This Page',
        'expense_records' => 'Expense Records',
        'switch_to_english' => 'Switch to English',
        'switch_to_bangla' => 'বাংলায় দেখুন',
        'meal' => 'meal',
        'day_of_week' => [
            'Saturday' => 'Saturday',
            'Sunday' => 'Sunday',
            'Monday' => 'Monday',
            'Tuesday' => 'Tuesday',
            'Wednesday' => 'Wednesday',
            'Thursday' => 'Thursday',
            'Friday' => 'Friday'
        ],
        'months' => [
            'Jan' => 'Jan',
            'Feb' => 'Feb',
            'Mar' => 'Mar',
            'Apr' => 'Apr',
            'May' => 'May',
            'Jun' => 'Jun',
            'Jul' => 'Jul',
            'Aug' => 'Aug',
            'Sep' => 'Sep',
            'Oct' => 'Oct',
            'Nov' => 'Nov',
            'Dec' => 'Dec'
        ]
    ]
];

$t = $translations[$lang];

// Bangla number conversion
function toBanglaNumber($number) {
    $english = ['0','1','2','3','4','5','6','7','8','9'];
    $bangla = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    return str_replace($english, $bangla, $number);
}

// Format date based on language
function formatDateLocalized($date, $lang, $t) {
    $dayOfWeek = date('l', strtotime($date));
    $day = date('d', strtotime($date));
    $month = date('M', strtotime($date));
    $year = date('Y', strtotime($date));
    
    $dayTranslated = $t['day_of_week'][$dayOfWeek];
    $monthTranslated = $t['months'][$month];
    
    if ($lang == 'bn') {
        return $dayTranslated . ', ' . toBanglaNumber($day) . ' ' . $monthTranslated . ' ' . toBanglaNumber($year);
    } else {
        return $dayOfWeek . ', ' . $day . ' ' . $month . ' ' . $year;
    }
}

// Get selected member from query string
$selectedMemberId = isset($_GET['member']) ? intval($_GET['member']) : null;
$selectedMemberName = null;

if ($selectedMemberId) {
    foreach ($members as $member) {
        if ($member['id'] == $selectedMemberId) {
            $selectedMemberName = $member['name'];
            break;
        }
    }
}

// Calculate settlements
calculateSettlements($period['id']);
$settlements = getSettlements($period['id']);
$expenses = getExpensesForPeriod($period['id']);
$meals = getAllMealsForPeriod($period['id']);

// Group meals by date
$mealsByDate = [];
foreach ($meals as $meal) {
    $date = $meal['meal_date'];
    if (!isset($mealsByDate[$date])) {
        $mealsByDate[$date] = [];
    }
    $mealsByDate[$date][] = $meal;
}
ksort($mealsByDate);

// Filter data if member is selected
if ($selectedMemberId) {
    $settlements = array_filter($settlements, function($s) use ($selectedMemberId) {
        return $s['member_id'] == $selectedMemberId;
    });
    
    $expenses = array_filter($expenses, function($e) use ($selectedMemberId) {
        return $e['member_id'] == $selectedMemberId;
    });
    
    foreach ($mealsByDate as $date => $dateMeals) {
        $mealsByDate[$date] = array_filter($dateMeals, function($m) use ($selectedMemberId) {
            return $m['member_id'] == $selectedMemberId;
        });
        if (empty($mealsByDate[$date])) {
            unset($mealsByDate[$date]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang == 'bn' ? 'bn' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?> - <?php echo htmlspecialchars($period['period_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body.bangla {
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        .lang-switcher {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .lang-btn {
            background: white;
            border: 2px solid #4CAF50;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            color: #4CAF50;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .lang-btn:hover {
            background: #4CAF50;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .filter-section select {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-top: 10px;
            font-family: inherit;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab {
            padding: 12px 24px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        .tab:hover {
            background: #f5f5f5;
        }
        .tab.active {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .meals-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .meals-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .meals-table th,
        .meals-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .meals-table th {
            background: #4CAF50;
            color: white;
            font-weight: 600;
        }
        .meals-table tr:hover {
            background: #f5f5f5;
        }
        .meals-table tr:last-child td {
            border-bottom: none;
        }
        .meal-count {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }
        
        .daily-meals-container {
            display: grid;
            gap: 20px;
        }
        .day-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .day-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .day-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .day-date {
            font-size: 1.1rem;
            font-weight: 600;
        }
        .day-total {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .day-meals {
            padding: 15px 20px;
        }
        .meal-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .meal-row:last-child {
            border-bottom: none;
        }
        .meal-member {
            font-size: 1rem;
            color: #333;
        }
        .meal-badge {
            background: #4CAF50;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1rem;
        }
        .expense-amount {
            color: #4CAF50;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        @media (max-width: 768px) {
            .lang-switcher {
                top: 10px;
                right: 10px;
            }
            .lang-btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }
        @media print {
            .filter-section, .tabs, .public-header a, .public-footer, .lang-switcher { display: none; }
        }
    </style>
</head>
<body class="public-view <?php echo $lang == 'bn' ? 'bangla' : ''; ?>">
    <!-- Language Switcher -->
    <div class="lang-switcher">
        <?php if ($lang == 'bn'): ?>
            <button class="lang-btn" onclick="switchLanguage('en')">
                <?php echo $t['switch_to_english']; ?>
            </button>
        <?php else: ?>
            <button class="lang-btn" onclick="switchLanguage('bn')">
                <?php echo $t['switch_to_bangla']; ?>
            </button>
        <?php endif; ?>
    </div>
    
    <div class="public-header">
        <h1>🍽️ <?php echo $t['title']; ?></h1>
        <h2><?php echo htmlspecialchars($period['period_name']); ?></h2>
        <?php if ($selectedMemberName): ?>
            <p style="margin-top: 10px; font-size: 1.1rem;">
                <?php echo $t['viewing']; ?>: <strong><?php echo htmlspecialchars($selectedMemberName); ?></strong>
                <a href="view.php?lang=<?php echo $lang; ?>" style="margin-left: 15px; color: white; text-decoration: underline;">
                    <?php echo $t['show_all']; ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <!-- Member Filter -->
        <div class="filter-section">
            <h3>🔍 <?php echo $t['filter_member']; ?></h3>
            <select id="memberFilter" onchange="filterByMember(this.value)">
                <option value="">-- <?php echo $t['show_all']; ?> --</option>
                <?php foreach ($members as $member): ?>
                    <option value="<?php echo $member['id']; ?>" <?php echo ($selectedMemberId == $member['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($member['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Period Stats -->
        <div class="public-stats">
            <div class="stat-card">
                <div class="stat-icon">🍽️</div>
                <div class="stat-info">
                    <h3><?php echo $lang == 'bn' ? toBanglaNumber($period['total_meals']) : $period['total_meals']; ?></h3>
                    <p><?php echo $t['total_meals']; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3>৳<?php echo $lang == 'bn' ? toBanglaNumber(formatCurrency($period['total_expense'])) : formatCurrency($period['total_expense']); ?></h3>
                    <p><?php echo $t['total_expense']; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <h3>৳<?php echo $lang == 'bn' ? toBanglaNumber(formatCurrency($period['meal_rate'])) : formatCurrency($period['meal_rate']); ?></h3>
                    <p><?php echo $t['meal_rate']; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="showTab('settlements')">💰 <?php echo $t['settlements']; ?></div>
            <div class="tab" onclick="showTab('meals')">🍽️ <?php echo $t['daily_meals']; ?></div>
            <div class="tab" onclick="showTab('expenses')">💵 <?php echo $t['expenses']; ?></div>
        </div>
        
        <!-- Tab Content: Settlements -->
        <div id="tab-settlements" class="tab-content active">
            <div class="settlements-section">
                <h2><?php echo $t['settlement_summary']; ?></h2>
                
                <?php if (empty($settlements)): ?>
                    <div class="no-data"><?php echo $t['no_data']; ?></div>
                <?php else: ?>
                    <div class="settlements-grid">
                        <?php foreach ($settlements as $settlement): ?>
                            <div class="settlement-card <?php echo $settlement['status']; ?>">
                                <div class="member-name"><?php echo htmlspecialchars($settlement['member_name']); ?></div>
                                
                                <div class="settlement-details">
                                    <div class="detail-row">
                                        <span><?php echo $t['total_meals']; ?>:</span>
                                        <strong><?php echo $lang == 'bn' ? toBanglaNumber($settlement['total_meals']) : $settlement['total_meals']; ?></strong>
                                    </div>
                                    <div class="detail-row">
                                        <span><?php echo $t['total_expense']; ?>:</span>
                                        <strong>৳<?php echo $lang == 'bn' ? toBanglaNumber(formatCurrency($settlement['total_expense'])) : formatCurrency($settlement['total_expense']); ?></strong>
                                    </div>
                                    <div class="detail-row">
                                        <span><?php echo $t['meal_cost']; ?>:</span>
                                        <strong>৳<?php echo $lang == 'bn' ? toBanglaNumber(formatCurrency($settlement['meal_cost'])) : formatCurrency($settlement['meal_cost']); ?></strong>
                                    </div>
                                    <div class="detail-row balance-row">
                                        <span><?php echo $t['balance']; ?>:</span>
                                        <strong class="balance-amount <?php echo $settlement['status']; ?>">
                                            ৳<?php echo $lang == 'bn' ? toBanglaNumber(formatCurrency(abs($settlement['balance']))) : formatCurrency(abs($settlement['balance'])); ?>
                                            <?php if ($settlement['status'] === 'credit'): ?>
                                                <span class="status-label">(<?php echo $t['will_take']; ?>)</span>
                                            <?php elseif ($settlement['status'] === 'due'): ?>
                                                <span class="status-label">(<?php echo $t['will_give']; ?>)</span>
                                            <?php endif; ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tab Content: Daily Meals -->
        <div id="tab-meals" class="tab-content">
            <h2>📅 <?php echo $t['daily_meal_records']; ?></h2>
            
            <?php if (empty($mealsByDate)): ?>
                <div class="no-data"><?php echo $t['no_data']; ?></div>
            <?php else: ?>
                <div class="daily-meals-container">
                    <?php foreach ($mealsByDate as $date => $dateMeals): ?>
                        <?php 
                        $dateTotal = 0;
                        $mealsWithCount = [];
                        foreach ($dateMeals as $meal) {
                            if ($meal['meal_count'] > 0) {
                                $dateTotal += $meal['meal_count'];
                                $mealsWithCount[] = $meal;
                            }
                        }
                        ?>
                        <?php if ($dateTotal > 0): ?>
                            <div class="day-card">
                                <div class="day-header">
                                    <span class="day-date">
                                        <?php echo formatDateLocalized($date, $lang, $t); ?>
                                    </span>
                                    <span class="day-total">
                                        <?php 
                                        $mealText = $lang == 'bn' ? toBanglaNumber($dateTotal) . ' ' . $t['meal'] : $dateTotal . ' ' . $t['meal'];
                                        if ($dateTotal != 1) $mealText .= $lang == 'bn' ? '' : 's';
                                        echo $mealText;
                                        ?>
                                    </span>
                                </div>
                                <div class="day-meals">
                                    <?php foreach ($mealsWithCount as $meal): ?>
                                        <div class="meal-row">
                                            <span class="meal-member">
                                                👤 <?php echo htmlspecialchars($meal['member_name']); ?>
                                            </span>
                                            <span class="meal-badge">
                                                <?php echo $lang == 'bn' ? toBanglaNumber($meal['meal_count']) : $meal['meal_count']; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Tab Content: Expenses -->
        <div id="tab-expenses" class="tab-content">
            <h2>💵 <?php echo $t['expense_records']; ?></h2>
            
            <?php if (empty($expenses)): ?>
                <div class="no-data"><?php echo $t['no_data']; ?></div>
            <?php else: ?>
                <div class="meals-table">
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo $t['date']; ?></th>
                                <th><?php echo $t['paid_by']; ?></th>
                                <th><?php echo $t['amount']; ?></th>
                                <th><?php echo $t['description']; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalExpense = 0;
                            foreach ($expenses as $expense): 
                                $totalExpense += $expense['amount'];
                            ?>
                                <tr>
                                    <td><?php echo formatDate($expense['expense_date']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['member_name']); ?></td>
                                    <td class="expense-amount">৳<?php echo $lang == 'bn' ? toBanglaNumber(formatCurrency($expense['amount'])) : formatCurrency($expense['amount']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background: #f5f5f5; font-weight: bold;">
                                <td colspan="2"><?php echo $t['total']; ?></td>
                                <td class="expense-amount">৳<?php echo $lang == 'bn' ? toBanglaNumber(formatCurrency($totalExpense)) : formatCurrency($totalExpense); ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="public-footer">
        <p><?php echo $t['updated']; ?>: <?php echo date('d M Y, h:i A'); ?></p>
        <p style="margin-top: 10px;">
            <button onclick="window.print()" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-family: inherit;">
                🖨️ <?php echo $t['print']; ?>
            </button>
        </p>
    </div>
    
    <script>
    function showTab(tabName) {
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(tab => tab.classList.remove('active'));
        
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        document.getElementById('tab-' + tabName).classList.add('active');
        event.target.classList.add('active');
    }
    
    function filterByMember(memberId) {
        const currentLang = '<?php echo $lang; ?>';
        if (memberId) {
            window.location.href = 'view.php?lang=' + currentLang + '&member=' + memberId;
        } else {
            window.location.href = 'view.php?lang=' + currentLang;
        }
    }
    
    function switchLanguage(lang) {
        const urlParams = new URLSearchParams(window.location.search);
        const member = urlParams.get('member');
        let url = 'view.php?lang=' + lang;
        if (member) {
            url += '&member=' + member;
        }
        window.location.href = url;
    }
    
    // Store language preference
    const currentLang = '<?php echo $lang; ?>';
    localStorage.setItem('preferredLanguage', currentLang);
    </script>
</body>
</html>

