<?php
// Обработчик для планов развития - должен подключаться ДО header.php

if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

require_once('event_user/plans_handler.php');

$user_id = $_SESSION['user']['id_user'];
$action = $_GET['action'] ?? 'list';
$plan_id = $_GET['id'] ?? null;

// Обработка POST запросов ДО любого вывода
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_plan'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $goals = trim($_POST['goals']);
        $deadline = $_POST['deadline'] ?: null;
        $profession_ids = $_POST['profession_ids'] ?? [];
        
        if (!empty($title)) {
            $new_plan_id = createPlan($link, $user_id, $title, $description, $goals, $deadline, $profession_ids);
            
            if ($new_plan_id) {
                $_SESSION['success_message'] = "План успешно создан!";
                header("Location: index.php?page=plan&action=view&id=$new_plan_id");
                exit();
            } else {
                $_SESSION['error_message'] = "Ошибка при создании плана";
            }
        } else {
            $_SESSION['error_message'] = "Название плана обязательно для заполнения";
        }
    }
    
    if (isset($_POST['update_plan']) && $plan_id) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $goals = trim($_POST['goals']);
        $deadline = $_POST['deadline'] ?: null;
        $profession_ids = $_POST['profession_ids'] ?? [];
        
        if (!empty($title)) {
            $success = updatePlan($link, $plan_id, $user_id, $title, $description, $goals, $deadline, $profession_ids);
            
            if ($success) {
                $_SESSION['success_message'] = "План успешно обновлен!";
                header("Location: index.php?page=plan&action=view&id=$plan_id");
                exit();
            } else {
                $_SESSION['error_message'] = "Ошибка при обновлении плана";
            }
        } else {
            $_SESSION['error_message'] = "Название плана обязательно для заполнения";
        }
    }
    
    if (isset($_POST['add_task']) && $plan_id) {
        $task_text = trim($_POST['task_text']);
        $task_deadline = $_POST['task_deadline'] ?: null;
        
        if (!empty($task_text)) {
            $success = addTaskToPlan($link, $plan_id, $task_text, $task_deadline);
            
            if ($success) {
                $_SESSION['success_message'] = "Задача успешно добавлена!";
            } else {
                $_SESSION['error_message'] = "Ошибка при добавлении задачи";
            }
        } else {
            $_SESSION['error_message'] = "Описание задачи обязательно для заполнения";
        }
        
        header("Location: index.php?page=plan&action=view&id=$plan_id");
        exit();
    }
    
    if (isset($_POST['update_task_status']) && isset($_POST['task_id'])) {
        $task_id = $_POST['task_id'];
        $is_completed = isset($_POST['is_completed']) ? 1 : 0;
        
        updateTaskStatus($link, $task_id, $is_completed);
        header("Location: index.php?page=plan&action=view&id=$plan_id");
        exit();
    }
    
    if (isset($_POST['delete_task']) && isset($_POST['task_id'])) {
        $task_id = $_POST['task_id'];
        deleteTask($link, $task_id);
        $_SESSION['success_message'] = "Задача успешно удалена!";
        header("Location: index.php?page=plan&action=view&id=$plan_id");
        exit();
    }
    
    // Добавляем обработчик удаления плана
    if (isset($_POST['delete_plan']) && isset($_POST['plan_id'])) {
        $plan_id_to_delete = $_POST['plan_id'];
        $success = deletePlan($link, $plan_id_to_delete, $user_id);
        
        if ($success) {
            $_SESSION['success_message'] = "План успешно удален!";
        } else {
            $_SESSION['error_message'] = "Ошибка при удалении плана";
        }
        
        header("Location: index.php?page=plan");
        exit();
    }
}
?>