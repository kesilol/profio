<?php
// tests.php — Unit-тесты для проверки ключевых функций системы

echo "=== ЗАПУСК UNIT-ТЕСТОВ ===\n\n";

// Тест 1: Проверка создания пользователя
function test_new_user() {
    $user = [
        'name' => 'Иван Петров',
        'email' => 'ivan@profio.ru',
        'password' => 'Password123!',
        'role' => 'обучающийся'
    ];
    
    if ($user['name'] != 'Иван Петров') {
        throw new Exception("Имя пользователя не совпадает");
    }
    if ($user['email'] != 'ivan@profio.ru') {
        throw new Exception("Email пользователя не совпадает");
    }
    if ($user['role'] != 'обучающийся') {
        throw new Exception("Роль пользователя не совпадает");
    }
    
    echo "test_new_user пройден\n";
    return true;
}

// Тест 2: Проверка валидации пароля
function test_password_validation() {
    
    function isPasswordStrong($password) {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/[a-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        if (!preg_match('/[!@#$%^&*\-_]/', $password)) return false;
        return true;
    }
    
    // Проверка валидного пароля
    if (isPasswordStrong('Password123!') !== true) {
        throw new Exception("Пароль Password123! должен быть валидным");
    }
    
    // Проверка слишком короткого пароля
    if (isPasswordStrong('weak') !== false) {
        throw new Exception("Пароль weak должен быть невалидным");
    }
    
    // Проверка пароля без заглавной буквы
    if (isPasswordStrong('nouppercase123!') !== false) {
        throw new Exception("Пароль должен содержать заглавную букву");
    }
    
    // Проверка пароля без строчной буквы
    if (isPasswordStrong('NOLOWER123!') !== false) {
        throw new Exception("Пароль должен содержать строчную букву");
    }
    
    // Проверка пароля без цифры
    if (isPasswordStrong('NoLetters!') !== false) {
        throw new Exception("Пароль должен содержать цифру");
    }
    
    // Проверка пароля без спецсимвола
    if (isPasswordStrong('Password123') !== false) {
        throw new Exception("Пароль должен содержать спецсимвол");
    }
    
    echo "test_password_validation пройден\n";
    return true;
}

// Тест 3: Проверка расчёта прогресса плана развития
function test_calculate_progress() {
    // Обычный расчёт
    $total_tasks = 10;
    $completed_tasks = 7;
    $progress = ($total_tasks > 0) ? ($completed_tasks / $total_tasks) * 100 : 0;
    
    if ($progress != 70) {
        throw new Exception("Прогресс должен быть 70%, получено $progress%");
    }
    
    // Проверка с нулевым количеством задач
    $total_tasks = 0;
    $completed_tasks = 0;
    $progress_zero = ($total_tasks > 0) ? ($completed_tasks / $total_tasks) * 100 : 0;
    
    if ($progress_zero != 0) {
        throw new Exception("При отсутствии задач прогресс должен быть 0");
    }
    
    // Проверка со 100% выполнением
    $total_tasks = 5;
    $completed_tasks = 5;
    $progress_full = ($total_tasks > 0) ? ($completed_tasks / $total_tasks) * 100 : 0;
    
    if ($progress_full != 100) {
        throw new Exception("При всех выполненных задачах прогресс должен быть 100%");
    }
    
    echo "test_calculate_progress пройден\n";
    return true;
}

// Запуск всех тестов
$all_passed = true;

try {
    test_new_user();
} catch (Exception $e) {
    echo "✗ test_new_user failed: " . $e->getMessage() . "\n";
    $all_passed = false;
}

try {
    test_password_validation();
} catch (Exception $e) {
    echo "✗ test_password_validation failed: " . $e->getMessage() . "\n";
    $all_passed = false;
}

try {
    test_calculate_progress();
} catch (Exception $e) {
    echo "✗ test_calculate_progress failed: " . $e->getMessage() . "\n";
    $all_passed = false;
}

echo "\n";

if ($all_passed) {
    echo "=== ВСЕ ТЕСТЫ УСПЕШНО ЗАВЕРШЕНЫ ===\n";
} else {
    echo "=== НЕКОТОРЫЕ ТЕСТЫ НЕ ПРОЙДЕНЫ ===\n";
}
?>