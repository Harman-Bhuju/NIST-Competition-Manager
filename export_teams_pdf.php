<?php
ob_start();
require_once 'session_config.php'; // Persistent login
require_once 'vendor/autoload.php';
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access.");
}

$category = isset($_GET['category']) ? $_GET['category'] : 'c_debug';

class PDF extends FPDF
{
    public $category;
    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(31, 41, 55);
        $title = 'NIST EVENT - ' . ($this->category === 'c_debug' ? 'C-Debug' : 'UI-UX') . ' - TEAM LIST';
        $this->Cell(0, 15, $title, 0, 1, 'C');
        $this->SetDrawColor(209, 213, 219);
        $this->SetLineWidth(0.1);
        $this->Line(10, 22, 200, 22);
        $this->Ln(8);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->category = $category;
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Table Styling
$pdf->SetFillColor(236, 240, 241);
$pdf->SetDrawColor(189, 195, 199);
$pdf->SetFont('Arial', 'B', 11);

if ($category === 'c_debug') {
    // Header
    $pdf->Cell(15, 12, 'SN', 1, 0, 'C', true);
    $pdf->Cell(50, 12, 'Team Name', 1, 0, 'C', true);
    $pdf->Cell(70, 12, 'Member Name', 1, 0, 'C', true);
    $pdf->Cell(30, 12, 'Section', 1, 0, 'C', true);
    $pdf->Cell(25, 12, 'Laptop', 1, 1, 'C', true);  // Changed to 1, 1 to end row

    $sql = "SELECT t.id, t.team_name, t.laptop, m.team_member as member_name, m.section
            FROM c_debug_teams t
            LEFT JOIN c_debug_members m ON t.id = m.team_id
            ORDER BY t.id ASC, m.team_member";
} else {
    // Header
    $pdf->Cell(15, 12, 'SN', 1, 0, 'C', true);
    $pdf->Cell(55, 12, 'Team Name', 1, 0, 'C', true);
    $pdf->Cell(85, 12, 'Member Name', 1, 0, 'C', true);
    $pdf->Cell(35, 12, 'Section', 1, 1, 'C', true);

    $sql = "SELECT t.id, t.team_name, m.member_name, m.section 
            FROM uiux_teams t
            LEFT JOIN uiux_members m ON t.id = m.team_id
            ORDER BY t.id ASC, m.member_name";
}

$result = $conn->query($sql);
$teams = [];
while ($row = $result->fetch_assoc()) {
    if (!$row['id']) continue;
    $teams[$row['id']]['team_name'] = $row['team_name'];
    if ($category === 'c_debug') {
        $teams[$row['id']]['laptop'] = $row['laptop'] ?? '';
    }
    $teams[$row['id']]['members'][] = [
        'name' => $row['member_name'],
        'section' => $row['section']
    ];
}

$sn = 1;
$pdf->SetFont('Arial', '', 11);
$rowHeight = 10;

foreach ($teams as $teamId => $data) {
    $members = $data['members'] ?? [];
    $numMembers = count($members);
    $totalHeight = $numMembers * $rowHeight;

    // Check if we need a new page
    if ($pdf->GetY() + $totalHeight > 270) {
        $pdf->AddPage();
    }

    $startY = $pdf->GetY();

    if ($category === 'c_debug') {
        // SN and Team Name (Spanning multiple rows)
        $pdf->Cell(15, $totalHeight, $sn++, 1, 0, 'C');

        $currentX = $pdf->GetX();
        $pdf->Cell(50, $totalHeight, htmlspecialchars($data['team_name']), 1, 0, 'C');

        // Laptop column (spanning)
        $pdf->SetXY($currentX + 50 + 70 + 30, $startY);
        $pdf->Cell(25, $totalHeight, htmlspecialchars($data['laptop']), 1, 0, 'C');

        // Member list
        foreach ($members as $index => $m) {
            $pdf->SetXY($currentX + 50, $startY + ($index * $rowHeight));
            $pdf->Cell(70, $rowHeight, htmlspecialchars($m['name']), 1, 0, 'L');
            $pdf->Cell(30, $rowHeight, htmlspecialchars($m['section']), 1, 0, 'C');
        }
    } else {
        // SN and Team Name (Spanning multiple rows)
        $pdf->Cell(15, $totalHeight, $sn++, 1, 0, 'C');

        $currentX = $pdf->GetX();
        $pdf->Cell(55, $totalHeight, htmlspecialchars($data['team_name']), 1, 0, 'C');

        // Member list
        foreach ($members as $index => $m) {
            $pdf->SetXY($currentX + 55, $startY + ($index * $rowHeight));
            $pdf->Cell(85, $rowHeight, htmlspecialchars($m['name']), 1, 0, 'L');
            $pdf->Cell(35, $rowHeight, htmlspecialchars($m['section']), 1, 1, 'C');
        }
    }

    // Move Y to after this team block for next team
    $pdf->SetY($startY + $totalHeight);
}

$pdf->Output('I', 'NIST_Team_List.pdf');
ob_end_flush();
