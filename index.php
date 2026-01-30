<?php
// Use shared persistent session config
require_once 'session_config.php';
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: nist_admin/admin_login.php");
    exit;
}

if ($_SESSION['admin_role'] === 'admin') {
    header("Location: nist_admin/c_debug_admin.php");
    exit;
}

$volunteer_id = $_SESSION['admin_id'];
$category = isset($_GET['cat']) ? $_GET['cat'] : 'c_debug';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Fetch global timer status
$settings = $conn->query("SELECT * FROM competition_settings WHERE category = 'c_debug'")->fetch_assoc();

// Fetch assigned teams for timer control
$assigned_teams = [];
if ($category === 'c_debug') {
    $assigned_sql = "SELECT t.id, t.team_name, a.status, t.timer_status, t.end_time 
                    FROM c_debug_teams t
                    JOIN volunteer_assignments a ON t.id = a.team_id
                    WHERE a.volunteer_id = $volunteer_id AND a.status = 'accepted'";
    $assigned_teams = $conn->query($assigned_sql);
}

// Fetch all participants for attendance
if ($category === 'c_debug') {
    $sql = "SELECT t.id as team_id, t.team_name, t.laptop, t.attendance, t.timer_status, t.end_time, t.marks,
                   m.id as member_id, m.team_member as name, m.section, a.username as attendance_by 
            FROM c_debug_teams t
            LEFT JOIN c_debug_members m ON t.id = m.team_id
            LEFT JOIN admin a ON t.attendance_updated_by_id = a.id";
} else {
    $sql = "SELECT t.id as team_id, t.team_name, t.attendance, 
                   m.id as member_id, m.member_name as name, m.section, a.username as attendance_by 
            FROM uiux_teams t
            LEFT JOIN uiux_members m ON t.id = m.team_id
            LEFT JOIN admin a ON t.attendance_updated_by_id = a.id";
}

if (!empty($search)) {
    if ($category === 'c_debug') {
        $sql .= " WHERE (t.team_name LIKE '%$search%' OR m.team_member LIKE '%$search%')";
    } else {
        $sql .= " WHERE (t.team_name LIKE '%$search%' OR m.member_name LIKE '%$search%')";
    }
}
$sql .= " ORDER BY t.id ASC, m.id ASC";
$result = $conn->query($sql);

