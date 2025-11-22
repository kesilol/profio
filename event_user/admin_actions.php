<?php
session_start();
require('../connect.php');

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    die("Access denied");
}

// Блокировка пользователя
if (isset($_POST['block_user'])) {
    $user_id = $_POST['user_id'];
    $block_reason = $_POST['block_reason'];
    $block_comment = $_POST['block_comment'] ?? '';
    
    $stmt = $link->prepare("UPDATE users SET is_active = 0, blocked_at = NOW(), block_reason = ? WHERE id = ?");
    $stmt->bind_param("si", $block_reason, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Пользователь успешно заблокирован";
    } else {
        $_SESSION['error'] = "Ошибка при блокировке пользователя";
    }
}

// Разблокировка пользователя
if (isset($_GET['action']) && $_GET['action'] === 'unblock' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    $stmt = $link->prepare("UPDATE users SET is_active = 1, blocked_at = NULL, block_reason = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Пользователь успешно разблокирован";
    } else {
        $_SESSION['error'] = "Ошибка при разблокировке пользователя";
    }
}

// Сброс пароля
if (isset($_GET['action']) && $_GET['action'] === 'reset_password' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $new_password = "password123";
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $link->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Пароль успешно сброшен на: password123";
    } else {
        $_SESSION['error'] = "Ошибка при сбросе пароля";
    }
}

// Удаление пользователя
if (isset($_GET['action']) && $_GET['action'] === 'delete_user' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Проверяем, что нельзя удалить администратора
    $user_role = $link->query("SELECT role FROM users WHERE id = $user_id")->fetch_assoc()['role'];
    
    if ($user_role === 'администратор') {
        $_SESSION['error'] = "Нельзя удалить администратора";
    } else {
        // Получаем информацию о пользователе для логирования
        $user_info = $link->query("SELECT name, email FROM users WHERE id = $user_id")->fetch_assoc();
        
        // Удаляем связанные записи (если нужно)
        $link->query("DELETE FROM curator_students WHERE student_id = $user_id OR curator_id = $user_id");
        $link->query("DELETE FROM test_results WHERE user_id = $user_id");
        $link->query("DELETE FROM development_plans WHERE user_id = $user_id");
        
        $stmt = $link->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['success'] = "Пользователь успешно удален";
        } else {
            $_SESSION['error'] = "Ошибка при удалении пользователя";
        }
    }
}

// Возвращаемся на страницу пользователей
header("Location: ../index.php?page=admin-users");
exit();
?>