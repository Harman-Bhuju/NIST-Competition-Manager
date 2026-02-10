<?php
ob_start();
require_once 'session_config.php';
require_once 'vendor/autoload.php';
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access.");
}

class NameCardPDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(31, 41, 55);
        $this->Cell(0, 15, 'UI/UX TEAM NAME CARDS', 0, 1, 'C');
        $this->SetDrawColor(209, 213, 219);
        $this->SetLineWidth(0.3);
        $this->Line(10, 20, 200, 20);
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function RoundedRect($x, $y, $w, $h, $r, $style = '', $angle = '1234')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));

        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        if (strpos($angle, '2') === false)
            $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $y) * $k));
        else
            $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);

        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        if (strpos($angle, '3') === false)
            $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - ($y + $h)) * $k));
        else
            $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);

        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        if (strpos($angle, '4') === false)
            $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - ($y + $h)) * $k));
        else
            $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);

        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - $yc) * $k));
        if (strpos($angle, '1') === false) {
            $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - $y) * $k));
            $this->_out(sprintf('%.2F %.2F l', ($x + $r) * $k, ($hp - $y) * $k));
        } else
            $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c ',
            $x1 * $this->k,
            ($h - $y1) * $this->k,
            $x2 * $this->k,
            ($h - $y2) * $this->k,
            $x3 * $this->k,
            ($h - $y3) * $this->k
        ));
    }

    function SetDash($black = null, $white = null)
    {
        if ($black !== null)
            $s = sprintf('[%.3F %.3F] 0 d', $black * $this->k, $white * $this->k);
        else
            $s = '[] 0 d';
        $this->_out($s);
    }
}

$pdf = new NameCardPDF();
$pdf->AddPage();

// Fetch UI/UX teams
$sql = "SELECT DISTINCT id, team_name FROM uiux_teams ORDER BY id ASC";
$result = $conn->query($sql);

$teams = [];
while ($row = $result->fetch_assoc()) {
    $teams[] = $row['team_name'];
}

// Card dimensions
$cardWidth = 90;  // Width of each card
$cardHeight = 40; // Height of each card
$cardsPerRow = 2; // 2 cards per row
$marginLeft = 15;
$marginTop = 30;
$spacing = 10;    // Space between cards

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetDrawColor(52, 73, 94);
$pdf->SetLineWidth(0.5);

$currentCard = 0;
foreach ($teams as $index => $teamName) {
    $row = floor($currentCard / $cardsPerRow);
    $col = $currentCard % $cardsPerRow;

    $x = $marginLeft + ($col * ($cardWidth + $spacing));
    $y = $marginTop + ($row * ($cardHeight + $spacing));

    // Check if we need a new page
    if ($y + $cardHeight > 270) {
        $pdf->AddPage();
        $currentCard = 0;
        $row = 0;
        $col = 0;
        $x = $marginLeft;
        $y = $marginTop;
    }

    $pdf->SetXY($x, $y);

    // Draw card border with rounded corners
    $pdf->RoundedRect($x, $y, $cardWidth, $cardHeight, 3, 'D');

    // Add team name in center
    $pdf->SetXY($x, $y + 10);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell($cardWidth, 10, htmlspecialchars($teamName), 0, 1, 'C');

    // Add "UI/UX" label at bottom
    $pdf->SetXY($x, $y + 25);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell($cardWidth, 10, 'UI/UX', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);

    // Add scissors icon indicator (dashed line around)
    $pdf->SetLineWidth(0.2);
    $pdf->SetDrawColor(150, 150, 150);
    $pdf->SetDash(2, 2);
    $pdf->RoundedRect($x - 2, $y - 2, $cardWidth + 4, $cardHeight + 4, 3, 'D');
    $pdf->SetDash(); // Reset to solid line
    $pdf->SetDrawColor(52, 73, 94);
    $pdf->SetLineWidth(0.5);

    $currentCard++;
}

$pdf->Output('I', 'UIUX_Name_Cards.pdf');
ob_end_flush();