$teams = [];
while ($row = $result->fetch_assoc()) {
    $tid = $row['team_id'];
    $teams[$tid]['team_name'] = $row['team_name'];
    $teams[$tid]['attendance'] = $row['attendance'];
    $teams[$tid]['attendance_by'] = $row['attendance_by'];
    if (isset($row['timer_status'])) $teams[$tid]['timer_status'] = $row['timer_status'];
    if (isset($row['end_time'])) $teams[$tid]['end_time'] = $row['end_time'];
    if (isset($row['laptop'])) $teams[$tid]['laptop'] = $row['laptop'];
    if (isset($row['marks'])) $teams[$tid]['marks'] = $row['marks'];
    if ($row['member_id']) {
        $teams[$tid]['members'][] = [
            'id' => $row['member_id'],
            'name' => $row['name'],
            'section' => $row['section']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Portal | NIST</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
</head>
<style>
    /* Navbar base */
    .navbar {
        width: 100%;
        background: #0d6efd;
        padding: 0.5rem 1rem;
    }

    /* Inner container */
    .nav-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    /* Brand */
    .navbar-brand {
        color: #fff;
        font-size: 1rem;
        text-decoration: none;
        white-space: nowrap;
    }

    /* Right section */
    .nav-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }

    /* Welcome text */
    .welcome-text {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.75);
        white-space: nowrap;
    }

    /* Logout button */
    .logout-btn {
        background: #dc3545;
        color: #fff;
        padding: 4px 12px;
        font-size: 0.75rem;
        text-decoration: none;
        border-radius: 4px;
        white-space: nowrap;
    }

    /* VOLUNTEER PAGE SPECIFIC RESPONSIVE STYLES */
    @media (max-width: 768px) {

        /* Navbar adjustments */
        .navbar {
            padding: 0.4rem 0.8rem;
        }

        .navbar-brand {
            font-size: 0.85rem;
        }

        .nav-inner {
            gap: 0.5rem;
        }

        .welcome-text {
            font-size: 0.7rem;
        }

        .logout-btn {
            padding: 3px 8px;
            font-size: 0.65rem;
        }

        /* Container padding */
        .container {
            padding: 0 0.8rem !important;
        }

        /* Global Timer Card */
        .card[style*="border-left:5px solid var(--info)"] {
            padding: 1rem !important;
            margin-bottom: 1rem !important;
        }

        .card[style*="border-left:5px solid var(--info)"] .text-muted {
            font-size: 0.65rem !important;
        }

        #global-timer-display {
            font-size: 1.8rem !important;
        }

        /* Timer Controls Card */
        .card[style*="background:var(--black)"] {
            padding: 1rem !important;
            margin-bottom: 1rem !important;
        }

        .card[style*="background:var(--black)"] h3 {
            font-size: 1.1rem !important;
            margin-bottom: 1rem !important;
        }

        /* Timer Controls Grid */
        .timer-control-card {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.8rem !important;
            padding: 1rem !important;
        }

        .timer-control-card>div:first-child {
            width: 100% !important;
        }

        .timer-control-card>div:first-child>div:first-child {
            font-size: 0.95rem !important;
        }

        .team-timer-status {
            font-size: 0.75rem !important;
            margin-top: 0.3rem !important;
        }

        .team-timer-action {
            width: 100% !important;
        }

        .team-timer-action button {
            width: 100% !important;
            display: block !important;
            text-align: center !important;
            padding: 0.6rem 1rem !important;
            font-size: 0.85rem !important;
        }

        .team-timer-action .badge {
            width: 100% !important;
            display: block !important;
            text-align: center !important;
            padding: 0.5rem !important;
            font-size: 0.75rem !important;
        }

        /* Category Switcher */
        div[style*="display:flex; gap:10px; margin-bottom:2rem"] {
            flex-direction: row !important;
            gap: 0.5rem !important;
            margin-bottom: 1rem !important;
        }

        div[style*="display:flex; gap:10px; margin-bottom:2rem"] .btn {
            flex: 1 !important;
            padding: 0.8rem 0.5rem !important;
            font-size: 0.85rem !important;
            font-weight: 700 !important;
        }

        /* Main Card */
        .card {
            padding: 1rem !important;
            margin-bottom: 1rem !important;
        }

        /* Header with buttons */
        div[style*="display:flex; justify-content:space-between"] {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 1rem !important;
            margin-bottom: 1.5rem !important;
            padding-bottom: 0.8rem !important;
        }

        div[style*="display:flex; justify-content:space-between"] h1 {
            font-size: 1.5rem !important;
            margin: 0 !important;
        }

        div[style*="display:flex; justify-content:space-between"]>div {
            width: 100% !important;
            flex-direction: column !important;
        }

        div[style*="display:flex; justify-content:space-between"] .btn {
            width: 100% !important;
            margin-bottom: 0.4rem !important;
            padding: 0.7rem !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
        }

        /* Search form */
        form[style*="display:flex; gap:15px"] {
            flex-direction: column !important;
            gap: 0.5rem !important;
            margin-bottom: 1.5rem !important;
        }

        form[style*="display:flex; gap:15px"] .form-control {
            width: 100% !important;
            padding: 0.7rem !important;
            font-size: 0.85rem !important;
        }

        form[style*="display:flex; gap:15px"] .btn {
            width: 100% !important;
            padding: 0.7rem !important;
            font-size: 0.85rem !important;
        }

        /* Table stackable */
        .table-stackable tbody tr {
            display: flex !important;
            flex-direction: column !important;
            position: relative !important;
            padding: 1rem !important;
            padding-right: 70px !important;
            margin-bottom: 0.8rem !important;
            border-radius: 8px !important;
        }

        /* Keep attendance checkbox on the right */
        .table-stackable .attendance-cell {
            position: absolute !important;
            top: 1rem !important;
            right: 1rem !important;
            width: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            gap: 0.3rem !important;
        }

        .attendance-check {
            width: 32px !important;
            height: 32px !important;
            margin: 0 !important;
        }

        .attendance-info {
            font-size: 0.55rem !important;
            white-space: nowrap !important;
            text-align: center !important;
        }

        /* Team name */
        .table-stackable td[data-label="Team"] {
            font-size: 1.1rem !important;
            margin-bottom: 0.5rem !important;
            padding-right: 50px !important;
        }

        /* Participants */
        .table-stackable td[data-label="Participants"] {
            width: 100% !important;
            margin-bottom: 0 !important;
        }

        .table-stackable td[data-label="Participants"]>div {
            gap: 0.4rem !important;
        }

        .table-stackable td[data-label="Participants"]>div>div {
            font-size: 0.9rem !important;
        }

        .table-stackable td[data-label="Participants"]>div>div span {
            font-size: 0.75rem !important;
        }

        /* Timer control grid */
        div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
            gap: 0.8rem !important;
        }
    }

    @media (max-width: 480px) {

        /* Extra small screens */
        .navbar-brand {
            font-size: 0.75rem;
        }

        .welcome-text {
            font-size: 0.65rem;
        }

        .logout-btn {
            padding: 2px 6px;
            font-size: 0.6rem;
        }

        .container {
            padding: 0 0.5rem !important;
        }

        #global-timer-display {
            font-size: 1.5rem !important;
        }

        .attendance-check {
            width: 28px !important;
            height: 28px !important;
        }

        .table-stackable tbody tr {
            padding: 0.8rem !important;
            padding-right: 60px !important;
        }

        .table-stackable .attendance-cell {
            right: 0.8rem !important;
            top: 0.8rem !important;
        }

        .table-stackable td[data-label="Team"] {
            font-size: 1rem !important;
        }

        .table-stackable td[data-label="Participants"]>div>div {
            font-size: 0.85rem !important;
        }

        h1 {
            font-size: 1.3rem !important;
        }

        .card {
            padding: 0.8rem !important;
        }

        .timer-control-card {
            padding: 0.8rem !important;
        }

        .team-timer-action button,
        .team-timer-action .badge {
            padding: 0.5rem !important;
            font-size: 0.8rem !important;
        }
    }

    @media (max-width: 360px) {

        /* Very small screens */
        .navbar-brand {
            font-size: 0.7rem;
        }

        .welcome-text strong {
            display: block;
            margin-top: 2px;
        }

        #global-timer-display {
            font-size: 1.3rem !important;
        }

        .table-stackable td[data-label="Team"] {
            font-size: 0.95rem !important;
        }

        div[style*="display:flex; gap:10px; margin-bottom:2rem"] .btn {
            font-size: 0.8rem !important;
            padding: 0.7rem 0.3rem !important;
        }
    }
