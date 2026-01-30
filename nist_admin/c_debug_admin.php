<?php
// Use shared persistent session config
require_once '../session_config.php';
require_once '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Stats
$stats = $conn->query("SELECT 
    (SELECT COUNT(*) FROM c_debug_teams) as total_teams,
    (SELECT COUNT(*) FROM c_debug_members) as total_members")->fetch_assoc();

// Fetch Data
$sql = "SELECT t.id, t.team_name, t.laptop, t.attendance, m.team_member, m.section 
        FROM c_debug_teams t
        LEFT JOIN c_debug_members m ON t.id = m.team_id
        ORDER BY t.id ASC";
$result = $conn->query($sql);
$teams = [];
while ($row = $result->fetch_assoc()) {
    $teams[$row['id']]['team_name'] = $row['team_name'];
    $teams[$row['id']]['laptop'] = $row['laptop'];
    $teams[$row['id']]['attendance'] = $row['attendance'];
    if ($row['team_member']) {
        $teams[$row['id']]['members'][] = ['name' => $row['team_member'], 'section' => $row['section']];
    }
}

// Delete Logic
if (isset($_GET['delete_team'])) {
    $tid = (int)$_GET['delete_team'];
    $conn->query("DELETE FROM c_debug_members WHERE team_id = $tid");
    $conn->query("DELETE FROM c_debug_teams WHERE id = $tid");
    header("Location: c_debug_admin.php");
    exit;
}

// Save Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_team'])) {
    $name = $conn->real_escape_string($_POST['team_name']);
    $laptop = $conn->real_escape_string($_POST['laptop']);
    $tid = (int)($_POST['team_id'] ?? 0);

    if ($tid > 0) {
        $conn->query("UPDATE c_debug_teams SET team_name='$name', laptop='$laptop' WHERE id=$tid");
    } else {
        $conn->query("INSERT INTO c_debug_teams (team_name, laptop) VALUES ('$name', '$laptop')");
        $tid = $conn->insert_id;
    }

    $conn->query("DELETE FROM c_debug_members WHERE team_id=$tid");
    if (isset($_POST['members'])) {
        foreach ($_POST['members'] as $m) {
            if (!empty($m['name'])) {
                $mname = $conn->real_escape_string($m['name']);
                $msec = $conn->real_escape_string($m['section']);
                $conn->query("INSERT INTO c_debug_members (team_id, team_member, section) VALUES ($tid, '$mname', '$msec')");
            }
        }
    }
    header("Location: c_debug_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-Debug Admin | NIST</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem; border-bottom:4px solid var(--border); padding-bottom:1rem;">
            <h1>C-Debug Dashboard</h1>
            <div style="display:flex; gap:10px;">
                <a href="export_pdf_admin.php?category=c_debug&winners_only=1" target="_blank" class="btn btn-success">Download Top 3 Winners</a>
                <a href="export_pdf_admin.php?category=c_debug" target="_blank" class="btn btn-primary">Export All Data (PDF)</a>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
            <div class="card" style="margin-bottom:0; text-align:center; padding:1.5rem;">
                <div class="text-muted" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">Total Teams</div>
                <div style="font-size:2.5rem; font-weight:800; color:var(--primary);"><?php echo $stats['total_teams']; ?></div>
            </div>
            <div class="card" style="margin-bottom:0; text-align:center; padding:1.5rem;">
                <div class="text-muted" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">Total Participants</div>
                <div style="font-size:2.5rem; font-weight:800; color:var(--secondary);"><?php echo $stats['total_members']; ?></div>
            </div>
            <div class="card" style="margin-bottom:0; text-align:center; padding:1.5rem; background:var(--gray-50);">
                <div class="text-muted" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">Global Timer</div>
                <div id="global-timer-display" style="font-size:2.5rem; font-weight:800; font-family:monospace;">20:00</div>
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
                        <label class="form-label">Laptop Info</label>
                        <select name="laptop" id="laptop" class="form-control">
                            <option value="YES">YES</option>
                            <option value="NO" selected>NO</option>
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

        <div class="card" style="border-left:5px solid var(--primary);">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Competition Controls</h3>
                <div style="display:flex; gap:10px;">
                    <div style="display:flex; gap:10px;">
                        <button onclick="timerAction('start_global')" class="btn btn-primary" id="start-btn">Start Competition</button>
                        <button onclick="timerAction('stop_global')" class="btn btn-danger">Stop All</button>
                        <button onclick="timerAction('reset_global')" class="btn btn-secondary" onclick="return confirm('Reset all timers?')">Reset Global</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-stackable">
                <thead>
                    <tr>
                        <th style="width:60px;">SN</th>
                        <th>Team Name</th>
                        <th>Laptop Info</th>
                        <th style="text-align:center;">Breakdown (E/I/H)</th>
                        <th style="text-align:center; width:80px;">Total</th>
                        <th>Timer Status</th>
                        <th style="text-align:center; width:120px;">Present</th>
                        <th style="text-align:center; width:200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $teams_sql = "SELECT t.*, a_s.username as stopped_by, a_a.username as attendance_by, a_sc.username as scored_by 
                                 FROM c_debug_teams t 
                                 LEFT JOIN admin a_s ON t.stopped_by_id = a_s.id 
                                 LEFT JOIN admin a_a ON t.attendance_updated_by_id = a_a.id 
                                 LEFT JOIN admin a_sc ON t.scored_by_id = a_sc.id
                                 ORDER BY t.id ASC";
                    $teams_res = $conn->query($teams_sql);
                    $sn = 1;
                    while ($t = $teams_res->fetch_assoc()): ?>
                        <tr>
                            <td data-label="SN"><?php echo $sn++; ?></td>
                            <td data-label="Team" style="font-weight:900;"><?php echo htmlspecialchars($t['team_name']); ?></td>
                            <td data-label="Laptop">
                                <select class="status-dropdown"
                                    data-team-id="<?php echo $t['id']; ?>" data-field="laptop"
                                    onchange="updateStatus(<?php echo $t['id']; ?>, 'laptop', this.value)"
                                    style="font-weight:700; <?php echo (strcasecmp($t['laptop'], 'YES') === 0) ? 'border-color:var(--secondary); color:var(--secondary);' : 'border-color:var(--danger); color:var(--danger);'; ?>">
                                    <option value="YES" <?php echo (strcasecmp($t['laptop'], 'YES') === 0) ? 'selected' : ''; ?>>YES</option>
                                    <option value="NO" <?php echo (strcasecmp($t['laptop'], 'NO') === 0) ? 'selected' : ''; ?>>NO</option>
                                </select>
                            </td>
                            <td data-label="Breakdown" style="text-align:center; font-size:0.85rem;" class="team-breakdown" data-team-id="<?php echo $t['id']; ?>">
                                <span class="badge" style="background:var(--gray-100); color:var(--black); border:1px solid var(--border);">E: <span class="val-e"><?php echo $t['easy_solved']; ?></span></span>
                                <span class="badge" style="background:var(--gray-100); color:var(--black); border:1px solid var(--border);">I: <span class="val-i"><?php echo $t['intermediate_solved']; ?></span></span>
                                <span class="badge" style="background:var(--gray-100); color:var(--black); border:1px solid var(--border);">H: <span class="val-h"><?php echo $t['hard_solved']; ?></span></span>
                            </td>
                            <td data-label="Total" style="text-align:center;" class="team-marks" data-team-id="<?php echo $t['id']; ?>">
                                <div style="font-weight:900; font-size:1.2rem; color:var(--primary);"><?php echo $t['marks']; ?></div>
                                <?php if ($t['scored_by']): ?>
                                    <div class="scored-by" style="font-size:0.65rem; color:var(--text-muted); margin-top:2px;">
                                        by <?php echo htmlspecialchars($t['scored_by']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Timer" class="timer-cell" data-team-id="<?php echo $t['id']; ?>">
                                <?php if ($t['timer_status'] === 'stopped'):
                                    $start = strtotime($t['start_time']);
                                    $end = strtotime($t['end_time']);
                                    $elapsed = $end - $start;
                                    $m = floor($elapsed / 60);
                                    $s = $elapsed % 60;
                                    $time_str = sprintf("%d:%02d", $m, $s);
                                ?>
                                    <div class="badge" style="background:var(--gray-100); color:var(--text-main); border:1px solid var(--border); font-size:0.75rem; line-height:1.4;">
                                        STOPPED BY: <?php echo htmlspecialchars($t['stopped_by'] ?: 'SYSTEM'); ?><br>
                                        TIME: <?php echo $time_str; ?>
                                    </div>
                                    <button onclick="timerAction('reset_team', <?php echo $t['id']; ?>)" class="btn btn-secondary" style="padding:4px 8px; font-size:0.7rem; margin-top:5px;">RESET</button>
                                <?php elseif ($t['timer_status'] === 'running'): ?>
                                    <div class="badge" style="background:var(--gray-50); color:var(--secondary); margin-bottom:5px;">
                                        RUNNING
                                    </div>
                                    <button onclick="timerAction('stop_team', <?php echo $t['id']; ?>)" class="btn btn-danger" style="padding:4px 8px; font-size:0.7rem; display:block; margin:0 auto;">STOP TEAM</button>
                                <?php else: ?>
                                    <div class="badge" style="background:var(--gray-50); color:var(--secondary);">
                                        <?php echo strtoupper($t['timer_status']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status" class="attendance-cell" data-team-id="<?php echo $t['id']; ?>" style="text-align:center;">
                                <input type="checkbox" class="attendance-check" data-id="<?php echo $t['id']; ?>"
                                    <?php echo $t['attendance'] ? 'checked' : ''; ?>
                                    style="transform:scale(1.5); cursor:pointer;">
                                <div class="attendance-info">
                                    <?php if ($t['attendance'] && $t['attendance_by']): ?>
                                        <div style="font-size:0.65rem; color:var(--text-muted); margin-top:4px;">BY: <?php echo htmlspecialchars($t['attendance_by']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Action" style="text-align:center;">
                                <div style="display:flex; gap:10px; justify-content:center;">
                                    <?php
                                    $m_sql = "SELECT team_member as name, section FROM c_debug_members WHERE team_id = " . $t['id'];
                                    $m_res = $conn->query($m_sql);
                                    $members = [];
                                    while ($m = $m_res->fetch_assoc()) $members[] = $m;
                                    $mjson = htmlspecialchars(json_encode($members), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <button onclick='editTeam(<?php echo $t['id']; ?>, "<?php echo addslashes($t['team_name']); ?>", "<?php echo addslashes($t['laptop']); ?>", `<?php echo $mjson; ?>`)' class="btn" style="padding:6px 12px; font-size:0.75rem;">Edit</button>
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

        function updatePermission(enabled) {
            fetch('../api/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=update_permission&category=c_debug&value=${enabled ? 1 : 0}`
            });
        }

        function timerAction(action, teamId = null) {
            const body = new URLSearchParams();
            body.append('action', action);
            if (teamId) body.append('team_id', teamId);

            fetch('../api/timer.php', {
                method: 'POST',
                body: body
            }).then(r => r.json()).then(data => {
                if (data.success) location.reload();
                else alert(data.message);
            });
        }

        let serverClientOffset = 0;
        let offsetKnown = false;

        function updateGlobalTimer() {
            fetch('../api/timer.php?action=status')
                .then(r => r.json())
                .then(data => {
                    const display = document.getElementById('global-timer-display');
                    const startBtn = document.getElementById('start-btn');

                    if (!offsetKnown && data.server_time) {
                        // Standardize date format for Safari/Firefox
                        const serverTime = data.server_time.replace(/-/g, "/");
                        serverClientOffset = new Date(serverTime).getTime() - new Date().getTime();
                        offsetKnown = true;
                    }

                    if (data.status === 'running') {
                        const start = new Date(data.start_time.replace(/-/g, "/")).getTime();
                        const now = new Date().getTime() + serverClientOffset;
                        let elapsed = Math.max(0, now - start);

                        // Cap at 20 minutes (1200000 ms)
                        if (elapsed >= 1200000) {
                            elapsed = 1200000;
                            if (data.status !== 'finished') setTimeout(() => location.reload(), 2000);
                        }

                        const m = Math.floor(elapsed / 60000);
                        const s = Math.floor((elapsed % 60000) / 1000);
                        display.innerText = `${m}:${s < 10 ? '0' : ''}${s}`;
                        display.style.color = 'var(--secondary)';
                        startBtn.innerText = "Competition Running";
                        startBtn.disabled = true;
                    } else if (data.status === 'finished') {
                        const start = new Date(data.start_time.replace(/-/g, "/")).getTime();
                        const end = new Date(data.end_time.replace(/-/g, "/")).getTime();
                        const elapsed = Math.max(0, end - start);

                        const m = Math.floor(elapsed / 60000);
                        const s = Math.floor((elapsed % 60000) / 1000);
                        display.innerText = `${m}:${s < 10 ? '0' : ''}${s}`;
                        display.style.color = 'var(--danger)';
                        startBtn.innerText = "Competition Finished";
                        startBtn.disabled = true;
                    } else {
                        display.innerText = "0:00";
                        display.style.color = 'var(--text-main)';
                        startBtn.innerText = "Start Competition";
                        startBtn.disabled = false;
                    }

                    // Real-time Row Updates
                    if (data.teams) {
                        data.teams.forEach(team => {
                            const cell = document.querySelector(`.timer-cell[data-team-id="${team.id}"]`);
                            if (!cell) return;

                            if (team.timer_status === 'stopped' && team.end_time) {
                                const start = new Date(data.start_time.replace(/-/g, "/")).getTime();
                                const end = new Date(team.end_time.replace(/-/g, "/")).getTime();
                                const elapsed = Math.max(0, Math.floor((end - start) / 1000));
                                const m = Math.floor(elapsed / 60);
                                const s = elapsed % 60;
                                const timeStr = `${m}:${s < 10 ? '0' : ''}${s}`;

                                cell.innerHTML = `
                                    <div class="badge" style="background:var(--gray-100); color:var(--text-main); border:1px solid var(--border); font-size:0.75rem; line-height:1.4;">
                                        STOPPED BY: ${team.stopped_by || 'SYSTEM'}<br>
                                        TIME: ${timeStr}
                                    </div>
                                    <button onclick="timerAction('reset_team', ${team.id})" class="btn btn-secondary" style="padding:4px 8px; font-size:0.7rem; margin-top:5px;">RESET</button>
                                `;
                            } else if (team.timer_status === 'running') {
                                cell.innerHTML = `
                                    <div class="badge" style="background:var(--gray-50); color:var(--secondary); margin-bottom:5px;">RUNNING</div>
                                    <button onclick="timerAction('stop_team', ${team.id})" class="btn btn-danger" style="padding:4px 8px; font-size:0.7rem; display:block; margin:0 auto;">STOP TEAM</button>
                                `;
                            } else {
                                cell.innerHTML = `<div class="badge" style="background:var(--gray-50); color:var(--secondary);">${team.timer_status.toUpperCase()}</div>`;
                            }

                            // Update attendance
                            const attCell = document.querySelector(`.attendance-cell[data-team-id="${team.id}"]`);
                            if (attCell) {
                                const ck = attCell.querySelector('.attendance-check');
                                const infoDiv = attCell.querySelector('.attendance-info');

                                if (ck && !ck.matches(':focus')) {
                                    ck.checked = team.attendance == 1;
                                }

                                if (team.attendance == 1 && team.attendance_by) {
                                    infoDiv.innerHTML = `<div style="font-size:0.65rem; color:var(--text-muted); margin-top:4px;">BY: ${team.attendance_by}</div>`;
                                } else {
                                    infoDiv.innerHTML = '';
                                }
                            }

                            const marksEl = document.querySelector(`.team-marks[data-team-id="${team.id}"]`);
                            if (marksEl && team.marks !== undefined) {
                                let html = `<div style="font-weight:900; font-size:1.2rem; color:var(--primary);">${team.marks}</div>`;
                                if (team.scored_by) {
                                    html += `<div class="scored-by" style="font-size:0.65rem; color:var(--text-muted); margin-top:2px;">by ${team.scored_by}</div>`;
                                }
                                marksEl.innerHTML = html;
                            }

                            const breakdownEl = document.querySelector(`.team-breakdown[data-team-id="${team.id}"]`);
                            if (breakdownEl) {
                                if (team.easy_solved !== undefined) breakdownEl.querySelector('.val-e').innerText = team.easy_solved;
                                if (team.intermediate_solved !== undefined) breakdownEl.querySelector('.val-i').innerText = team.intermediate_solved;
                                if (team.hard_solved !== undefined) breakdownEl.querySelector('.val-h').innerText = team.hard_solved;
                            }
                        });
                    }
                });
        }
        setInterval(updateGlobalTimer, 1000);
        updateGlobalTimer();

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

        function editTeam(id, name, laptop, members) {
            document.getElementById('team_id').value = id;
            document.getElementById('team_name').value = name;
            document.getElementById('laptop').value = laptop;
            document.getElementById('member-rows').innerHTML = '';
            document.getElementById('form-title').innerText = "Edit Team: " + name;
            JSON.parse(members).forEach(m => addMemberRow(m.name, m.section));
            window.scrollTo(0, 0);
        }

        function resetForm() {
            document.getElementById('team_id').value = 0;
            document.getElementById('team_name').value = '';
            document.getElementById('laptop').value = '';
            document.getElementById('member-rows').innerHTML = '';
            document.getElementById('form-title').innerText = "Add New Team";
        }

        function updateStatus(teamId, field, value) {
            const body = new URLSearchParams();
            body.append('type', 'c_debug');
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
                        if (value === 'YES') {
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

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('attendance-check')) {
                const id = e.target.dataset.id;
                const status = e.target.checked ? 1 : 0;
                fetch('../api/attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `type=c_debug&team_id=${id}&status=${status}`
                }).then(r => r.json()).then(data => {
                    if (data.success) updateGlobalTimer();
                });
            }
        });
    </script>
</body>

</html>