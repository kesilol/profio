<?php
// event_user/pdf_functions.php - ИСПРАВЛЕННЫЙ ФИНАЛЬНЫЙ ВАРИАНТ

// Проверяем наличие TCPDF
$tcpdf_path = __DIR__ . '/../tcpdf/tcpdf.php';
if (!file_exists($tcpdf_path)) {
    die('Ошибка: TCPDF не найден по пути: ' . $tcpdf_path);
}

require_once($tcpdf_path);

class PDFReportGenerator extends TCPDF {
    
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        
        $this->SetFont('dejavusans', '', 10);
        $this->SetAutoPageBreak(true, 20);
        $this->SetMargins(15, 25, 15);
    }
    
    public function Header() {
        $this->SetFillColor(103, 58, 183);
        $this->Rect(0, 0, 210, 25, 'F');
        
        $this->SetY(5);
        $this->SetFont('dejavusans', 'B', 16);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, 'PROFIO', 0, 1, 'C');
        
        $this->SetFont('dejavusans', '', 9);
        $this->SetTextColor(220, 220, 255);
        $this->Cell(0, 5, 'Система профессиональной ориентации', 0, 1, 'C');
        
        $this->SetDrawColor(255, 255, 255);
        $this->SetLineWidth(0.3);
        $this->Line(15, 23, 195, 23);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        
        // Номер страницы по центру
        $this->Cell(0, 8, 'Страница ' . $this->getAliasNumPage() . ' из ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
    
    /**
     * Рисует компактную шкалу MBTI
     */
    public function drawMBTIScale($leftText, $centerText, $rightText, $percentage, $dominantSide = null) {
        $y = $this->GetY();
        
        if ($y > 250) {
            $this->AddPage();
            $y = $this->GetY();
        }
        
        $this->SetFont('dejavusans', 'B', 9);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 5, $centerText, 0, 1, 'C');
        
        $this->SetFont('dejavusans', '', 7);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(75, 4, $leftText, 0, 0, 'L');
        $this->Cell(40, 4, '', 0, 0);
        $this->Cell(75, 4, $rightText, 0, 1, 'R');
        
        $barWidth = 170;
        $barHeight = 8;
        $x = 20;
        $y = $this->GetY();
        
        $this->SetFillColor(230, 230, 230);
        $this->Rect($x, $y, $barWidth, $barHeight, 'F');
        
        $fillWidth = ($percentage / 100) * $barWidth;
        $this->SetFillColor(103, 58, 183);
        $this->Rect($x, $y, $fillWidth, $barHeight, 'F');
        
        if ($dominantSide) {
            $this->SetY($y + $barHeight + 3);
            $this->SetFont('dejavusans', 'B', 7);
            $this->SetTextColor(103, 58, 183);
            $this->Cell(0, 4, $dominantSide, 0, 1, 'C');
            $this->SetY($y + $barHeight + 10);
        } else {
            $this->SetY($y + $barHeight + 5);
        }
    }
}

