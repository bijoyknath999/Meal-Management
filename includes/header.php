<?php
// Get current page name for active navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <a href="index.php">
                <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjAgMTIwIiB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCI+PGRlZnM+PGxpbmVhckdyYWRpZW50IGlkPSJiZyIgeDE9IjAlIiB5MT0iMCUiIHgyPSIxMDAlIiB5Mj0iMTAwJSI+PHN0b3Agb2Zmc2V0PSIwJSIgc3R5bGU9InN0b3AtY29sb3I6IzYzNjZGMSIvPjxzdG9wIG9mZnNldD0iMTAwJSIgc3R5bGU9InN0b3AtY29sb3I6IzRGNDZFNSIvPjwvbGluZWFyR3JhZGllbnQ+PC9kZWZzPjxjaXJjbGUgY3g9IjYwIiBjeT0iNjAiIHI9IjU4IiBmaWxsPSJ1cmwoI2JnKSIvPjxwYXRoIGQ9Ik0zMCA2NSBRMzAgODUgNjAgODUgUTkwIDg1IDkwIDY1IiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPjxlbGxpcHNlIGN4PSI2MCIgY3k9IjYzIiByeD0iMzQiIHJ5PSI2IiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjQiLz48cGF0aCBkPSJNNDUgNTAgUTQyIDQwIDQ3IDMwIiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBvcGFjaXR5PSIuOCIvPjxwYXRoIGQ9Ik02MCA0OCBRNjMgMzggNTggMjgiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMi41IiBzdHJva2UtbGluZWNhcD0icm91bmQiIG9wYWNpdHk9Ii44Ii8+PHBhdGggZD0iTTc1IDUwIFE3OCA0MCA3MyAzMCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIyLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgb3BhY2l0eT0iLjgiLz48L3N2Zzg=" alt="Logo" class="logo-img">
                <span>Meal Manager</span>
            </a>
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

