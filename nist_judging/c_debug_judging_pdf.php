<?php
ob_start();
require_once '../vendor/autoload.php';
require_once '../db.php';

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'NIST - C-Debug Judging Scorecard', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, '5 Easy (1 pt) | 10 Intermediate (3 pts) | 5 Hard (5 pts)', 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);

// Table Header
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(10, 10, 'SN', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Team Name', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Time Taken', 1, 0, 'C', true);
$pdf->Cell(55, 10, 'Solved Questions', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Marks', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Rank', 1, 1, 'C', true);

// Fetch Data
$sql = "SELECT id, team_name, easy_solved, intermediate_solved, hard_solved, marks, start_time, end_time FROM c_debug_teams ORDER BY id ASC";
$result = $conn->query($sql);

$pdf->SetFont('Arial', '', 10);
$rowHeight = 24;

$sn = 1;
while ($row = $result->fetch_assoc()) {
    if ($pdf->GetY() + $rowHeight > 270) $pdf->AddPage();

    $startY = $pdf->GetY();

    // Borders
    $pdf->Cell(10, $rowHeight, '', 1, 0, 'C');
    $pdf->Cell(35, $rowHeight, '', 1, 0, 'C');
    $pdf->Cell(30, $rowHeight, '', 1, 0, 'C');
    $pdf->Cell(55, $rowHeight, '', 1, 0, 'C');
    $pdf->Cell(30, $rowHeight, '', 1, 0, 'C');
    $pdf->Cell(30, $rowHeight, '', 1, 1, 'C');

    // Content
    $pdf->SetXY(10, $startY);
    $pdf->Cell(10, $rowHeight, $sn++, 0, 0, 'C');

    $pdf->SetXY(20, $startY);
    $pdf->MultiCell(35, 6, htmlspecialchars($row['team_name']), 0, 'C');

    // Time Taken
    $pdf->SetXY(55, $startY);
    if ($row['start_time'] && $row['end_time']) {
        $time = strtotime($row['end_time']) - strtotime($row['start_time']);
        $time_text = floor($time / 60) . "m " . ($time % 60) . "s";
        $pdf->Cell(30, $rowHeight, $time_text, 0, 0, 'C');
    } else {
        $pdf->Cell(30, $rowHeight, '____/20m', 0, 0, 'C');
    }

    // Questions
    $pdf->SetXY(85, $startY);
    $pdf->SetFont('Arial', '', 8);
    $e = $row['easy_solved'] > 0 ? $row['easy_solved'] : "____";
    $i = $row['intermediate_solved'] > 0 ? $row['intermediate_solved'] : "____";
    $h = $row['hard_solved'] > 0 ? $row['hard_solved'] : "____";
    $text = "Easy: $e/5\nIntermediate: $i/10\nHard: $h/5";
    $pdf->MultiCell(55, 8, $text, 0, 'L');

    // Marks
    $pdf->SetXY(140, $startY);
    $pdf->SetFont('Arial', 'B', 10);
    $m = $row['marks'] > 0 ? $row['marks'] : "____";
    $pdf->Cell(30, $rowHeight, $m, 0, 0, 'C');

    $pdf->SetY($startY + $rowHeight);
}

$pdf->Output('I', 'C_Debug_Judging_Scorecard.pdf');
ob_end_flush();
