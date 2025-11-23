<?php
// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

$user_id = $_SESSION['user']['id_user'];

// Получаем все результаты тестов пользователя
$results = $link->query("
    SELECT tr.*, t.title as test_title, t.description as test_description,
           COUNT(r.id) as recommendations_count
    FROM test_results tr 
    JOIN tests t ON tr.test_id = t.id 
    LEFT JOIN recommendations r ON tr.user_id = r.user_id AND tr.result_type = r.result_type
    WHERE tr.user_id = '$user_id' 
    GROUP BY tr.id
    ORDER BY tr.completed_at DESC
");
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            Мои результаты тестов
        </h2>

        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands">Мои результаты</span>
        </div>

        <?php if ($results->num_rows > 0): ?>
            <!-- Список результатов -->
            <div class="grid grid-cols-1 gap-6">
                <?php while ($result = $results->fetch_assoc()): ?>
                    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                        <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-3">
                            <div class="flex items-center gap-4 w-full md:w-auto">
                                <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg grid place-items-center bg-blue/20">
                                    <img src="assets/images/icons/icon-doc.svg" alt="Тест">
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">
                                        <?php echo $result['test_title']; ?>
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-dark-500">
                                        Пройден: <?php echo date('d.m.Y в H:i', strtotime($result['completed_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs px-3 py-1 rounded-full bg-green/20 text-green font-semibold self-start md:self-auto">
                                <?php echo ucfirst($result['result_type']); ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                                <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Набранные баллы</p>
                                <p class="text-xl font-bold text-gray-1100 dark:text-gray-dark-1100">
                                    <?php echo $result['total_score']; ?>
                                </p>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                                <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Рекомендации</p>
                                <p class="text-xl font-bold text-color-brands">
                                    <?php echo $result['recommendations_count']; ?>
                                </p>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-dark-50">
                                <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Тип результата</p>
                                <p class="text-sm font-semibold text-gray-1100 dark:text-gray-dark-1100">
                                    <?php echo $result['result_type']; ?>
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <a href="index.php?page=test-result&result_id=<?php echo $result['id']; ?>"
                                class="btn bg-color-brands text-white px-4 py-2 text-sm">
                                Подробнее
                            </a>
                            <a href="index.php?page=test&id=<?php echo $result['test_id']; ?>"
                                class="btn border border-neutral text-gray-500 dark:border-dark-neutral-border dark:text-gray-dark-500 px-4 py-2 text-sm">
                                Пройти снова
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <!-- Нет результатов -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-dark-100 grid place-items-center mx-auto mb-4">
                    <img src="assets/images/icons/icon-doc.svg" alt="Нет результатов" class="w-8 h-8 opacity-50">
                </div>
                <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                    Результаты тестов отсутствуют
                </h3>
                <p class="text-gray-500 dark:text-gray-dark-500 mb-6">
                    Пройдите свой первый профориентационный тест, чтобы увидеть результаты здесь
                </p>
                <a href="index.php?page=tests" class="btn bg-color-brands text-white px-6">
                    Пройти тест
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>