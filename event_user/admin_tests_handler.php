<?php
session_start();
require('../connect.php');

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    die("Access denied");
}

// Обработка тестов
if (isset($_POST['title']) && isset($_POST['description']) && isset($_POST['test_type_id'])) {
    // Если есть test_id - это редактирование, иначе - добавление
    if (isset($_POST['test_id']) && !empty($_POST['test_id'])) {
        // Редактирование теста
        $id = $_POST['test_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $test_type_id = $_POST['test_type_id'];

        $stmt = $link->prepare("UPDATE tests SET title=?, description=?, test_type_id=? WHERE id=?");
        $stmt->bind_param("ssii", $title, $description, $test_type_id, $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Тест успешно обновлен";
        } else {
            $_SESSION['error'] = "Ошибка при обновлении теста: " . $stmt->error;
        }
    } else {
        // Добавление теста
        $title = $_POST['title'];
        $description = $_POST['description'];
        $test_type_id = $_POST['test_type_id'];

        $stmt = $link->prepare("INSERT INTO tests (title, description, test_type_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $description, $test_type_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Тест успешно добавлен";
        } else {
            $_SESSION['error'] = "Ошибка при добавлении теста: " . $stmt->error;
        }
    }
    header("Location: ../index.php?page=admin-tests");
    exit();
}

// Удаление теста
if (isset($_GET['delete_test'])) {
    $id = $_GET['delete_test'];

    // Проверяем, есть ли связанные записи
    $check_stmt = $link->prepare("SELECT COUNT(*) as count FROM test_results WHERE test_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        $_SESSION['error'] = "Невозможно удалить тест, так как с ним связаны результаты тестирования";
    } else {
        // Удаляем связанные записи (вопросы и ответы удалятся каскадно)
        $stmt = $link->prepare("DELETE FROM tests WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Тест успешно удален";
        } else {
            $_SESSION['error'] = "Ошибка при удалении теста";
        }
    }
    header("Location: ../index.php?page=admin-tests");
    exit();
}
?>