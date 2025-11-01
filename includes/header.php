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
            <a href="index.php">Dashboard</a>
            <a href="meals.php">Meals</a>
            <a href="expenses.php">Expenses</a>
            <a href="settlements.php">Settlements</a>
            <a href="report.php">Reports</a>
            <a href="members.php">Members</a>
            <a href="periods.php">Periods</a>
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

