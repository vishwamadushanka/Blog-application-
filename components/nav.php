<?php
require_once __DIR__ . '/../backend/middleware.php';

$user = current_user();
?>
<nav class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="logo"><span>Blog Application</span></a>
        <ul class="nav-links">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <?php if (is_logged_in()): ?>
                <li>
                    <a href="editor.php" class="btn btn-primary btn-sm">Write Blog</a>
                </li>
                <li>
                    <div class="user-badge">
                        <span><?= htmlspecialchars($user['username']) ?></span>
                    </div>
                </li>
                <li><a href="logout.php" class="nav-link">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="nav-link">Log In</a></li>
                <li><a href="register.php" class="btn btn-primary btn-sm">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
