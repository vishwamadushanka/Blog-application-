<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}


function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'user'
    ];
}


function require_login(string $redirect_url = 'login.php'): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = "You must be logged in to access that page.";
        header("Location: " . $redirect_url);
        exit;
    }
}


function can_modify_post(int $post_user_id): bool {
    if (!is_logged_in()) {
        return false;
    }
    $user = current_user();
    return ((int)$user['id'] === (int)$post_user_id) || ($user['role'] === 'admin');
}


function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}


function sanitize_input(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
