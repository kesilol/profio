<?php
session_start();
require('connect.php');

// Если не авторизован и открывает главную - показываем landing
if (!isset($_SESSION['user']) && (!isset($_GET['page']) || $_GET['page'] === 'main')) {
    require('landing.php');
    exit();
}

$routes = [
    'main' => 'page/main.php',
    'sign-up' => 'page/sign-up.php',
    'sign-in' => 'page/sign-in.php',
    'sign-up-success' => 'page/sign-up-success.php',
    'tests' => 'page/tests.php',
    'test' => 'page/test.php',
    'test-result' => 'page/test-result.php',
    'my-results' => 'page/my-results.php',
    'professions' => 'page/professions.php',
    'profession-detail' => 'page/profession-detail.php',
    'recommendations' => 'page/recommendations.php',
    'reports' => 'page/reports.php',
    'plan' => 'page/plan.php',
    'info' => 'page/info.php',
    'manage-students' => 'page/manage-students.php',
    'student-detail' => 'page/student-detail.php',
    'admin-dashboard' => 'page/admin-dashboard.php',
    'admin-users' => 'page/admin-users.php',
    'admin-tests' => 'page/admin-tests.php',
    'admin-professions' => 'page/admin-professions.php',
    'admin-companies' => 'page/admin-companies.php',
    'admin-analytics' => 'page/admin-analytics.php'
];

$authPages = ['sign-up', 'sign-in', 'sign-up-success'];
$page = $_GET['page'] ?? 'main';

// Для страницы планов подключаем обработчик ДО header
if ($page === 'plan') {
    require_once('event_user/plans_processor.php');
}

// Подключаем соответствующий хедер
if (in_array($page, $authPages)) {
    require('page/auth-header.php');
} else {
    require('page/header.php');
}

// Подключаем контент страницы
if (isset($routes[$page])) {
    require($routes[$page]);
} else {
    echo "<h1>Страница не найдена</h1>";
}

// Подключаем соответствующий футер
if (in_array($page, $authPages)) {
    require('page/auth-footer.php');
} else {
    require('page/footer.php');
}
?>