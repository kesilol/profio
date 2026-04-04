<?php
session_start();
require('../connect.php');
require_once(__DIR__ . '/admin_logs_handler.php');

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    die("Access denied");
}

$admin_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;

// Обработка компаний
if (isset($_POST['name']) && isset($_POST['industry']) && isset($_POST['location'])) {
    // Если есть company_id - это редактирование, иначе - добавление
    if (isset($_POST['company_id']) && !empty($_POST['company_id'])) {
        // Редактирование компании
        $id = $_POST['company_id'];
        $name = $_POST['name'];
        $industry = $_POST['industry'];
        $description = $_POST['description'];
        $website = $_POST['website'] ?? '';
        $location = $_POST['location'];
        $employee_count = $_POST['employee_count'] ?? '';
        $image_url = $_POST['image_url'] ?? 'assets/images/companies/default.png';

        $stmt = $link->prepare("UPDATE companies SET name=?, industry=?, description=?, website=?, location=?, employee_count=?, image_url=? WHERE id=?");
        $stmt->bind_param("sssssssi", $name, $industry, $description, $website, $location, $employee_count, $image_url, $id);

        if ($stmt->execute()) {
            logAdminAction($admin_id, 'Обновление компании', 'company', $id, 
                "Обновлена компания: {$name}");
            $_SESSION['success'] = "Компания успешно обновлена";
        } else {
            $_SESSION['error'] = "Ошибка при обновлении компании: " . $stmt->error;
        }
    } else {
        // Добавление компании
        $name = $_POST['name'];
        $industry = $_POST['industry'];
        $description = $_POST['description'];
        $website = $_POST['website'] ?? '';
        $location = $_POST['location'];
        $employee_count = $_POST['employee_count'] ?? '';
        $image_url = $_POST['image_url'] ?? 'assets/images/companies/default.png';

        $stmt = $link->prepare("INSERT INTO companies (name, industry, description, website, location, employee_count, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $name, $industry, $description, $website, $location, $employee_count, $image_url);

        if ($stmt->execute()) {
            $company_id = $link->insert_id;
            logAdminAction($admin_id, 'Создание компании', 'company', $company_id, 
                "Создана компания: {$name}");
            $_SESSION['success'] = "Компания успешно добавлена";
        } else {
            $_SESSION['error'] = "Ошибка при добавлении компании: " . $stmt->error;
        }
    }
    header("Location: ../index.php?page=admin-companies");
    exit();
}

// Обработка учебных заведений
if (isset($_POST['name']) && isset($_POST['type']) && isset($_POST['location'])) {
    // Если есть institution_id - это редактирование, иначе - добавление
    if (isset($_POST['institution_id']) && !empty($_POST['institution_id'])) {
        // Редактирование учебного заведения
        $id = $_POST['institution_id'];
        $name = $_POST['name'];
        $type = $_POST['type'];
        $location = $_POST['location'];
        $description = $_POST['description'];
        $website = $_POST['website'] ?? '';
        $contact_email = $_POST['contact_email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $image_url = $_POST['image_url'] ?? 'assets/images/institutions/default.png';

        $stmt = $link->prepare("UPDATE educational_institutions SET name=?, type=?, location=?, description=?, website=?, contact_email=?, phone=?, image_url=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $name, $type, $location, $description, $website, $contact_email, $phone, $image_url, $id);

        if ($stmt->execute()) {
            logAdminAction($admin_id, 'Обновление учебного заведения', 'institution', $id, 
                "Обновлено учебное заведение: {$name}");
            $_SESSION['success'] = "Учебное заведение успешно обновлено";
        } else {
            $_SESSION['error'] = "Ошибка при обновлении учебного заведения: " . $stmt->error;
        }
    } else {
        // Добавление учебного заведения
        $name = $_POST['name'];
        $type = $_POST['type'];
        $location = $_POST['location'];
        $description = $_POST['description'];
        $website = $_POST['website'] ?? '';
        $contact_email = $_POST['contact_email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $image_url = $_POST['image_url'] ?? 'assets/images/institutions/default.png';

        $stmt = $link->prepare("INSERT INTO educational_institutions (name, type, location, description, website, contact_email, phone, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $name, $type, $location, $description, $website, $contact_email, $phone, $image_url);

        if ($stmt->execute()) {
            $institution_id = $link->insert_id;
            logAdminAction($admin_id, 'Создание учебного заведения', 'institution', $institution_id, 
                "Создано учебное заведение: {$name}");
            $_SESSION['success'] = "Учебное заведение успешно добавлено";
        } else {
            $_SESSION['error'] = "Ошибка при добавлении учебного заведения: " . $stmt->error;
        }
    }
    header("Location: ../index.php?page=admin-companies");
    exit();
}

// Удаление компании
if (isset($_GET['delete_company'])) {
    $id = $_GET['delete_company'];
    
    // Получаем информацию о компании
    $company_info = $link->query("SELECT name FROM companies WHERE id = $id")->fetch_assoc();

    // Проверяем, есть ли связанные записи
    $check_stmt = $link->prepare("SELECT COUNT(*) as count FROM profession_companies WHERE company_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        logAdminAction($admin_id, 'Попытка удаления компании', 'company', $id, 
            "Попытка удалить компанию: {$company_info['name']} - ОТКАЗАНО (есть связанные профессии)");
        $_SESSION['error'] = "Невозможно удалить компанию, так как с ней связаны профессии";
    } else {
        $stmt = $link->prepare("DELETE FROM companies WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            logAdminAction($admin_id, 'Удаление компании', 'company', $id, 
                "Удалена компания: {$company_info['name']}");
            $_SESSION['success'] = "Компания успешно удалена";
        } else {
            $_SESSION['error'] = "Ошибка при удалении компании";
        }
    }
    header("Location: ../index.php?page=admin-companies");
    exit();
}

// Удаление учебного заведения
if (isset($_GET['delete_institution'])) {
    $id = $_GET['delete_institution'];
    
    // Получаем информацию об учебном заведении
    $institution_info = $link->query("SELECT name FROM educational_institutions WHERE id = $id")->fetch_assoc();

    // Проверяем, есть ли связанные записи
    $check_stmt = $link->prepare("SELECT COUNT(*) as count FROM profession_institutions WHERE institution_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        logAdminAction($admin_id, 'Попытка удаления учебного заведения', 'institution', $id, 
            "Попытка удалить учебное заведение: {$institution_info['name']} - ОТКАЗАНО (есть связанные профессии)");
        $_SESSION['error'] = "Невозможно удалить учебное заведение, так как с ним связаны профессии";
    } else {
        $stmt = $link->prepare("DELETE FROM educational_institutions WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            logAdminAction($admin_id, 'Удаление учебного заведения', 'institution', $id, 
                "Удалено учебное заведение: {$institution_info['name']}");
            $_SESSION['success'] = "Учебное заведение успешно удалено";
        } else {
            $_SESSION['error'] = "Ошибка при удалении учебного заведения";
        }
    }
    header("Location: ../index.php?page=admin-companies");
    exit();
}
?>