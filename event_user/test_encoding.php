<?php
// Простой тест кодировки и PDF
ob_end_clean();

require_once(__DIR__ . '/../tcpdf/tcpdf.php');

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Profio');
$pdf->SetAuthor('Profio');
$pdf->SetTitle('Тест кодировки');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// Простой текст
$html = '<h1>Тест кириллицы в PDF</h1>';
$html .= '<p>Привет! Это тест русского текста.</p>';
$html .= '<p>Работает ли кодировка UTF-8?</p>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('test.pdf', 'I');
exit();