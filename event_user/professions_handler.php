<?php
function getProfessionsWithFilters($link, $filters = []) {
    $category_filter = $filters['category'] ?? '';
    $demand_filter = $filters['demand'] ?? '';
    $education_filter = $filters['education'] ?? '';
    $search_query = $filters['search'] ?? '';
    $sort_by = $filters['sort'] ?? 'title';

    $query = "SELECT * FROM professions WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search_query)) {
        $query .= " AND (title LIKE ? OR description LIKE ? OR required_skills LIKE ?)";
        $search_term = "%$search_query%";
        $params = array_merge($params, [$search_term, $search_term, $search_term]);
        $types .= "sss";
    }

    if (!empty($category_filter)) {
        $query .= " AND category = ?";
        $params[] = $category_filter;
        $types .= "s";
    }

    if (!empty($demand_filter)) {
        $query .= " AND demand_level = ?";
        $params[] = $demand_filter;
        $types .= "s";
    }

    if (!empty($education_filter)) {
        $query .= " AND education_level = ?";
        $params[] = $education_filter;
        $types .= "s";
    }

    // Правильная сортировка
    $sort_options = [
        'title_asc' => 'title ASC',
        'title_desc' => 'title DESC',
        'salary_high' => "
            CASE 
                WHEN salary_range LIKE '% - %' THEN CAST(SUBSTRING_INDEX(salary_range, ' - ', -1) AS UNSIGNED)
                WHEN salary_range REGEXP '[0-9]+' THEN CAST(SUBSTRING_INDEX(salary_range, ' ', 1) AS UNSIGNED)
                ELSE 0 
            END DESC,
            title ASC
        ",
        'salary_low' => "
            CASE 
                WHEN salary_range LIKE '% - %' THEN CAST(SUBSTRING_INDEX(salary_range, ' - ', 1) AS UNSIGNED)
                WHEN salary_range REGEXP '[0-9]+' THEN CAST(SUBSTRING_INDEX(salary_range, ' ', 1) AS UNSIGNED)
                ELSE 0 
            END ASC,
            title ASC
        "
    ];

    $query .= " ORDER BY " . ($sort_options[$sort_by] ?? 'title ASC');

    $stmt = $link->prepare($query);
    if ($stmt === false) {
        error_log("SQL Error: " . $link->error);
        return $link->query("SELECT * FROM professions ORDER BY title ASC");
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt->get_result();
}

function getFilterOptions($link) {
    return [
        'categories' => $link->query("SELECT DISTINCT category FROM professions ORDER BY category")->fetch_all(MYSQLI_ASSOC),
        'demand_levels' => $link->query("SELECT DISTINCT demand_level FROM professions ORDER BY FIELD(demand_level, 'высокий', 'средний', 'низкий')")->fetch_all(MYSQLI_ASSOC),
        'education_levels' => $link->query("SELECT DISTINCT education_level FROM professions ORDER BY FIELD(education_level, 'среднее', 'среднее-специальное', 'бакалавриат', 'магистратура', 'аспирантура')")->fetch_all(MYSQLI_ASSOC)
    ];
}

// Константы для фронтенда
define('PROFESSION_ICONS', [
    'техническая' => 'bi-cpu',
    'гуманитарная' => 'bi-people',
    'творческая' => 'bi-palette',
    'научная' => 'bi-flask',
    'бизнес' => 'bi-graph-up'
]);

define('DEMAND_COLORS', [
    'высокий' => 'bg-green/20 text-green',
    'средний' => 'bg-yellow/20 text-yellow', 
    'низкий' => 'bg-red/20 text-red'
]);

// Функция для получения HTML карточки профессии (для AJAX)
function getProfessionCardHTML($profession) {
    $icon = PROFESSION_ICONS[$profession['category']] ?? 'bi-briefcase';
    $demand_class = DEMAND_COLORS[$profession['demand_level']] ?? 'bg-blue-100 text-blue-800';
    
    ob_start();
    ?>
    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 hover:shadow-lg transition-shadow flex flex-col h-full">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg grid place-items-center">
                    <i class="bi <?php echo $icon; ?> text-lg"></i>
                </div>
                <h4 class="text-normal font-semibold text-gray-1100 dark:text-gray-dark-1100">
                    <?php echo htmlspecialchars($profession['title']); ?>
                </h4>
            </div>
        </div>

        <p class="text-gray-500 dark:text-gray-dark-500 text-sm mb-4 line-clamp-3">
            <?php echo htmlspecialchars($profession['description']); ?>
        </p>

        <div class="flex flex-wrap gap-2 mb-4">
            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700">
                <?php echo ucfirst($profession['category']); ?>
            </span>
            <span class="text-xs px-2 py-1 rounded-full <?php echo $demand_class; ?>">
                <?php echo ucfirst($profession['demand_level']); ?> спрос
            </span>
            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700">
                <?php echo $profession['education_level']; ?>
            </span>
        </div>

        <div class="mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Зарплата</p>
            <p class="text-lg font-bold text-gray-1100 dark:text-gray-dark-1100">
                <?php echo $profession['salary_range']; ?>
            </p>
        </div>

        <div class="mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-2">Ключевые навыки:</p>
            <p class="text-sm text-gray-1100 dark:text-gray-dark-1100 line-clamp-2">
                <?php echo htmlspecialchars($profession['required_skills']); ?>
            </p>
        </div>

        <div class="mt-auto pt-4">
            <a href="index.php?page=profession-detail&id=<?php echo $profession['id']; ?>"
                class="btn bg-color-brands text-white w-full text-center inline-flex items-center justify-center">
                Подробнее о профессии
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Функция для AJAX ответа (возвращает только результаты)
function returnAjaxResults($link, $filters) {
    $professions = getProfessionsWithFilters($link, $filters);
    $count = $professions->num_rows;
    ?>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100" id="resultsCount">
            Найдено профессий: <?php echo $count; ?>
        </h3>
    </div>
    <div id="professionsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if ($count > 0): ?>
            <?php while ($profession = $professions->fetch_assoc()): ?>
                <?php echo getProfessionCardHTML($profession); ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full">
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
                    <button onclick="resetFilters()" class="btn bg-color-brands text-white px-6">
                        Сбросить фильтры
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>