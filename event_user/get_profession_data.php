<?php
session_start();
require('../connect.php');

if ($_SESSION['user']['role'] !== 'администратор') {
    die(json_encode(['success' => false, 'error' => 'Access denied']));
}

$id = $_GET['id'] ?? 0;
$stmt = $link->prepare("SELECT p.*, pd.responsibilities, pd.career_growth, pd.employment_prospects, pd.related_courses, pd.image_url 
                       FROM professions p 
                       LEFT JOIN profession_details pd ON p.id = pd.profession_id 
                       WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($profession = $result->fetch_assoc()) {
    $response = ['success' => true] + $profession;
    // Добавляем детали в отдельный объект для удобства
    $response['details'] = [
        'responsibilities' => $profession['responsibilities'],
        'career_growth' => $profession['career_growth'],
        'employment_prospects' => $profession['employment_prospects'],
        'related_courses' => $profession['related_courses'],
        'image_url' => $profession['image_url']
    ];
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'Profession not found']);
}
?>