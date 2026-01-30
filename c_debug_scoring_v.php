<?php
// Use shared persistent session config
require_once 'session_config.php';
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: nist_admin/admin_login.php");
    exit;
}

// Check permission if user is a volunteer
if ($_SESSION['admin_role'] !== 'admin') {
    $v_id = $_SESSION['admin_id'];
    $p_check = $conn->query("SELECT can_enter_marks FROM admin WHERE id=$v_id")->fetch_assoc();
    if (!$p_check['can_enter_marks']) {
        die("Permission denied: Admin has disabled your mark entry access.");
    }
}

// Handle scoring update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_score'])) {
    $team_id = (int)$_POST['team_id'];
    $easy = (int)$_POST['easy_solved'];
    $inter = (int)$_POST['intermediate_solved'];
    $hard = (int)$_POST['hard_solved'];

    // Auto calculate marks: Easy=1, Inter=3, Hard=5
    $total_marks = ($easy * 1) + ($inter * 3) + ($hard * 5);
    $scored_by = $_SESSION['admin_id'];

    $stmt = $conn->prepare("UPDATE c_debug_teams SET easy_solved = ?, intermediate_solved = ?, hard_solved = ?, marks = ?, scored_by_id = ? WHERE id = ?");
    $stmt->bind_param("iiiiii", $easy, $inter, $hard, $total_marks, $scored_by, $team_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'marks' => $total_marks, 'scored_by' => $_SESSION['admin_user']]);
    exit;
}

$search = $_GET['search'] ?? '';
$sql = "SELECT t.id, t.team_name, t.easy_solved, t.intermediate_solved, t.hard_solved, t.marks, a.username as scored_by 
        FROM c_debug_teams t 
        LEFT JOIN admin a ON t.scored_by_id = a.id";
