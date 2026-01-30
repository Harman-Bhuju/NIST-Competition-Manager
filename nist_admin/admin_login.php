<?php
// Use shared persistent session config
require_once '../session_config.php';
require_once '../db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_user'] = $username;

            $stmt_role = $conn->prepare("SELECT role FROM admin WHERE id = ?");
            $stmt_role->bind_param("i", $row['id']);
            $stmt_role->execute();
            $role_result = $stmt_role->get_result()->fetch_assoc();
            $_SESSION['admin_role'] = $role_result['role'];

            if ($_SESSION['admin_role'] === 'admin') {
                header("Location: c_debug_admin.php");
            } else {
                header("Location: ../index.php");
            }
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | NIST</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
        }

        .login-head {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-head h1 {
            color: #fff;
            font-size: 2.25rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #818cf8, #c084fc);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body>
    <div class="login-card card">
        <div class="login-head">
            <h1>NIST EVENT</h1>
            <p style="color:rgba(255,255,255,0.6); font-size:0.875rem;">Portal Authentication</p>
        </div>

        <?php if ($error): ?>
            <div style="background:rgba(239, 68, 68, 0.1); border:1px solid var(--danger); color:#ef4444; padding:12px; border-radius:8px; text-align:center; margin-bottom:1.5rem; font-size:0.875rem; font-weight:600;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="admin_user">
            </div>
            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; padding:1rem;">Sign In</button>
        </form>

        <div style="text-align:center; margin-top:2rem;">
            <a href="../index.php" style="color:var(--primary-solid); text-decoration:none; font-size:0.875rem; font-weight:700;">&larr; Return to Portal</a>
        </div>
    </div>
</body>

</html>