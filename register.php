<?php
// register.php - User registration view
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
    <title>Register - IN2120 Blog Application</title>
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
                        <h1 class="auth-title">Create Account</h1>
                        <p class="auth-subtitle">Join IN2120 Blog platform and start writing posts</p>
                    </div>

                    <form action="backend/auth.php" method="POST">
                        <input type="hidden" name="action" value="register">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-control" required placeholder="Choose a unique username">
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required placeholder="e.g. user@example.com">
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required placeholder="At least 6 characters">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Re-enter password">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Create Account</button>
                    </form>

                    <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
                        Already registered? <a href="login.php" style="color: var(--secondary); font-weight: 600;">Log in here</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/components/footer.php'; ?>
</body>
</html>
