<?php
// Подключаем обработчик профессий
require_once('event_user/professions_handler.php');

// Получаем параметры фильтрации
$filters = [
    'category' => $_GET['category'] ?? '',
    'demand' => $_GET['demand'] ?? '',
    'education' => $_GET['education'] ?? '',
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? 'title'
];

// Получаем данные
$professions = getProfessionsWithFilters($link, $filters);
$filterOptions = getFilterOptions($link);
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            Каталог профессий
        </h2>

        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands">Каталог профессий</span>
        </div>

        <!-- Поиск и фильтры -->
        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mb-6">
            <form method="GET" action="" class="space-y-6">
                <input type="hidden" name="page" value="professions">

                <!-- Строка поиска и сортировки -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Поиск -->
                    <div class="bg-gray-100 flex rounded-xl py-3 px-4 dark:bg-gray-dark-100">
                        <img src="assets/images/icons/icon-search-normal.svg" alt="Поиск">
                        <input class="input w-full bg-transparent outline-none pl-2 h-5 text-gray-300 focus:!outline-none placeholder:text-gray-300 dark:placeholder:text-gray-dark-300 placeholder:font-semibold"
                            type="text" name="search" placeholder="Поиск по профессиям..."
                            value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>

                    <!-- Сортировка -->
                    <select name="sort" class="bg-gray-100 rounded-xl py-3 px-4 dark:bg-gray-dark-100 border-none focus:outline-none">
                        <option value="title_asc" <?php echo $filters['sort'] == 'title_asc' ? 'selected' : ''; ?>>По названию (А-Я)</option>
                        <option value="title_desc" <?php echo $filters['sort'] == 'title_desc' ? 'selected' : ''; ?>>По названию (Я-А)</option>
                        <option value="salary_high" <?php echo $filters['sort'] == 'salary_high' ? 'selected' : ''; ?>>По зарплате (высокая)</option>
                        <option value="salary_low" <?php echo $filters['sort'] == 'salary_low' ? 'selected' : ''; ?>>По зарплате (низкая)</option>
                    </select>

                    <!-- Кнопка сброса -->
                    <a href="index.php?page=professions" class="btn border border-neutral text-gray-500 dark:border-dark-neutral-border dark:text-gray-dark-500 px-6 text-center flex items-center justify-center">
                        Сбросить фильтры
                    </a>
                </div>

                <!-- Фильтры -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Фильтр по категории -->
                    <div>
                        <label class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-2">Категория</label>
                        <select name="category" class="bg-gray-100 rounded-xl py-3 px-4 dark:bg-gray-dark-100 border-none focus:outline-none w-full">
                            <option value="">Все категории</option>
                            <?php foreach ($filterOptions['categories'] as $cat): ?>
                                <option value="<?php echo $cat['category']; ?>" <?php echo $filters['category'] == $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Фильтр по востребованности -->
                    <div>
                        <label class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-2">Востребованность</label>
                        <select name="demand" class="bg-gray-100 rounded-xl py-3 px-4 dark:bg-gray-dark-100 border-none focus:outline-none w-full">
                            <option value="">Любая востребованность</option>
                            <?php foreach ($filterOptions['demand_levels'] as $demand): ?>
                                <option value="<?php echo $demand['demand_level']; ?>" <?php echo $filters['demand'] == $demand['demand_level'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($demand['demand_level']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Фильтр по образованию -->
                    <div>
                        <label class="block text-sm text-gray-500 dark:text-gray-dark-500 mb-2">Образование</label>
                        <select name="education" class="bg-gray-100 rounded-xl py-3 px-4 dark:bg-gray-dark-100 border-none focus:outline-none w-full">
                            <option value="">Любое образование</option>
                            <?php foreach ($filterOptions['education_levels'] as $edu): ?>
                                <option value="<?php echo $edu['education_level']; ?>" <?php echo $filters['education'] == $edu['education_level'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($edu['education_level']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Кнопка применения фильтров -->
                <div class="text-center pt-4">
                    <button type="submit" class="btn bg-color-brands text-white px-8">
                        Применить фильтры
                    </button>
                </div>
            </form>
        </div>

        <!-- Результаты -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">
                    Найдено профессий: <?php echo $professions->num_rows; ?>
                </h3>
            </div>

            <?php if ($professions->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($profession = $professions->fetch_assoc()): ?>
                        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 hover:shadow-lg transition-shadow flex flex-col h-full">
                            <!-- Заголовок и иконка -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg grid place-items-center">
                                        <i class="bi <?php echo PROFESSION_ICONS[$profession['category']] ?? 'bi-briefcase'; ?> text-lg"></i>
                                    </div>
                                    <h4 class="text-normal font-semibold text-gray-1100 dark:text-gray-dark-1100">
                                        <?php echo $profession['title']; ?>
                                    </h4>
                                </div>
                            </div>

                            <!-- Описание -->
                            <p class="text-gray-500 dark:text-gray-dark-500 text-sm mb-4 line-clamp-3">
                                <?php echo $profession['description']; ?>
                            </p>

                            <!-- Теги -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <span class="text-xs px-2 py-1 rounded-full">
                                    <?php echo ucfirst($profession['category']); ?>
                                </span>
                                <span class="text-xs px-2 py-1 rounded-full <?php echo DEMAND_COLORS[$profession['demand_level']] ?? ''; ?>">
                                    <?php echo ucfirst($profession['demand_level']); ?> спрос
                                </span>
                                <span class="text-xs px-2 py-1 rounded-full">
                                    <?php echo $profession['education_level']; ?>
                                </span>
                            </div>

                            <!-- Зарплата -->
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Зарплата</p>
                                <p class="text-lg font-bold text-gray-1100 dark:text-gray-dark-1100">
                                    <?php echo $profession['salary_range']; ?>
                                </p>
                            </div>

                            <!-- Необходимые навыки -->
                            <div class="mb-4">
                                <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-2">Ключевые навыки:</p>
                                <p class="text-sm text-gray-1100 dark:text-gray-dark-1100 line-clamp-2">
                                    <?php echo $profession['required_skills']; ?>
                                </p>
                            </div>

                            <!-- Кнопка деталей - прижата к низу -->
                            <div class="mt-auto pt-4">
                                <a href="index.php?page=profession-detail&id=<?php echo $profession['id']; ?>"
                                    class="btn bg-color-brands text-white w-full text-center inline-flex items-center justify-center">
                                    Подробнее о профессии
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <!-- Нет результатов -->
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-12 text-center">
                    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-dark-100 grid place-items-center mx-auto mb-4">
                        <i class="bi bi-search text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
                        Профессии не найдены
                    </h3>
                    <p class="text-gray-500 dark:text-gray-dark-500 mb-6">
                        Попробуйте изменить параметры поиска или фильтры
                    </p>
                    <a href="index.php?page=professions" class="btn bg-color-brands text-white px-6">
                        Сбросить фильтры
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>