function safeText($text) {
    if (empty($text)) return '';
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function parseMBTIScores($total_score) {
    $scores = ['IE' => 50, 'SN' => 50, 'TF' => 50, 'JP' => 50];
    if (!empty($total_score) && strpos($total_score, ':') !== false) {
        $parts = explode(',', $total_score);
        foreach ($parts as $part) {
            $pair = explode(':', $part);
            if (count($pair) == 2 && isset($scores[$pair[0]])) {
                $scores[$pair[0]] = (int)$pair[1];
            }
        }
    }
    return $scores;
}

function getLatestMBTIResultWithScores($user_id, $link) {
    $mbti_test_id_query = $link->query("SELECT id FROM tests WHERE test_type_id = (SELECT id FROM test_types WHERE name = 'mbti') LIMIT 1");
    $mbti_test_id = $mbti_test_id_query->num_rows > 0 ? $mbti_test_id_query->fetch_assoc()['id'] : 0;
    
    if (!$mbti_test_id) return null;
    
    $result = $link->query("
        SELECT tr.*, t.title as test_title
        FROM test_results tr 
        JOIN tests t ON tr.test_id = t.id 
        WHERE tr.user_id = '$user_id' AND tr.test_id = '$mbti_test_id'
        ORDER BY tr.completed_at DESC
        LIMIT 1
    ");
    
    if ($result->num_rows == 0) return null;
    
    $data = $result->fetch_assoc();
    $data['mbti_scores'] = parseMBTIScores($data['total_score']);
    
    return $data;
}

function getLatestHollandResult($user_id, $link) {
    $holland_test_id_query = $link->query("SELECT id FROM tests WHERE test_type_id = (SELECT id FROM test_types WHERE name = 'голланд') LIMIT 1");
    $holland_test_id = $holland_test_id_query->num_rows > 0 ? $holland_test_id_query->fetch_assoc()['id'] : 0;
    
    if (!$holland_test_id) return null;
    
    $result = $link->query("
        SELECT tr.*, t.title as test_title
        FROM test_results tr 
        JOIN tests t ON tr.test_id = t.id 
        WHERE tr.user_id = '$user_id' AND tr.test_id = '$holland_test_id'
        ORDER BY tr.completed_at DESC
        LIMIT 1
    ");
    
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function getLatestKlimovResult($user_id, $link) {
    $klimov_test_id_query = $link->query("SELECT id FROM tests WHERE test_type_id = (SELECT id FROM test_types WHERE name = 'климов') LIMIT 1");
    $klimov_test_id = $klimov_test_id_query->num_rows > 0 ? $klimov_test_id_query->fetch_assoc()['id'] : 0;
    
    if (!$klimov_test_id) return null;
    
    $result = $link->query("
        SELECT tr.*, t.title as test_title
        FROM test_results tr 
        JOIN tests t ON tr.test_id = t.id 
        WHERE tr.user_id = '$user_id' AND tr.test_id = '$klimov_test_id'
        ORDER BY tr.completed_at DESC
        LIMIT 1
    ");
    
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function getMBTITypeInfo($type_code, $link) {
    if (!$type_code) return null;
    $result = $link->query("SELECT * FROM mbti_types WHERE type_code = '$type_code' LIMIT 1");
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function getHollandTypeInfo($type_name, $link) {
    if (!$type_name) return null;
    
    $result = $link->query("SELECT * FROM holland_types WHERE type_name = '$type_name' LIMIT 1");
    if ($result->num_rows == 0) {
        $code_map = [
            'Реалистичный' => 'Р',
            'Интеллектуальный' => 'И',
            'Социальный' => 'С',
            'Конвенциональный' => 'К',
            'Предприимчивый' => 'П',
            'Артистичный' => 'А'
        ];
        $type_code = $code_map[$type_name] ?? '';
        if ($type_code) {
            $result = $link->query("SELECT * FROM holland_types WHERE type_code = '$type_code' LIMIT 1");
        }
    }
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function getUniqueProfessionRecommendations($user_id, $link, $mbti_result, $holland_result, $klimov_result) {
    $recommendations_by_profession = [];
    
    if ($mbti_result && !empty($mbti_result['result_type'])) {
        $mbti_type = $mbti_result['result_type'];
        $mbti_professions = $link->query("
            SELECT p.*, mpr.relevance_score as match_percentage
            FROM mbti_profession_relations mpr
            JOIN professions p ON mpr.profession_id = p.id
            WHERE mpr.mbti_type_code = '$mbti_type'
            ORDER BY mpr.relevance_score DESC
            LIMIT 5
        ");
        
        while ($prof = $mbti_professions->fetch_assoc()) {
            $prof_id = $prof['id'];
            if (!isset($recommendations_by_profession[$prof_id])) {
                $recommendations_by_profession[$prof_id] = [
                    'profession' => $prof,
                    'source' => 'MBTI',
                    'match_percentage' => $prof['match_percentage']
                ];
            }
        }
    }
    
    if ($holland_result && !empty($holland_result['result_type'])) {
        $holland_type = $holland_result['result_type'];
        $type_code_map = [
            'Реалистичный' => 'Р',
            'Интеллектуальный' => 'И',
            'Социальный' => 'С',
            'Конвенциональный' => 'К',
            'Предприимчивый' => 'П',
            'Артистичный' => 'А'
        ];
        $type_code = $type_code_map[$holland_type] ?? '';
        
        if ($type_code) {
            $holland_professions = $link->query("
                SELECT p.*, hpr.relevance_score as match_percentage
                FROM holland_profession_relations hpr
                JOIN professions p ON hpr.profession_id = p.id
                WHERE hpr.holland_type_code = '$type_code'
                ORDER BY hpr.relevance_score DESC
                LIMIT 5
            ");
            
            while ($prof = $holland_professions->fetch_assoc()) {
                $prof_id = $prof['id'];
                if (!isset($recommendations_by_profession[$prof_id])) {
                    $recommendations_by_profession[$prof_id] = [
                        'profession' => $prof,
                        'source' => 'Голланд',
                        'match_percentage' => $prof['match_percentage']
                    ];
                }
            }
        }
    }
    
    if ($klimov_result && !empty($klimov_result['result_type'])) {
        $klimov_type = $klimov_result['result_type'];
        $klimov_recommendations = $link->query("
            SELECT r.*, p.title as profession_title, p.description as profession_description,
                   p.salary_range, p.demand_level, p.category
            FROM recommendations r 
            JOIN professions p ON r.profession_id = p.id 
            WHERE r.user_id = '$user_id' 
            AND r.result_type = '$klimov_type'
            AND r.recommendation_text LIKE '%Климова%'
            ORDER BY r.match_percentage DESC
            LIMIT 5
        ");
        
        while ($rec = $klimov_recommendations->fetch_assoc()) {
            $prof_id = $rec['profession_id'];
            if (!isset($recommendations_by_profession[$prof_id])) {
                $recommendations_by_profession[$prof_id] = [
                    'profession' => [
                        'id' => $rec['profession_id'],
                        'title' => $rec['profession_title'],
                        'description' => $rec['profession_description'],
                        'salary_range' => $rec['salary_range'],
                        'demand_level' => $rec['demand_level'],
                        'category' => $rec['category']
                    ],
                    'source' => 'Климов',
                    'match_percentage' => $rec['match_percentage']
                ];
            }
        }
    }
    
    $unique_recommendations = array_values($recommendations_by_profession);
    usort($unique_recommendations, function($a, $b) {
        return $b['match_percentage'] - $a['match_percentage'];
    });
    
    return $unique_recommendations;
}

/**
 * Основная функция генерации PDF отчета
 */
function generateStudentPDFReport($user_id, $user_info, $link) {
    $pdf = new PDFReportGenerator('P', 'mm', 'A4', true, 'UTF-8', false);
    
    $user_name = safeText($user_info['name'] ?? 'Не указано');
    $user_email = safeText($user_info['email'] ?? 'Не указано');
    $user_education = safeText($user_info['education_level'] ?? 'Не указано');
    
    // Получаем данные
    $mbti_result = getLatestMBTIResultWithScores($user_id, $link);
    $holland_result = getLatestHollandResult($user_id, $link);
    $klimov_result = getLatestKlimovResult($user_id, $link);
    
    $mbti_type_info = $mbti_result ? getMBTITypeInfo($mbti_result['result_type'], $link) : null;
    $holland_type_info = $holland_result ? getHollandTypeInfo($holland_result['result_type'], $link) : null;
    
    $unique_recommendations = getUniqueProfessionRecommendations($user_id, $link, $mbti_result, $holland_result, $klimov_result);
    
    $tests_count = 0;
    if ($mbti_result) $tests_count++;
    if ($holland_result) $tests_count++;
    if ($klimov_result) $tests_count++;
    
    $recommendations_count = count($unique_recommendations);
    
    $plans_query = $link->query("SELECT COUNT(*) as count FROM development_plans WHERE user_id = '$user_id'");
    $plans_count = $plans_query->num_rows > 0 ? $plans_query->fetch_assoc()['count'] : 0;
    
    $pdf->SetCreator('Profio System');
    $pdf->SetAuthor('Profio');
    $pdf->SetTitle('Отчет по профориентации - ' . $user_name);
    
    // ========== ПЕРВАЯ СТРАНИЦА ==========
    $pdf->AddPage();
    
    // Заголовок
    $pdf->SetY(40);
    $pdf->SetFont('dejavusans', 'B', 22);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->Cell(0, 10, 'ПЕРСОНАЛЬНЫЙ ОТЧЕТ', 0, 1, 'C');
    
    $pdf->SetFont('dejavusans', '', 13);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 7, 'по профессиональной ориентации', 0, 1, 'C');
    $pdf->Ln(15);
    
    // Информация об обучающемся
    $pdf->SetFillColor(248, 246, 255);
    $pdf->SetDrawColor(103, 58, 183);
    $pdf->SetLineWidth(0.5);
    $pdf->RoundedRect(15, $pdf->GetY(), 180, 48, 5, '1111', 'DF');
    
    $pdf->SetY($pdf->GetY() + 6);
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->Cell(0, 6, 'Информация об обучающемся', 0, 1, 'C');
    
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->Ln(2);
    
    $pdf->Cell(35, 6, 'ФИО:', 0, 0);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 6, $user_name, 0, 1);
    
    $pdf->Cell(35, 6, 'Email:', 0, 0);
    $pdf->Cell(0, 6, $user_email, 0, 1);
    
    $pdf->Cell(35, 6, 'Образование:', 0, 0);
    $pdf->Cell(0, 6, $user_education, 0, 1);
    
    $pdf->Ln(20); // Увеличен отступ перед статистикой
    
    // Статистика
    $statsY = $pdf->GetY();
    $cardWidth = 56;
    $cardHeight = 40;
    $spacing = 6;
    
    // Карточка 1
    $pdf->SetFillColor(238, 234, 250);
    $pdf->SetDrawColor(180, 170, 210);
    $pdf->SetLineWidth(0.3);
    $pdf->RoundedRect(15, $statsY, $cardWidth, $cardHeight, 4, '1111', 'DF');
    
    $pdf->SetFont('dejavusans', 'B', 20);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->SetXY(15 + ($cardWidth/2) - 12, $statsY + 10);
    $pdf->Cell(24, 8, $tests_count, 0, 0, 'C');
    
    $pdf->SetFont('dejavusans', '', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetXY(15 + ($cardWidth/2) - 28, $statsY + 24);
    $pdf->Cell(56, 4, 'Пройдено тестов', 0, 0, 'C');
    
    // Карточка 2
    $pdf->RoundedRect(15 + $cardWidth + $spacing, $statsY, $cardWidth, $cardHeight, 4, '1111', 'DF');
    
    $pdf->SetFont('dejavusans', 'B', 20);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->SetXY(15 + $cardWidth + $spacing + ($cardWidth/2) - 12, $statsY + 10);
    $pdf->Cell(24, 8, $recommendations_count, 0, 0, 'C');
    
    $pdf->SetFont('dejavusans', '', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetXY(15 + $cardWidth + $spacing + ($cardWidth/2) - 28, $statsY + 24);
    $pdf->Cell(56, 4, 'Рекомендаций', 0, 0, 'C');
    
    // Карточка 3
    $pdf->RoundedRect(15 + ($cardWidth + $spacing) * 2, $statsY, $cardWidth, $cardHeight, 4, '1111', 'DF');
    
    $pdf->SetFont('dejavusans', 'B', 20);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->SetXY(15 + ($cardWidth + $spacing) * 2 + ($cardWidth/2) - 12, $statsY + 10);
    $pdf->Cell(24, 8, $plans_count, 0, 0, 'C');
    
    $pdf->SetFont('dejavusans', '', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetXY(15 + ($cardWidth + $spacing) * 2 + ($cardWidth/2) - 28, $statsY + 24);
    $pdf->Cell(56, 4, 'Планов развития', 0, 0, 'C');
    
    $pdf->SetY($statsY + $cardHeight + 15);
    
    // ========== MBTI РАЗДЕЛ ==========
    if ($mbti_result && $mbti_type_info) {
        $pdf->AddPage();
        
        $pdf->SetFont('dejavusans', 'B', 18);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 8, 'Тест MBTI', 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, 'Типология личности Майерс-Бриггс', 0, 1, 'C');
        $pdf->Ln(8);
        
        $pdf->SetFillColor(103, 58, 183);
        $pdf->RoundedRect(15, $pdf->GetY(), 180, 38, 5, '1111', 'DF');
        
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(20, $pdf->GetY() + 5);
        $pdf->Cell(0, 5, 'Ваш тип личности', 0, 1, 'C');
        
        $pdf->SetFont('dejavusans', 'B', 20);
        $pdf->SetXY(20, $pdf->GetY() + 3);
        $pdf->Cell(0, 8, $mbti_result['result_type'], 0, 1, 'C');
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetXY(20, $pdf->GetY() + 2);
        $pdf->Cell(0, 5, safeText($mbti_type_info['type_name']), 0, 1, 'C');
        
        $pdf->SetY($pdf->GetY() + 12);
        
        if (!empty($mbti_type_info['full_description'])) {
            $pdf->SetFont('dejavusans', '', 9);
            $pdf->SetTextColor(60, 60, 60);
            $desc = safeText($mbti_type_info['full_description']);
            if (mb_strlen($desc) > 400) {
                $desc = mb_substr($desc, 0, 400) . '...';
            }
            $pdf->MultiCell(0, 5, $desc, 0, 'L', 0, 1);
            $pdf->Ln(5);
        }
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 7, 'Характеристики', 0, 1);
        
        if (!empty($mbti_type_info['strengths'])) {
            $pdf->SetFont('dejavusans', 'B', 9);
            $pdf->SetTextColor(46, 125, 50);
            $pdf->Cell(0, 5, '✓ Сильные стороны', 0, 1);
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->SetTextColor(60, 60, 60);
            $strength = safeText($mbti_type_info['strengths']);
            if (mb_strlen($strength) > 250) $strength = mb_substr($strength, 0, 250) . '...';
            $pdf->MultiCell(0, 4, $strength, 0, 'L', 0, 1);
            $pdf->Ln(3);
        }
        
        if (!empty($mbti_type_info['weaknesses'])) {
            $pdf->SetFont('dejavusans', 'B', 9);
            $pdf->SetTextColor(230, 81, 0);
            $pdf->Cell(0, 5, '⚠ Слабые стороны', 0, 1);
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->SetTextColor(60, 60, 60);
            $weakness = safeText($mbti_type_info['weaknesses']);
            if (mb_strlen($weakness) > 250) $weakness = mb_substr($weakness, 0, 250) . '...';
            $pdf->MultiCell(0, 4, $weakness, 0, 'L', 0, 1);
            $pdf->Ln(5);
        }
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 7, 'Шкалы личности', 0, 1);
        $pdf->Ln(2);
        
        $mbti_scores = $mbti_result['mbti_scores'];
        $type_letters = str_split($mbti_result['result_type']);
        
        $scales = [
            ['key' => 'IE', 'left' => 'Интроверсия (I)', 'right' => 'Экстраверсия (E)', 'name' => 'Источник энергии'],
            ['key' => 'SN', 'left' => 'Сенсорика (S)', 'right' => 'Интуиция (N)', 'name' => 'Способ восприятия'],
            ['key' => 'TF', 'left' => 'Мышление (T)', 'right' => 'Чувство (F)', 'name' => 'Принятие решений'],
            ['key' => 'JP', 'left' => 'Суждение (J)', 'right' => 'Восприятие (P)', 'name' => 'Образ жизни']
        ];
        
        $idx = 0;
        foreach ($scales as $scale) {
            $score = $mbti_scores[$scale['key']];
            $currentLetter = isset($type_letters[$idx]) ? $type_letters[$idx] : '';
            
            $dominantText = '';
            if ($currentLetter == 'I' || $currentLetter == 'S' || $currentLetter == 'T' || $currentLetter == 'J') {
                $dominantText = 'Преобладает: ' . $scale['left'];
            } elseif ($currentLetter == 'E' || $currentLetter == 'N' || $currentLetter == 'F' || $currentLetter == 'P') {
                $dominantText = 'Преобладает: ' . $scale['right'];
            }
            
            $pdf->drawMBTIScale($scale['left'], $scale['name'], $scale['right'], $score, $dominantText);
            $idx++;
        }
        
        $pdf->Ln(3);
        
        if (!empty($mbti_type_info['career_advice'])) {
            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->SetTextColor(103, 58, 183);
            $pdf->Cell(0, 6, 'Советы по карьере', 0, 1);
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->SetTextColor(60, 60, 60);
            $advice = safeText($mbti_type_info['career_advice']);
            if (mb_strlen($advice) > 300) $advice = mb_substr($advice, 0, 300) . '...';
            $pdf->MultiCell(0, 4, $advice, 0, 'L', 0, 1);
            $pdf->Ln(3);
        }
    }
    
    // ========== ГОЛЛАНД РАЗДЕЛ ==========
    if ($holland_result && $holland_type_info) {
        $pdf->AddPage();
        
        $pdf->SetFont('dejavusans', 'B', 18);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 8, 'Тест Голланда', 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, 'Профессиональная типология личности', 0, 1, 'C');
        $pdf->Ln(8);
        
        $pdf->SetFillColor(103, 58, 183);
        $pdf->RoundedRect(15, $pdf->GetY(), 180, 38, 5, '1111', 'DF');
        
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(20, $pdf->GetY() + 5);
        $pdf->Cell(0, 5, 'Ваш профессиональный тип', 0, 1, 'C');
        
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->SetXY(20, $pdf->GetY() + 3);
        $pdf->Cell(0, 7, safeText($holland_type_info['type_name']) . ' (' . $holland_type_info['type_code'] . ')', 0, 1, 'C');
        
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetXY(20, $pdf->GetY() + 2);
        $pdf->Cell(0, 5, 'Баллы: ' . $holland_result['total_score'], 0, 1, 'C');
        
        $pdf->SetY($pdf->GetY() + 12);
        
        $description = !empty($holland_type_info['full_description']) ? $holland_type_info['full_description'] : $holland_type_info['description'];
        if (!empty($description)) {
            $pdf->SetFont('dejavusans', '', 9);
            $pdf->SetTextColor(60, 60, 60);
            $desc = safeText($description);
            if (mb_strlen($desc) > 350) $desc = mb_substr($desc, 0, 350) . '...';
            $pdf->MultiCell(0, 5, $desc, 0, 'L', 0, 1);
            $pdf->Ln(5);
        }
        
        if (!empty($holland_type_info['strengths'])) {
            $pdf->SetFont('dejavusans', 'B', 9);
            $pdf->SetTextColor(46, 125, 50);
            $pdf->Cell(0, 5, '✓ Сильные стороны', 0, 1);
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->SetTextColor(60, 60, 60);
            $strength = safeText($holland_type_info['strengths']);
            if (mb_strlen($strength) > 250) $strength = mb_substr($strength, 0, 250) . '...';
            $pdf->MultiCell(0, 4, $strength, 0, 'L', 0, 1);
            $pdf->Ln(3);
        }
        
        if (!empty($holland_type_info['weaknesses'])) {
            $pdf->SetFont('dejavusans', 'B', 9);
            $pdf->SetTextColor(230, 81, 0);
            $pdf->Cell(0, 5, '⚠ Слабые стороны', 0, 1);
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->SetTextColor(60, 60, 60);
            $weakness = safeText($holland_type_info['weaknesses']);
            if (mb_strlen($weakness) > 250) $weakness = mb_substr($weakness, 0, 250) . '...';
            $pdf->MultiCell(0, 4, $weakness, 0, 'L', 0, 1);
            $pdf->Ln(3);
        }
        
        if (!empty($holland_type_info['career_advice'])) {
            $pdf->SetFont('dejavusans', 'B', 9);
            $pdf->SetTextColor(103, 58, 183);
            $pdf->Cell(0, 5, 'Советы по карьере', 0, 1);
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->SetTextColor(60, 60, 60);
            $advice = safeText($holland_type_info['career_advice']);
            if (mb_strlen($advice) > 250) $advice = mb_substr($advice, 0, 250) . '...';
            $pdf->MultiCell(0, 4, $advice, 0, 'L', 0, 1);
            $pdf->Ln(3);
        }
    }
    
    // ========== КЛИМОВ РАЗДЕЛ ==========
    if ($klimov_result) {
        $pdf->AddPage();
        
        $pdf->SetFont('dejavusans', 'B', 18);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 8, 'Тест Климова', 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, 'Классификация профессий по предмету труда', 0, 1, 'C');
        $pdf->Ln(8);
        
        $type_rus = [
            'технический' => 'Человек-Техника',
            'гуманитарный' => 'Человек-Человек',
            'творческий' => 'Человек-Художественный образ',
            'документальный' => 'Человек-Знаковая система',
            'научный' => 'Человек-Природа'
        ];
        
        $type_name_rus = $type_rus[$klimov_result['result_type']] ?? ucfirst($klimov_result['result_type']);
        
        $pdf->SetFillColor(103, 58, 183);
        $pdf->RoundedRect(15, $pdf->GetY(), 180, 35, 5, '1111', 'DF');
        
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(20, $pdf->GetY() + 5);
        $pdf->Cell(0, 5, 'Ваш тип профессий', 0, 1, 'C');
        
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->SetXY(20, $pdf->GetY() + 3);
        $pdf->Cell(0, 7, $type_name_rus, 0, 1, 'C');
        
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetXY(20, $pdf->GetY() + 2);
        $pdf->Cell(0, 5, 'Баллы: ' . $klimov_result['total_score'], 0, 1, 'C');
        
        $pdf->SetY($pdf->GetY() + 12);
        
        $type_descriptions = [
            'технический' => 'Вам подходят профессии, связанные с техникой, механизмами, ремонтом, конструированием.',
            'гуманитарный' => 'Вам подходят профессии, связанные с общением, обучением, помощью другим людям.',
            'творческий' => 'Вам подходят профессии, связанные с творчеством, искусством, дизайном.',
            'документальный' => 'Вам подходят профессии, связанные с документами, цифрами, базами данных.',
            'научный' => 'Вам подходят профессии, связанные с исследованиями, анализом, экспериментами.'
        ];
        
        $desc = $type_descriptions[$klimov_result['result_type']] ?? 'Результаты теста помогут определить наиболее подходящую профессиональную сферу.';
        
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->MultiCell(0, 5, $desc, 0, 'L', 0, 1);
    }
    
    // ========== РЕКОМЕНДАЦИИ ==========
    if (!empty($unique_recommendations)) {
        $pdf->AddPage();
        
        $pdf->SetFont('dejavusans', 'B', 18);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 8, 'Рекомендованные профессии', 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, 'На основе результатов пройденных тестов', 0, 1, 'C');
        $pdf->Ln(10);
        
        foreach ($unique_recommendations as $index => $rec) {
            $prof = $rec['profession'];
            $y = $pdf->GetY();
            
            if ($y > 230) {
                $pdf->AddPage();
                $y = $pdf->GetY();
            }
            
            $category_ru = [
                'техническая' => 'Техническая',
                'гуманитарная' => 'Гуманитарная',
                'творческая' => 'Творческая',
                'научная' => 'Научная',
                'бизнес' => 'Бизнес'
            ];
            $category_name = $category_ru[$prof['category']] ?? ($prof['category'] ?? 'Не указана');
            
            $pdf->SetFillColor(248, 246, 255);
            $pdf->SetDrawColor(200, 190, 220);
            $pdf->SetLineWidth(0.3);
            $pdf->RoundedRect(15, $y, 180, 60, 4, '1111', 'DF');
            
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->SetTextColor(103, 58, 183);
            $pdf->SetXY(20, $y + 5);
            $pdf->Cell(0, 5, ($index + 1) . '. ' . safeText($prof['title']), 0, 1);
            
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->SetFillColor(103, 58, 183);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY(20, $y + 13);
            $pdf->Cell(30, 4, $rec['match_percentage'] . '%', 0, 0, 'C', true);
            
            $pdf->SetFillColor(200, 200, 200);
            $pdf->SetTextColor(60, 60, 60);
            $pdf->SetXY(52, $y + 13);
            $pdf->Cell(35, 4, safeText($rec['source']), 0, 0, 'C', true);
            
            $pdf->SetXY(89, $y + 13);
            $pdf->Cell(35, 4, safeText($category_name), 0, 0, 'C', true);
            
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->SetTextColor(60, 60, 60);
            $pdf->SetXY(20, $y + 21);
            $pdf->Cell(0, 4, 'Зарплата: ' . safeText($prof['salary_range'] ?? 'Не указано'), 0, 1);
            
            $demand = strtolower($prof['demand_level'] ?? '');
            if ($demand == 'высокий') {
                $pdf->SetTextColor(76, 175, 80);
            } elseif ($demand == 'средний') {
                $pdf->SetTextColor(255, 152, 0);
            } else {
                $pdf->SetTextColor(244, 67, 54);
            }
            $pdf->SetXY(20, $pdf->GetY());
            $pdf->Cell(0, 4, 'Спрос на рынке: ' . safeText(ucfirst($demand)), 0, 1);
            
            $pdf->SetTextColor(80, 80, 80);
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->SetXY(20, $y + 33);
            $description = safeText($prof['description'] ?? '');
            if (mb_strlen($description) > 180) {
                $description = mb_substr($description, 0, 180) . '...';
            }
            $pdf->MultiCell(170, 3.5, $description, 0, 'L');
            
            $pdf->SetY($y + 60);
            $pdf->Ln(5);
        }
    }
    
    // ========== ПРОСТОЙ ФИНАЛЬНЫЙ ЛИСТ ==========
    $pdf->AddPage();
    
    $pdf->SetFillColor(103, 58, 183);
    $pdf->Rect(0, 0, 210, 40, 'F');
    
    $pdf->SetY(18);
    $pdf->SetFont('dejavusans', 'B', 20);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'Спасибо за использование PROFIO!', 0, 1, 'C');
    
    $pdf->SetY(65);
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->Cell(0, 8, 'Ваш персональный отчет готов!', 0, 1, 'C');
    
    $pdf->SetY(85);
    $pdf->SetFont('dejavusans', '', 11);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell(0, 7, 'Этот отчет поможет вам в построении карьерного пути и профессиональном развитии. Используйте полученные рекомендации для достижения ваших целей.', 0, 'C');
    
    $pdf->SetY(125);
    $pdf->SetFont('dejavusans', 'I', 9);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->MultiCell(0, 6, 'Отчет сгенерирован автоматически системой Profio ' . date('d.m.Y') . '.', 0, 'C');
    
    return $pdf->Output('profio_report.pdf', 'S');
}
?>