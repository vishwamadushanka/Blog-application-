<?php


require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $_SESSION['flash_error'] = "Invalid request token. Please try again.";
        header("Location: ../register.php");
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    
    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['flash_error'] = "All fields are required.";
        header("Location: ../register.php");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_error'] = "Please provide a valid email address.";
        header("Location: ../register.php");
        exit;
    }

    if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $_SESSION['flash_error'] = "Username must be at least 3 characters and contain only letters, numbers, and underscores.";
        header("Location: ../register.php");
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['flash_error'] = "Password must be at least 6 characters long.";
        header("Location: ../register.php");
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['flash_error'] = "Passwords do not match.";
        header("Location: ../register.php");
        exit;
    }

   
    try {
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = :username OR email = :email");
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = "Username or email is already taken.";
            header("Location: ../register.php");
            exit;
        }

        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert_stmt = $pdo->prepare("INSERT INTO user (username, email, password, role) VALUES (:username, :email, :password, 'user')");
        $insert_stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashed_password
        ]);

        $user_id = $pdo->lastInsertId();

        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'user';
        $_SESSION['flash_success'] = "Registration successful! Welcome to IN2120 Blog.";

        header("Location: ../index.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = "Database error during registration: " . $e->getMessage();
        header("Location: ../register.php");
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $_SESSION['flash_error'] = "Invalid request token. Please try again.";
        header("Location: ../login.php");
        exit;
    }

    $identity = trim($_POST['identity'] ?? ''); // username or email
    $password = $_POST['password'] ?? '';

    if (empty($identity) || empty($password)) {
        $_SESSION['flash_error'] = "Please enter your username/email and password.";
        header("Location: ../login.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username = :identity_user OR email = :identity_email");
        $stmt->execute([
            'identity_user' => $identity,
            'identity_email' => $identity
        ]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($user['username']) . "!";

            header("Location: ../index.php");
            exit;
        } else {
            $_SESSION['flash_error'] = "Invalid credentials. Please check your username/email and password.";
            header("Location: ../login.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = "Database error during login: " . $e->getMessage();
        header("Location: ../login.php");
        exit;
    }
}
