<?php
require_once '../session_config.php'; // Persistent login
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../nist_admin/admin_login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NIST Judging Portal</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .judging-header {
            background: #9b59b6;
            color: white;
            padding: 3rem 0;
            text-align: center;
            margin-bottom: 2rem;
        }

        .judging-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .judge-card {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .btn-preview {
            display: inline-block;
            background: #8e44ad;
            color: white;
            padding: 12px 25px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <!-- Using the same admin navbar structure -->
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="navbar-brand">NIST VOLUNTEER</a>
            <ul class="navbar-menu">
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="index.php" class="active">Judging Portal</a></li>
                <li><a href="../nist_admin/logout.php" style="color:var(--danger) !important;">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="judging-header">
        <div class="container">
            <h1>Judging Scorecard Portal</h1>
            <p>Select a category to preview official scorecards for judges</p>
        </div>
    </div>

    <div class="container">
        <div class="judging-grid">
            <div class="judge-card">
                <h3 style="color: var(--primary); margin-bottom: 1rem;">C-Debug Judging</h3>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 1.5rem;">Official scorecard with sections for easy, intermediate, and hard questions, time required, and marks.</p>
                <a href="c_debug_judging_pdf.php" target="_blank" class="btn btn-primary" style="width:100%; margin-bottom: 0.8rem;">Preview C-Debug Scorecard</a>
                <a href="../export_teams_pdf.php?category=c_debug" target="_blank" class="btn" style="width:100%; background:var(--gray-200); color:var(--black); border:none;">Download Team List (Names Only)</a>
            </div>

            <div class="judge-card">
                <h3 style="color: var(--primary); margin-bottom: 1rem;">UI-UX Judging</h3>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 1.5rem;">Official scorecard with criteria for Innovation, Usability, Aesthetics, Responsiveness, and Presentation.</p>
                <a href="ui_ux_judging_pdf.php" target="_blank" class="btn btn-primary" style="width:100%; margin-bottom: 0.8rem;">Preview UI-UX Scorecard</a>
                <a href="../export_teams_pdf.php?category=ui_ux" target="_blank" class="btn" style="width:100%; background:var(--gray-200); color:var(--black); border:none;">Download Team List (Names Only)</a>
            </div>
        </div>
    </div>
</body>

</html>