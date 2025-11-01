<?php
// Get current page name for active navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <a href="index.php">🍽️ Meal Manager</a>
        </div>
        
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <nav class="main-nav" id="mainNav">
            <a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Dashboard</a>
            <a href="meals.php" class="<?php echo ($currentPage == 'meals.php') ? 'active' : ''; ?>">Meals</a>
            <a href="expenses.php" class="<?php echo ($currentPage == 'expenses.php') ? 'active' : ''; ?>">Expenses</a>
            <a href="settlements.php" class="<?php echo ($currentPage == 'settlements.php') ? 'active' : ''; ?>">Settlements</a>
            <a href="report.php" class="<?php echo ($currentPage == 'report.php') ? 'active' : ''; ?>">Reports</a>
            <a href="members.php" class="<?php echo ($currentPage == 'members.php') ? 'active' : ''; ?>">Members</a>
            <a href="periods.php" class="<?php echo ($currentPage == 'periods.php') ? 'active' : ''; ?>">Periods</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>
    </div>
</header>

<script>
function toggleMobileMenu() {
    const nav = document.getElementById('mainNav');
    nav.classList.toggle('active');
}
</script>

