<?php
session_start();
require('../connect.php');

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=sign-in");
    exit();
}

// Проверяем роль - только студенты могут скачивать свои отчеты
if ($_SESSION['user']['role'] !== 'студент') {
    die('Access denied. Only students can download their reports.');
}

require_once('reports_handler.php');

$user_id = $_SESSION['user']['id_user'];

try {
    downloadStudentPDF($link, $user_id);
} catch (Exception $e) {
    error_log("PDF Download Error: " . $e->getMessage());
    die("Ошибка при генерации PDF: " . $e->getMessage());
}
?>