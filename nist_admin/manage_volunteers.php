<?php
require_once '../session_config.php'; // Persistent login
require_once '../db.php';

// Access control: only admin can access this page
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Check role
$stmt = $conn->prepare("SELECT role FROM admin WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$user_role = $stmt->get_result()->fetch_assoc()['role'];
if ($user_role !== 'admin') {
    die("Access denied. Admins only.");
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_volunteer'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';
            $stmt = $conn->prepare("INSERT INTO admin (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);
            if ($stmt->execute()) {
                $message = "Volunteer registered successfully!";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

// Fetch volunteers
$volunteers = $conn->query("SELECT id, username, can_enter_marks FROM admin WHERE role = 'user' ORDER BY username ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Volunteers | NIST</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem; border-bottom:4px solid var(--border); padding-bottom:1rem;">
            <h1>Volunteer Management</h1>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap:3rem;">
            <!-- Registration Form -->
            <div class="card">
                <h3>Create Account</h3>
                <p style="color:var(--text-muted); font-size:0.75rem; font-weight:800; text-transform:uppercase; margin-bottom:1.5rem; letter-spacing:0.05em;">New volunteer registration portal</p>

                <?php if ($message): ?>
                    <div style="background:rgba(16, 185, 129, 0.1); color:var(--secondary); border:1px solid var(--secondary); padding:12px; margin-bottom:1.5rem; font-size:0.8rem; font-weight:700; border-radius:8px; text-align:center;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div style="background:rgba(239, 68, 68, 0.1); color:var(--danger); border:1px solid var(--danger); padding:12px; margin-bottom:1.5rem; font-size:0.8rem; font-weight:700; border-radius:8px; text-align:center;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required placeholder="USERNAME">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <div class="form-group" style="margin-bottom:2.5rem;">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
                    </div>
                    <button type="submit" name="register_volunteer" class="btn btn-primary" style="width:100%; padding:1.25rem;">Register Now</button>
                </form>
            </div>

            <!-- List Section -->
            <div class="card">
                <h3>Registered List</h3>
                <div class="table-responsive" style="margin-top:1.5rem;">
                    <table class="table-stackable">
                        <thead>
                            <tr>
                                <th style="width:60px;">SN</th>
                                <th>Username</th>
                                <th style="text-align:center;">Marks Entry</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sn = 1;
                            while ($v = $volunteers->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="SN"><?php echo $sn++; ?></td>
                                    <td data-label="User" style="font-weight:900; text-transform:uppercase;"><?php echo htmlspecialchars($v['username']); ?></td>
                                    <td data-label="Marks" style="text-align:center;">
                                        <input type="checkbox" onchange="toggleMarks(<?php echo $v['id']; ?>, this.checked)"
                                            <?php echo $v['can_enter_marks'] ? 'checked' : ''; ?>
                                            style="transform:scale(1.3); cursor:pointer;">
                                    </td>
                                    <td data-label="Action" style="text-align:center;">
                                        <a href="volunteer_requests.php?id=<?php echo $v['id']; ?>" class="btn btn-primary" style="padding:8px 16px; font-size:0.75rem;">View Requests</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleMarks(id, enabled) {
            fetch('../api/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=update_volunteer_permission&volunteer_id=${id}&value=${enabled ? 1 : 0}`
            });
        }
    </script>
</body>

</html>