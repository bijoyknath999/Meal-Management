<?php
// Get current page name for active navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <a href="index.php">
                <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjAgMTIwIiB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCI+CiAgPGRlZnM+CiAgICA8bGluZWFyR3JhZGllbnQgaWQ9ImJnIiB4MT0iMCUiIHkxPSIwJSIgeDI9IjEwMCUiIHkyPSIxMDAlIj4KICAgICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3R5bGU9InN0b3AtY29sb3I6IzYzNjZGMSIvPgogICAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0eWxlPSJzdG9wLWNvbG9yOiM0RjQ2RTUiLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgPC9kZWZzPgogIDwhLS0gQmFja2dyb3VuZCBjaXJjbGUgLS0+CiAgPGNpcmNsZSBjeD0iNjAiIGN5PSI2MCIgcj0iNTgiIGZpbGw9InVybCgjYmcpIi8+CiAgPCEtLSBCb3dsIGJvZHkgLS0+CiAgPHBhdGggZD0iTTMwIDY1IFEzMCA4NSA2MCA4NSBROTAgODUgOTAgNjUiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+CiAgPCEtLSBCb3dsIHJpbSAtLT4KICA8ZWxsaXBzZSBjeD0iNjAiIGN5PSI2MyIgcng9IjM0IiByeT0iNiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSI0Ii8+CiAgPCEtLSBTdGVhbSBsaW5lcyAtLT4KICA8cGF0aCBkPSJNNDUgNTAgUTQyIDQwIDQ3IDMwIiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBvcGFjaXR5PSIwLjgiLz4KICA8cGF0aCBkPSJNNjAgNDggUTYzIDM4IDU4IDI4IiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBvcGFjaXR5PSIwLjgiLz4KICA8cGF0aCBkPSJNNzUgNTAgUTc4IDQwIDczIDMwIiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBvcGFjaXR5PSIwLjgiLz4KICA8IS0tIFJpY2UgZG90cyBpbiBib3dsIC0tPgogIDxjaXJjbGUgY3g9IjUwIiBjeT0iNzIiIHI9IjIiIGZpbGw9IndoaXRlIiBvcGFjaXR5PSIwLjUiLz4KICA8Y2lyY2xlIGN4PSI2MCIgY3k9Ijc0IiByPSIyIiBmaWxsPSJ3aGl0ZSIgb3BhY2l0eT0iMC41Ii8+CiAgPGNpcmNsZSBjeD0iNzAiIGN5PSI3MiIgcj0iMiIgZmlsbD0id2hpdGUiIG9wYWNpdHk9IjAuNSIvPgogIDxjaXJjbGUgY3g9IjU1IiBjeT0iNzgiIHI9IjEuNSIgZmlsbD0id2hpdGUiIG9wYWNpdHk9IjAuNCIvPgogIDxjaXJjbGUgY3g9IjY1IiBjeT0iNzciIHI9IjEuNSIgZmlsbD0id2hpdGUiIG9wYWNpdHk9IjAuNCIvPgo8L3N2Zz4K" alt="Logo" class="logo-img">
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

