<?php
session_start();
require('../connect.php');

// Устанавливаем заголовок для JSON ответа
header('Content-Type: application/json');

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

// Добавление связи профессии с компанией
if (isset($_POST['add_company_connection'])) {
    $profession_id = intval($_POST['profession_id']);
    $company_id = intval($_POST['company_id']);
    $position_name = trim($_POST['position_name']);
    $experience_level = $_POST['experience_level'];
    
    // Проверяем, не существует ли уже такая связь
    $check_stmt = $link->prepare("SELECT id FROM profession_companies WHERE profession_id = ? AND company_id = ? AND position_name = ?");
    $check_stmt->bind_param("iis", $profession_id, $company_id, $position_name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Такая связь уже существует']);
        exit();
    }
    
    $stmt = $link->prepare("INSERT INTO profession_companies (profession_id, company_id, position_name, experience_level) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $profession_id, $company_id, $position_name, $experience_level);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Связь с компанией добавлена']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ошибка при добавлении связи: ' . $stmt->error]);
    }
    exit();
}

// Добавление связи профессии с учебным заведением
if (isset($_POST['add_institution_connection'])) {
    $profession_id = intval($_POST['profession_id']);
    $institution_id = intval($_POST['institution_id']);
    $program_name = trim($_POST['program_name']);
    $duration = trim($_POST['duration']);
    $cost = trim($_POST['cost']);
    
    // Проверяем, не существует ли уже такая связь
    $check_stmt = $link->prepare("SELECT id FROM profession_institutions WHERE profession_id = ? AND institution_id = ? AND program_name = ?");
    $check_stmt->bind_param("iis", $profession_id, $institution_id, $program_name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Такая связь уже существует']);
        exit();
    }
    
    $stmt = $link->prepare("INSERT INTO profession_institutions (profession_id, institution_id, program_name, duration, cost) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $profession_id, $institution_id, $program_name, $duration, $cost);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Связь с учебным заведением добавлена']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ошибка при добавлении связи: ' . $stmt->error]);
    }
    exit();
}

// Удаление связи с компанией
if (isset($_GET['delete_company_connection'])) {
    $id = intval($_GET['delete_company_connection']);
    $profession_id = intval($_GET['profession_id']);
    
    $stmt = $link->prepare("DELETE FROM profession_companies WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Связь с компанией удалена";
    } else {
        $_SESSION['error'] = "Ошибка при удалении связи";
    }
    
    header("Location: ../index.php?page=admin-professions&profession_id=$profession_id");
    exit();
}

// Удаление связи с учебным заведением
if (isset($_GET['delete_institution_connection'])) {
    $id = intval($_GET['delete_institution_connection']);
    $profession_id = intval($_GET['profession_id']);
    
    $stmt = $link->prepare("DELETE FROM profession_institutions WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Связь с учебным заведением удалена";
    } else {
        $_SESSION['error'] = "Ошибка при удалении связи";
    }
    
    header("Location: ../index.php?page=admin-professions&profession_id=$profession_id");
    exit();
}

// Если запрос не распознан
echo json_encode(['success' => false, 'error' => 'Неизвестный запрос']);
?>