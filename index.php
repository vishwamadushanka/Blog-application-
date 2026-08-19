<?php

require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/middleware.php';

$search = trim($_GET['search'] ?? '');
$csrf_token = generate_csrf_token();

$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);


try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT b.*, u.username, u.email 
            FROM blogPost b
            JOIN user u ON b.user_id = u.id
            WHERE b.title LIKE :search_title OR b.content LIKE :search_content
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([
            'search_title' => "%{$search}%",
            'search_content' => "%{$search}%"
        ]);
    } else {
        $stmt = $pdo->query("
            SELECT b.*, u.username, u.email 
            FROM blogPost b
            JOIN user u ON b.user_id = u.id
            ORDER BY b.created_at DESC
        ");
    }
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $posts = [];
    $error = "Failed to load blog posts: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/components/nav.php'; ?>

    <section class="hero">
        <div class="container">
            <h1 class="hero-title">Discover. Read. Share.</h1>
            <p class="hero-subtitle">Discover the latest posts, read articles from different authors, and share your thoughts with the community. Whether you're looking for knowledge, ideas, or inspiration, you'll find something interesting here.</p>

            <form action="index.php" method="GET" class="search-container"> 
                <input type="text" name="search" class="search-input" placeholder="Search blog posts by title or keyword..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>
    </section>

    <main class="main-content">
        <div class="container">

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.5rem; font-weight: 700;">
                    <?= !empty($search) ? 'Search Results for "' . htmlspecialchars($search) . '"' : 'Latest Articles' ?>
                </h2>
            </div>

            <?php if (empty($posts)): ?>
                <div style="text-align: center; padding: 4rem 1rem; background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-light);">
                    
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">No Blog Posts Found</h3>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Publish your own articals here</p>
                    <?php if (is_logged_in()): ?>
                        <a href="editor.php" class="btn btn-primary">Create Blog Post</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Register to Create Post</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                        <article class="post-card">
                            <div>
                                <div class="post-meta">
                                    <span class="post-author">@<?= htmlspecialchars($post['username']) ?></span>
                                    <span>&bull;</span>
                                    <span><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                                </div>
                                <h3 class="post-card-title">
                                    <a href="blog.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a>
                                </h3>
                                <p class="post-excerpt">
                                    <?= htmlspecialchars($post['excerpt'] ?: mb_substr(strip_tags($post['content']), 0, 150) . '...') ?>
                                </p>
                            </div>

                            <div class="post-card-footer">
                                <a href="blog.php?id=<?= $post['id'] ?>" class="btn btn-secondary btn-sm">Read Article </a>

                                <?php if (can_modify_post($post['user_id'])): ?>
                                    <div class="owner-actions">
                                        <a href="editor.php?id=<?= $post['id'] ?>" class="btn btn-secondary btn-sm" title="Edit your post">Edit</a>
                                        <form action="backend/post_actions.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/components/footer.php'; ?>
</body>
</html>
