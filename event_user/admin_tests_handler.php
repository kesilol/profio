<?php
session_start();
require('../connect.php');
require_once(__DIR__ . '/admin_logs_handler.php');

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    die("Access denied");
}

$admin_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;

// Обработка тестов - ТОЛЬКО РЕДАКТИРОВАНИЕ
if (isset($_POST['title']) && isset($_POST['description']) && isset($_POST['test_type_id'])) {
    // Проверяем, что есть test_id - это обязательно для редактирования
    if (isset($_POST['test_id']) && !empty($_POST['test_id'])) {
        // Редактирование теста
        $id = $_POST['test_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $test_type_id = $_POST['test_type_id'];

        $stmt = $link->prepare("UPDATE tests SET title=?, description=?, test_type_id=? WHERE id=?");
        $stmt->bind_param("ssii", $title, $description, $test_type_id, $id);

        if ($stmt->execute()) {
            logAdminAction($admin_id, 'Обновление теста', 'test', $id, 
                "Обновлен тест: {$title}");
            $_SESSION['success'] = "Тест успешно обновлен";
        } else {
            $_SESSION['error'] = "Ошибка при обновлении теста: " . $stmt->error;
        }
    } else {
        // Если нет test_id - это попытка добавления, блокируем
        logAdminAction($admin_id, 'Попытка создания теста', 'test', null, 
            "Попытка создать новый тест: {$title} - ОТКАЗАНО (создание тестов запрещено)");
        $_SESSION['error'] = "Добавление новых тестов запрещено. Вы можете только редактировать существующие тесты.";
    }
    header("Location: ../index.php?page=admin-tests");
    exit();
}

// Удаление теста
if (isset($_GET['delete_test'])) {
    $id = $_GET['delete_test'];
    
    // Получаем информацию о тесте
    $test_info = $link->query("SELECT title FROM tests WHERE id = $id")->fetch_assoc();

    // Проверяем, есть ли связанные записи
    $check_stmt = $link->prepare("SELECT COUNT(*) as count FROM test_results WHERE test_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        logAdminAction($admin_id, 'Попытка удаления теста', 'test', $id, 
            "Попытка удалить тест: {$test_info['title']} - ОТКАЗАНО (есть результаты тестирования)");
        $_SESSION['error'] = "Невозможно удалить тест, так как с ним связаны результаты тестирования";
    } else {
        // Удаляем связанные записи (вопросы и ответы удалятся каскадно)
        $stmt = $link->prepare("DELETE FROM tests WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            logAdminAction($admin_id, 'Удаление теста', 'test', $id, 
                "Удален тест: {$test_info['title']}");
            $_SESSION['success'] = "Тест успешно удален";
        } else {
            $_SESSION['error'] = "Ошибка при удалении теста";
        }
    }
    header("Location: ../index.php?page=admin-tests");
    exit();
}
?>