if ($search) {
    $sql .= " WHERE t.team_name LIKE '%" . $conn->real_escape_string($search) . "%'";
}
$sql .= " ORDER BY t.id ASC";
$teams = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoring Dashboard | NIST</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <style>
        /* Score input container - mobile first */
        .score-inputs-container {
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
            margin: 0.8rem 0;
            justify-content: center;
        }

        .score-input-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 70px;
        }

        .score-input-group input {
            width: 100%;
            text-align: center;
            font-weight: 800;
            font-size: 0.9rem;
            padding: 0.4rem 0.2rem;
            border: 2px solid var(--border);
            border-radius: 4px;
        }

        .score-input-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-top: 0.2rem;
            text-align: center;
        }

        .total-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.4rem 0.6rem;
            background: var(--gray-50);
            border-radius: 8px;
            border: 2px solid var(--primary);
        }

        .total-display .total-value {
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--primary);
        }

        .total-display .total-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        /* Tablet enhancement */
        @media (min-width: 481px) {
            .score-inputs-container {
                gap: 0.8rem;
                margin: 1rem 0;
            }

            .score-input-group {
                max-width: 90px;
            }

            .score-input-group input {
                font-size: 1.1rem;
                padding: 0.5rem 0.3rem;
            }

            .score-input-label {
                font-size: 0.7rem;
                margin-top: 0.3rem;
            }

            .total-display {
                padding: 0.5rem 0.8rem;
            }

            .total-display .total-value {
                font-size: 1.4rem;
            }

            .total-display .total-label {
                font-size: 0.7rem;
            }
        }

        /* Desktop enhancement */
        @media (min-width: 769px) {
            .score-inputs-container {
                gap: 1rem;
            }

            .score-input-group {
                max-width: 100px;
            }

            .score-input-group input {
                font-size: 1.2rem;
                padding: 0.6rem;
            }

            .total-display {
                padding: 0.5rem 1rem;
            }

            .total-display .total-value {
                font-size: 1.5rem;
            }
        }

        /* SCORING PAGE SPECIFIC RESPONSIVE STYLES */
        @media (max-width: 768px) {
            body {
                padding: 0 !important;
            }

            .container {
                padding: 0 0.8rem !important;
                margin-top: 1rem !important;
            }

            .header-buttons {
                display: flex !important;
                flex-direction: row !important;
                gap: 0.5rem !important;
                margin-bottom: 1rem !important;
                align-items: center !important;
            }

            .header-buttons .btn {
                flex: 1 !important;
                padding: 0.6rem 0.5rem !important;
                font-size: 0.75rem !important;
                font-weight: 600 !important;
                white-space: nowrap !important;
            }

            .header-buttons .badge {
                flex: 1 !important;
                text-align: center !important;
                padding: 0.6rem 0.5rem !important;
                font-size: 0.75rem !important;
            }

            div[style*="margin-bottom:2.5rem"] {
                margin-bottom: 1.5rem !important;
            }

            div[style*="margin-bottom:2.5rem"] h1 {
                font-size: 1.4rem !important;
                padding-bottom: 0.6rem !important;
            }

            .card {
                padding: 1rem !important;
                margin-bottom: 1rem !important;
            }

            .card h3 {
                font-size: 1rem !important;
                margin-bottom: 0.8rem !important;
            }

            form[style*="display:flex"] {
                flex-direction: row !important;
                gap: 0.4rem !important;
                flex-wrap: wrap !important;
            }

            form[style*="display:flex"] .form-control {
                flex: 1 !important;
                min-width: 150px !important;
                padding: 0.6rem !important;
                font-size: 0.8rem !important;
            }

            form[style*="display:flex"] .btn {
                padding: 0.6rem 0.8rem !important;
                font-size: 0.75rem !important;
                font-weight: 600 !important;
            }

            .table-stackable thead {
                display: none !important;
            }

            .table-stackable tbody tr {
                display: block !important;
                padding: 1rem !important;
                margin-bottom: 1rem !important;
                border-radius: 8px !important;
                border: 2px solid var(--border) !important;
                background: var(--white);
            }

            .table-stackable tbody td {
                display: block !important;
                width: 100% !important;
                text-align: left !important;
                padding: 0 !important;
                border: none !important;
            }

            .table-stackable td[data-label="Team"] {
                font-size: 1rem !important;
                font-weight: 900 !important;
                margin-bottom: 0.8rem !important;
                padding-bottom: 0.6rem !important;
                border-bottom: 2px solid var(--gray-200) !important;
            }

            .table-stackable td[data-label="Action"] {
                padding-top: 0.8rem !important;
                margin-top: 0.8rem !important;
                border-top: 2px solid var(--gray-200) !important;
            }

            .table-stackable td[data-label="Action"] button {
                width: 100% !important;
                padding: 0.6rem !important;
                font-size: 0.85rem !important;
                font-weight: 700 !important;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 0.5rem !important;
            }

            .card {
                padding: 0.8rem !important;
            }

            div[style*="margin-bottom:2.5rem"] h1 {
                font-size: 1.2rem !important;
            }

            .table-stackable tbody tr {
                padding: 0.8rem !important;
            }

            .table-stackable td[data-label="Team"] {
                font-size: 0.95rem !important;
            }

            .header-buttons .btn,
            .header-buttons .badge {
                font-size: 0.7rem !important;
                padding: 0.5rem 0.3rem !important;
            }
        }

        @media (max-width: 360px) {
            div[style*="margin-bottom:2.5rem"] h1 {
                font-size: 1.1rem !important;
            }

            .score-inputs-container {
                gap: 0.3rem !important;
            }

            .score-input-group {
                max-width: 55px !important;
            }
        }
    </style>
</head>

