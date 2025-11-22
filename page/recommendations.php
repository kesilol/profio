<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

$user_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;

// Подключаемся к базе
require_once('connect.php');
require_once('event_user/education_handler.php');
require_once('event_user/companies_handler.php');

// Получаем рекомендации пользователя из базы
$recommendations_query = "
    SELECT r.*, p.title as profession_title, p.description as profession_description,
           p.category, p.salary_range, p.demand_level,
           pd.image_url as profession_image
    FROM recommendations r 
    LEFT JOIN professions p ON r.profession_id = p.id 
    LEFT JOIN profession_details pd ON p.id = pd.profession_id
    WHERE r.user_id = ? 
    ORDER BY r.match_percentage DESC
";

$stmt = $link->prepare($recommendations_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recommendations = $stmt->get_result();

// Инициализация переменной
$recommendations_count = $recommendations ? $recommendations->num_rows : 0;
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px] space-y-8">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            Мои рекомендации
        </h2>
        
        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands">Мои рекомендации</span>
        </div>

        <!-- Основной контент -->
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Левая колонка - Рекомендации -->
            <div class="flex-1 flex flex-col gap-6">
                <!-- Заголовок и описание -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-3">
                        Персональные рекомендации
                    </h3>
                    <p class="text-gray-500 dark:text-gray-dark-500">
                        На основе ваших результатов тестирования мы подобрали профессии, которые лучше всего соответствуют вашим склонностям и способностям.
                        <?php if ($recommendations_count > 0): ?>
                            <span class="font-semibold text-color-brands">Найдено <?php echo $recommendations_count; ?> рекомендаций.</span>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Список рекомендаций -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 flex flex-col gap-6">
                    <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                        Рекомендуемые профессии
                    </h3>
                    
                    <?php if ($recommendations_count > 0): ?>
                        <?php while($rec = $recommendations->fetch_assoc()): 
                            // Получаем ВУЗы и компании для каждой профессии
                            $institutions = getInstitutionsForProfession($link, $rec['profession_id']);
                            $companies = getCompaniesForProfession($link, $rec['profession_id']);
                        ?>
                            <div class="border border-gray-200 dark:border-gray-dark-200 rounded-xl p-6 hover:shadow-md transition-shadow">
                                <!-- Заголовок и процент -->
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                                    <div class="flex items-start gap-4 flex-1">
                                        <?php if (!empty($rec['profession_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($rec['profession_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($rec['profession_title']); ?>"
                                                 class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                        <?php else: ?>
                                            <div class="w-16 h-16 rounded-lg bg-gray-100 dark:bg-gray-dark-100 grid place-items-center flex-shrink-0">
                                                <i class="bi bi-briefcase text-2xl text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100 text-xl mb-2">
                                                <?php echo htmlspecialchars($rec['profession_title'] ?? 'Профессия'); ?>
                                            </h4>
                                            <div class="flex flex-wrap gap-2 mb-2">
                                                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-800 dark:bg-gray-dark-100 dark:text-gray-dark-800">
                                                    <?php echo ucfirst($rec['category'] ?? ''); ?>
                                                </span>
                                                <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    <?php echo ucfirst($rec['demand_level'] ?? ''); ?> спрос
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col items-end gap-2">
                                        <span class="text-lg font-bold text-color-brands">
                                            <?php echo $rec['match_percentage'] ?? 0; ?>%
                                        </span>
                                        <span class="text-sm text-gray-500">совпадение</span>
                                    </div>
                                </div>
                                
                                <!-- Описание профессии -->
                                <?php if (!empty($rec['profession_description'])): ?>
                                    <p class="text-gray-500 dark:text-gray-dark-500 text-sm mb-4">
                                        <?php echo htmlspecialchars($rec['profession_description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <!-- Зарплата -->
                                <?php if (!empty($rec['salary_range'])): ?>
                                    <div class="mb-4">
                                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Зарплата</p>
                                        <p class="text-lg font-bold text-gray-1100 dark:text-gray-dark-1100">
                                            <?php echo $rec['salary_range']; ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Рекомендация -->
                                <?php if (!empty($rec['recommendation_text'])): ?>
                                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-4">
                                        <p class="text-sm text-blue-800 dark:text-blue-200">
                                            <strong>Рекомендация:</strong> <?php echo htmlspecialchars($rec['recommendation_text']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- ВУЗы (первые 2) -->
                                <?php if ($institutions && $institutions->num_rows > 0): ?>
                                    <div class="mb-4">
                                    <p class="text-sm text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                                            <i class="bi bi-mortarboard text-color-brands"></i>
                                            <strong>Где учиться:</strong>
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <?php 
                                            $count = 0;
                                            $institutions->data_seek(0); // Сбрасываем указатель
                                            while ($institution = $institutions->fetch_assoc()): 
                                                if ($count >= 2) break;
                                                $count++;
                                            ?>
                                                <span class="text-xs px-3 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    <?php echo $institution['name']; ?>
                                                </span>
                                            <?php endwhile; ?>
                                            <?php if ($institutions->num_rows > 2): ?>
                                                <span class="text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-dark-100 dark:text-gray-dark-600">
                                                    +<?php echo $institutions->num_rows - 2; ?> ещё
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Компании (первые 2) -->
                                <?php if ($companies && $companies->num_rows > 0): ?>
                                    <div class="mb-4">
                                    <p class="text-sm text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                                            <i class="bi bi-briefcase text-color-brands"></i>
                                            <strong>Где работать:</strong>
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <?php 
                                            $count = 0;
                                            $companies->data_seek(0); // Сбрасываем указатель
                                            while ($company = $companies->fetch_assoc()): 
                                                if ($count >= 2) break;
                                                $count++;
                                            ?>
                                                <span class="text-xs px-3 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    <?php echo $company['name']; ?>
                                                </span>
                                            <?php endwhile; ?>
                                            <?php if ($companies->num_rows > 2): ?>
                                                <span class="text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-dark-100 dark:text-gray-dark-600">
                                                    +<?php echo $companies->num_rows - 2; ?> ещё
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Кнопки действий -->
                                <div class="flex flex-wrap gap-2 pt-4 border-t border-gray-200 dark:border-gray-dark-200">
                                    <?php if (!empty($rec['profession_id'])): ?>
                                        <a href="index.php?page=profession-detail&id=<?php echo $rec['profession_id']; ?>" 
                                           class="btn bg-color-brands text-white btn-sm flex items-center gap-2">
                                            <i class="bi bi-eye"></i>
                                            Подробнее о профессии
                                        </a>
                                    <?php endif; ?>
                                    <a href="index.php?page=professions" class="btn btn-outline btn-sm">
                                        Все профессии
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-dark-100 grid place-items-center mx-auto mb-4">
                                <i class="bi bi-stars text-2xl text-gray-400"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                                Рекомендации отсутствуют
                            </h4>
                            <p class="text-gray-500 dark:text-gray-dark-500 mb-4">
                                Пройдите тестирование, чтобы получить персональные рекомендации
                            </p>
                            <a href="index.php?page=tests" class="btn bg-color-brands text-white">
                                Пройти тестирование
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Правая колонка - Дополнительно -->
            <div class="lg:w-80 flex flex-col gap-6">
                <!-- Статистика -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 flex flex-col gap-4">
                    <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                        <i class="bi bi-graph-up text-color-brands"></i> Ваша статистика
                    </h3>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 dark:text-gray-dark-500">Всего рекомендаций:</span>
                        <span class="font-semibold"><?php echo $recommendations_count; ?></span>
                    </div>

                    <?php if ($recommendations_count > 0): ?>
                        <?php 
                        $recommendations->data_seek(0);
                        $max_match = 0;
                        while($rec = $recommendations->fetch_assoc()) {
                            if ($rec['match_percentage'] > $max_match) {
                                $max_match = $rec['match_percentage'];
                            }
                        }
                        ?>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 dark:text-gray-dark-500">Лучшее совпадение:</span>
                            <span class="font-semibold text-green-600"><?php echo $max_match; ?>%</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="pt-4">
                        <a href="index.php?page=my-results" class="btn btn-outline w-full flex items-center gap-2">
                            <i class="bi bi-clipboard-data"></i>
                            Мои результаты тестов
                        </a>
                    </div>
                </div>

                <!-- Быстрые действия -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 flex flex-col gap-3">
                    <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                        <i class="bi bi-lightning text-color-brands"></i> Быстрые действия
                    </h3>
                    <a href="index.php?page=tests" class="btn btn-outline w-full justify-start gap-2">
                        <i class="bi bi-pencil-square"></i>
                        Пройти тестирование
                    </a>
                    <a href="index.php?page=professions" class="btn btn-outline w-full justify-start gap-2">
                        <i class="bi bi-briefcase"></i>
                        Все профессии
                    </a>
                    <a href="index.php?page=my-results" class="btn btn-outline w-full justify-start gap-2">
                        <i class="bi bi-graph-up"></i>
                        Мои результаты
                    </a>
                </div>

                <!-- Советы -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 flex flex-col gap-3 text-sm">
                    <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                        <i class="bi bi-lightbulb text-yellow-500"></i> Советы
                    </h3>
                    <div class="flex items-start gap-2">
                        <i class="bi bi-check-circle text-green-500 mt-0.5"></i>
                        <span class="text-gray-600 dark:text-gray-dark-400">Изучите подробнее рекомендованные профессии</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="bi bi-check-circle text-green-500 mt-0.5"></i>
                        <span class="text-gray-600 dark:text-gray-dark-400">Пройдите дополнительные тесты для уточнения</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="bi bi-check-circle text-green-500 mt-0.5"></i>
                        <span class="text-gray-600 dark:text-gray-dark-400">Сравните рекомендации с вашими интересами</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>