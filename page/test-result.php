<?php
// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

// Получаем ID результата
$result_id = $_GET['result_id'] ?? 0;

// Получаем информацию о результате
$result = $link->query("
    SELECT tr.*, t.title as test_title 
    FROM test_results tr 
    JOIN tests t ON tr.test_id = t.id 
    WHERE tr.id = '$result_id' AND tr.user_id = '{$_SESSION['user']['id_user']}'
")->fetch_assoc();

if (!$result) {
    header("Location: index.php?page=tests");
    exit();
}

// Получаем рекомендации для этого результата
$recommendations = $link->query("
    SELECT r.*, p.title as profession_title, p.description as profession_description
    FROM recommendations r 
    JOIN professions p ON r.profession_id = p.id 
    WHERE r.user_id = '{$_SESSION['user']['id_user']}' 
    AND r.result_type = '{$result['result_type']}'
    ORDER BY r.match_percentage DESC
    LIMIT 3
");
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            Результаты теста
        </h2>
        
        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <a class="capitalize" href="index.php?page=tests">Тестирование</a>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands">Результаты</span>
        </div>

        <!-- Основные результаты -->
        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mb-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 rounded-full bg-green/20 grid place-items-center mx-auto mb-4">
                    <img src="assets/images/icons/icon-check-circle.svg" alt="Успех" class="w-8 h-8">
                </div>
                <h3 class="text-xl font-bold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                    Тест "<?php echo $result['test_title']; ?>" завершен!
                </h3>
                <p class="text-gray-500 dark:text-gray-dark-500">
                    Вы успешно прошли профориентационный тест
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                    <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Набранные баллы</p>
                    <p class="text-2xl font-bold text-gray-1100 dark:text-gray-dark-1100"><?php echo $result['total_score']; ?></p>
                </div>
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                    <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Тип личности</p>
                    <p class="text-xl font-bold text-color-brands"><?php echo ucfirst($result['result_type']); ?></p>
                </div>
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                    <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Дата прохождения</p>
                    <p class="text-sm font-semibold text-gray-1100 dark:text-gray-dark-1100">
                        <?php echo date('d.m.Y H:i', strtotime($result['completed_at'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Рекомендации -->
        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
            <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                Рекомендованные профессии
            </h3>
            
            <?php if ($recommendations->num_rows > 0): ?>
                <div class="grid grid-cols-1 gap-4">
                    <?php while($rec = $recommendations->fetch_assoc()): ?>
                        <div class="flex items-center justify-between p-4 rounded-lg border border-neutral dark:border-dark-neutral-border">
                            <div>
                                <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-1">
                                    <?php echo $rec['profession_title']; ?>
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-dark-500">
                                    <?php echo $rec['profession_description']; ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-3 py-1 rounded-full bg-green/20 text-green text-sm font-semibold">
                                    <?php echo $rec['match_percentage']; ?>% совпадение
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 dark:text-gray-dark-500 text-center py-4">
                    Рекомендации будут доступны после анализа результатов
                </p>
            <?php endif; ?>

            <div class="mt-6 flex gap-4">
                <a href="index.php?page=tests" class="btn bg-color-brands text-white px-6">
                    Пройти другой тест
                </a>
                <a href="index.php?page=main" class="btn border border-neutral text-gray-500 dark:border-dark-neutral-border dark:text-gray-dark-500 px-6">
                    На главную
                </a>
            </div>
        </div>
    </div>
</main>