<body style="background:var(--gray-50);">
    <div class="container mt-4">
        <!-- Header buttons in single row -->
        <div class="header-buttons" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <a href="index.php" class="btn btn-secondary" style="font-weight:700; border:2px solid var(--black);">
                &larr; Back
            </a>
            <div class="badge" style="background:var(--black); color:var(--white); padding:8px 15px;">Volunteer Portal</div>
        </div>

        <div style="margin-bottom:2.5rem; border-bottom:5px solid var(--black); padding-bottom:1rem;">
            <h1 style="margin:0; font-size:2.5rem;">Scoring & Marks</h1>
        </div>

        <div class="card">
            <h3 style="margin-bottom:1.5rem;">Filter Teams</h3>
            <form action="" method="GET" style="display:flex; gap:15px; flex-wrap:wrap;">
                <input type="text" name="search" class="form-control" placeholder="SEARCH TEAM NAME..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1; text-transform:uppercase; font-weight:800; min-width:200px;">
                <button type="submit" class="btn btn-primary" style="padding:0.6rem 1.5rem;">Search</button>
                <?php if ($search): ?>
                    <a href="c_debug_scoring_v.php" class="btn btn-secondary" style="padding:0.6rem 1.5rem;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table-stackable">
                <thead>
                    <tr>
                        <th>Team Name</th>
                        <th style="text-align:center;">Scores</th>
                        <th style="text-align:center; width:150px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($t = $teams->fetch_assoc()): ?>
                        <tr id="row-<?php echo $t['id']; ?>">
                            <td data-label="Team" style="font-weight:900; text-transform:uppercase;">
                                <?php echo htmlspecialchars($t['team_name']); ?>
                            </td>

                            <td data-label="Scores">
                                <div class="score-inputs-container">
                                    <div class="score-input-group">
                                        <input type="number" class="form-control score-input-easy" value="<?php echo $t['easy_solved']; ?>" min="0" oninput="this.dataset.lastEdit=Date.now()">
                                        <div class="score-input-label">Easy (1pt)</div>
                                    </div>
                                    <div class="score-input-group">
                                        <input type="number" class="form-control score-input-inter" value="<?php echo $t['intermediate_solved']; ?>" min="0" oninput="this.dataset.lastEdit=Date.now()">
                                        <div class="score-input-label">Inter (3pt)</div>
                                    </div>
                                    <div class="score-input-group">
                                        <input type="number" class="form-control score-input-hard" value="<?php echo $t['hard_solved']; ?>" min="0" oninput="this.dataset.lastEdit=Date.now()">
                                        <div class="score-input-label">Hard (5pt)</div>
                                    </div>
                                    <div class="total-display">
                                        <div class="total-value marks-display"><?php echo $t['marks']; ?></div>
                                        <div class="total-label">Total</div>
                                        <?php if ($t['scored_by']): ?>
                                            <div class="scored-by-label" style="font-size:0.6rem; color:var(--text-muted); margin-top:4px;">by <?php echo htmlspecialchars($t['scored_by']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <td data-label="Action" style="text-align:center;">
                                <button onclick="saveScore(<?php echo $t['id']; ?>)" class="btn btn-primary" style="width:auto; padding:0.5rem 1.5rem; min-width:100px; margin:0 auto;">Save</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function saveScore(teamId) {
            const row = document.getElementById('row-' + teamId);
            const easy = row.querySelector('.score-input-easy').value;
            const inter = row.querySelector('.score-input-inter').value;
            const hard = row.querySelector('.score-input-hard').value;

            const formData = new URLSearchParams();
            formData.append('update_score', '1');
            formData.append('team_id', teamId);
            formData.append('easy_solved', easy);
            formData.append('intermediate_solved', inter);
            formData.append('hard_solved', hard);

            fetch('c_debug_scoring_v.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        row.querySelector('.marks-display').innerText = data.marks;

                        let label = row.querySelector('.scored-by-label');
                        if (!label) {
                            label = document.createElement('div');
                            label.className = 'scored-by-label';
                            label.style.fontSize = '0.6rem';
                            label.style.color = 'var(--text-muted)';
                            label.style.marginTop = '4px';
                            row.querySelector('.total-display').appendChild(label);
                        }
                        label.innerText = 'by ' + data.scored_by;

                        row.style.background = '#f0fdf4';
                        setTimeout(() => row.style.background = 'transparent', 1500);
                    }
                });
        }

        function refreshScores() {
            fetch('api/timer.php?action=status&category=c_debug')
                .then(r => r.json())
                .then(data => {
                    if (data.teams) {
                        data.teams.forEach(team => {
                            if (row) {
                                const easyIn = row.querySelector('.score-input-easy');
                                const interIn = row.querySelector('.score-input-inter');
                                const hardIn = row.querySelector('.score-input-hard');
                                const marksDisp = row.querySelector('.marks-display');

                                const now = Date.now();

                                function shouldUpdate(el) {
                                    if (el === document.activeElement) return false;
                                    const lastEdit = parseInt(el.dataset.lastEdit || 0);
                                    return (now - lastEdit > 5000); // 5s buffer
                                }

                                if (easyIn && shouldUpdate(easyIn)) easyIn.value = team.easy_solved;
                                if (interIn && shouldUpdate(interIn)) interIn.value = team.intermediate_solved;
                                if (hardIn && shouldUpdate(hardIn)) hardIn.value = team.hard_solved;

                                if (marksDisp) marksDisp.innerText = team.marks;

                                if (team.scored_by) {
                                    let label = row.querySelector('.scored-by-label');
                                    if (!label) {
                                        label = document.createElement('div');
                                        label.className = 'scored-by-label';
                                        label.style.fontSize = '0.6rem';
                                        label.style.color = 'var(--text-muted)';
                                        label.style.marginTop = '4px';
                                        row.querySelector('.total-display').appendChild(label);
                                    }
                                    label.innerText = 'by ' + team.scored_by;
                                }
                            }
                        });
                    }
                });
        }
        setInterval(refreshScores, 2000);
    </script>
</body>

</html>