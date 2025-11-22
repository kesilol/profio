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
?>