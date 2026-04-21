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

// Функция для проверки даты (не может быть в прошлом)
function isValidFutureDate($date) {
    if (empty($date)) return true; // пустая дата - допустимо
    $today = date('Y-m-d');
    return $date >= $today;
}

// Функция для проверки, что дата задачи не позже даты плана
function isTaskDateValid($link, $plan_id, $task_deadline) {
    if (empty($task_deadline)) return true;
    
    $plan_query = $link->prepare("SELECT deadline FROM development_plans WHERE id = ?");
    $plan_query->bind_param("i", $plan_id);
    $plan_query->execute();
    $plan_result = $plan_query->get_result()->fetch_assoc();
    
    if ($plan_result && $plan_result['deadline']) {
        return $task_deadline <= $plan_result['deadline'];
    }
    return true;
}

// Обработка POST запросов ДО любого вывода
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_plan'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $goals = trim($_POST['goals']);
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
        $profession_ids = $_POST['profession_ids'] ?? [];
        
        // Проверка даты
        if ($deadline && !isValidFutureDate($deadline)) {
            $_SESSION['error_message'] = "Дата завершения не может быть в прошлом!";
            header("Location: index.php?page=plan&action=create");
            exit();
        }
        
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
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
        $profession_ids = $_POST['profession_ids'] ?? [];
        
        // Проверка даты
        if ($deadline && !isValidFutureDate($deadline)) {
            $_SESSION['error_message'] = "Дата завершения не может быть в прошлом!";
            header("Location: index.php?page=plan&action=edit&id=$plan_id");
            exit();
        }
        
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
        $task_deadline = !empty($_POST['task_deadline']) ? $_POST['task_deadline'] : null;
        
        // Проверка даты задачи
        if ($task_deadline && !isValidFutureDate($task_deadline)) {
            $_SESSION['error_message'] = "Срок выполнения задачи не может быть в прошлом!";
            header("Location: index.php?page=plan&action=view&id=$plan_id");
            exit();
        }
        
        // Проверка, что дата задачи не позже даты плана
        if ($task_deadline && !isTaskDateValid($link, $plan_id, $task_deadline)) {
            // Получаем дату плана для сообщения
            $plan_query = $link->prepare("SELECT deadline FROM development_plans WHERE id = ?");
            $plan_query->bind_param("i", $plan_id);
            $plan_query->execute();
            $plan_result = $plan_query->get_result()->fetch_assoc();
            $plan_deadline = $plan_result['deadline'] ? date('d.m.Y', strtotime($plan_result['deadline'])) : 'не указана';
            
            $_SESSION['error_message'] = "Срок выполнения задачи не может быть позже даты завершения плана ($plan_deadline)";
            header("Location: index.php?page=plan&action=view&id=$plan_id");
            exit();
        }
        
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
        
        // Если задача отмечается как выполненная, проверяем не просрочена ли она
        if ($is_completed == 1) {
            $task_query = $link->prepare("SELECT deadline, task_text FROM plan_tasks WHERE id = ?");
            $task_query->bind_param("i", $task_id);
            $task_query->execute();
            $task = $task_query->get_result()->fetch_assoc();
            
            if ($task && $task['deadline'] && $task['deadline'] < date('Y-m-d')) {
                $_SESSION['error_message'] = "Задача просрочена! Нельзя отметить её как выполненную.";
                header("Location: index.php?page=plan&action=view&id=$plan_id");
                exit();
            }
        }
        
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