<?php
require_once __DIR__ . '/backend/middleware.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$csrf_token = generate_csrf_token();
$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - IN2120 Blog Application</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/components/nav.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="auth-wrapper">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <div class="auth-card">
                    <div class="auth-header">
                        <h1 class="auth-title">Welcome Back</h1>
                        <p class="auth-subtitle">Sign in to manage your blogs and publish content</p>
                    </div>

                    <form action="backend/auth.php" method="POST">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <div class="form-group">
                            <label for="identity" class="form-label">Username or Email</label>
                            <input type="text" id="identity" name="identity" class="form-control" required placeholder="Enter username or email">
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Sign In</button>
                    </form>

                    <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
                        Don't have an account yet? <a href="register.php" style="color: var(--secondary); font-weight: 600;">Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/components/footer.php'; ?>
</body>
</html>
