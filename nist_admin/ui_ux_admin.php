<?php
require_once '../session_config.php'; // Persistent login
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Stats
$stats = $conn->query("SELECT 
    (SELECT COUNT(*) FROM uiux_teams) as total_teams,
    (SELECT COUNT(*) FROM uiux_members) as total_members")->fetch_assoc();

// Fetch Data
$sql = "SELECT t.id, t.team_name, t.payment, t.attendance, m.member_name, m.section 
        FROM uiux_teams t
        LEFT JOIN uiux_members m ON t.id = m.team_id
        ORDER BY t.id ASC";
$result = $conn->query($sql);
$teams = [];
while ($row = $result->fetch_assoc()) {
    $teams[$row['id']]['team_name'] = $row['team_name'];
    $teams[$row['id']]['payment'] = $row['payment'];
    $teams[$row['id']]['attendance'] = $row['attendance'];
    if ($row['member_name']) {
        $teams[$row['id']]['members'][] = ['name' => $row['member_name'], 'section' => $row['section']];
    }
}

// Delete Logic
if (isset($_GET['delete_team'])) {
    $tid = (int)$_GET['delete_team'];
    $conn->query("DELETE FROM uiux_members WHERE team_id = $tid");
    $conn->query("DELETE FROM uiux_teams WHERE id = $tid");
    header("Location: ui_ux_admin.php");
    exit;
}

// Save Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_team'])) {
    $name = $conn->real_escape_string($_POST['team_name']);
    $payment = $conn->real_escape_string($_POST['payment']);
    $tid = (int)($_POST['team_id'] ?? 0);

    if ($tid > 0) {
        $conn->query("UPDATE uiux_teams SET team_name='$name', payment='$payment' WHERE id=$tid");
    } else {
        $conn->query("INSERT INTO uiux_teams (team_name, payment) VALUES ('$name', '$payment')");
        $tid = $conn->insert_id;
    }

    $conn->query("DELETE FROM uiux_members WHERE team_id=$tid");
    if (isset($_POST['members'])) {
        foreach ($_POST['members'] as $m) {
            if (!empty($m['name'])) {
                $mname = $conn->real_escape_string($m['name']);
                $msec = $conn->real_escape_string($m['section']);
                $conn->query("INSERT INTO uiux_members (team_id, member_name, section) VALUES ($tid, '$mname', '$msec')");
            }
        }
    }
    header("Location: ui_ux_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UI-UX Admin | NIST</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem; border-bottom:5px solid var(--black); padding-bottom:1rem;">
            <h1>UI-UX Dashboard</h1>
            <a href="export_pdf_admin.php?category=ui_ux" target="_blank" class="btn btn-primary">Export Data (PDF)</a>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
            <div class="card" style="border-left:5px solid var(--primary); text-align:center;">
                <div class="text-muted" style="font-size:0.75rem; font-weight:900; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:0.5rem;">Total Teams</div>
                <div style="font-size:2.5rem; font-weight:900; line-height:1;"><?php echo $stats['total_teams']; ?></div>
            </div>
            <div class="card" style="border-left:5px solid var(--secondary); text-align:center;">
                <div class="text-muted" style="font-size:0.75rem; font-weight:900; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:0.5rem;">Present Teams</div>
                <div style="font-size:2.5rem; font-weight:900; line-height:1;">
                    <span id="present-teams-count"><?php echo $conn->query("SELECT COUNT(*) FROM uiux_teams WHERE attendance=1")->fetch_row()[0]; ?></span>
                </div>
            </div>
            <div class="card" style="border-left:5px solid var(--info); text-align:center;">
                <div class="text-muted" style="font-size:0.75rem; font-weight:900; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:0.5rem;">Paid Teams</div>
                <div style="font-size:2.5rem; font-weight:900; line-height:1;">
                    <span id="paid-teams-count"><?php echo $conn->query("SELECT COUNT(*) FROM uiux_teams WHERE payment='paid'")->fetch_row()[0]; ?></span>
                </div>
            </div>
            <div class="card" style="border-left:5px solid var(--warning); text-align:center;">
                <div class="text-muted" style="font-size:0.75rem; font-weight:900; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:0.5rem;">Participants</div>
                <div style="font-size:2.5rem; font-weight:900; line-height:1;"><?php echo $stats['total_members']; ?></div>
            </div>
        </div>

        <div class="card">
            <h3 id="form-title">Team Editor</h3>
            <form action="" method="POST">
                <input type="hidden" name="team_id" id="team_id" value="0">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; margin-top:1.5rem;">
                    <div class="form-group">
                        <label class="form-label">Team Name</label>
                        <input type="text" name="team_name" id="team_name" class="form-control" placeholder="ENTER NAME" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Status</label>
                        <select name="payment" id="payment" class="form-control">
                            <option value="paid">PAID</option>
                            <option value="not_paid">NOT PAID</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top:1rem;">
                    <label class="form-label">Participants</label>
                    <div id="member-rows"></div>
                    <button type="button" onclick="addMemberRow()" class="btn" style="width:100%; border-style:dashed; margin-top:1rem;">+ Add Participant Row</button>
                </div>

                <div style="margin-top:2.5rem; display:flex; gap:15px;">
                    <button type="submit" name="save_team" class="btn btn-primary" style="flex:1;">Save Team Changes</button>
                    <button type="button" onclick="resetForm()" class="btn" style="flex:1;">Clear Form</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table-stackable">
                <thead>
                    <tr>
                        <th style="width:60px;">SN</th>
                        <th>Team Name</th>
                        <th>Payment Status</th>
                        <th>Member Details</th>
                        <th style="text-align:center; width:120px;">Present</th>
                        <th style="text-align:center; width:200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $teams_sql = "SELECT t.*, a_a.username as attendance_by 
                                 FROM uiux_teams t 
                                 LEFT JOIN admin a_a ON t.attendance_updated_by_id = a_a.id 
                                 ORDER BY t.id ASC";
                    $teams_res = $conn->query($teams_sql);
                    $sn = 1;
                    while ($t = $teams_res->fetch_assoc()): ?>
                        <tr>
                            <td data-label="SN"><?php echo $sn++; ?></td>
                            <td data-label="Team" style="font-weight:900;"><?php echo htmlspecialchars($t['team_name']); ?></td>
                            <td data-label="Payment">
                                <select class="status-dropdown"
                                    data-team-id="<?php echo $t['id']; ?>" data-field="payment"
                                    onchange="updateStatus(<?php echo $t['id']; ?>, 'payment', this.value)"
                                    style="font-weight:800; text-transform:uppercase; <?php echo (strcasecmp($t['payment'], 'paid') === 0) ? 'border-color:var(--secondary); color:var(--secondary);' : 'border-color:var(--danger); color:var(--danger);'; ?>">
                                    <option value="paid" <?php echo (strcasecmp($t['payment'], 'paid') === 0) ? 'selected' : ''; ?>>PAID</option>
                                    <option value="not_paid" <?php echo (strcasecmp($t['payment'], 'not_paid') === 0) ? 'selected' : ''; ?>>NOT PAID</option>
                                </select>
                            </td>
                            <td data-label="Members">
                                <?php
                                $m_sql = "SELECT member_name as name, section FROM uiux_members WHERE team_id = " . $t['id'];
                                $m_res = $conn->query($m_sql);
                                $members = [];
                                while ($m = $m_res->fetch_assoc()): ?>
                                    <div style="font-size:0.8rem; font-weight:700; margin-bottom:2px;">
                                        &raquo; <?php echo htmlspecialchars($m['name']); ?> [<?php echo htmlspecialchars($m['section']); ?>]
                                    </div>
                                    <?php $members[] = $m; ?>
                                <?php endwhile; ?>
                            </td>
                            <td data-label="Status" class="attendance-cell" data-team-id="<?php echo $t['id']; ?>" style="text-align:center;">
                                <input type="checkbox" class="attendance-check" data-id="<?php echo $t['id']; ?>"
                                    <?php echo $t['attendance'] ? 'checked' : ''; ?>
                                    style="transform:scale(1.5); cursor:pointer;">
                                <div class="attendance-info">
                                    <?php if (!empty($t['attendance_by'])): ?>
                                        <div style="font-size:0.7rem; color:#555; font-weight:600; margin-top:4px;">
                                            <?php echo $t['attendance'] ? 'Checked by' : 'Unchecked by'; ?> <?php echo htmlspecialchars($t['attendance_by']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Action" style="text-align:center;">
                                <div style="display:flex; gap:10px; justify-content:center;">
                                    <?php $mjson = htmlspecialchars(json_encode($members), ENT_QUOTES, 'UTF-8'); ?>
                                    <button onclick='editTeam(<?php echo $t['id']; ?>, "<?php echo addslashes($t['team_name']); ?>", "<?php echo $t['payment']; ?>", `<?php echo $mjson; ?>`)' class="btn" style="padding:6px 12px; font-size:0.75rem;">Edit</button>
                                    <a href="?delete_team=<?php echo $t['id']; ?>" class="btn btn-danger" style="padding:6px 12px; font-size:0.75rem;" onclick="return confirm('Confirm deletion?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let counter = 0;
        const currentUser = "<?php echo htmlspecialchars($_SESSION['admin_user']); ?>";
        const lastActionTimes = {};

        function addMemberRow(name = '', sec = '') {
            const div = document.createElement('div');
            div.style = "display:flex; gap:10px; margin-bottom:8px;";
            div.innerHTML = `
                <input type="text" name="members[${counter}][name]" value="${name}" placeholder="Name" class="form-control" required>
                <input type="text" name="members[${counter}][section]" value="${sec}" placeholder="Sec" class="form-control" style="width:100px;">
                <button type="button" onclick="this.parentElement.remove()" class="btn btn-danger">&times;</button>
            `;
            document.getElementById('member-rows').appendChild(div);
            counter++;
        }

        function editTeam(id, name, payment, members) {
            document.getElementById('team_id').value = id;
            document.getElementById('team_name').value = name;
            document.getElementById('payment').value = payment;
            document.getElementById('member-rows').innerHTML = '';
            document.getElementById('form-title').innerText = "Edit Team: " + name;
            JSON.parse(members).forEach(m => addMemberRow(m.name, m.section));
            window.scrollTo(0, 0);
        }

        function resetForm() {
            document.getElementById('team_id').value = 0;
            document.getElementById('team_name').value = '';
            document.getElementById('payment').value = 'paid';
            document.getElementById('member-rows').innerHTML = '';
            document.getElementById('form-title').innerText = "Add New Team";
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('attendance-check')) {
                const id = e.target.dataset.id;
                const status = e.target.checked ? 1 : 0;

                // Optimistic UI Update
                lastActionTimes[id] = Date.now();
                const cell = e.target.closest('.attendance-cell');
                const infoDiv = cell.querySelector('.attendance-info');
                const actionText = status ? 'Checked by' : 'Unchecked by';
                infoDiv.innerHTML = `<div style="font-size:0.7rem; color:#555; font-weight:600; margin-top:4px;">${actionText} ${currentUser}</div>`;

                // Update Stats
                const countEl = document.getElementById('present-teams-count');
                if (countEl) {
                    let current = parseInt(countEl.innerText);
                    countEl.innerText = status ? current + 1 : current - 1;
                }

                fetch('../api/attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `type=ui_ux&team_id=${id}&status=${status}`
                });
            }
        });

        function updateStatus(teamId, field, value) {
            // Stats logic
            if (field === 'payment') {
                const countEl = document.getElementById('paid-teams-count');
                if (countEl) {
                    let current = parseInt(countEl.innerText);
                    if (value === 'paid') countEl.innerText = current + 1;
                    else countEl.innerText = current - 1;
                }
            }

            const body = new URLSearchParams();
            body.append('type', 'ui_ux');
            body.append('team_id', teamId);
            body.append('field', field);
            body.append('value', value);

            fetch('../api/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    const dropdown = document.querySelector(`select[data-team-id="${teamId}"][data-field="${field}"]`);
                    if (dropdown) {
                        if (value === 'paid') {
                            dropdown.style.borderColor = 'var(--secondary)';
                            dropdown.style.color = 'var(--secondary)';
                        } else {
                            dropdown.style.borderColor = 'var(--danger)';
                            dropdown.style.color = 'var(--danger)';
                        }
                    }
                } else {
                    alert('Update failed: ' + data.message);
                }
            });
        }

        setInterval(() => {
            fetch('../api/attendance.php?type=ui_ux&t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Object.keys(data.attendance).forEach(id => {
                            const att = data.attendance[id];
                            const cell = document.querySelector(`.attendance-cell[data-team-id="${id}"]`);
                            if (cell) {
                                const ck = cell.querySelector('.attendance-check');
                                const infoDiv = cell.querySelector('.attendance-info');

                                const lastAct = lastActionTimes[id] || 0;
                                if (Date.now() - lastAct > 4000) {
                                    if (ck && !ck.matches(':focus')) ck.checked = att.status == 1;

                                    if (att.by) {
                                        const isChecked = (att.status == 1 || att.status === '1' || att.status === true);
                                        const actionText = isChecked ? 'Checked by' : 'Unchecked by';
                                        infoDiv.innerHTML = `<div style="font-size:0.7rem; color:#555; font-weight:600; margin-top:4px;">${actionText} ${att.by}</div>`;
                                    } else {
                                        infoDiv.innerHTML = '';
                                    }
                                }
                            }
                        });

                        // UPDATE STATS
                        const allAtt = Object.values(data.attendance);
                        const presentCount = allAtt.filter(a => a.status == 1).length;
                        const paidCount = allAtt.filter(a => a.payment && a.payment.toLowerCase() === 'paid').length;

                        const pEl = document.getElementById('present-teams-count');
                        if (pEl) pEl.innerText = presentCount;

                        const pdEl = document.getElementById('paid-teams-count');
                        if (pdEl) pdEl.innerText = paidCount;
                    }
                });
        }, 1000);
    </script>
</body>

</html>