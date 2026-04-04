<?php
session_start();
require('../connect.php');
require_once(__DIR__ . '/admin_logs_handler.php');

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    die("Access denied");
}

$admin_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;

// Обработка профессий
if (isset($_POST['title']) && isset($_POST['description']) && isset($_POST['category'])) {
    // Если есть profession_id - это редактирование, иначе - добавление
    if (isset($_POST['profession_id']) && !empty($_POST['profession_id'])) {
        // Редактирование профессии
        $id = $_POST['profession_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $required_skills = $_POST['required_skills'];
        $salary_range = $_POST['salary_range'];
        $education_level = $_POST['education_level'];
        $demand_level = $_POST['demand_level'];
        $category = $_POST['category'];
        
        // Получаем старые данные для лога
        $old_profession = $link->query("SELECT * FROM professions WHERE id = $id")->fetch_assoc();

        $stmt = $link->prepare("UPDATE professions SET title=?, description=?, required_skills=?, salary_range=?, education_level=?, demand_level=?, category=? WHERE id=?");
        $stmt->bind_param("sssssssi", $title, $description, $required_skills, $salary_range, $education_level, $demand_level, $category, $id);

        if ($stmt->execute()) {
            // Обновляем детали профессии
            $responsibilities = $_POST['responsibilities'] ?? '';
            $career_growth = $_POST['career_growth'] ?? '';
            $employment_prospects = $_POST['employment_prospects'] ?? '';
            $related_courses = $_POST['related_courses'] ?? '';
            $image_url = $_POST['image_url'] ?? 'assets/images/professions/default.png';

            // Проверяем, есть ли уже детали
            $check_stmt = $link->prepare("SELECT id FROM profession_details WHERE profession_id = ?");
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
                // Обновляем существующие детали
                $update_stmt = $link->prepare("UPDATE profession_details SET responsibilities=?, career_growth=?, employment_prospects=?, related_courses=?, image_url=? WHERE profession_id=?");
                $update_stmt->bind_param("sssssi", $responsibilities, $career_growth, $employment_prospects, $related_courses, $image_url, $id);
                $update_stmt->execute();
            } else {
                // Добавляем новые детали
                $insert_stmt = $link->prepare("INSERT INTO profession_details (profession_id, responsibilities, career_growth, employment_prospects, related_courses, image_url) VALUES (?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("isssss", $id, $responsibilities, $career_growth, $employment_prospects, $related_courses, $image_url);
                $insert_stmt->execute();
            }

            logAdminAction($admin_id, 'Редактирование профессии', 'profession', $id, 
                "Изменена профессия: '{$old_profession['title']}' → '{$title}'");
            $_SESSION['success'] = "Профессия успешно обновлена";
        } else {
            $_SESSION['error'] = "Ошибка при обновлении профессии: " . $stmt->error;
        }
    } else {
        // Добавление профессии
        $title = $_POST['title'];
        $description = $_POST['description'];
        $required_skills = $_POST['required_skills'];
        $salary_range = $_POST['salary_range'];
        $education_level = $_POST['education_level'];
        $demand_level = $_POST['demand_level'];
        $category = $_POST['category'];

        $stmt = $link->prepare("INSERT INTO professions (title, description, required_skills, salary_range, education_level, demand_level, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $title, $description, $required_skills, $salary_range, $education_level, $demand_level, $category);

        if ($stmt->execute()) {
            $profession_id = $stmt->insert_id;
            
            // Добавляем детали профессии
            $responsibilities = $_POST['responsibilities'] ?? '';
            $career_growth = $_POST['career_growth'] ?? '';
            $employment_prospects = $_POST['employment_prospects'] ?? '';
            $related_courses = $_POST['related_courses'] ?? '';
            $image_url = $_POST['image_url'] ?? 'assets/images/professions/default.png';

            $detail_stmt = $link->prepare("INSERT INTO profession_details (profession_id, responsibilities, career_growth, employment_prospects, related_courses, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $detail_stmt->bind_param("isssss", $profession_id, $responsibilities, $career_growth, $employment_prospects, $related_courses, $image_url);
            $detail_stmt->execute();

            logAdminAction($admin_id, 'Создание профессии', 'profession', $profession_id, 
                "Создана профессия: '{$title}' (категория: {$category}, зарплата: {$salary_range})");
            $_SESSION['success'] = "Профессия успешно добавлена";
        } else {
            $_SESSION['error'] = "Ошибка при добавлении профессии: " . $stmt->error;
        }
    }
    header("Location: ../index.php?page=admin-professions");
    exit();
}

// Удаление профессии
if (isset($_GET['delete_profession'])) {
    $id = $_GET['delete_profession'];
    
    // Получаем информацию о профессии
    $profession_info = $link->query("SELECT title FROM professions WHERE id = $id")->fetch_assoc();

    // Проверяем, есть ли связанные записи
    $check_stmt = $link->prepare("SELECT COUNT(*) as count FROM recommendations WHERE profession_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        logAdminAction($admin_id, 'Попытка удаления профессии', 'profession', $id, 
            "Попытка удалить профессию: '{$profession_info['title']}' - ОТКАЗАНО (есть связанные рекомендации)");
        $_SESSION['error'] = "Невозможно удалить профессию, так как с ней связаны рекомендации";
    } else {
        // Удаляем связанные записи
        $link->query("DELETE FROM profession_details WHERE profession_id = $id");
        $link->query("DELETE FROM profession_companies WHERE profession_id = $id");
        $link->query("DELETE FROM profession_institutions WHERE profession_id = $id");
        $link->query("DELETE FROM plan_professions WHERE profession_id = $id");
        $link->query("DELETE FROM mbti_profession_relations WHERE profession_id = $id");
        $link->query("DELETE FROM holland_profession_relations WHERE profession_id = $id");
        
        $stmt = $link->prepare("DELETE FROM professions WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            logAdminAction($admin_id, 'Удаление профессии', 'profession', $id, 
                "Удалена профессия: '{$profession_info['title']}'");
            $_SESSION['success'] = "Профессия успешно удалена";
        } else {
            $_SESSION['error'] = "Ошибка при удалении профессии";
        }
    }
    header("Location: ../index.php?page=admin-professions");
    exit();
}
?>