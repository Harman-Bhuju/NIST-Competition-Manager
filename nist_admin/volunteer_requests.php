<?php
require_once '../session_config.php'; // Persistent login
require_once '../db.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// Handle approval/rejection
if (isset($_GET['action']) && isset($_GET['req_id'])) {
    $req_id = (int)$_GET['req_id'];
    $action = $_GET['action'];

    if ($action === 'accept') {
        $conn->query("UPDATE volunteer_assignments SET status = 'accepted' WHERE id = $req_id");
    } else if ($action === 'delete') {
        $conn->query("DELETE FROM volunteer_assignments WHERE id = $req_id");
    }
    header("Location: volunteer_requests.php" . (isset($_GET['id']) ? "?id=" . $_GET['id'] : ""));
    exit;
}

$volunteer_filter = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT a.id, v.username as volunteer_name, t.team_name, a.status 
        FROM volunteer_assignments a
        JOIN admin v ON a.volunteer_id = v.id
        JOIN c_debug_teams t ON a.team_id = t.id";
if ($volunteer_filter > 0) {
    $sql .= " WHERE a.volunteer_id = $volunteer_filter";
}
$sql .= " ORDER BY a.status ASC, v.username ASC";
$requests = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Requests | NIST</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem; border-bottom:4px solid var(--border); padding-bottom:1rem;">
            <h1>Team Assignments</h1>
        </div>
        <p style="color:var(--text-muted); font-size:0.8rem; font-weight:800; text-transform:uppercase; margin-bottom:2rem; letter-spacing:0.05em;">Review volunteer responsibilities</p>

        <div class="card">
            <div class="table-responsive">
                <table class="table-stackable">
                    <thead>
                        <tr>
                            <th style="width:60px;">SN</th>
                            <th>Volunteer</th>
                            <th>Team Name</th>
                            <th style="text-align:center; width:120px;">Status</th>
                            <th style="text-align:center; width:200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($requests->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center">NO PENDING ASSIGNMENTS FOUND</td>
                            </tr>
                        <?php else: ?>
                            <?php $sn = 1;
                            while ($r = $requests->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="SN"><?php echo $sn++; ?></td>
                                    <td data-label="Volunteer" style="font-weight:900; text-transform:uppercase;"><?php echo htmlspecialchars($r['volunteer_name']); ?></td>
                                    <td data-label="Team" style="font-weight:700;"><?php echo htmlspecialchars($r['team_name']); ?></td>
                                    <td data-label="Status" style="text-align:center;">
                                        <div class="badge" style="background:<?php echo $r['status'] === 'accepted' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)'; ?>;
                                            color:<?php echo $r['status'] === 'accepted' ? 'var(--secondary)' : 'var(--warning)'; ?>;
                                            border:1px solid <?php echo $r['status'] === 'accepted' ? 'var(--secondary)' : 'var(--warning)'; ?>;">
                                            <?php echo strtoupper($r['status']); ?>
                                        </div>
                                    </td>
                                    <td data-label="Actions" style="text-align:center;">
                                        <div style="display:flex; gap:8px; justify-content:center;">
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <a href="?action=accept&req_id=<?php echo $r['id']; ?><?php echo $volunteer_filter ? "&id=$volunteer_filter" : ""; ?>" class="btn btn-primary" style="padding:6px 12px; font-size:0.7rem;">Accept</a>
                                            <?php endif; ?>
                                            <a href="?action=delete&req_id=<?php echo $r['id']; ?><?php echo $volunteer_filter ? "&id=$volunteer_filter" : ""; ?>" onclick="return confirm('Remove this assignment?')" class="btn btn-danger" style="padding:6px 12px; font-size:0.7rem;">Dismiss</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
    function refreshRequests() {
        fetch('../api/timer.php?action=volunteer_requests')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.requests) {
                    const tbody = document.querySelector('.table-stackable tbody');
                    if (data.requests.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">NO PENDING ASSIGNMENTS FOUND</td></tr>';
                        return;
                    }
                    let html = '';
                    let sn = 1;
                    data.requests.forEach(r => {
                        const statusColor = r.status === 'accepted' ? 'var(--secondary)' : 'var(--warning)';
                        const statusBg = r.status === 'accepted' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)';
                        html += `<tr>
                            <td data-label="SN">${sn++}</td>
                            <td data-label="Volunteer" style="font-weight:900; text-transform:uppercase;">${r.volunteer_name}</td>
                            <td data-label="Team" style="font-weight:700;">${r.team_name}</td>
                            <td data-label="Status" style="text-align:center;">
                                <div class="badge" style="background:${statusBg}; color:${statusColor}; border:1px solid ${statusColor};">${r.status.toUpperCase()}</div>
                            </td>
                            <td data-label="Actions" style="text-align:center;">
                                <div style="display:flex; gap:8px; justify-content:center;">
                                    ${r.status === 'pending' ? `<a href="?action=accept&req_id=${r.id}" class="btn btn-primary" style="padding:6px 12px; font-size:0.7rem;">Accept</a>` : ''}
                                    <a href="?action=delete&req_id=${r.id}" onclick="return confirm('Remove this assignment?')" class="btn btn-danger" style="padding:6px 12px; font-size:0.7rem;">Dismiss</a>
                                </div>
                            </td>
                        </tr>`;
                    });
                    tbody.innerHTML = html;
                }
            });
    }
    setInterval(refreshRequests, 3000);
</script>
</body>

</html>