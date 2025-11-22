<?php
// Подключаем обработчик ДО любого вывода
require_once('event_user/plans_processor.php');

// Получение данных в зависимости от действия
switch ($action) {
    case 'create':
        $professions = getAllProfessions($link);
        break;
        
    case 'view':
    case 'edit':
        if ($plan_id) {
            $plan = getPlanDetails($link, $plan_id, $user_id);
            if (!$plan) {
                $_SESSION['error_message'] = "План не найден";
                header("Location: index.php?page=plan");
                exit();
            }
            if ($action === 'edit') {
                $professions = getAllProfessions($link);
            }
        }
        break;
        
    case 'list':
    default:
        $plans = getUserPlans($link, $user_id);
        break;
}

// Получение сообщений из сессии
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            <?php
            switch ($action) {
                case 'create': echo 'Создание плана развития'; break;
                case 'view': echo 'План развития'; break;
                case 'edit': echo 'Редактирование плана'; break;
                default: echo 'Мои планы развития';
            }
            ?>
        </h2>
        
        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <a class="capitalize" href="index.php?page=plan">Планы развития</a>
            <?php if ($action !== 'list'): ?>
                <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
                <span class="capitalize text-color-brands">
                    <?php 
                    switch ($action) {
                        case 'create': echo 'Создание'; break;
                        case 'view': echo $plan['title'] ?? 'Просмотр'; break;
                        case 'edit': echo 'Редактирование'; break;
                    }
                    ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- Сообщения об успехе/ошибке -->
        <?php if (!empty($success_message)): ?>
            <div class="rounded-xl bg-green-50 border border-green-200 p-4 mb-6">
                <div class="flex items-center gap-3">
                    <i class="bi bi-check-circle-fill text-green-500 text-lg"></i>
                    <span class="text-green-800"><?php echo $success_message; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="rounded-xl bg-red-50 border border-red-200 p-4 mb-6">
                <div class="flex items-center gap-3">
                    <i class="bi bi-exclamation-circle-fill text-red-500 text-lg"></i>
                    <span class="text-red-800"><?php echo $error_message; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Контент в зависимости от действия -->
        <?php switch ($action): 
            case 'create':
            case 'edit':
                require 'page/components/plan_form.php';
                break;
                
            case 'view':
                require 'page/components/plan_view.php';
                break;
                
            default:
                require 'page/components/plan_list.php';
                break;
        endswitch; ?>
    </div>
</main>