</style>

<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="index.php" class="navbar-brand">NIST VOLUNTEER</a>

            <div class="nav-right">
                <span class="welcome-text">
                    Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_user']); ?></strong>
                </span>
                <a href="nist_admin/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Global Timer Section -->
        <div class="card" style="margin-bottom:2rem; background:var(--gray-50); text-align:center; padding:1.5rem; border-left:5px solid var(--info);">
            <div class="text-muted" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">Global Competition Timer</div>
            <div id="global-timer-display" style="font-size:2.5rem; font-weight:800; font-family:monospace; color:var(--text-main);">00:00</div>
        </div>
        <!-- Timer Action Section (Only for C-Debug) -->
        <?php if ($category === 'c_debug' && $assigned_teams && $assigned_teams->num_rows > 0): ?>
            <div class="card" style="background:var(--black); color:var(--white);">
                <h3 style="color:var(--white);">My Timer Controls</h3>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:15px; margin-top:2rem;">
                    <?php while ($team = $assigned_teams->fetch_assoc()): ?>
                        <div class="timer-control-card" data-team-id="<?php echo $team['id']; ?>" style="background:rgba(255,255,255,0.05); padding:1.25rem; border:2px solid var(--white); display:flex; justify-content:space-between; align-items:center; border-radius:10px;">
                            <div style="flex:1;">
                                <div style="font-weight:900; text-transform:uppercase; font-size:1.1rem; letter-spacing:0.02em; color:var(--white);"><?php echo htmlspecialchars($team['team_name']); ?></div>
                                <div class="team-timer-status" style="font-size:0.9rem; font-weight:900; color:var(--white); text-transform:uppercase; letter-spacing:0.06em; margin-top:4px;">
                                    <?php
                                    if ($team['timer_status'] === 'stopped' && $team['end_time']) {
                                        $start_ts = strtotime($settings['start_time']);
                                        $end_ts = strtotime($team['end_time']);
                                        $elapsed = max(0, $end_ts - $start_ts);
                                        $m = floor($elapsed / 60);
                                        $s = $elapsed % 60;
                                        echo "<span style='color:var(--danger); text-shadow:0 0 10px rgba(255,0,0,0.3);'>STOPPED AT " . $m . ":" . ($s < 10 ? '0' : '') . $s . "</span>";
                                    } else {
                                        echo $team['timer_status'] === 'running' ? '<span style="color:var(--secondary); text-shadow:0 0 10px rgba(0,255,0,0.2);">ALIVE / RUNNING</span>' : '<span style="color:rgba(255,255,255,0.6);">WAITING TO START</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="team-timer-action">
                                <?php if ($team['timer_status'] === 'running' && $settings['status'] === 'running'): ?>
                                    <button onclick="stopTimer(<?php echo $team['id']; ?>)" class="btn" style="background:var(--white); color:var(--black); padding:8px 20px; font-weight:900; box-shadow:0 0 15px rgba(255,255,255,0.3);">STOP</button>
                                <?php elseif ($team['timer_status'] === 'stopped'): ?>
                                    <div class="badge" style="background:var(--danger); color:var(--white); font-weight:900;">DONE</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Category Switcher -->
        <div style="display:flex; gap:10px; margin-bottom:2rem;">
            <a href="?cat=c_debug" class="btn <?php echo $category === 'c_debug' ? 'btn-primary' : ''; ?>" style="flex:1; padding:1.25rem;">C-DEBUG</a>
            <a href="?cat=ui_ux" class="btn <?php echo $category === 'ui_ux' ? 'btn-primary' : ''; ?>" style="flex:1; padding:1.25rem;">UI-UX</a>
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:2.5rem; border-bottom:5px solid var(--black); padding-bottom:1rem;">
                <h1 style="margin:0; border:none;">Checklist</h1>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <?php if ($category === 'c_debug'): ?>
                        <a href="request_assignment.php" class="btn btn-primary" style="background:var(--info); border:none;">Manage My Teams</a>
                    <?php endif; ?>

                    <?php
                    // Correct permission check: check current volunteer's permission
                    $v_id = $_SESSION['admin_id'];
                    $v_p = $conn->query("SELECT can_enter_marks FROM admin WHERE id = $v_id")->fetch_assoc();

                    if ($category === 'c_debug' && ($v_p['can_enter_marks'] ?? 0)): ?>
                        <a href="c_debug_scoring_v.php" class="btn btn-secondary">Enter Marks & Scores</a>
                    <?php endif; ?>
                    <a href="export_teams_pdf.php?category=<?php echo $category; ?>" target="_blank" class="btn">PDF Export</a>
                </div>
            </div>

            <form action="" method="GET" style="display:flex; gap:15px; margin-bottom:2.5rem;">
                <input type="hidden" name="cat" value="<?php echo $category; ?>">
                <input type="text" name="search" class="form-control" placeholder="SEARCH TEAM OR NAME..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1; font-weight:800; text-transform:uppercase;">
                <button type="submit" class="btn btn-primary" style="padding:0 30px;">Search</button>
                <?php if ($search): ?>
                    <a href="?cat=<?php echo $category; ?>" class="btn" style="border-style:dashed;">Clear</a>
                <?php endif; ?>
            </form>

            <div class="table-responsive">
                <table class="table-stackable">
                    <thead style="display:none !important;">
                        <tr>
                            <th style="width:50px;">SN</th>
                            <th>Team Detail</th>
                            <th>Participants</th>
                            <th style="text-align:center; width:120px;">Attendance Check</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teams)): ?>
                            <tr>
                                <td colspan="4" class="text-center">NO DATA FOUND FOR CATEGORY: <?php echo strtoupper($category); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php $sn = 1;
                            foreach ($teams as $tid => $data):
                                $members = $data['members'] ?? [];
                            ?>
                                <tr>
                                    <td data-label="SN" style="display:none;"><?php echo $sn++; ?></td>
                                    <td data-label="Team" style="font-weight:900; font-size:1.3rem; letter-spacing:-0.01em; color:var(--black); margin-bottom:1rem;">
                                        <?php echo htmlspecialchars($data['team_name']); ?>
                                    </td>
                                    <td data-label="Participants">
                                        <div style="display:flex; flex-direction:column; gap:8px;">
                                            <?php foreach ($members as $m): ?>
                                                <div style="font-size:1rem; font-weight:700; color:var(--text-main);">
                                                    &gt; <?php echo htmlspecialchars($m['name']); ?>
                                                    <span style="font-size:0.8rem; color:var(--text-muted); font-weight:500;">(<?php echo htmlspecialchars($m['section']); ?>)</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td data-label="Attendance Check" class="attendance-cell" data-team-id="<?php echo $tid; ?>">
                                        <input type="checkbox" class="attendance-check"
                                            data-id="<?php echo $tid; ?>"
                                            <?php echo ($data['attendance'] ?? 0) ? 'checked' : ''; ?>
                                            style="cursor:pointer; accent-color:var(--black);">
                                        <div class="attendance-info"></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function stopTimer(teamId) {
            if (!confirm('Stop timer for this team?')) return;
            fetch('api/timer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=stop_team&team_id=${teamId}`
            }).then(r => r.json()).then(data => {
                if (data.success) updateGlobalTimer();
                else alert('Error: ' + data.message);
            });
        }

        let serverClientOffset = 0;
        let offsetKnown = false;

        function updateGlobalTimer() {
            fetch(`api/timer.php?action=status&category=<?php echo $category; ?>`)
                .then(r => r.json())
                .then(data => {
                    const display = document.getElementById('global-timer-display');
                    if (!offsetKnown && data.server_time) {
                        serverClientOffset = new Date(data.server_time.replace(/-/g, "/")).getTime() - new Date().getTime();
                        offsetKnown = true;
                    }

                    if (data.status === 'running') {
                        const start = new Date(data.start_time.replace(/-/g, "/")).getTime();
                        const now = new Date().getTime() + serverClientOffset;
                        let elapsed = Math.max(0, now - start);
                        if (elapsed >= 1200000) elapsed = 1200000;
                        const m = Math.floor(elapsed / 60000);
                        const s = Math.floor((elapsed % 60000) / 1000);
                        display.innerText = `${m}:${s < 10 ? '0' : ''}${s}`;
                        display.style.color = 'var(--info)';
                    } else if (data.status === 'finished') {
                        const start = new Date(data.start_time.replace(/-/g, "/")).getTime();
                        const end = new Date(data.end_time.replace(/-/g, "/")).getTime();
                        const elapsed = Math.max(0, end - start);
                        const m = Math.floor(elapsed / 60000);
                        const s = Math.floor((elapsed % 60000) / 1000);
                        display.innerText = `${m}:${s < 10 ? '0' : ''}${s}`;
                        display.style.color = 'var(--danger)';
                    } else {
                        display.innerText = "00:00";
                        display.style.color = 'var(--text-main)';
                    }

                    if (data.teams) {
                        data.teams.forEach(team => {
                            const controlCard = document.querySelector(`.timer-control-card[data-team-id="${team.id}"]`);
                            if (controlCard) {
                                const statusDiv = controlCard.querySelector('.team-timer-status');
                                const actionDiv = controlCard.querySelector('.team-timer-action');

                                if (team.timer_status === 'stopped' && team.end_time) {
                                    const start = new Date(data.start_time.replace(/-/g, "/")).getTime();
                                    const end = new Date(team.end_time.replace(/-/g, "/")).getTime();
                                    const elapsed = Math.max(0, Math.floor((end - start) / 1000));
                                    const m = Math.floor(elapsed / 60);
                                    const s = elapsed % 60;
                                    statusDiv.innerHTML = `<span style="color:var(--danger); font-weight:900; text-shadow:0 0 10px rgba(255,0,0,0.3);">STOPPED AT ${m}:${s < 10 ? '0' : ''}${s}</span>`;
                                    actionDiv.innerHTML = `<div class="badge" style="background:var(--danger); color:var(--white); font-weight:900; box-shadow:0 0 10px rgba(220,53,69,0.3);">DONE</div>`;
                                } else if (team.timer_status === 'running' && data.status === 'running') {
                                    statusDiv.innerHTML = '<span style="color:var(--secondary); font-weight:900; text-shadow:0 0 10px rgba(0,255,0,0.2);">ALIVE / RUNNING</span>';
                                    actionDiv.innerHTML = `<button onclick="stopTimer(${team.id})" class="btn" style="background:var(--white); color:var(--black); padding:10px 25px; font-weight:900; box-shadow:0 0 20px rgba(255,255,255,0.4);">STOP</button>`;
                                } else {
                                    const stMsg = data.status === 'finished' ? 'FINISHED' : 'WAITING...';
                                    statusDiv.innerHTML = `<span style="color:rgba(255,255,255,0.6); font-weight:900;">${stMsg}</span>`;
                                    actionDiv.innerHTML = '';
                                }
                            }

                            const attCell = document.querySelector(`.attendance-cell[data-team-id="${team.id}"]`);
                            if (attCell) {
                                const ck = attCell.querySelector('.attendance-check');
                                const infoDiv = attCell.querySelector('.attendance-info');
                                if (ck && !ck.matches(':focus')) ck.checked = team.attendance == 1;

                                if (team.attendance == 1 && team.attendance_by) {
                                    infoDiv.innerHTML = `<div style="font-size:0.65rem; color:var(--text-muted); margin-top:4px;">BY: ${team.attendance_by}</div>`;
                                } else {
                                    infoDiv.innerHTML = '';
                                }
                            }

                        });
                    }
                });
        }
        setInterval(updateGlobalTimer, 1000);
        updateGlobalTimer();

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('attendance-check')) {
                fetch('api/attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `type=<?php echo $category; ?>&team_id=${e.target.dataset.id}&status=${e.target.checked ? 1 : 0}`
                }).then(r => r.json()).then(data => {
                    if (data.success) updateGlobalTimer();
                });
            }
        });
    </script>
</body>

</html>