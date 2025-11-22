<?php
session_start();
require('connect.php');

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

$user_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;
$message = '';
$message_type = '';
$errors = [];

// Получаем данные пользователя
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $link->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Обработка формы редактирования профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $education_level = $_POST['education_level'];
    
    // Валидация
    if (empty($name)) {
        $errors[] = "Имя не может быть пустым";
    }
    
    if (empty($email)) {
        $errors[] = "Email не может быть пустым";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат email";
    }
    
    // Проверяем, не занят ли email другим пользователем
    if (empty($errors)) {
        $email_check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $link->prepare($email_check_query);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $email_result = $stmt->get_result();
        
        if ($email_result->num_rows > 0) {
            $errors[] = "Этот email уже используется другим пользователем";
        }
    }
    
    // Если ошибок нет, обновляем данные
    if (empty($errors)) {
        $update_query = "UPDATE users SET name = ?, email = ?, education_level = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $link->prepare($update_query);
        $stmt->bind_param("sssi", $name, $email, $education_level, $user_id);
        
        if ($stmt->execute()) {
            $message = "Профиль успешно обновлен";
            $message_type = "success";
            // Обновляем данные в сессии
            $_SESSION['user']['login'] = $name;
            // Обновляем локальные данные
            $user_data['name'] = $name;
            $user_data['email'] = $email;
            $user_data['education_level'] = $education_level;
        } else {
            $errors[] = "Ошибка при обновлении профиля";
        }
    }
}

// Обработка формы смены пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Валидация пароля
    if (empty($current_password)) {
        $errors[] = "Введите текущий пароль";
    }
    
    if (empty($new_password)) {
        $errors[] = "Введите новый пароль";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "Пароль должен содержать не менее 6 символов";
    }
    
    if (empty($confirm_password)) {
        $errors[] = "Подтвердите новый пароль";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "Новые пароли не совпадают";
    }
    
    // Проверяем текущий пароль
    if (empty($errors)) {
        if (password_verify($current_password, $user_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $link->prepare($update_query);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $message = "Пароль успешно изменен";
                $message_type = "success";
            } else {
                $errors[] = "Ошибка при изменении пароля";
            }
        } else {
            $errors[] = "Текущий пароль неверен";
        }
    }
}

// Если есть ошибки, показываем их
if (!empty($errors)) {
    $message = implode("<br>", $errors);
    $message_type = "error";
}
?>
<!DOCTYPE html>
<html class="scroll-smooth overflow-x-hidden" lang="ru">
<head>
    <meta charset="utf-8">
    <title>Настройки - Profio</title>
    <meta name="description" content="Настройки пользователя системы Profio">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <link rel="icon" href="assets/images/icons/icon-favicon.svg" type="image/x-icon" sizes="16x16">
    <link rel="stylesheet" href="assets/styles/tailwind.min.css?v=5.0">
    <link rel="stylesheet" href="assets/styles/style.min.css?v=5.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chivo:wght@400;700;900&family=Noto+Sans:wght@400;500;600;700;800&display=swap">
</head>
<body class="w-screen relative overflow-x-hidden min-h-screen bg-gray-100 scrollbar-hide dark:bg-[#000]">
    <?php require('page/header.php'); ?>
    
    <main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
        <div>
            <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
                Настройки
            </h2>
            
            <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
                <div class="flex items-center gap-x-1">
                    <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                    <a class="capitalize" href="index.php">Главная</a>
                </div>
                <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
                <span class="capitalize text-color-brands">Настройки</span>
            </div>

            <!-- Сообщения -->
            <?php if ($message): ?>
                <div class="rounded-lg p-4 mb-6 <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Редактирование профиля -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                        Редактирование профиля
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-2">
                                Имя
                            </label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user_data['name']); ?>"
                                   class="w-full px-3 py-2 border border-neutral dark:border-dark-neutral-border rounded-lg focus:outline-none focus:ring-2 focus:ring-color-brands"
                                   required>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-2">
                                Email
                            </label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user_data['email']); ?>"
                                   class="w-full px-3 py-2 border border-neutral dark:border-dark-neutral-border rounded-lg focus:outline-none focus:ring-2 focus:ring-color-brands"
                                   required>
                        </div>
                        
                        <div>
                            <label for="education_level" class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-2">
                                Уровень образования
                            </label>
                            <select id="education_level" name="education_level" 
                                    class="w-full px-3 py-2 border border-neutral dark:border-dark-neutral-border rounded-lg focus:outline-none focus:ring-2 focus:ring-color-brands">
                                <option value="среднее" <?php echo $user_data['education_level'] === 'среднее' ? 'selected' : ''; ?>>Среднее</option>
                                <option value="среднее-специальное" <?php echo $user_data['education_level'] === 'среднее-специальное' ? 'selected' : ''; ?>>Среднее специальное</option>
                                <option value="бакалавриат" <?php echo $user_data['education_level'] === 'бакалавриат' ? 'selected' : ''; ?>>Бакалавриат</option>
                                <option value="магистратура" <?php echo $user_data['education_level'] === 'магистратура' ? 'selected' : ''; ?>>Магистратура</option>
                                <option value="аспирантура" <?php echo $user_data['education_level'] === 'аспирантура' ? 'selected' : ''; ?>>Аспирантура</option>
                            </select>
                        </div>
                        
                        <div class="text-sm text-gray-500 dark:text-gray-dark-500">
                            <p><strong>Роль:</strong> <?php echo ucfirst($user_data['role']); ?></p>
                            <p><strong>Дата регистрации:</strong> <?php echo date('d.m.Y', strtotime($user_data['created_at'])); ?></p>
                        </div>
                        
                        <button type="submit" name="update_profile" 
                                class="btn bg-color-brands text-white w-full">
                            Сохранить изменения
                        </button>
                    </form>
                </div>

                <!-- Смена пароля -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                        Смена пароля
                    </h3>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-2">
                                Текущий пароль
                            </label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="w-full px-3 py-2 border border-neutral dark:border-dark-neutral-border rounded-lg focus:outline-none focus:ring-2 focus:ring-color-brands"
                                   required>
                            <p class="text-xs text-gray-400 mt-1">Введите ваш текущий пароль для подтверждения</p>
                        </div>
                        
                        <div>
                            <label for="new_password" class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-2">
                                Новый пароль
                            </label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="w-full px-3 py-2 border border-neutral dark:border-dark-neutral-border rounded-lg focus:outline-none focus:ring-2 focus:ring-color-brands"
                                   minlength="6" required>
                            <p class="text-xs text-gray-400 mt-1">Минимум 6 символов</p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-2">
                                Подтвердите новый пароль
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="w-full px-3 py-2 border border-neutral dark:border-dark-neutral-border rounded-lg focus:outline-none focus:ring-2 focus:ring-color-brands"
                                   minlength="6" required>
                            <p class="text-xs text-gray-400 mt-1">Повторите новый пароль</p>
                        </div>
                        
                        <button type="submit" name="change_password" 
                                class="btn bg-color-brands text-white w-full">
                            Сменить пароль
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <?php require('page/footer.php'); ?>
</body>
</html>