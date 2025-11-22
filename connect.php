<?php
$host = 'localhost';
$user = 'root';
$password = '';
$db_name = 'profio';

$link = mysqli_connect($host, $user, $password, $db_name);

// Проверка соединения
if (!$link) {
    die("Ошибка подключения к БД: " . mysqli_connect_error());
}

// Установка кодировки
mysqli_set_charset($link, 'utf8mb4');


?>