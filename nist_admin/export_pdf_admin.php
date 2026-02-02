<?php
ob_start();
require_once '../session_config.php'; // Persistent login
require_once '../vendor/autoload.php';
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access.");
}

$category = isset($_GET['category']) ? $_GET['category'] : '';
if (empty($category)) {
    die("Category not specified.");
}

// FPDF installed via Composer is global and doesn't use namespaces
class PDF extends FPDF
{
    public $category;
    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(31, 41, 55);
        $title = 'NIST EVENT - ' . ($this->category === 'c_debug' ? 'C-Debug' : 'UI-UX');
        if (isset($_GET['winners_only'])) $title .= ' - TOP 3 WINNERS';
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
$pdf->SetFont('Arial', 'B', 10);

if ($category === 'c_debug') {
    $winners_only = isset($_GET['winners_only']) ? (int)$_GET['winners_only'] : 0;

    $pdf->Cell(10, 10, 'SN', 1, 0, 'C', true);
    $pdf->Cell(35, 10, 'Team Name', 1, 0, 'C', true);
    $pdf->Cell(45, 10, 'Member Name', 1, 0, 'C', true);
    $pdf->Cell(15, 10, 'Sec', 1, 0, 'C', true);
    $pdf->Cell(20, 10, 'E/I/H', 1, 0, 'C', true);
    $pdf->Cell(15, 10, 'Mark', 1, 0, 'C', true);
    $pdf->Cell(35, 10, 'Time', 1, 0, 'C', true);
    $pdf->Cell(15, 10, 'Rank', 1, 1, 'C', true);

    if ($winners_only) {
        $id_res = $conn->query("SELECT id FROM c_debug_teams ORDER BY marks DESC, (UNIX_TIMESTAMP(end_time) - UNIX_TIMESTAMP(start_time)) ASC, id ASC LIMIT 3");
        $top_ids = [];
        while ($id_row = $id_res->fetch_assoc()) $top_ids[] = $id_row['id'];
        $id_list = !empty($top_ids) ? implode(',', $top_ids) : '0';

        $sql = "SELECT t.id, t.team_name, t.laptop, t.easy_solved, t.intermediate_solved, t.hard_solved, t.marks, t.start_time, t.end_time, m.team_member, m.section 
                FROM c_debug_teams t
                LEFT JOIN c_debug_members m ON t.id = m.team_id
                WHERE t.id IN ($id_list)
                ORDER BY FIELD(t.id, $id_list), m.team_member";
    } else {
        $sql = "SELECT t.id, t.team_name, t.laptop, t.easy_solved, t.intermediate_solved, t.hard_solved, t.marks, t.start_time, t.end_time, m.team_member, m.section 
                FROM c_debug_teams t
                LEFT JOIN c_debug_members m ON t.id = m.team_id
                ORDER BY t.id ASC, m.team_member";
    }

    $result = $conn->query($sql);
    $teams = [];
    $all_teams_for_ranking = []; // To calculate rank if printing all

    // Sort logic for ranking (outside of winners_only)
    $rank_sql = "SELECT id, marks, (UNIX_TIMESTAMP(end_time) - UNIX_TIMESTAMP(start_time)) as time_diff FROM c_debug_teams WHERE start_time IS NOT NULL AND end_time IS NOT NULL ORDER BY marks DESC, time_diff ASC";
    $rank_res = $conn->query($rank_sql);
    $ranks = [];
    $r_pos = 1;
    while ($r_row = $rank_res->fetch_assoc()) {
        $ranks[$r_row['id']] = $r_pos++;
    }

    while ($row = $result->fetch_assoc()) {
        $teams[$row['id']]['team_name'] = $row['team_name'];
        $teams[$row['id']]['easy'] = $row['easy_solved'];
        $teams[$row['id']]['inter'] = $row['intermediate_solved'];
        $teams[$row['id']]['hard'] = $row['hard_solved'];
        $teams[$row['id']]['marks'] = $row['marks'];
        $teams[$row['id']]['rank'] = $ranks[$row['id']] ?? 'N/A';
        $teams[$row['id']]['time'] = ($row['start_time'] && $row['end_time']) ? (strtotime($row['end_time']) - strtotime($row['start_time'])) : 0;
        $teams[$row['id']]['members'][] = ['name' => $row['team_member'], 'section' => $row['section']];
    }

    $sn = 1;
    $count = 0;
    $pdf->SetFont('Arial', '', 9);
    foreach ($teams as $teamId => $data) {
        if ($winners_only && $count >= 3) break;
        $count++;
        $members = $data['members'];
        $numMembers = count($members);
        $rowHeight = 8;
        $totalHeight = max($numMembers * $rowHeight, 10);

        if ($pdf->GetY() + $totalHeight > 275) $pdf->AddPage();

        $startY = $pdf->GetY();

        // Border Rect for Team Name and SN (unified height)
        $pdf->Cell(10, $totalHeight, $sn++, 1, 0, 'C');

        $currentX = $pdf->GetX();
        $pdf->Cell(35, $totalHeight, htmlspecialchars($data['team_name']), 1, 0, 'C');
        $pdf->SetXY($currentX + 35, $startY);

        // Members & Sections
        $memberX = $pdf->GetX();
        foreach ($members as $index => $m) {
            $pdf->SetXY($memberX, $startY + ($index * $rowHeight));
            $pdf->Cell(45, $rowHeight, htmlspecialchars($m['name']), 1, 0, 'L');
            $pdf->Cell(15, $rowHeight, htmlspecialchars($m['section']), 1, 0, 'C');
        }

        // Stats
        $pdf->SetXY($memberX + 60, $startY);
        $solved_text = $data['easy'] . "/" . $data['inter'] . "/" . $data['hard'];
        $pdf->Cell(20, $totalHeight, $solved_text, 1, 0, 'C');
        $pdf->Cell(15, $totalHeight, $data['marks'], 1, 0, 'C');

        $m = floor($data['time'] / 60);
        $s = $data['time'] % 60;
        $time_text = ($data['time'] > 0) ? $m . "m " . $s . "s" : "N/A";
        $pdf->Cell(35, $totalHeight, $time_text, 1, 0, 'C');
        $pdf->Cell(15, $totalHeight, $data['rank'], 1, 1, 'C');
    }
} else {
    // UI-UX Category
    $pdf->Cell(10, 10, 'SN', 1, 0, 'C', true);
    $pdf->Cell(50, 10, 'Team Name', 1, 0, 'C', true);
    $pdf->Cell(70, 10, 'Member Name', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Section', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Payment', 1, 1, 'C', true);

    $sql = "SELECT t.id, t.team_name, t.payment, m.member_name, m.section 
            FROM uiux_teams t
            LEFT JOIN uiux_members m ON t.id = m.team_id
            ORDER BY t.id ASC, m.member_name";
    $result = $conn->query($sql);
    $teams = [];
    while ($row = $result->fetch_assoc()) {
        if (!$row['id']) continue;
        $teams[$row['id']]['team_name'] = $row['team_name'];
        $teams[$row['id']]['payment'] = $row['payment'];
        $teams[$row['id']]['members'][] = [
            'name' => $row['member_name'],
            'section' => $row['section']
        ];
    }

    $sn = 1;
    $pdf->SetFont('Arial', '', 10);
    foreach ($teams as $teamId => $data) {
        $members = $data['members'] ?? [];
        $numMembers = count($members);
        $rowHeight = 8;
        $totalHeight = max($numMembers * $rowHeight, 10);

        if ($pdf->GetY() + $totalHeight > 275) $pdf->AddPage();

        $startY = $pdf->GetY();

        $pdf->Cell(10, $totalHeight, $sn++, 1, 0, 'C');

        $currentX = $pdf->GetX();
        $pdf->Cell(50, $totalHeight, htmlspecialchars($data['team_name']), 1, 0, 'C');
        $pdf->SetXY($currentX + 50, $startY);

        // Members
        $memberX = $pdf->GetX();
        foreach ($members as $index => $m) {
            $pdf->SetXY($memberX, $startY + ($index * $rowHeight));
            $pdf->Cell(70, $rowHeight, htmlspecialchars($m['name']), 1, 0, 'L');
            $pdf->Cell(30, $rowHeight, htmlspecialchars($m['section']), 1, 1, 'C');
        }

        // Payment
        $pdf->SetXY($memberX + 100, $startY);
        $payment_label = (strtolower($data['payment']) === 'paid') ? 'Paid' : 'Not Paid';
        $pdf->Cell(30, $totalHeight, $payment_label, 1, 1, 'C');
    }
}

$pdf->Output('I', 'NIST_Competition_List.pdf');
ob_end_flush();
