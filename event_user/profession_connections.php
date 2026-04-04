<?php
session_start();
require('../connect.php');
require_once(__DIR__ . '/admin_logs_handler.php');

// Устанавливаем заголовок для JSON ответа
header('Content-Type: application/json');

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

$admin_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;

// Добавление связи профессии с компанией
if (isset($_POST['add_company_connection'])) {
    $profession_id = intval($_POST['profession_id']);
    $company_id = intval($_POST['company_id']);
    $position_name = trim($_POST['position_name']);
    $experience_level = $_POST['experience_level'];
    
    // Получаем названия для лога
    $profession_info = $link->query("SELECT title FROM professions WHERE id = $profession_id")->fetch_assoc();
    $company_info = $link->query("SELECT name FROM companies WHERE id = $company_id")->fetch_assoc();
    
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
        $connection_id = $link->insert_id;
        logAdminAction($admin_id, 'Создание связи профессии с компанией', 'profession_company', $connection_id, 
            "Добавлена связь: профессия '{$profession_info['title']}' → компания '{$company_info['name']}' (должность: {$position_name}, уровень: {$experience_level})");
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
    
    // Получаем названия для лога
    $profession_info = $link->query("SELECT title FROM professions WHERE id = $profession_id")->fetch_assoc();
    $institution_info = $link->query("SELECT name FROM educational_institutions WHERE id = $institution_id")->fetch_assoc();
    
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
        $connection_id = $link->insert_id;
        logAdminAction($admin_id, 'Создание связи профессии с учебным заведением', 'profession_institution', $connection_id, 
            "Добавлена связь: профессия '{$profession_info['title']}' → учебное заведение '{$institution_info['name']}' (программа: {$program_name}, срок: {$duration}, стоимость: {$cost})");
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
    
    // Получаем информацию о связи для лога
    $connection_info = $link->query("
        SELECT pc.*, p.title as profession_title, c.name as company_name 
        FROM profession_companies pc
        JOIN professions p ON pc.profession_id = p.id
        JOIN companies c ON pc.company_id = c.id
        WHERE pc.id = $id
    ")->fetch_assoc();
    
    $stmt = $link->prepare("DELETE FROM profession_companies WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAdminAction($admin_id, 'Удаление связи профессии с компанией', 'profession_company', $id, 
            "Удалена связь: профессия '{$connection_info['profession_title']}' → компания '{$connection_info['company_name']}' (должность: {$connection_info['position_name']})");
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
    
    // Получаем информацию о связи для лога
    $connection_info = $link->query("
        SELECT pi.*, p.title as profession_title, i.name as institution_name 
        FROM profession_institutions pi
        JOIN professions p ON pi.profession_id = p.id
        JOIN educational_institutions i ON pi.institution_id = i.id
        WHERE pi.id = $id
    ")->fetch_assoc();
    
    $stmt = $link->prepare("DELETE FROM profession_institutions WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAdminAction($admin_id, 'Удаление связи профессии с учебным заведением', 'profession_institution', $id, 
            "Удалена связь: профессия '{$connection_info['profession_title']}' → учебное заведение '{$connection_info['institution_name']}' (программа: {$connection_info['program_name']})");
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