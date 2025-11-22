<?php
session_start();
require('../connect.php');

if ($_SESSION['user']['role'] !== 'администратор') {
    die(json_encode(['success' => false, 'error' => 'Access denied']));
}

$id = $_GET['id'] ?? 0;
$stmt = $link->prepare("SELECT * FROM educational_institutions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($institution = $result->fetch_assoc()) {
    echo json_encode(['success' => true] + $institution);
} else {
    echo json_encode(['success' => false, 'error' => 'Institution not found']);
}
?>