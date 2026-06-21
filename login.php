<?php
require_once 'config.php';
require_once 'auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header('Location: index.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo APP_VERSION; ?>">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjAgMTIwIiB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCI+CiAgPGRlZnM+CiAgICA8bGluZWFyR3JhZGllbnQgaWQ9ImJnIiB4MT0iMCUiIHkxPSIwJSIgeDI9IjEwMCUiIHkyPSIxMDAlIj4KICAgICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3R5bGU9InN0b3AtY29sb3I6IzYzNjZGMSIvPgogICAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0eWxlPSJzdG9wLWNvbG9yOiM0RjQ2RTUiLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgPC9kZWZzPgogIDwhLS0gQmFja2dyb3VuZCBjaXJjbGUgLS0+CiAgPGNpcmNsZSBjeD0iNjAiIGN5PSI2MCIgcj0iNTgiIGZpbGw9InVybCgjYmcpIi8+CiAgPCEtLSBCb3dsIGJvZHkgLS0+CiAgPHBhdGggZD0iTTMwIDY1IFEzMCA4NSA2MCA4NSBROTAgODUgOTAgNjUiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+CiAgPCEtLSBCb3dsIHJpbSAtLT4KICA8ZWxsaXBzZSBjeD0iNjAiIGN5PSI2MyIgcng9IjM0IiByeT0iNiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSI0Ii8+CiAgPCEtLSBTdGVhbSBsaW5lcyAtLT4KICA8cGF0aCBkPSJNNDUgNTAgUTQyIDQwIDQ3IDMwIiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBvcGFjaXR5PSIwLjgiLz4KICA8cGF0aCBkPSJNNjAgNDggUTYzIDM4IDU4IDI4IiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBvcGFjaXR5PSIwLjgiLz4KICA8cGF0aCBkPSJNNzUgNTAgUTc4IDQwIDczIDMwIiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBvcGFjaXR5PSIwLjgiLz4KICA8IS0tIFJpY2UgZG90cyBpbiBib3dsIC0tPgogIDxjaXJjbGUgY3g9IjUwIiBjeT0iNzIiIHI9IjIiIGZpbGw9IndoaXRlIiBvcGFjaXR5PSIwLjUiLz4KICA8Y2lyY2xlIGN4PSI2MCIgY3k9Ijc0IiByPSIyIiBmaWxsPSJ3aGl0ZSIgb3BhY2l0eT0iMC41Ii8+CiAgPGNpcmNsZSBjeD0iNzAiIGN5PSI3MiIgcj0iMiIgZmlsbD0id2hpdGUiIG9wYWNpdHk9IjAuNSIvPgogIDxjaXJjbGUgY3g9IjU1IiBjeT0iNzgiIHI9IjEuNSIgZmlsbD0id2hpdGUiIG9wYWNpdHk9IjAuNCIvPgogIDxjaXJjbGUgY3g9IjY1IiBjeT0iNzciIHI9IjEuNSIgZmlsbD0id2hpdGUiIG9wYWNpdHk9IjAuNCIvPgo8L3N2Zz4K" alt="Logo" style="width:80px;height:80px;border-radius:16px;margin-bottom:12px;">
                <h1 style="margin-bottom:4px;">Meal Manager</h1>
                <p>Admin Login</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
        </div>
    </div>
</body>
</html>

