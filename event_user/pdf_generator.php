<?php
require_once('../tcpdf/tcpdf.php');

class PDFReportGenerator extends TCPDF {

    public function Header() {
        // Верхний блок с фоном
        $this->SetFillColor(103, 58, 183);
        $this->Rect(0, 0, 210, 25, 'F');

        // Логотип и название
        $this->SetFont('dejavusans', 'B', 18);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 15, 'PROFIO', 0, 1, 'C');

        $this->SetFont('dejavusans', '', 10);
        $this->Cell(0, 0, 'Система профессиональной ориентации', 0, 1, 'C');

        // Белая линия снизу
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

    // Красивый блок
    public function StyledBlock($x, $y, $w, $h, $fill_color, $border_color, $border_width = 0.4) {
        $this->SetFillColor($fill_color[0], $fill_color[1], $fill_color[2]);
        $this->SetDrawColor($border_color[0], $border_color[1], $border_color[2]);
        $this->SetLineWidth($border_width);
        $this->RoundedRect($x, $y, $w, $h, 4, '1111', 'DF');
    }

    // Прогресс-бар
    public function ProgressBar($x, $y, $width, $height, $progress, $color = [103, 58, 183]) {
        $this->SetFillColor(230, 230, 230);
        $this->RoundedRect($x, $y, $width, $height, 2, '1111', 'F');

        $fill_width = ($width * $progress) / 100;
        $this->SetFillColor($color[0], $color[1], $color[2]);
        $this->RoundedRect($x, $y, $fill_width, $height, 2, '1111', 'F');

        $this->SetFont('dejavusans', 'B', 8);
        $this->SetTextColor(60, 60, 60);
        $this->SetXY($x + $width + 4, $y - 1);
        $this->Cell(0, $height, $progress . '%', 0, 0, 'L');
    }
}

