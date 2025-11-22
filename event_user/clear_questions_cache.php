<?php
// Этот файл будет принудительно очищать кэш вопросов
session_start();
require('../connect.php');

if ($_SESSION['user']['role'] !== 'администратор') {
    die('Access denied');
}

// Ничего не кэшируем
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

echo "Cache cleared";
?>