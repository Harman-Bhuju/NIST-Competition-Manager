<?php
ob_start();
require_once '../vendor/autoload.php';
require_once '../db.php';

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'NIST - UI/UX Judging Scorecard', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, 'Total Marks: 100', 0, 1, 'C');
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
$pdf->SetFont('Arial', '', 10);

// Table Header
// Column Widths: 40 | 30 | 30 | 30 | 30 | 30 (Total 190)
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(10, 10, 'SN', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Team Name', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Innovation (30)', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Usability (20)', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Aesthetics (20)', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Responsiveness (20)', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Presentation (10)', 1, 1, 'C', true);

// Fetch Data DESC
$sql = "SELECT id, team_name FROM uiux_teams ORDER BY id ASC";
$result = $conn->query($sql);

$pdf->SetFont('Arial', '', 10);
$rowHeight = 15; // Space for judging marks

$sn = 1;
while ($row = $result->fetch_assoc()) {
    // Base row height
    $rowHeight = 15;

    // Boundary check for new page
    if ($pdf->GetY() + $rowHeight > 270) $pdf->AddPage();

    $startY = $pdf->GetY();

    // 1. Draw Border Boxes first for the whole row
    $pdf->Cell(10, $rowHeight, '', 1, 0, 'C'); // SN
    $pdf->Cell(30, $rowHeight, '', 1, 0, 'C'); // Team Name
    $pdf->Cell(30, $rowHeight, '', 1, 0, 'C'); // Innovation
    $pdf->Cell(30, $rowHeight, '', 1, 0, 'C'); // Usability
    $pdf->Cell(30, $rowHeight, '', 1, 0, 'C'); // Aesthetics
    $pdf->Cell(30, $rowHeight, '', 1, 0, 'C'); // Responsiveness
    $pdf->Cell(30, $rowHeight, '', 1, 1, 'C'); // Presentation

    // 2. Insert Content using SetXY to stay inside the boxes
    // SN
    $pdf->SetXY(10, $startY);
    $pdf->Cell(10, $rowHeight, $sn++, 0, 0, 'C');

    // Team Name (Wrapped if needed)
    $pdf->SetXY(20, $startY);
    $currentY = $pdf->GetY();
    // Use a smaller line height for MultiCell to allow wrapping within the 15mm box
    $pdf->MultiCell(30, 5, htmlspecialchars($row['team_name']), 0, 'C');

    // Reset Y for the next row
    $pdf->SetY($startY + $rowHeight);
}

$pdf->Output('I', 'UI_UX_Judging_Scorecard.pdf');
ob_end_flush();
