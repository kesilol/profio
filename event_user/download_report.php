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

$user_id = $_SESSION['user']['id_user'];

try {
    // Очищаем ВСЕ буферы вывода
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // 1. Получаем полную информацию о пользователе
    $user_info = [
        'name' => $_SESSION['user']['name'] ?? 'Не указано',
        'email' => $_SESSION['user']['email'] ?? 'Не указано',
        'education_level' => $_SESSION['user']['education_level'] ?? 'Не указано'
    ];
    
    // Если в сессии нет данных, берем из БД
    if ($user_info['name'] == 'Не указано' || $user_info['education_level'] == 'Не указано') {
        $user_query = $link->prepare("SELECT name, email, education_level FROM users WHERE id = ?");
        if ($user_query) {
            $user_query->bind_param("i", $user_id);
            $user_query->execute();
            $user_result = $user_query->get_result();
            $db_user_info = $user_result->fetch_assoc();
            
            if ($db_user_info) {
                $user_info = array_merge($user_info, $db_user_info);
            }
        }
    }
    
    // 2. Проверяем наличие pdf_functions.php (новая версия)
    $pdf_functions_path = __DIR__ . '/pdf_functions.php';
    
    if (!file_exists($pdf_functions_path)) {
        die('Ошибка: Файл генерации PDF не найден.');
    }
    
    require_once($pdf_functions_path);
    
    // 3. Генерируем PDF (НОВЫЙ ВЫЗОВ - передаем только user_id, user_info и link)
    //    В новой версии функция сама получает все данные из БД
    $pdf_content = generateStudentPDFReport($user_id, $user_info, $link);
    
    // 4. Отправляем PDF с правильными заголовками
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="profio_отчет_' . date('Y-m-d') . '.pdf"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . strlen($pdf_content));
    
    // 5. Выводим PDF
    echo $pdf_content;
    exit();
    
} catch (Exception $e) {
    error_log("PDF Download Error: " . $e->getMessage());
    
    // Очищаем буфер и показываем ошибку
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Ошибка при генерации PDF</h1>';
    echo '<p><strong>Сообщение:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    
    // Отладочная информация
    echo '<h3>Отладочная информация:</h3>';
    echo '<pre>';
    echo 'User ID: ' . htmlspecialchars($user_id) . "\n";
    echo 'Session user data: ' . print_r($_SESSION['user'], true) . "\n";
    echo '</pre>';
    
    echo '<p><a href="' . ($_SERVER['HTTP_REFERER'] ?? '../index.php?page=reports') . '">Вернуться назад</a></p>';
    exit();
}
?>