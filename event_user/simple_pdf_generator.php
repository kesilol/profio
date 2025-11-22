<?php
// генератор PDF
require_once('../tcpdf/tcpdf.php');

function generateSimpleStudentPDF($report_data, $user_info, $link) {
    // Создаем PDF с поддержкой UTF-8
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('Profio');
    $pdf->SetAuthor('Profio');
    $pdf->SetTitle('Отчет по профориентации - ' . $user_info['name']);
    
    // Устанавливаем шрифт с поддержкой кириллицы
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->AddPage();
    
    // Заголовок
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, 'ОТЧЕТ ПО ПРОФОРИЕНТАЦИИ', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Информация о студенте
    $pdf->SetFont('dejavusans', '', 10);
    $content = "Студент: " . $user_info['name'] . "\n";
    $content .= "Email: " . $user_info['email'] . "\n";
    $content .= "Образование: " . $user_info['education_level'] . "\n\n";
    
    $content .= "СТАТИСТИКА:\n";
    $content .= "- Тестов пройдено: " . ($report_data['user']['tests_count'] ?? 0) . "\n";
    $content .= "- Рекомендаций: " . ($report_data['user']['recommendations_count'] ?? 0) . "\n";
    $content .= "- Планов развития: " . ($report_data['user']['plans_count'] ?? 0) . "\n\n";
    
    if ($report_data['test_results'] && $report_data['test_results']->num_rows > 0) {
        $content .= "ТЕСТЫ:\n";
        $report_data['test_results']->data_seek(0);
        while ($test = $report_data['test_results']->fetch_assoc()) {
            $content .= "- " . $test['test_name'] . " (" . $test['total_score'] . " баллов)\n";
        }
        $content .= "\n";
    }
    
    if ($report_data['recommendations'] && count($report_data['recommendations']) > 0) {
        $content .= "РЕКОМЕНДАЦИИ:\n\n";
        foreach ($report_data['recommendations'] as $rec) {
            $content .= $rec['profession_title'] . " (" . $rec['match_percentage'] . "%)\n";
            $content .= "Категория: " . $rec['category'] . "\n";
            $content .= "Зарплата: " . $rec['salary_range'] . "\n";
            $content .= "Спрос: " . $rec['demand_level'] . "\n";
            
            // Места обучения
            $institutions = getInstitutionsForProfessionReport($link, $rec['profession_id'], 2);
            if ($institutions && $institutions->num_rows > 0) {
                $content .= "Где учиться:\n";
                $institutions->data_seek(0);
                while ($institution = $institutions->fetch_assoc()) {
                    $location = !empty($institution['location']) ? ' (' . $institution['location'] . ')' : '';
                    $content .= "  • " . $institution['name'] . $location . "\n";
                }
            }
            
            // Места работы
            $companies = getCompaniesForProfessionReport($link, $rec['profession_id'], 2);
            if ($companies && $companies->num_rows > 0) {
                $content .= "Где работать:\n";
                $companies->data_seek(0);
                while ($company = $companies->fetch_assoc()) {
                    $industry = !empty($company['industry']) ? ' (' . $company['industry'] . ')' : '';
                    $content .= "  • " . $company['name'] . $industry . "\n";
                }
            }
            $content .= "\n";
        }
    }
    
    $pdf->MultiCell(0, 10, $content);
    return $pdf->Output('', 'S');
}
?>