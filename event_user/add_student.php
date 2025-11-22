<?php
session_start();
require('../connect.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'куратор') {
    header("Location: ../index.php?page=sign-in");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_email'])) {
    $curator_id = $_SESSION['user']['id_user'];
    $student_email = trim($_POST['student_email']);
    
    // Ищем студента по email с проверкой роли
    $student_query = $link->prepare("SELECT id, name, role FROM users WHERE email = ? AND role = 'студент'");
    $student_query->bind_param("s", $student_email);
    $student_query->execute();
    $student_result = $student_query->get_result();
    
    if ($student_result->num_rows > 0) {
        $student = $student_result->fetch_assoc();
        $student_id = $student['id'];
        $student_name = $student['name'];
        
        // Проверяем, не добавлен ли уже студент ЭТОМУ куратору
        $check_query = $link->prepare("SELECT id FROM curator_students WHERE curator_id = ? AND student_id = ?");
        $check_query->bind_param("ii", $curator_id, $student_id);
        $check_query->execute();
        $check_result = $check_query->get_result();
        
        if ($check_result->num_rows === 0) {
            // Проверяем, не привязан ли студент к ДРУГОМУ куратору
            $other_curator_query = $link->prepare("
                SELECT cs.curator_id, u.name as curator_name 
                FROM curator_students cs 
                JOIN users u ON cs.curator_id = u.id 
                WHERE cs.student_id = ? 
                LIMIT 1
            ");
            $other_curator_query->bind_param("i", $student_id);
            $other_curator_query->execute();
            $other_curator_result = $other_curator_query->get_result();
            
            if ($other_curator_result->num_rows > 0) {
                $other_curator = $other_curator_result->fetch_assoc();
                $_SESSION['error'] = "Студент {$student_name} уже привязан к куратору {$other_curator['curator_name']}";
            } else {
                // Добавляем студента (студент свободен)
                $insert_query = $link->prepare("INSERT INTO curator_students (curator_id, student_id) VALUES (?, ?)");
                $insert_query->bind_param("ii", $curator_id, $student_id);
                
                if ($insert_query->execute()) {
                    $_SESSION['success'] = "Студент {$student_name} успешно добавлен в ваш список";
                } else {
                    $_SESSION['error'] = "Ошибка при добавлении студента: " . $link->error;
                }
            }
        } else {
            $_SESSION['error'] = "Студент {$student_name} уже в вашем списке";
        }
    } else {
        // Проверяем, существует ли пользователь вообще
        $user_check = $link->prepare("SELECT id, name, role FROM users WHERE email = ?");
        $user_check->bind_param("s", $student_email);
        $user_check->execute();
        $user_result = $user_check->get_result();
        
        if ($user_result->num_rows > 0) {
            $user = $user_result->fetch_assoc();
            if ($user['role'] !== 'студент') {
                $_SESSION['error'] = "Пользователь {$user['name']} не является студентом";
            } else {
                $_SESSION['error'] = "Студент {$user['name']} не найден или недоступен для добавления";
            }
        } else {
            $_SESSION['error'] = "❌ Студент с email <strong>{$student_email}</strong> не найден в системе";
        }
    }
    
    header("Location: ../index.php?page=manage-students");
    exit();
}
?>