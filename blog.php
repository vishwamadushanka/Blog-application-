<?php
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/middleware.php';

$post_id = (int)($_GET['id'] ?? 0);
$csrf_token = generate_csrf_token();

$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

if ($post_id <= 0) {
    $_SESSION['flash_error'] = "Invalid blog post ID requested.";
    header("Location: index.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, u.username, u.email 
        FROM blogPost b 
        JOIN user u ON b.user_id = u.id 
        WHERE b.id = :id
    ");
    $stmt->execute(['id' => $post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        $_SESSION['flash_error'] = "Blog post not found or has been deleted.";
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database query error: " . htmlspecialchars($e->getMessage()));
}

$is_owner = can_modify_post($post['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> - IN2120 Blog Application</title>
    <link rel="stylesheet" href="css/style.css">
    
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/components/nav.php'; ?>

    <main class="main-content">
        <div class="container">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="single-blog-wrapper">
                <a href="index.php" class="btn btn-secondary btn-sm" style="margin-bottom: 1.5rem;">&larr; Back to Articles</a>

                <header class="single-blog-header">
                    <h1 class="single-blog-title"><?= htmlspecialchars($post['title']) ?></h1>
                    
                    <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                        <div class="author-card">
                            <span class="user-avatar"><?= strtoupper(substr($post['username'], 0, 1)) ?></span>
                            <span>Written by <strong>@<?= htmlspecialchars($post['username']) ?></strong></span>
                        </div>
                        <span style="color: var(--text-muted); font-size: 0.9rem;">
                            Published on <?= date('F j, Y', strtotime($post['created_at'])) ?>
                            <?php if ($post['updated_at'] !== $post['created_at']): ?>
                                (Updated <?= date('M j, Y', strtotime($post['updated_at'])) ?>)
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if ($is_owner): ?>
                        <div style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 0.75rem;">
                            <a href="editor.php?id=<?= $post['id'] ?>" class="btn btn-secondary btn-sm">Edit Blog Post</a>
                            <form action="backend/post_actions.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this blog post?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete Blog Post</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </header>

                <article class="single-blog-content">
                    <div id="markdown-render-target" class="markdown-body"></div>
                    <textarea id="raw-markdown" style="display: none;"><?= htmlspecialchars($post['content']) ?></textarea>
                </article>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/components/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rawContent = document.getElementById('raw-markdown').value;
            const target = document.getElementById('markdown-render-target');
            
            if (typeof marked !== 'undefined') {
                target.innerHTML = marked.parse(rawContent);
            } else {
                target.innerHTML = rawContent.replace(/\n/g, '<br>');
            }
        });
    </script>
</body>
</html>
