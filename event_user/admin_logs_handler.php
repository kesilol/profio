<?php
session_start();
require_once(__DIR__ . '/../connect.php');

// Функция для записи лога
function logAdminAction($admin_id, $action, $target_type = null, $target_id = null, $details = null) {
    global $link;
    
    $stmt = $link->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issis", $admin_id, $action, $target_type, $target_id, $details);
    return $stmt->execute();
}

// Функция для получения логов с фильтрацией
function getAdminLogs($link, $filters = [], $limit = 50, $offset = 0) {
    $sql = "SELECT al.*, u.name as admin_name, u.email as admin_email 
            FROM admin_logs al
            LEFT JOIN users u ON al.admin_id = u.id
            WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($filters['admin_id'])) {
        $sql .= " AND al.admin_id = ?";
        $params[] = $filters['admin_id'];
        $types .= "i";
    }
    
    if (!empty($filters['action'])) {
        $sql .= " AND al.action LIKE ?";
        $params[] = "%" . $filters['action'] . "%";
        $types .= "s";
    }
    
    if (!empty($filters['target_type'])) {
        $sql .= " AND al.target_type = ?";
        $params[] = $filters['target_type'];
        $types .= "s";
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(al.created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(al.created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $link->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Функция для подсчета общего количества логов
function countAdminLogs($link, $filters = []) {
    $sql = "SELECT COUNT(*) as total FROM admin_logs al WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($filters['admin_id'])) {
        $sql .= " AND al.admin_id = ?";
        $params[] = $filters['admin_id'];
        $types .= "i";
    }
    
    if (!empty($filters['action'])) {
        $sql .= " AND al.action LIKE ?";
        $params[] = "%" . $filters['action'] . "%";
        $types .= "s";
    }
    
    if (!empty($filters['target_type'])) {
        $sql .= " AND al.target_type = ?";
        $params[] = $filters['target_type'];
        $types .= "s";
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(al.created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(al.created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }
    
    $stmt = $link->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Функция для очистки старых логов
function clearOldLogs($link, $days = 90) {
    $stmt = $link->prepare("DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $days);
    return $stmt->execute();
}

// Получение списка типов действий для фильтра
function getActionTypes($link) {
    $result = $link->query("SELECT DISTINCT action FROM admin_logs ORDER BY action");
    $actions = [];
    while ($row = $result->fetch_assoc()) {
        $actions[] = $row['action'];
    }
    return $actions;
}

// Получение списка типов целей для фильтра
function getTargetTypes($link) {
    $result = $link->query("SELECT DISTINCT target_type FROM admin_logs WHERE target_type IS NOT NULL ORDER BY target_type");
    $types = [];
    while ($row = $result->fetch_assoc()) {
        $types[] = $row['target_type'];
    }
    return $types;
}

// Обработка AJAX запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'администратор') {
        echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
        exit();
    }
    
    $admin_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;
    $action = $_POST['action'] ?? '';
    
    // Очистка старых логов
    if ($action === 'clear_logs') {
        $days = intval($_POST['days'] ?? 90);
        if (clearOldLogs($link, $days)) {
            logAdminAction($admin_id, 'Очистка логов', 'admin_logs', null, "Удалены логи старше {$days} дней");
            echo json_encode(['success' => true, 'message' => "Логи старше {$days} дней удалены"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ошибка при очистке логов']);
        }
        exit();
    }
    
    // Экспорт логов в CSV
    if ($action === 'export_csv') {
        $filters = json_decode($_POST['filters'] ?? '{}', true);
        $logs = getAdminLogs($link, $filters, 10000, 0);
        
        $filename = "admin_logs_" . date('Y-m-d_H-i-s') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Администратор', 'Действие', 'Тип цели', 'ID цели', 'Детали', 'Дата и время']);
        
        while ($log = $logs->fetch_assoc()) {
            fputcsv($output, [
                $log['id'],
                $log['admin_name'] . ' (' . $log['admin_email'] . ')',
                $log['action'],
                $log['target_type'],
                $log['target_id'],
                $log['details'],
                $log['created_at']
            ]);
        }
        fclose($output);
        exit();
    }
}

// GET запрос на получение логов
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_logs'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'администратор') {
        echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
        exit();
    }
    
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 50);
    $offset = ($page - 1) * $limit;
    
    $filters = [
        'admin_id' => $_GET['admin_id'] ?? null,
        'action' => $_GET['action'] ?? null,
        'target_type' => $_GET['target_type'] ?? null,
        'date_from' => $_GET['date_from'] ?? null,
        'date_to' => $_GET['date_to'] ?? null
    ];
    
    // Убираем пустые фильтры
    $filters = array_filter($filters);
    
    $logs = getAdminLogs($link, $filters, $limit, $offset);
    $total = countAdminLogs($link, $filters);
    
    $logs_array = [];
    while ($log = $logs->fetch_assoc()) {
        $logs_array[] = $log;
    }
    
    echo json_encode([
        'success' => true,
        'logs' => $logs_array,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ]);
    exit();
}
?>