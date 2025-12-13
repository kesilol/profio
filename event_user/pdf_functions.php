<?php
// event_user/pdf_functions.php - УПРОЩЕННАЯ ВЕРСИЯ

// Проверяем наличие TCPDF
$tcpdf_path = __DIR__ . '/../tcpdf/tcpdf.php';
if (!file_exists($tcpdf_path)) {
    die('Ошибка: TCPDF не найден по пути: ' . $tcpdf_path);
}

require_once($tcpdf_path);

class PDFReportGenerator extends TCPDF {
    
    public function Header() {
        // Верхний блок с фиолетовым фоном
        $this->SetFillColor(103, 58, 183);
        $this->Rect(0, 0, 210, 25, 'F');
        
        // Логотип и название
        $html = '<div style="text-align:center; color:white;">
            <h1 style="font-size:18px; margin:5px 0; font-weight:bold;">PROFIO</h1>
            <p style="font-size:10px; margin:0;">Система профессиональной ориентации</p>
        </div>';
        
        $this->writeHTMLCell(0, 0, 0, 5, $html, 0, 1, false, true, 'C');
        
        // Белая разделительная линия
        $this->SetDrawColor(255, 255, 255);
        $this->SetLineWidth(0.3);
        $this->Line(15, 23, 195, 23);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $html = '<div style="text-align:center; font-size:8px; color:#808080;">
            Страница ' . $this->getAliasNumPage() . ' из ' . $this->getAliasNbPages() . '
        </div>';
        $this->writeHTMLCell(0, 0, 0, '', $html, 0, 1, false, true, 'C');
    }
}

function generateStudentPDFReport($report_data, $user_info, $link) {
    $pdf = new PDFReportGenerator('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Безопасные значения по умолчанию
    $user_name = $user_info['name'] ?? 'Не указано';
    $user_email = $user_info['email'] ?? 'Не указано';
    $user_education = $user_info['education_level'] ?? 'Не указано';
    
    // Настройки документа
    $pdf->SetCreator('Profio System');
    $pdf->SetAuthor('Profio');
    $pdf->SetTitle('Отчет по профориентации - ' . $user_name);
    $pdf->SetSubject('Профориентационный отчет');
    
    // Устанавливаем поля
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetAutoPageBreak(true, 25);
    
    // ===== ПЕРВАЯ СТРАНИЦА =====
    $pdf->AddPage();
    
    // Главный заголовок
    $title_html = '
    <div style="text-align:center;">
        <h1 style="color:#673ab7; font-size:24px; margin-bottom:5px;">ПЕРСОНАЛЬНЫЙ ОТЧЕТ</h1>
        <h2 style="color:#673ab7; font-size:16px; margin-top:0;">по профессиональной ориентации</h2>
    </div>
    ';
    $pdf->writeHTML($title_html, true, false, true, false, '');
    $pdf->Ln(20);
    
    // Информация о студенте
    $student_html = '
    <div style="background-color:#f8f6ff; border:1px solid #673ab7; border-radius:8px; padding:20px; margin-bottom:15px;">
        <h3 style="color:#673ab7; margin-top:0; border-bottom:1px solid #d1c4e9; padding-bottom:10px;">Информация о студенте</h3>
        <table style="width:100%;">
            <tr>
                <td style="width:30%; padding:5px;"><strong>ФИО:</strong></td>
                <td style="padding:5px;">' . htmlspecialchars($user_name) . '</td>
            </tr>
            <tr>
                <td style="padding:5px;"><strong>Email:</strong></td>
                <td style="padding:5px;">' . htmlspecialchars($user_email) . '</td>
            </tr>
            <tr>
                <td style="padding:5px;"><strong>Образование:</strong></td>
                <td style="padding:5px;">' . htmlspecialchars($user_education) . '</td>
            </tr>
        </table>
    </div>
    ';
    $pdf->writeHTML($student_html, true, false, true, false, '');
    $pdf->Ln(15);
    
    // Общая статистика
    $tests_count = $report_data['user']['tests_count'] ?? 0;
    $recs_count = $report_data['user']['recommendations_count'] ?? 0;
    $plans_count = $report_data['user']['plans_count'] ?? 0;
    
    $stats_html = '
    <h3 style="color:#673ab7; border-bottom:2px solid #673ab7; padding-bottom:5px;">Общая статистика</h3>
    <table style="width:100%; border-collapse: collapse; margin-top:10px;">
        <tr style="background-color:#e6dffa;">
            <td style="border:1px solid #673ab7; padding:10px; border-radius:5px 0 0 5px; width:70%;"><strong>Пройдено тестов</strong></td>
            <td style="border:1px solid #673ab7; padding:10px; border-radius:0 5px 5px 0; text-align:center; width:30%; background-color:#673ab7; color:white; font-weight:bold;">' . $tests_count . '</td>
        </tr>
        <tr style="background-color:#e6dffa;">
            <td style="border:1px solid #673ab7; padding:10px; border-radius:5px 0 0 5px;"><strong>Получено рекомендаций</strong></td>
            <td style="border:1px solid #673ab7; padding:10px; border-radius:0 5px 5px 0; text-align:center; background-color:#673ab7; color:white; font-weight:bold;">' . $recs_count . '</td>
        </tr>
        <tr style="background-color:#e6dffa;">
            <td style="border:1px solid #673ab7; padding:10px; border-radius:5px 0 0 5px;"><strong>Планов развития</strong></td>
            <td style="border:1px solid #673ab7; padding:10px; border-radius:0 5px 5px 0; text-align:center; background-color:#673ab7; color:white; font-weight:bold;">' . $plans_count . '</td>
        </tr>
    </table>
    ';
    $pdf->writeHTML($stats_html, true, false, true, false, '');
    
    // ===== ПРОСТАЯ ЗАКЛЮЧИТЕЛЬНАЯ СТРАНИЦА =====
    $pdf->AddPage();
    
    $final_html = '
    <div style="background: linear-gradient(135deg, #673ab7 0%, #9575cd 100%); color:white; padding:40px 20px; text-align:center; border-radius:10px;">
        <h1 style="color:white; font-size:28px; margin-bottom:15px;">Спасибо за использование PROFIO!</h1>
        <p style="font-size:14px; opacity:0.9;">Ваш персональный отчет готов для использования</p>
    </div>
    
    <div style="text-align:center; margin:50px 0 30px 0;">
        <div style="display:inline-block; padding:20px; background-color:#f8f6ff; border-radius:10px; border:2px solid #d1c4e9;">
            <h2 style="color:#673ab7; margin-top:0;">Ваш персональный отчет готов!</h2>
            <p style="color:#666; font-size:14px; max-width:500px; margin:0 auto;">Этот отчет поможет вам в построении карьерного пути и профессиональном развитии.</p>
        </div>
    </div>
    
    <div style="margin-top:40px; padding-top:20px; border-top:1px solid #eee; text-align:center;">
        <p style="color:#787878; font-size:10px; font-style:italic;">
            Отчет сгенерирован автоматически системой Profio<br>
            Дата генерации: ' . date('d.m.Y H:i:s') . '<br>
            Для консультации обратитесь к вашему куратору
        </p>
    </div>
    ';
    
    $pdf->writeHTML($final_html, true, false, true, false, '');
    
    return $pdf->Output('profio_report.pdf', 'S');
}
?>