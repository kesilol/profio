<?php
// event_user/pdf_generator.php - ФИНАЛЬНАЯ РАБОЧАЯ ВЕРСИЯ

// Отключаем ВСЕ ошибки
error_reporting(0);
ini_set('display_errors', 0);

// Очищаем буферы
while (ob_get_level()) {
    ob_end_clean();
}

// Проверяем наличие TCPDF
$tcpdf_path = __DIR__ . '/../tcpdf/tcpdf.php';
if (!file_exists($tcpdf_path)) {
    exit();
}

require_once($tcpdf_path);

class PDFReportGenerator extends TCPDF {
    
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        
        // Используем dejavusans который точно работает
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);
    }
    
    public function Header() {
        // Верхний блок с фиолетовым фоном
        $this->SetFillColor(103, 58, 183);
        $this->Rect(0, 0, 210, 25, 'F');
        
        // Используем writeHTMLCell с правильным шрифтом
        $this->SetFont('dejavusans', 'B', 18);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(5);
        $this->Cell(0, 8, 'PROFIO', 0, 1, 'C');
        
        $this->SetFont('dejavusans', '', 9);
        $this->Cell(0, 6, 'Система профессиональной ориентации', 0, 1, 'C');
        
        // Белая линия
        $this->SetDrawColor(255, 255, 255);
        $this->SetLineWidth(0.3);
        $this->Line(15, 23, 195, 23);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Страница ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

function generateStudentPDFReport($report_data, $user_info, $link) {
    $pdf = new PDFReportGenerator('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Настройки документа
    $pdf->SetCreator('Profio System');
    $pdf->SetAuthor('Profio');
    $pdf->SetTitle('Отчет по профориентации - ' . ($user_info['name'] ?? ''));
    
    // Устанавливаем поля
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(true, 25);
    
    // ===== ПЕРВАЯ СТРАНИЦА =====
    $pdf->AddPage();
    
    // Главный заголовок
    $pdf->SetY(35);
    $pdf->SetFont('dejavusans', 'B', 20);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->Cell(0, 10, 'ПЕРСОНАЛЬНЫЙ ОТЧЕТ', 0, 1, 'C');
    $pdf->SetFont('dejavusans', '', 14);
    $pdf->Cell(0, 6, 'по профессиональной ориентации', 0, 1, 'C');
    $pdf->Ln(20);
    
    // Информация о студенте
    $y = $pdf->GetY();
    $pdf->SetFillColor(248, 246, 255);
    $pdf->SetDrawColor(103, 58, 183);
    $pdf->SetLineWidth(0.3);
    $pdf->RoundedRect(15, $y, 180, 35, 4, '1111', 'DF');
    
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->SetXY(20, $y + 5);
    $pdf->Cell(0, 6, 'Информация о обучающемся', 0, 1);
    
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(20, $y + 13);
    $pdf->Cell(0, 5, 'ФИО: ' . ($user_info['name'] ?? 'Не указано'), 0, 1);
    $pdf->SetX(20);
    $pdf->Cell(0, 5, 'Email: ' . ($user_info['email'] ?? 'Не указано'), 0, 1);
    $pdf->SetX(20);
    $pdf->Cell(0, 5, 'Образование: ' . ($user_info['education_level'] ?? 'Не указано'), 0, 1);
    $pdf->Ln(20);
    
    // Общая статистика
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->Cell(0, 8, 'Общая статистика', 0, 1);
    $pdf->Ln(3);
    
    // Статистика 1
    $y = $pdf->GetY();
    $pdf->SetFillColor(230, 223, 250);
    $pdf->RoundedRect(15, $y, 180, 15, 3, '1111', 'DF');
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->SetXY(20, $y + 4);
    $pdf->Cell(100, 6, 'Пройдено тестов', 0, 0, 'L');
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->Cell(0, 6, $report_data['user']['tests_count'] ?? 0, 0, 1, 'R');
    $pdf->Ln(4);
    
    // Статистика 2
    $y = $pdf->GetY();
    $pdf->SetFillColor(230, 223, 250);
    $pdf->RoundedRect(15, $y, 180, 15, 3, '1111', 'DF');
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->SetXY(20, $y + 4);
    $pdf->Cell(100, 6, 'Получено рекомендаций', 0, 0, 'L');
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->Cell(0, 6, $report_data['user']['recommendations_count'] ?? 0, 0, 1, 'R');
    $pdf->Ln(4);
    
    // Статистика 3
    $y = $pdf->GetY();
    $pdf->SetFillColor(230, 223, 250);
    $pdf->RoundedRect(15, $y, 180, 15, 3, '1111', 'DF');
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->SetXY(20, $y + 4);
    $pdf->Cell(100, 6, 'Планов развития', 0, 0, 'L');
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->Cell(0, 6, $report_data['user']['plans_count'] ?? 0, 0, 1, 'R');
    $pdf->Ln(10);
    
    // ===== РЕЗУЛЬТАТЫ ТЕСТОВ =====
    if (!empty($report_data['test_results']) && $report_data['test_results']->num_rows > 0) {
        $pdf->AddPage();
        
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 10, 'Результаты тестирования', 0, 1, 'C');
        $pdf->Ln(6);
        
        $test_num = 1;
        $report_data['test_results']->data_seek(0);
        
        while ($test = $report_data['test_results']->fetch_assoc()) {
            $y = $pdf->GetY();
            if ($y > 250) {
                $pdf->AddPage();
                $y = $pdf->GetY();
            }
            
            $pdf->SetFillColor(245, 243, 255);
            $pdf->SetDrawColor(103, 58, 183);
            $pdf->SetLineWidth(0.3);
            $pdf->RoundedRect(15, $y, 180, 30, 4, '1111', 'DF');
            
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->SetTextColor(103, 58, 183);
            $pdf->SetXY(20, $y + 5);
            $pdf->Cell(0, 5, $test_num . '. ' . ($test['test_name'] ?? 'Без названия'), 0, 1);
            
            $pdf->SetFont('dejavusans', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(20, $y + 12);
            $pdf->Cell(0, 4, 'Баллы: ' . ($test['total_score'] ?? 0), 0, 1);
            $pdf->SetX(20);
            $pdf->Cell(0, 4, 'Тип: ' . ($test['result_type'] ?? 'Не указан'), 0, 1);
            $pdf->SetX(20);
            $date = !empty($test['completed_at']) ? date('d.m.Y', strtotime($test['completed_at'])) : 'Не указана';
            $pdf->Cell(0, 4, 'Дата: ' . $date, 0, 1);
            
            $pdf->Ln(8);
            $test_num++;
        }
    }
    
    // ===== РЕКОМЕНДАЦИИ =====
    if (!empty($report_data['recommendations_with_images'])) {
        $pdf->AddPage();
        
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 10, 'Рекомендованные профессии', 0, 1, 'C');
        $pdf->Ln(6);
        
        foreach ($report_data['recommendations_with_images'] as $index => $rec) {
            $y = $pdf->GetY();
            if ($y > 150) {
                $pdf->AddPage();
                $y = $pdf->GetY();
            }
            
            $pdf->SetFillColor(248, 246, 255);
            $pdf->SetDrawColor(180, 170, 210);
            $pdf->SetLineWidth(0.3);
            $pdf->RoundedRect(15, $y, 180, 80, 4, '1111', 'DF');
            
            // Название профессии
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->SetTextColor(103, 58, 183);
            $pdf->SetXY(20, $y + 8);
            $pdf->Cell(0, 6, $rec['profession_title'] ?? 'Не указано', 0, 1);
            
            // Совпадение
            $pdf->SetFont('dejavusans', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(20, $y + 20);
            $pdf->Cell(0, 4, 'Совпадение с профилем: ' . ($rec['match_percentage'] ?? 0) . '%', 0, 1);
            
            // Категория и зарплата
            $pdf->SetXY(20, $y + 28);
            $pdf->Cell(0, 4, 'Категория: ' . ($rec['category'] ?? 'Не указано'), 0, 1);
            $pdf->SetX(20);
            $pdf->Cell(0, 4, 'Средняя зарплата: ' . ($rec['salary_range'] ?? 'Не указано'), 0, 1);
            
            // Спрос
            $demand = strtolower($rec['demand_level'] ?? '');
            if ($demand == 'высокий' || $demand == 'high') {
                $pdf->SetTextColor(76, 175, 80);
            } elseif ($demand == 'средний' || $demand == 'medium') {
                $pdf->SetTextColor(255, 152, 0);
            } else {
                $pdf->SetTextColor(244, 67, 54);
            }
            
            $pdf->SetX(20);
            $pdf->Cell(0, 4, 'Спрос на рынке: ' . ucfirst($rec['demand_level'] ?? 'Не указано'), 0, 1);
            
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(12);
        }
    }
    
    // ===== ЗАКЛЮЧИТЕЛЬНАЯ СТРАНИЦА =====
    $pdf->AddPage();
    
    // Фиолетовый блок
    $pdf->SetFillColor(103, 58, 183);
    $pdf->Rect(0, 0, 210, 40, 'F');
    
    $pdf->SetY(15);
    $pdf->SetFont('dejavusans', 'B', 20);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'Спасибо за использование PROFIO!', 0, 1, 'C');
    
    $pdf->SetY(60);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 8, 'Ваш персональный отчет готов!', 0, 1, 'C');
    
    $pdf->SetY(80);
    $pdf->SetFont('dejavusans', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCell(0, 8, 'Этот отчет поможет вам в построении карьерного пути и профессиональном развитии. Используйте полученные рекомендации для достижения ваших целей.', 0, 'C');
    
    $pdf->SetY(120);
    $pdf->SetFont('dejavusans', 'I', 10);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->MultiCell(0, 6, 'Отчет сгенерирован автоматически системой Profio. Для консультации обратитесь к вашему куратору.', 0, 'C');
    
    return $pdf->Output('report.pdf', 'S');
}
?>