function generateStudentPDFReport($report_data, $user_info, $link) {
    $pdf = new PDFReportGenerator(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Profio System');
    $pdf->SetAuthor('Profio');
    $pdf->SetTitle('Отчет по профориентации - ' . $user_info['name']);
    $pdf->SetSubject('Профориентационный отчет');

    // ===== ПЕРВАЯ СТРАНИЦА =====
    $pdf->AddPage();
    $pdf->SetY(40);
    $pdf->SetFont('dejavusans', 'B', 22);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->Cell(0, 12, 'ПЕРСОНАЛЬНЫЙ ОТЧЕТ', 0, 1, 'C');
    $pdf->SetFont('dejavusans', '', 16);
    $pdf->Cell(0, 8, 'по профессиональной ориентации', 0, 1, 'C');
    $pdf->Ln(20);

    // Блок информации о студенте
    $y = $pdf->GetY();
    $pdf->StyledBlock(15, $y, 180, 40, [248, 246, 255], [103, 58, 183]);
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->SetXY(25, $y + 8);
    $pdf->Cell(0, 6, 'Информация о студенте', 0, 1);

    $pdf->SetFont('dejavusans', '', 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(25, $y + 18);
    $pdf->Cell(0, 6, 'ФИО: ' . $user_info['name'], 0, 1);
    $pdf->SetX(25);
    $pdf->Cell(0, 6, 'Email: ' . $user_info['email'], 0, 1);
    $pdf->SetX(25);
    $pdf->Cell(0, 6, 'Образование: ' . ucfirst($user_info['education_level']), 0, 1);
    $pdf->Ln(18);

    // ===== ОБЩАЯ СТАТИСТИКА =====
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->Cell(0, 10, 'Общая статистика', 0, 1);
    $pdf->Ln(4);

    $stats = [
        ['label' => 'Пройдено тестов', 'value' => $report_data['user']['tests_count'] ?? 0],
        ['label' => 'Получено рекомендаций', 'value' => $report_data['user']['recommendations_count'] ?? 0],
        ['label' => 'Планов развития', 'value' => $report_data['user']['plans_count'] ?? 0]
    ];

    foreach ($stats as $s) {
        $y = $pdf->GetY();
        $pdf->StyledBlock(15, $y, 180, 20, [230, 223, 250], [103, 58, 183]);
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->SetXY(25, $y + 6);
        $pdf->Cell(100, 6, $s['label'], 0, 0, 'L');
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 6, $s['value'], 0, 1, 'R');
        $pdf->Ln(6);
    }

    // ===== РЕЗУЛЬТАТЫ ТЕСТОВ =====
    if ($report_data['test_results'] && $report_data['test_results']->num_rows > 0) {
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', 'B', 18);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 12, 'Результаты тестирования', 0, 1, 'C');
        $pdf->Ln(6);

        $test_num = 1;
        $report_data['test_results']->data_seek(0);
        while ($test = $report_data['test_results']->fetch_assoc()) {
            $y = $pdf->GetY();
            $pdf->StyledBlock(15, $y, 180, 35, [245, 243, 255], [103, 58, 183]);

            $pdf->SetXY(25, $y + 6);
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->SetTextColor(103, 58, 183);
            $pdf->Cell(0, 6, "{$test_num}. {$test['test_name']}", 0, 1);

            $pdf->SetFont('dejavusans', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(25, $y + 16);
            $pdf->Cell(0, 6, "Баллы: " . $test['total_score'], 0, 1);
            $pdf->SetX(25);
            $pdf->Cell(0, 6, "Тип: " . $test['result_type'], 0, 1);
            $pdf->SetX(25);
            $pdf->Cell(0, 6, "Дата: " . date('d.m.Y', strtotime($test['completed_at'])), 0, 1);
            $pdf->Ln(8);
            $test_num++;
        }
    }

    // ===== РЕКОМЕНДОВАННЫЕ ПРОФЕССИИ =====
    if (!empty($report_data['recommendations_with_images'])) {
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', 'B', 18);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 12, 'Рекомендованные профессии', 0, 1, 'C');
        $pdf->Ln(8);

        foreach ($report_data['recommendations_with_images'] as $rec) {
            $y = $pdf->GetY();
            $pdf->StyledBlock(15, $y, 180, 95, [248, 246, 255], [180, 170, 210]);
            $pdf->SetXY(25, $y + 8);

            $pdf->SetFont('dejavusans', 'B', 13);
            $pdf->SetTextColor(103, 58, 183);
            $pdf->Cell(0, 6, $rec['profession_title'], 0, 1);

            $pdf->SetFont('dejavusans', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(25, $y + 18);
            $pdf->Cell(0, 6, 'Совпадение с профилем:', 0, 1);
            $pdf->ProgressBar(25, $y + 24, 100, 6, $rec['match_percentage'], [103, 58, 183]);

            $pdf->SetXY(25, $y + 36);
            $pdf->Cell(0, 6, 'Категория: ' . $rec['category'], 0, 1);
            $pdf->SetX(25);
            $pdf->Cell(0, 6, 'Средняя зарплата: ' . $rec['salary_range'], 0, 1);

            $color = match($rec['demand_level']) {
                'высокий' => [76, 175, 80],
                'средний' => [255, 152, 0],
                default => [244, 67, 54]
            };
            $pdf->SetTextColor($color[0], $color[1], $color[2]);
            $pdf->SetX(25);
            $pdf->Cell(0, 6, 'Спрос на рынке: ' . ucfirst($rec['demand_level']), 0, 1);
            $pdf->SetTextColor(0, 0, 0);

            // Где учиться
            $inst = getInstitutionsForProfessionReport($link, $rec['profession_id'], 3);
            if ($inst && $inst->num_rows > 0) {
                $pdf->SetFont('dejavusans', 'B', 10);
                $pdf->SetTextColor(103, 58, 183);
                $pdf->SetXY(25, $y + 54);
                $pdf->Cell(0, 6, 'Где учиться:', 0, 1);
                $pdf->SetFont('dejavusans', '', 9);
                $pdf->SetTextColor(0, 0, 0);
                $iy = $y + 60;
                while ($row = $inst->fetch_assoc()) {
                    $program = getProgramDetails($link, $rec['profession_id'], $row['id']);
                    $extra = $program ? ' — ' . $program['duration'] . ' (' . $program['cost'] . ')' : '';
                    $pdf->SetXY(30, $iy);
                    $pdf->Cell(0, 5, '• ' . $row['name'] . ' (' . $row['location'] . ')' . $extra, 0, 1);
                    $iy += 5;
                }
            }

            // Где работать
            $comp = getCompaniesForProfessionReport($link, $rec['profession_id'], 3);
            if ($comp && $comp->num_rows > 0) {
                $pdf->SetFont('dejavusans', 'B', 10);
                $pdf->SetTextColor(103, 58, 183);
                $pdf->SetXY(25, $iy + 2);
                $pdf->Cell(0, 6, 'Где работать:', 0, 1);
                $pdf->SetFont('dejavusans', '', 9);
                $pdf->SetTextColor(0, 0, 0);
                $cy = $iy + 8;
                while ($c = $comp->fetch_assoc()) {
                    $pos = getPositionDetails($link, $rec['profession_id'], $c['id']);
                    $extra = $pos ? ' — ' . $pos['position_name'] . ' (' . $pos['experience_level'] . ')' : '';
                    $pdf->SetXY(30, $cy);
                    $pdf->Cell(0, 5, '• ' . $c['name'] . ' (' . $c['industry'] . ')' . $extra, 0, 1);
                    $cy += 5;
                }
            }

            $pdf->Ln(18);
        }
    }

    // ===== ПЛАН ПРОФЕССИОНАЛЬНОГО РАЗВИТИЯ =====
if (!empty($report_data['development_plans'])) {
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', 'B', 18);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->Cell(0, 12, 'План профессионального развития', 0, 1, 'C');
    $pdf->Ln(8);

    foreach ($report_data['development_plans'] as $plan) {
        $y = $pdf->GetY();
        $pdf->StyledBlock(15, $y, 180, 40, [245, 243, 255], [103, 58, 183]);
        $pdf->SetXY(25, $y + 8);
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->SetTextColor(103, 58, 183);
        $pdf->Cell(0, 6, $plan['title'], 0, 1);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetX(25);
        $pdf->MultiCell(160, 5, $plan['description']);
        $pdf->Ln(5);
    }
}


    // ===== ФИНАЛЬНАЯ СТРАНИЦА =====
    $pdf->AddPage();
    $pdf->SetFillColor(103, 58, 183);
    $pdf->Rect(0, 0, 210, 40, 'F');
    $pdf->SetY(15);
    $pdf->SetFont('dejavusans', 'B', 20);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'Спасибо за использование PROFIO!', 0, 1, 'C');

    $pdf->SetY(60);
    $pdf->SetTextColor(103, 58, 183);
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, 'Ваш персональный отчет готов!', 0, 1, 'C');

    $pdf->SetY(80);
    $pdf->SetFont('dejavusans', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCell(0, 8, 'Этот отчет поможет вам в построении карьерного пути и профессиональном развитии. Используйте полученные рекомендации для достижения ваших целей.', 0, 'C');
    $pdf->Ln(8);

    $pdf->SetFont('dejavusans', 'I', 10);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->MultiCell(0, 6, 'Отчет сгенерирован автоматически системой Profio. Для консультации обратитесь к вашему куратору.', 0, 'C');

    return $pdf->Output('', 'S');
}
?>
