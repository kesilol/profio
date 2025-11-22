<?php
session_start();
require('../connect.php'); // Добавлена ../ для перехода на уровень выше

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'куратор') {
    header("Location: ../index.php?page=sign-in");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $curator_id = $_SESSION['user']['id_user'];
    $student_id = intval($_POST['student_id']);
    
    // Получаем имя студента для сообщения
    $name_query = $link->prepare("SELECT name FROM users WHERE id = ?");
    $name_query->bind_param("i", $student_id);
    $name_query->execute();
    $name_result = $name_query->get_result();
    $student_name = "Студент";
    if ($name_result->num_rows > 0) {
        $student = $name_result->fetch_assoc();
        $student_name = $student['name'];
    }
    
    $delete_query = $link->prepare("DELETE FROM curator_students WHERE curator_id = ? AND student_id = ?");
    $delete_query->bind_param("ii", $curator_id, $student_id);
    
    if ($delete_query->execute()) {
        $_SESSION['success'] = "Студент {$student_name} удален из списка";
    } else {
        $_SESSION['error'] = "Ошибка при удалении студента: " . $link->error;
    }
    
    header("Location: ../index.php?page=manage-students");
    exit();
}
?>