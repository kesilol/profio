<?php
// Константы для статусов задач
define('TASK_STATUS_PENDING', 'ожидает');
define('TASK_STATUS_IN_PROGRESS', 'в процессе');
define('TASK_STATUS_COMPLETED', 'выполнено');

// Получение планов пользователя
function getUserPlans($link, $user_id) {
    $sql = "SELECT dp.*, 
                   COUNT(pt.id) as total_tasks,
                   COUNT(CASE WHEN pt.is_completed = 1 THEN 1 END) as completed_tasks,
                   CASE 
                       WHEN COUNT(pt.id) = 0 THEN 0
                       ELSE ROUND((COUNT(CASE WHEN pt.is_completed = 1 THEN 1 END) / COUNT(pt.id)) * 100, 2)
                   END as progress_percentage
            FROM development_plans dp
            LEFT JOIN plan_tasks pt ON dp.id = pt.plan_id
            WHERE dp.user_id = ?
            GROUP BY dp.id
            ORDER BY dp.created_at DESC";
    
    $stmt = $link->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Получение деталей плана
function getPlanDetails($link, $plan_id, $user_id) {
    // Основная информация о плане
    $sql = "SELECT dp.*, 
                   COUNT(pt.id) as total_tasks,
                   COUNT(CASE WHEN pt.is_completed = 1 THEN 1 END) as completed_tasks,
                   CASE 
                       WHEN COUNT(pt.id) = 0 THEN 0
                       ELSE ROUND((COUNT(CASE WHEN pt.is_completed = 1 THEN 1 END) / COUNT(pt.id)) * 100, 2)
                   END as progress_percentage
            FROM development_plans dp
            LEFT JOIN plan_tasks pt ON dp.id = pt.plan_id
            WHERE dp.id = ? AND dp.user_id = ?
            GROUP BY dp.id";
    
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ii", $plan_id, $user_id);
    $stmt->execute();
    $plan = $stmt->get_result()->fetch_assoc();
    
    if (!$plan) return null;
    
    // Задачи плана
    $tasks_sql = "SELECT * FROM plan_tasks 
                  WHERE plan_id = ? 
                  ORDER BY task_order ASC, created_at ASC";
    $tasks_stmt = $link->prepare($tasks_sql);
    $tasks_stmt->bind_param("i", $plan_id);
    $tasks_stmt->execute();
    $plan['tasks'] = $tasks_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Связанные профессии
    $professions_sql = "SELECT p.* 
                       FROM plan_professions pp
                       JOIN professions p ON pp.profession_id = p.id
                       WHERE pp.plan_id = ?";
    $professions_stmt = $link->prepare($professions_sql);
    $professions_stmt->bind_param("i", $plan_id);
    $professions_stmt->execute();
    $plan['professions'] = $professions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $plan;
}

// Создание нового плана
function createPlan($link, $user_id, $title, $description, $goals, $deadline, $profession_ids = []) {
    $link->begin_transaction();
    
    try {
        // Создаем основной план
        $sql = "INSERT INTO development_plans (user_id, title, description, goals, deadline) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("issss", $user_id, $title, $description, $goals, $deadline);
        $stmt->execute();
        
        $plan_id = $link->insert_id;
        
        // Добавляем связанные профессии
        if (!empty($profession_ids)) {
            $profession_sql = "INSERT INTO plan_professions (plan_id, profession_id) VALUES (?, ?)";
            $profession_stmt = $link->prepare($profession_sql);
            
            foreach ($profession_ids as $profession_id) {
                $profession_stmt->bind_param("ii", $plan_id, $profession_id);
                $profession_stmt->execute();
            }
        }
        
        $link->commit();
        return $plan_id;
    } catch (Exception $e) {
        $link->rollback();
        return false;
    }
}

// Обновление плана
function updatePlan($link, $plan_id, $user_id, $title, $description, $goals, $deadline, $profession_ids = []) {
    $link->begin_transaction();
    
    try {
        // Обновляем основной план
        $sql = "UPDATE development_plans 
                SET title = ?, description = ?, goals = ?, deadline = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("ssssii", $title, $description, $goals, $deadline, $plan_id, $user_id);
        $stmt->execute();
        
        // Обновляем связанные профессии
        $delete_sql = "DELETE FROM plan_professions WHERE plan_id = ?";
        $delete_stmt = $link->prepare($delete_sql);
        $delete_stmt->bind_param("i", $plan_id);
        $delete_stmt->execute();
        
        if (!empty($profession_ids)) {
            $profession_sql = "INSERT INTO plan_professions (plan_id, profession_id) VALUES (?, ?)";
            $profession_stmt = $link->prepare($profession_sql);
            
            foreach ($profession_ids as $profession_id) {
                $profession_stmt->bind_param("ii", $plan_id, $profession_id);
                $profession_stmt->execute();
            }
        }
        
        $link->commit();
        return true;
    } catch (Exception $e) {
        $link->rollback();
        return false;
    }
}

// Добавление задачи в план
function addTaskToPlan($link, $plan_id, $task_text, $deadline = null) {
    if (!$plan_id) {
        return false;
    }
    
    // Получаем максимальный порядок задач
    $order_sql = "SELECT COALESCE(MAX(task_order), 0) + 1 as next_order 
                  FROM plan_tasks WHERE plan_id = ?";
    $order_stmt = $link->prepare($order_sql);
    $order_stmt->bind_param("i", $plan_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result()->fetch_assoc();
    $next_order = $order_result['next_order'];
    
    $sql = "INSERT INTO plan_tasks (plan_id, task_text, task_order, deadline) 
            VALUES (?, ?, ?, ?)";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("isis", $plan_id, $task_text, $next_order, $deadline);
    return $stmt->execute();
}

// Обновление статуса задачи
function updateTaskStatus($link, $task_id, $is_completed) {
    $sql = "UPDATE plan_tasks SET is_completed = ? WHERE id = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ii", $is_completed, $task_id);
    return $stmt->execute();
}

// Удаление задачи
function deleteTask($link, $task_id) {
    $sql = "DELETE FROM plan_tasks WHERE id = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("i", $task_id);
    return $stmt->execute();
}

// Удаление плана
function deletePlan($link, $plan_id, $user_id) {
    $link->begin_transaction();
    
    try {
        // Удаляем связанные профессии
        $delete_professions_sql = "DELETE FROM plan_professions WHERE plan_id = ?";
        $delete_professions_stmt = $link->prepare($delete_professions_sql);
        $delete_professions_stmt->bind_param("i", $plan_id);
        $delete_professions_stmt->execute();
        
        // Удаляем задачи плана
        $delete_tasks_sql = "DELETE FROM plan_tasks WHERE plan_id = ?";
        $delete_tasks_stmt = $link->prepare($delete_tasks_sql);
        $delete_tasks_stmt->bind_param("i", $plan_id);
        $delete_tasks_stmt->execute();
        
        // Удаляем сам план
        $delete_plan_sql = "DELETE FROM development_plans WHERE id = ? AND user_id = ?";
        $delete_plan_stmt = $link->prepare($delete_plan_sql);
        $delete_plan_stmt->bind_param("ii", $plan_id, $user_id);
        $delete_plan_stmt->execute();
        
        $link->commit();
        return true;
    } catch (Exception $e) {
        $link->rollback();
        return false;
    }
}

// Получение всех профессий для выпадающего списка
function getAllProfessions($link) {
    $sql = "SELECT id, title FROM professions ORDER BY title";
    $result = $link->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>