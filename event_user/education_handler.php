<?php
// Функции для работы с учебными заведениями

// Получить учебные заведения для профессии
function getInstitutionsForProfession($link, $profession_id) {
    return $link->query("
        SELECT ei.*, pi.program_name, pi.duration, pi.cost
        FROM educational_institutions ei
        JOIN profession_institutions pi ON ei.id = pi.institution_id
        WHERE pi.profession_id = '$profession_id'
        ORDER BY ei.type DESC, ei.name ASC
    ");
}

// Получить все учебные заведения с фильтрами
function getAllInstitutions($link, $filters = []) {
    $sql = "SELECT * FROM educational_institutions WHERE 1=1";
    
    if (!empty($filters['type'])) {
        $sql .= " AND type = '" . $link->real_escape_string($filters['type']) . "'";
    }
    
    if (!empty($filters['location'])) {
        $sql .= " AND location LIKE '%" . $link->real_escape_string($filters['location']) . "%'";
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (name LIKE '%" . $link->real_escape_string($filters['search']) . "%' 
                      OR description LIKE '%" . $link->real_escape_string($filters['search']) . "%')";
    }
    
    $sql .= " ORDER BY type DESC, name ASC";
    
    return $link->query($sql);
}
?>