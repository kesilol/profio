<?php
function getProfessionDetail($link, $profession_id) {
    $profession = $link->query("
        SELECT p.*, pd.responsibilities, pd.career_growth, pd.employment_prospects, pd.related_courses, pd.image_url
        FROM professions p 
        LEFT JOIN profession_details pd ON p.id = pd.profession_id 
        WHERE p.id = '$profession_id'
    ")->fetch_assoc();
    
    return $profession;
}

function getSimilarProfessions($link, $profession_id, $category) {
    $similar_professions = $link->query("
        SELECT * FROM professions 
        WHERE category = '$category' 
        AND id != '$profession_id'
        ORDER BY title ASC 
        LIMIT 3
    ");
    
    return $similar_professions;
}

// Маппинг иконок
define('PROFESSION_ICONS_DETAIL', [
    'техническая' => 'bi-cpu',
    'гуманитарная' => 'bi-people',
    'творческая' => 'bi-palette',
    'научная' => 'bi-flask',
    'бизнес' => 'bi-graph-up'
]);
?>