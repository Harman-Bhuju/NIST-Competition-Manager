<?php
require_once 'session_config.php'; // Persistent login
require_once 'db.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] === 'admin') {
    header("Location: nist_admin/admin_login.php");
    exit;
}

$volunteer_id = $_SESSION['admin_id'];

// Handle assignment request
if (isset($_POST['request_team'])) {
    $team_id = (int)$_POST['team_id'];

    // Check if already requested by this volunteer
    $check = $conn->query("SELECT id FROM volunteer_assignments WHERE volunteer_id = $volunteer_id AND team_id = $team_id");
    if ($check->num_rows > 0) {
        $error = "You have already requested this team.";
    } else {
        // Check if team is already taken (accepted) by another volunteer
        $taken = $conn->query("SELECT id FROM volunteer_assignments WHERE team_id = $team_id AND status = 'accepted'");
        if ($taken->num_rows > 0) {
            $error = "This team is already assigned to another volunteer.";
        } else {
            $stmt = $conn->prepare("INSERT INTO volunteer_assignments (volunteer_id, team_id, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("ii", $volunteer_id, $team_id);
            $stmt->execute();
            $message = "Request sent successfully!";
        }
    }
}

// Handle withdraw request
if (isset($_POST['withdraw_team'])) {
    $team_id = (int)$_POST['team_id'];
    $conn->query("DELETE FROM volunteer_assignments WHERE volunteer_id = $volunteer_id AND team_id = $team_id AND status = 'pending'");
    $message = "Request withdrawn successfully.";
}

// Fetch all C-Debug teams
$sql = "SELECT id, team_name FROM c_debug_teams ORDER BY team_name ASC";
$teams = $conn->query($sql);

// Fetch existing assignments for this volunteer
$my_assignments = [];
$res = $conn->query("SELECT team_id, status FROM volunteer_assignments WHERE volunteer_id = $volunteer_id");
while ($row = $res->fetch_assoc()) $my_assignments[$row['team_id']] = $row['status'];

// Fetch ALL team assignments to show who has taken each team
$all_assignments = [];
$res2 = $conn->query("SELECT a.team_id, a.status, v.username as volunteer_name 
                      FROM volunteer_assignments a 
                      JOIN admin v ON a.volunteer_id = v.id 
                      WHERE a.status = 'accepted'");
while ($row = $res2->fetch_assoc()) $all_assignments[$row['team_id']] = $row['volunteer_name'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments | NIST</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <style>
        @media (max-width: 768px) {
            .container {
                padding: 0 0.5rem !important;
            }

            h1 {
                font-size: 1.3rem !important;
            }

            .table-stackable thead {
                display: none !important;
            }

            .table-stackable tbody tr {
                display: flex;
                flex-wrap: wrap;
                padding: 0.8rem;
                margin-bottom: 0.8rem;
                border: 1px solid var(--border);
                border-radius: 8px;
                background: var(--white);
            }

            .table-stackable td {
                border: none !important;
                padding: 0.3rem 0 !important;
            }

            .table-stackable td[data-label="SN"] {
                display: none !important;
            }

            .table-stackable td[data-label="Team"] {
                width: 100%;
                font-size: 1rem !important;
                margin-bottom: 0.5rem;
            }

            .table-stackable td[data-label="Status"] {
                width: 100%;
            }

            .table-stackable td[data-label="Status"] .btn {
                padding: 8px 15px !important;
                font-size: 0.8rem !important;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 0.3rem !important;
            }

            h1 {
                font-size: 1.1rem !important;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="navbar-brand">NIST VOLUNTEER</a>
            <a href="index.php" class="btn btn-secondary" style="padding:4px 12px; font-size:0.75rem;">Back to Portal</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem; border-bottom:4px solid var(--border); padding-bottom:1rem;">
            <h1>Volunteer For Teams</h1>
        </div>

        <?php if (isset($message)): ?>
            <div class="badge" style="background:rgba(16, 185, 129, 0.1); color:var(--secondary); border:1px solid var(--secondary); display:block; padding:15px; margin-bottom:20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="badge" style="background:rgba(239, 68, 68, 0.1); color:var(--danger); border:1px solid var(--danger); display:block; padding:15px; margin-bottom:20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table-stackable">
                <thead>
                    <tr>
                        <th style="width:60px;">SN</th>
                        <th>Team Name</th>
                        <th style="text-align:center; width:200px;">Status / Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sn = 1;
                    while ($t = $teams->fetch_assoc()): ?>
                        <tr data-team-id="<?php echo $t['id']; ?>">
                            <td data-label="SN"><?php echo $sn++; ?></td>
                            <td data-label="Team" style="font-weight:700; text-transform:uppercase;"><?php echo htmlspecialchars($t['team_name']); ?></td>
                            <td data-label="Status" style="text-align:center;" class="status-cell">
                                <?php if (isset($my_assignments[$t['id']])): ?>
                                    <div style="display:flex; flex-direction:column; gap:8px;">
                                        <div class="badge" style="background:<?php echo $my_assignments[$t['id']] === 'accepted' ? 'rgba(16,185,129,0.1)' : 'rgba(245,158,11,0.1)'; ?>; color:<?php echo $my_assignments[$t['id']] === 'accepted' ? 'var(--secondary)' : 'var(--warning)'; ?>; border:1px solid <?php echo $my_assignments[$t['id']] === 'accepted' ? 'var(--secondary)' : 'var(--warning)'; ?>;">
                                            <?php echo strtoupper($my_assignments[$t['id']]); ?>
                                        </div>
                                        <?php if ($my_assignments[$t['id']] === 'pending'): ?>
                                            <form method="POST">
                                                <input type="hidden" name="team_id" value="<?php echo $t['id']; ?>">
                                                <button type="submit" name="withdraw_team" class="btn btn-danger" style="padding:2px 8px; font-size:0.6rem; width:100%;">WITHDRAW</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif (isset($all_assignments[$t['id']])): ?>
                                    <div class="badge" style="background:rgba(99,102,241,0.1); color:var(--info); border:1px solid var(--info); font-size:0.7rem;">
                                        TAKEN BY: <?php echo htmlspecialchars($all_assignments[$t['id']]); ?>
                                    </div>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="team_id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" name="request_team" class="btn btn-primary" style="padding:4px 12px; font-size:0.75rem; width:100%;">REQUEST</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <script>
        const myVolunteerId = <?php echo $volunteer_id; ?>;

        function refreshAssignments() {
            fetch('api/timer.php?action=team_assignment_status&volunteer_id=' + myVolunteerId)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.teams) {
                        data.teams.forEach(team => {
                            const row = document.querySelector(`tr[data-team-id="${team.id}"]`);
                            if (!row) return;
                            const cell = row.querySelector('.status-cell');
                            if (!cell) return;

                            let html = '';
                            if (team.status === 'my_accepted') {
                                html = `<div class="badge" style="background:rgba(16,185,129,0.1); color:var(--secondary); border:1px solid var(--secondary);">ACCEPTED</div>`;
                            } else if (team.status === 'my_pending') {
                                html = `<div style="display:flex; flex-direction:column; gap:8px;">
                                    <div class="badge" style="background:rgba(245,158,11,0.1); color:var(--warning); border:1px solid var(--warning);">PENDING</div>
                                    <form method="POST"><input type="hidden" name="team_id" value="${team.id}"><button type="submit" name="withdraw_team" class="btn btn-danger" style="padding:2px 8px; font-size:0.6rem; width:100%;">WITHDRAW</button></form>
                                </div>`;
                            } else if (team.status === 'taken') {
                                html = `<div class="badge" style="background:rgba(99,102,241,0.1); color:var(--info); border:1px solid var(--info); font-size:0.7rem;">TAKEN BY: ${team.taken_by}</div>`;
                            } else {
                                html = `<form method="POST"><input type="hidden" name="team_id" value="${team.id}"><button type="submit" name="request_team" class="btn btn-primary" style="padding:4px 12px; font-size:0.75rem; width:100%;">REQUEST</button></form>`;
                            }
                            cell.innerHTML = html;
                        });
                    }
                });
        }
        setInterval(refreshAssignments, 3000);
    </script>
</body>

</html>