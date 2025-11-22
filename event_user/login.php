<?php
session_start();

//вход

// print_r($_POST);

require('../connect.php');

//получение данных из формы
$email = $_POST['email'];
$pass = $_POST['psw'];

//проверка пользователя - ДОБАВИЛИ ПРОВЕРКУ is_active
$sql = "SELECT * FROM `users` WHERE `email` = '$email' AND `is_active` = 1";
$sql_user_login = $link->query($sql);

// Проверяем успешность запроса
if ($sql_user_login === false) {
    // Выводим ошибку SQL для отладки
    die("Ошибка SQL запроса: " . $link->error);
}

if (mysqli_num_rows($sql_user_login) > 0) {
    $sql_user = mysqli_fetch_assoc($sql_user_login);
    
    // Проверяем пароль с помощью password_verify (для bcrypt)
    if (password_verify($pass, $sql_user['password'])) {
        // print_r($sql_user);
        $_SESSION['user'] = [
            'id_user' => $sql_user['id'],
            'login' => $sql_user['name'],
            'email' => $sql_user['email'],
            'role' => $sql_user['role']
        ];

        header("Location: ../index.php?page=main");
    } else {
        $_SESSION['error_login'] = 'Введён не верный пароль или логин!';
        header("Location: ../index.php?page=sign-in");
    }

} else {
    // Проверяем, существует ли пользователь но заблокирован
    $sql_check_blocked = "SELECT * FROM `users` WHERE `email` = '$email' AND `is_active` = 0";
    $blocked_user = $link->query($sql_check_blocked);
    
    if (mysqli_num_rows($blocked_user) > 0) {
        $blocked_user_data = mysqli_fetch_assoc($blocked_user);
        $block_reason = $blocked_user_data['block_reason'] ?? 'причина не указана';
        $_SESSION['error_login'] = "Ваш аккаунт заблокирован. Причина: $block_reason. Обратитесь к администратору.";
    } else {
        $_SESSION['error_login'] = 'Введён не верный пароль или логин!';
    }
    
    header("Location: ../index.php?page=sign-in");
};
?>