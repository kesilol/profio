<?php
session_start();
require('../connect.php');
require_once(__DIR__ . '/admin_logs_handler.php');

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    die("Access denied");
}

$admin_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;

// Блокировка пользователя
if (isset($_POST['block_user'])) {
    $user_id = $_POST['user_id'];
    $block_reason = $_POST['block_reason'];
    $block_comment = $_POST['block_comment'] ?? '';
    
    // Получаем информацию о пользователе
    $user_info = $link->query("SELECT name, email FROM users WHERE id = $user_id")->fetch_assoc();
    
    $stmt = $link->prepare("UPDATE users SET is_active = 0, blocked_at = NOW(), block_reason = ? WHERE id = ?");
    $stmt->bind_param("si", $block_reason, $user_id);
    
    if ($stmt->execute()) {
        logAdminAction($admin_id, 'Блокировка пользователя', 'user', $user_id, 
            "Заблокирован пользователь: {$user_info['name']} ({$user_info['email']}). Причина: {$block_reason}");
        $_SESSION['success'] = "Пользователь успешно заблокирован";
    } else {
        $_SESSION['error'] = "Ошибка при блокировке пользователя";
    }
}

// Разблокировка пользователя
if (isset($_GET['action']) && $_GET['action'] === 'unblock' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Получаем информацию о пользователе
    $user_info = $link->query("SELECT name, email FROM users WHERE id = $user_id")->fetch_assoc();
    
    $stmt = $link->prepare("UPDATE users SET is_active = 1, blocked_at = NULL, block_reason = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        logAdminAction($admin_id, 'Разблокировка пользователя', 'user', $user_id, 
            "Разблокирован пользователь: {$user_info['name']} ({$user_info['email']})");
        $_SESSION['success'] = "Пользователь успешно разблокирован";
    } else {
        $_SESSION['error'] = "Ошибка при разблокировке пользователя";
    }
}

// Удаление пользователя
if (isset($_GET['action']) && $_GET['action'] === 'delete_user' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Проверяем, что нельзя удалить администратора
    $user_info = $link->query("SELECT name, email, role FROM users WHERE id = $user_id")->fetch_assoc();
    
    if (!$user_info) {
        $_SESSION['error'] = "Пользователь не найден";
        header("Location: ../index.php?page=admin-users");
        exit();
    }
    
    if ($user_info['role'] === 'администратор') {
        logAdminAction($admin_id, 'Попытка удаления администратора', 'user', $user_id, 
            "Попытка удалить администратора: {$user_info['name']} ({$user_info['email']}) - ОТКАЗАНО");
        $_SESSION['error'] = "Нельзя удалить администратора";
        header("Location: ../index.php?page=admin-users");
        exit();
    }
    
    // ★★★ ПОЛНОЕ УДАЛЕНИЕ ВСЕХ СВЯЗАННЫХ ЗАПИСЕЙ ★★★
    
    // Включаем транзакцию для гарантии целостности
    $link->begin_transaction();
    
    try {
        // 1. Удаляем связи куратор-студент
        $link->query("DELETE FROM curator_students WHERE student_id = $user_id OR curator_id = $user_id");
        
        // 2. Удаляем результаты тестов
        $link->query("DELETE FROM test_results WHERE user_id = $user_id");
        
        // 3. Удаляем планы развития
        $link->query("DELETE FROM development_plans WHERE user_id = $user_id");
        
        // 4. Удаляем рекомендации
        $link->query("DELETE FROM recommendations WHERE user_id = $user_id");
        
        // 5. Удаляем обратную связь (feedback) - если есть связь с user_id
        $link->query("UPDATE feedback SET user_id = NULL WHERE user_id = $user_id");
        
        // 6. Удаляем записи из админ-логов (если есть)
        $link->query("DELETE FROM admin_logs WHERE admin_id = $user_id");
        
        // 7. Проверяем, есть ли еще какие-то таблицы с foreign key на users
        // Получаем все таблицы с внешними ключами на users
        $fk_tables = $link->query("
            SELECT DISTINCT TABLE_NAME, COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME = 'users' 
            AND REFERENCED_TABLE_SCHEMA = DATABASE()
            AND TABLE_SCHEMA = DATABASE()
        ");
        
        while ($fk = $fk_tables->fetch_assoc()) {
            $table = $fk['TABLE_NAME'];
            $column = $fk['COLUMN_NAME'];
            // Обновляем или удаляем записи в зависимости от таблицы
            if ($table === 'feedback') {
                $link->query("UPDATE $table SET $column = NULL WHERE $column = $user_id");
            } else {
                $link->query("DELETE FROM $table WHERE $column = $user_id");
            }
        }
        
        // 8. Наконец, удаляем самого пользователя
        $stmt = $link->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $link->commit();
            logAdminAction($admin_id, 'Удаление пользователя', 'user', $user_id, 
                "Удален пользователь: {$user_info['name']} ({$user_info['email']})");
            $_SESSION['success'] = "Пользователь успешно удален вместе со всеми связанными данными";
        } else {
            throw new Exception("Не удалось удалить пользователя");
        }
        
    } catch (Exception $e) {
        $link->rollback();
        $_SESSION['error'] = "Ошибка при удалении пользователя: " . $e->getMessage();
    }
}

// Возвращаемся на страницу пользователей
header("Location: ../index.php?page=admin-users");
exit();
?>