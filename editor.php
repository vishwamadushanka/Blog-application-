<?php
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/backend/middleware.php';

require_login('login.php');

$csrf_token = generate_csrf_token();
$post_id = (int)($_GET['id'] ?? 0);
$is_editing = $post_id > 0;

$title = '';
$content = '';
$post = null;

$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

if ($is_editing) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM blogPost WHERE id = :id");
        $stmt->execute(['id' => $post_id]);
        $post = $stmt->fetch();

        if (!$post) {
            $_SESSION['flash_error'] = "Blog post not found.";
            header("Location: index.php");
            exit;
        }

        if (!can_modify_post($post['user_id'])) {
            $_SESSION['flash_error'] = "Authorization denied: You cannot edit blog posts created by other users.";
            header("Location: blog.php?id=" . $post_id);
            exit;
        }

        $title = $post['title'];
        $content = $post['content'];
    } catch (PDOException $e) {
        $error = "Error loading blog post: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_editing ? 'Edit Blog Post' : 'Create New Blog Post' ?> - IN2120</title>
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

            <div class="editor-container">
                <form action="backend/post_actions.php" method="POST" id="editor-form">
                    <input type="hidden" name="action" value="<?= $is_editing ? 'update' : 'create' ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <?php if ($is_editing): ?>
                        <input type="hidden" name="id" value="<?= $post_id ?>">
                    <?php endif; ?>

                    <div class="editor-header">
                        <h1 style="font-size: 1.75rem; font-weight: 800;">
                            <?= $is_editing ? 'Edit Blog Post' : 'Write New Blog Post' ?>
                        </h1>
                        <div style="display: flex; gap: 0.75rem;">
                            <a href="<?= $is_editing ? 'blog.php?id=' . $post_id : 'index.php' ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <?= $is_editing ? 'Update Blog Post' : 'Publish Article' ?>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="title" class="form-label">Blog Title</label>
                        <input type="text" id="title" name="title" class="form-control" required placeholder="Enter an engaging blog title..." value="<?= htmlspecialchars($title) ?>">
                    </div>

                    <div style="margin-bottom: 0.75rem; display: flex; gap: 0.4rem; flex-wrap: wrap; background: var(--bg-surface); padding: 0.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="insertFormat('**', '**')"><strong>B</strong></button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="insertFormat('*', '*')"><em>I</em></button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="insertFormat('# ', '')">H1</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="insertFormat('## ', '')">H2</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="insertFormat('```javascript\n', '\n```')">Code</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="insertFormat('> ', '')">Quote</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="insertFormat('- ', '')">List</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="insertFormat('[', '](https://)')">Link</button>
                    </div>

                    <div class="editor-grid">
                        <div class="editor-panel">
                            <div class="panel-title">Editor</div>
                            <textarea id="content" name="content" class="form-control" style="flex: 1; min-height: 420px; font-family: var(--font-mono); font-size: 0.9rem;" required placeholder="Write your blog post content using Markdown syntax..."><?= htmlspecialchars($content) ?></textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/components/footer.php'; ?>

    <script>
        const contentInput = document.getElementById('content');
        const previewArea = document.getElementById('preview');

        function updatePreview() {
            const rawText = contentInput.value;
            if (typeof marked !== 'undefined') {
                previewArea.innerHTML = rawText.trim() ? marked.parse(rawText) : '<p style="color: var(--text-muted); font-style: italic;">Live preview will appear here as you type...</p>';
            } else {
                previewArea.innerHTML = rawText.replace(/\n/g, '<br>');
            }
        }

        contentInput.addEventListener('input', updatePreview);
        updatePreview();

        function insertFormat(prefix, suffix) {
            const start = contentInput.selectionStart;
            const end = contentInput.selectionEnd;
            const text = contentInput.value;
            const selectedText = text.substring(start, end);
            const replacement = prefix + selectedText + suffix;

            contentInput.value = text.substring(0, start) + replacement + text.substring(end);
            contentInput.focus();
            contentInput.setSelectionRange(start + prefix.length, end + prefix.length);
            
        }
    </script>
</body>
</html>
