<?php


require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';

require_login('../login.php');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

if (!verify_csrf_token($token)) {
    $_SESSION['flash_error'] = "Security token mismatch. Please try again.";
    header("Location: ../index.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title) || empty($content)) {
        $_SESSION['flash_error'] = "Title and Content cannot be empty.";
        header("Location: ../editor.php");
        exit;
    }

    $clean_text = preg_replace('/[#*_`>~]/', '', $content);
    $excerpt = mb_substr($clean_text, 0, 180);
    if (mb_strlen($clean_text) > 180) {
        $excerpt .= '...';
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO blogPost (user_id, title, content, excerpt) VALUES (:user_id, :title, :content, :excerpt)");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt
        ]);

        $new_id = $pdo->lastInsertId();
        $_SESSION['flash_success'] = "Blog post published successfully!";
        header("Location: ../blog.php?id=" . $new_id);
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = "Error saving blog post: " . $e->getMessage();
        header("Location: ../editor.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $post_id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($post_id <= 0) {
        $_SESSION['flash_error'] = "Invalid blog post ID.";
        header("Location: ../index.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT user_id FROM blogPost WHERE id = :id");
    $stmt->execute(['id' => $post_id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        $_SESSION['flash_error'] = "Blog post not found.";
        header("Location: ../index.php");
        exit;
    }

    if (!can_modify_post($existing['user_id'])) {
        $_SESSION['flash_error'] = "Authorization denied: You can only edit your own blog posts.";
        header("Location: ../blog.php?id=" . $post_id);
        exit;
    }

    if (empty($title) || empty($content)) {
        $_SESSION['flash_error'] = "Title and Content cannot be empty.";
        header("Location: ../editor.php?id=" . $post_id);
        exit;
    }

    $clean_text = preg_replace('/[#*_`>~]/', '', $content);
    $excerpt = mb_substr($clean_text, 0, 180);
    if (mb_strlen($clean_text) > 180) {
        $excerpt .= '...';
    }

    try {
        $update_stmt = $pdo->prepare("UPDATE blogPost SET title = :title, content = :content, excerpt = :excerpt WHERE id = :id");
        $update_stmt->execute([
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'id' => $post_id
        ]);

        $_SESSION['flash_success'] = "Blog post updated successfully!";
        header("Location: ../blog.php?id=" . $post_id);
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = "Error updating blog post: " . $e->getMessage();
        header("Location: ../editor.php?id=" . $post_id);
        exit;
    }
}

if (($action === 'delete') && ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET')) {
    $post_id = (int)($_REQUEST['id'] ?? 0);

    if ($post_id <= 0) {
        $_SESSION['flash_error'] = "Invalid blog post ID.";
        header("Location: ../index.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT user_id FROM blogPost WHERE id = :id");
    $stmt->execute(['id' => $post_id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        $_SESSION['flash_error'] = "Blog post not found.";
        header("Location: ../index.php");
        exit;
    }

    if (!can_modify_post($existing['user_id'])) {
        $_SESSION['flash_error'] = "Authorization denied: You can only delete your own blog posts.";
        header("Location: ../blog.php?id=" . $post_id);
        exit;
    }

    try {
        $del_stmt = $pdo->prepare("DELETE FROM blogPost WHERE id = :id");
        $del_stmt->execute(['id' => $post_id]);

        $_SESSION['flash_success'] = "Blog post deleted successfully.";
        header("Location: ../index.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = "Error deleting blog post: " . $e->getMessage();
        header("Location: ../blog.php?id=" . $post_id);
        exit;
    }
}

header("Location: ../index.php");
exit;
