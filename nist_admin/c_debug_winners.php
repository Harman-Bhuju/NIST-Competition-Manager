<?php
require_once '../session_config.php'; // Persistent login
require_once '../db.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

// Fetch top 3 winners based on marks
// If marks are equal, we could use time or ID, but let's stick to marks for now
$winners_sql = "SELECT id, team_name, marks, easy_solved, intermediate_solved, hard_solved, end_time, start_time
                FROM c_debug_teams 
                ORDER BY marks DESC, (UNIX_TIMESTAMP(end_time) - UNIX_TIMESTAMP(start_time)) ASC 
                LIMIT 3";
$winners_result = $conn->query($winners_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>C-Debug Winners - NIST</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .winner-card {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 2px solid #eee;
            position: relative;
        }

        .winner-1 {
            border-color: #f1c40f;
            transform: scale(1.05);
            z-index: 2;
        }

        .winner-2 {
            border-color: #bdc3c7;
        }

        .winner-3 {
            border-color: #cd7f32;
        }

        .rank-badge {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            background: #2c3e50;
            color: white;
            padding: 5px 20px;
            border-radius: 20px;
            font-weight: bold;
        }

        .marks-large {
            font-size: 2.5rem;
            font-weight: 900;
            color: #3498db;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h2 style="text-align:center; margin-bottom:3rem;">C-Debug Hall of Fame (Top 3)</h2>

        <div style="display:flex; justify-content:center; align-items:flex-end; gap:30px; margin-bottom:4rem;">
            <?php
            $ranks = [1, 2, 3];
            $winners = [];
            while ($w = $winners_result->fetch_assoc()) $winners[] = $w;

            // Reordering for podium: 2, 1, 3
            $podium_order = [1, 0, 2]; // indices
            foreach ($podium_order as $idx):
                if (!isset($winners[$idx])) continue;
                $w = $winners[$idx];
                $rank = $idx + 1;
            ?>
                <div class="winner-card winner-<?php echo $rank; ?>" style="flex:1;">
                    <div class="rank-badge"><?php echo $rank; ?><?php echo $rank == 1 ? 'st' : ($rank == 2 ? 'nd' : 'rd'); ?> Place</div>
                    <h3 style="margin-top:10px;"><?php echo htmlspecialchars($w['team_name']); ?></h3>
                    <div class="marks-large"><?php echo $w['marks']; ?></div>
                    <p style="color:#7f8c8d; font-size:0.9rem;">Points Scored</p>
                    <div style="margin-bottom:20px; font-size:0.8rem; color:#95a5a6;">
                        Solved: E(<?php echo $w['easy_solved']; ?>) I(<?php echo $w['intermediate_solved']; ?>) H(<?php echo $w['hard_solved']; ?>)
                    </div>
                    <a href="export_pdf_admin.php?category=c_debug&winners_only=1" class="btn-pdf" style="font-size:0.8rem; padding:8px 15px;">Download Details</a>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center;">
            <a href="export_pdf_admin.php?category=c_debug&winners_only=1" class="btn-pdf" style="background:#e74c3c;">Download Top 3 Winners PDF</a>
        </div>
    </div>
</body>

</html>