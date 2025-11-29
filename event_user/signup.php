<?php
session_start();
require('../connect.php');

// Функция для проверки сложности пароля
function isPasswordStrong($password) {
    // Не менее 8 символов
    if (strlen($password) < 8) {
        return "Пароль должен содержать не менее 8 символов";
    }
    
    // Заглавная буква
    if (!preg_match('/[A-Z]/', $password)) {
        return "Пароль должен содержать хотя бы одну заглавную букву (A-Z)";
    }
    
    // Строчная буква
    if (!preg_match('/[a-z]/', $password)) {
        return "Пароль должен содержать хотя бы одну строчную букву (a-z)";
    }
    
    // Цифра
    if (!preg_match('/[0-9]/', $password)) {
        return "Пароль должен содержать хотя бы одну цифру (0-9)";
    }
    
    // Спецсимвол
    if (!preg_match('/[!@#$%^&*]/', $password)) {
        return "Пароль должен содержать хотя бы один спецсимвол (!@#$%^&*)";
    }
    
    return true;
}

// Получение данных из формы
$login = $_POST['login'];
$email = $_POST['email'];
$role = $_POST['role'];
$education_level = $_POST['education_level'];
$pass1 = $_POST['password1'];
$pass2 = $_POST['password2'];

// Сохраняем данные формы в сессии
$_SESSION['form_data'] = [
    'login' => $login,
    'email' => $email,
    'role' => $role,
    'education_level' => $education_level
];

// Проверка обязательных полей
if (empty($login) || empty($email) || empty($role) || empty($education_level) || empty($pass1) || empty($pass2)) {
    $_SESSION['error_required'] = 'Все поля обязательны для заполнения';
} else {
    // Проверка уникальности email
    $sql_user_email = $link->query("SELECT * FROM `users` WHERE `email` = '$email'");
    if (mysqli_num_rows($sql_user_email) > 0) {
        $_SESSION['error_email'] = 'Такой email уже используется!';
    } 
    // Проверка сложности пароля
    elseif (isPasswordStrong($pass1) !== true) {
        $password_error = isPasswordStrong($pass1);
        $_SESSION['error_pas'] = $password_error;
    }
    // Проверка совпадения паролей
    elseif ($pass1 !== $pass2) {
        $_SESSION['error_pas'] = 'Пароли не совпадают!';
    } else {
        $pas_hash = password_hash($pass1, PASSWORD_DEFAULT);
        $result = mysqli_query($link, "INSERT INTO `users` (`name`, `email`, `password`, `role`, `education_level`) VALUES ('$login', '$email', '$pas_hash', '$role', '$education_level')");
        
        if ($result) {
            $user_id = $link->insert_id;
            
            // ★★★★ СОХРАНЕНИЕ РЕЗУЛЬТАТОВ ОНБОРДИНГ-ТЕСТА ★★★★
            if (isset($_SESSION['onboarding_results'])) {
                $test_data = $_SESSION['onboarding_results'];
                
                // Сохраняем результат теста
                $link->query("INSERT INTO test_results (user_id, test_id, total_score, result_type) 
                              VALUES ('$user_id', '{$test_data['test_id']}', '{$test_data['total_score']}', '{$test_data['result_type']}')");
                
                $result_id = $link->insert_id;
                
                // Создаем рекомендации на основе результата
                $recommendations_map = [
                    'технический' => [1, 7],    // Программист, Инженер
                    'гуманитарный' => [2, 4],   // Психолог, Учитель
                    'творческий' => [3, 5],     // Дизайнер, Маркетолог
                    'научный' => [6],           // Ученый-исследователь
                    'документальный' => [5]     // Маркетолог
                ];
                
                if (isset($recommendations_map[$test_data['result_type']])) {
                    foreach ($recommendations_map[$test_data['result_type']] as $profession_id) {
                        $match_percentage = 80 + ($test_data['total_score'] % 20);
                        $recommendation_text = "На основе теста {$test_data['test_title']} от " . date('d.m.Y');
                        
                        $link->query("INSERT INTO recommendations (user_id, result_type, profession_id, match_percentage, recommendation_text) 
                                      VALUES ('$user_id', '{$test_data['result_type']}', '$profession_id', '$match_percentage', '$recommendation_text')");
                    }
                }
                
                // Сохраняем информацию о результате в сессии для показа на главной
                $_SESSION['recent_test_result'] = [
                    'result_type' => $test_data['result_type'],
                    'test_title' => $test_data['test_title'],
                    'result_id' => $result_id
                ];
                
                // Очищаем временные данные
                unset($_SESSION['onboarding_results']);
                unset($_SESSION['onboarding_completed']);
            }
            
            // Очищаем данные формы при успешной регистрации
            unset($_SESSION['form_data']);
            unset($_SESSION['error_email']);
            unset($_SESSION['error_pas']);
            unset($_SESSION['error_required']);
            
            // Создаем сессию пользователя
            $_SESSION['user'] = [
                'id_user' => $user_id,
                'login' => $login,
                'email' => $email,
                'role' => $role
            ];
            
            $_SESSION['message'] = 'Регистрация успешна!';
            header("Location: ../index.php?page=main");
            exit();
        }
    }
}

header("Location: ../index.php?page=sign-up");
exit();
?>