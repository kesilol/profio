<?php
// Функции для работы с компаниями

// Получить компании для профессии
function getCompaniesForProfession($link, $profession_id) {
    return $link->query("
        SELECT c.*, pc.position_name, pc.experience_level
        FROM companies c
        JOIN profession_companies pc ON c.id = pc.company_id
        WHERE pc.profession_id = '$profession_id'
        ORDER BY c.name ASC
    ");
}

// Получить все компании с фильтрами
function getAllCompanies($link, $filters = []) {
    $sql = "SELECT * FROM companies WHERE 1=1";
    
    if (!empty($filters['industry'])) {
        $sql .= " AND industry = '" . $link->real_escape_string($filters['industry']) . "'";
    }
    
    if (!empty($filters['location'])) {
        $sql .= " AND location LIKE '%" . $link->real_escape_string($filters['location']) . "%'";
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (name LIKE '%" . $link->real_escape_string($filters['search']) . "%' 
                      OR description LIKE '%" . $link->real_escape_string($filters['search']) . "%')";
    }
    
    $sql .= " ORDER BY name ASC";
    
    return $link->query($sql);
}

// Получить уникальные отрасли для фильтров
function getCompanyIndustries($link) {
    return $link->query("SELECT DISTINCT industry FROM companies WHERE industry IS NOT NULL ORDER BY industry");
}
?>