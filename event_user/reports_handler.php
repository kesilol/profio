<?php
// Функции для работы с отчетами

// Получить данные отчета для студента
function getStudentReportData($link, $user_id) {
    $data = [];
    
    try {
        // Основная информация о пользователе
        $user_query = $link->prepare("SELECT name, email, education_level FROM users WHERE id = ?");
        if (!$user_query) {
            throw new Exception("Ошибка подготовки запроса пользователя: " . $link->error);
        }
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        $data['user'] = $user_result->fetch_assoc();
        
        // Статистика пользователя
        $stats_query = $link->prepare("
            SELECT 
                (SELECT COUNT(*) FROM test_results WHERE user_id = ?) as tests_count,
                (SELECT COUNT(*) FROM recommendations WHERE user_id = ?) as recommendations_count,
                (SELECT COUNT(*) FROM development_plans WHERE user_id = ?) as plans_count
        ");
        if (!$stats_query) {
            throw new Exception("Ошибка подготовки запроса статистики: " . $link->error);
        }
        $stats_query->bind_param("iii", $user_id, $user_id, $user_id);
        $stats_query->execute();
        $stats_result = $stats_query->get_result();
        $stats = $stats_result->fetch_assoc();
        $data['user']['tests_count'] = $stats['tests_count'] ?? 0;
        $data['user']['recommendations_count'] = $stats['recommendations_count'] ?? 0;
        $data['user']['plans_count'] = $stats['plans_count'] ?? 0;
        
        // Результаты тестов
        $tests_query = $link->prepare("
            SELECT tr.*, t.title as test_name, t.description as test_description
            FROM test_results tr
            JOIN tests t ON tr.test_id = t.id
            WHERE tr.user_id = ?
            ORDER BY tr.completed_at DESC
        ");
        if (!$tests_query) {
            throw new Exception("Ошибка подготовки запроса тестов: " . $link->error);
        }
        $tests_query->bind_param("i", $user_id);
        $tests_query->execute();
        $data['test_results'] = $tests_query->get_result();
        
        // УНИКАЛЬНЫЕ рекомендации (только лучший процент для каждой профессии)
        $rec_query = $link->prepare("
            SELECT r.*, p.title as profession_title, p.category, p.salary_range, p.demand_level,
                   p.description as profession_description
            FROM recommendations r
            JOIN professions p ON r.profession_id = p.id
            WHERE r.user_id = ?
            AND (r.profession_id, r.match_percentage) IN (
                SELECT profession_id, MAX(match_percentage)
                FROM recommendations
                WHERE user_id = ?
                GROUP BY profession_id
            )
            ORDER BY r.match_percentage DESC
        ");
        if (!$rec_query) {
            throw new Exception("Ошибка подготовки запроса рекомендаций: " . $link->error);
        }
        $rec_query->bind_param("ii", $user_id, $user_id);
        $rec_query->execute();
        $data['recommendations'] = $rec_query->get_result();

        // Получаем изображения для рекомендаций
        if ($data['recommendations'] && $data['recommendations']->num_rows > 0) {
            $data['recommendations']->data_seek(0);
            $recommendations_with_images = [];
            while ($rec = $data['recommendations']->fetch_assoc()) {
                // Получаем изображение для профессии
                $image_query = $link->prepare("SELECT image_url FROM profession_details WHERE profession_id = ?");
                if ($image_query) {
                    $image_query->bind_param("i", $rec['profession_id']);
                    $image_query->execute();
                    $image_result = $image_query->get_result();
                    $image_data = $image_result->fetch_assoc();
                    $rec['image_url'] = $image_data['image_url'] ?? null;
                    $image_query->close();
                }
                $recommendations_with_images[] = $rec;
            }
            // Сохраняем массив с изображениями
            $data['recommendations_with_images'] = $recommendations_with_images;
        }
        
        // Активный план развития (не завершенный и прогресс не 100%)
        $plan_query = $link->prepare("
            SELECT dp.*, 
                   (SELECT COUNT(*) FROM plan_tasks WHERE plan_id = dp.id) as total_tasks,
                   (SELECT COUNT(*) FROM plan_tasks WHERE plan_id = dp.id AND is_completed = 1) as completed_tasks
            FROM development_plans dp
            WHERE dp.user_id = ? AND dp.is_completed = 0
            HAVING completed_tasks < total_tasks
            ORDER BY dp.created_at DESC
            LIMIT 1
        ");
        if (!$plan_query) {
            throw new Exception("Ошибка подготовки запроса плана: " . $link->error);
        }
        $plan_query->bind_param("i", $user_id);
        $plan_query->execute();
        $plan_result = $plan_query->get_result();
        $data['development_plan'] = $plan_result->fetch_assoc();
        
        // Задачи плана развития
        if ($data['development_plan']) {
            $tasks_query = $link->prepare("SELECT * FROM plan_tasks WHERE plan_id = ? ORDER BY task_order ASC");
            if (!$tasks_query) {
                throw new Exception("Ошибка подготовки запроса задач: " . $link->error);
            }
            $tasks_query->bind_param("i", $data['development_plan']['id']);
            $tasks_query->execute();
            $data['plan_tasks'] = $tasks_query->get_result();
        }
        
    } catch (Exception $e) {
        $data['error'] = "Ошибка при получении данных: " . $e->getMessage();
        error_log("Report Error: " . $e->getMessage());
    }
    
    return $data;
}

// Получить данные отчета для куратора
function getCuratorReportData($link, $curator_id) {
    $data = [];
    
    try {
        // Получаем список студентов куратора
        $students_list_query = $link->prepare("
            SELECT student_id FROM curator_students WHERE curator_id = ?
        ");
        $students_list_query->bind_param("i", $curator_id);
        $students_list_query->execute();
        $students_list_result = $students_list_query->get_result();
        
        $student_ids = [];
        while ($row = $students_list_result->fetch_assoc()) {
            $student_ids[] = $row['student_id'];
        }
        
        // Подсчет студентов с рекомендациями
        $students_with_rec = 0;
        if (!empty($student_ids)) {
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
            $rec_students_query = $link->prepare("
                SELECT COUNT(DISTINCT user_id) as count 
                FROM recommendations 
                WHERE user_id IN ($placeholders)
            ");
            $types = str_repeat('i', count($student_ids));
            $rec_students_query->bind_param($types, ...$student_ids);
            $rec_students_query->execute();
            $rec_result = $rec_students_query->get_result();
            $students_with_rec = $rec_result->fetch_assoc()['count'] ?? 0;
        }
        
        // Общая статистика
        $stats_query = $link->prepare("
            SELECT 
                COUNT(DISTINCT cs.student_id) as total_students,
                COUNT(DISTINCT tr.id) as total_tests,
                COUNT(DISTINCT dp.id) as total_plans,
                AVG(tr.total_score) as avg_test_score
            FROM curator_students cs
            LEFT JOIN test_results tr ON cs.student_id = tr.user_id
            LEFT JOIN development_plans dp ON cs.student_id = dp.user_id
            WHERE cs.curator_id = ?
        ");
        if (!$stats_query) {
            throw new Exception("Ошибка подготовки запроса статистики куратора: " . $link->error);
        }
        $stats_query->bind_param("i", $curator_id);
        $stats_query->execute();
        $stats_result = $stats_query->get_result();
        $data['overall_stats'] = $stats_result->fetch_assoc();
        
        // Добавляем количество студентов с рекомендациями
        $data['overall_stats']['students_with_recommendations'] = $students_with_rec;
        
        // Список студентов
        $students_query = $link->prepare("
            SELECT u.id, u.name, u.email, u.education_level,
                   COUNT(DISTINCT tr.id) as tests_completed,
                   COUNT(DISTINCT r.id) as recommendations_count,
                   MAX(tr.completed_at) as last_test_date
            FROM curator_students cs
            JOIN users u ON cs.student_id = u.id
            LEFT JOIN test_results tr ON u.id = tr.user_id
            LEFT JOIN recommendations r ON u.id = r.user_id
            WHERE cs.curator_id = ?
            GROUP BY u.id, u.name, u.email, u.education_level
            ORDER BY u.name
        ");
        if (!$students_query) {
            throw new Exception("Ошибка подготовки запроса обучающихся: " . $link->error);
        }
        $students_query->bind_param("i", $curator_id);
        $students_query->execute();
        $data['students'] = $students_query->get_result();
        
        // Статистика по тестам
        $test_stats_query = $link->prepare("
            SELECT 
                t.id,
                t.title,
                COUNT(tr.id) as completions,
                COUNT(DISTINCT tr.user_id) as unique_students,
                AVG(tr.total_score) as avg_score
            FROM tests t
            JOIN test_results tr ON t.id = tr.test_id
            JOIN curator_students cs ON tr.user_id = cs.student_id
            WHERE cs.curator_id = ?
            GROUP BY t.id, t.title
            ORDER BY completions DESC
        ");
        if (!$test_stats_query) {
            throw new Exception("Ошибка подготовки запроса статистики тестов: " . $link->error);
        }
        $test_stats_query->bind_param("i", $curator_id);
        $test_stats_query->execute();
        $data['test_stats'] = $test_stats_query->get_result();
        
        // Распределение по категориям профессий (уникальные рекомендации)
        $prof_stats_query = $link->prepare("
            SELECT 
                p.category,
                COUNT(DISTINCT r.id) as recommendations_count,
                AVG(r.match_percentage) as avg_match
            FROM recommendations r
            JOIN professions p ON r.profession_id = p.id
            JOIN curator_students cs ON r.user_id = cs.student_id
            WHERE cs.curator_id = ?
            AND (r.profession_id, r.match_percentage) IN (
                SELECT profession_id, MAX(match_percentage)
                FROM recommendations r2
                WHERE r2.user_id = r.user_id
                GROUP BY profession_id
            )
            GROUP BY p.category
            ORDER BY recommendations_count DESC
        ");
        if (!$prof_stats_query) {
            throw new Exception("Ошибка подготовки запроса статистики профессий: " . $link->error);
        }
        $prof_stats_query->bind_param("i", $curator_id);
        $prof_stats_query->execute();
        $data['profession_stats'] = $prof_stats_query->get_result();
        
    } catch (Exception $e) {
        $data['error'] = "Ошибка при получении данных: " . $e->getMessage();
        error_log("Curator Report Error: " . $e->getMessage());
    }
    
    return $data;
}

// Функции для получения мест обучения и работы
function getInstitutionsForProfessionReport($link, $profession_id, $limit = 3) {
    $query = "
        SELECT ei.id, ei.name, ei.type, ei.location 
        FROM educational_institutions ei
        INNER JOIN profession_institutions pi ON ei.id = pi.institution_id
        WHERE pi.profession_id = ?
        LIMIT ?
    ";
    
    $stmt = $link->prepare($query);
    if (!$stmt) {
        error_log("Ошибка подготовки запроса учебных заведений: " . $link->error);
        return false;
    }
    $stmt->bind_param("ii", $profession_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

function getCompaniesForProfessionReport($link, $profession_id, $limit = 3) {
    $query = "
        SELECT c.id, c.name, c.industry, c.location 
        FROM companies c
        INNER JOIN profession_companies pc ON c.id = pc.company_id
        WHERE pc.profession_id = ?
        LIMIT ?
    ";
    
    $stmt = $link->prepare($query);
    if (!$stmt) {
        error_log("Ошибка подготовки запроса компаний: " . $link->error);
        return false;
    }
    $stmt->bind_param("ii", $profession_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Функция для получения детальной информации о программе обучения
function getProgramDetails($link, $profession_id, $institution_id) {
    $query = "SELECT program_name, duration, cost FROM profession_institutions WHERE profession_id = ? AND institution_id = ?";
    $stmt = $link->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ii", $profession_id, $institution_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return false;
}

// Функция для получения детальной информации о позиции в компании
function getPositionDetails($link, $profession_id, $company_id) {
    $query = "SELECT position_name, experience_level FROM profession_companies WHERE profession_id = ? AND company_id = ?";
    $stmt = $link->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ii", $profession_id, $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return false;
}

// Функция для скачивания PDF отчета
function downloadStudentPDF($link, $user_id) {
    require_once('pdf_generator.php');
    
    // Получаем данные для отчета
    $report_data = getStudentReportData($link, $user_id);
    
    if (isset($report_data['error'])) {
        die("Ошибка при получении данных отчета: " . $report_data['error']);
    }
    
    // Получаем информацию о пользователе
    $user_query = $link->prepare("SELECT name, email, education_level FROM users WHERE id = ?");
    if (!$user_query) {
        die("Ошибка подготовки запроса пользователя: " . $link->error);
    }
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user_info = $user_result->fetch_assoc();
    
    if (!$user_info) {
        die("Пользователь не найден");
    }
    
    // Генерируем PDF
    $pdf_content = generateStudentPDFReport($report_data, $user_info, $link);
    
    // Отправляем файл
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="profio_report_' . date('Y-m-d') . '.pdf"');
    header('Content-Length: ' . strlen($pdf_content));
    
    echo $pdf_content;
    exit();
}

function getAdminReportData($link) {
    $data = [];
    
    try {
        // Общая статистика системы
        $data['overall_stats'] = [
            'total_students' => getTotalStudents($link),
            'total_curators' => getTotalCurators($link),
            'total_tests' => getTotalTestsCompleted($link),
            'total_recommendations' => getTotalRecommendations($link),
            'active_plans' => getActiveDevelopmentPlans($link)
        ];
        
        // Статистика по тестам
        $data['test_stats'] = getAdminTestStats($link);
        
        // Последняя активность
        $data['recent_activity'] = getRecentActivity($link);
        
    } catch (Exception $e) {
        $data['error'] = "Ошибка загрузки данных администратора: " . $e->getMessage();
        error_log("Admin Report Error: " . $e->getMessage());
    }
    
    return $data;
}

// Вспомогательные функции для администратора
function getTotalStudents($link) {
    $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'обучающийся'";
    $result = mysqli_query($link, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['count'];
    }
    return 0;
}

function getTotalCurators($link) {
    $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'куратор'";
    $result = mysqli_query($link, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['count'];
    }
    return 0;
}

function getTotalTestsCompleted($link) {
    $sql = "SELECT COUNT(*) as count FROM test_results";
    $result = mysqli_query($link, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['count'];
    }
    return 0;
}

function getTotalRecommendations($link) {
    $sql = "SELECT COUNT(*) as count FROM recommendations";
    $result = mysqli_query($link, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['count'];
    }
    return 0;
}

function getActiveDevelopmentPlans($link) {
    $sql = "SELECT COUNT(*) as count FROM development_plans WHERE is_completed = 0";
    $result = mysqli_query($link, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['count'];
    }
    return 0;
}

function getAdminTestStats($link) {
    $sql = "SELECT 
                t.title,
                COUNT(tr.id) as completions,
                COUNT(DISTINCT tr.user_id) as unique_students,
                AVG(tr.total_score) as avg_score
            FROM tests t
            LEFT JOIN test_results tr ON t.id = tr.test_id
            GROUP BY t.id, t.title
            ORDER BY completions DESC";
    return mysqli_query($link, $sql);
}

function getRecentActivity($link) {
    $sql = "SELECT 
                'test' as type,
                u.name as user_name,
                t.title as test_title,
                tr.completed_at as date
            FROM test_results tr
            JOIN users u ON tr.user_id = u.id
            JOIN tests t ON tr.test_id = t.id
            UNION ALL
            SELECT 
                'recommendation' as type,
                u.name as user_name,
                p.title as item_title,
                tr.completed_at as date 
            FROM recommendations r
            JOIN users u ON r.user_id = u.id
            JOIN professions p ON r.profession_id = p.id
            JOIN test_results tr ON r.user_id = tr.user_id  
            ORDER BY date DESC
            LIMIT 10";
    return mysqli_query($link, $sql);
}
?>