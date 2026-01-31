<?php
require_once '../session_config.php'; // Persistent login
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$user_id = $_SESSION['admin_id'];
$user_role = $_SESSION['admin_role'];

if ($action === 'status') {
    $category = $_REQUEST['category'] ?? 'c_debug';
    // Get global status and team statuses
    $settings = $conn->query("SELECT * FROM competition_settings WHERE category = 'c_debug'")->fetch_assoc();

    if ($category === 'c_debug') {
        $teams_sql = "SELECT t.id, t.team_name, t.timer_status, t.end_time, t.attendance, t.laptop, 
                             t.marks, t.easy_solved, t.intermediate_solved, t.hard_solved,
                             a.username as stopped_by, a2.username as attendance_by, a3.username as scored_by
                      FROM c_debug_teams t
                      LEFT JOIN admin a ON t.stopped_by_id = a.id
                      LEFT JOIN admin a2 ON t.attendance_updated_by_id = a2.id
                      LEFT JOIN admin a3 ON t.scored_by_id = a3.id
                      ORDER BY t.id ASC";
    } else {
        $teams_sql = "SELECT t.id, t.team_name, t.attendance, t.marks,
                             a2.username as attendance_by
                      FROM uiux_teams t
                      LEFT JOIN admin a2 ON t.attendance_updated_by_id = a2.id
                      ORDER BY t.id ASC";
    }
    $teams_result = $conn->query($teams_sql);
    $teams = [];
    while ($row = $teams_result->fetch_assoc()) {
        $teams[] = $row;
    }

    $server_time = date('Y-m-d H:i:s');

    // Auto-stop logic (20 minutes = 1200 seconds)
    if ($settings['status'] === 'running' && $settings['start_time']) {
        $start_ts = strtotime($settings['start_time']);
        $now_ts = strtotime($server_time);
        $elapsed = $now_ts - $start_ts;

        if ($elapsed >= 1200) {
            $end_time = date('Y-m-d H:i:s', $start_ts + 1200);
            $conn->query("UPDATE competition_settings SET status = 'finished', end_time = '$end_time' WHERE category = 'c_debug'");
            $conn->query("UPDATE c_debug_teams SET timer_status = 'stopped', end_time = '$end_time' WHERE timer_status = 'running'");

            // Re-fetch settings after update
            $settings = $conn->query("SELECT * FROM competition_settings WHERE category = 'c_debug'")->fetch_assoc();
        }
    }

    echo json_encode([
        'success' => true,
        'status' => $settings['status'],
        'start_time' => $settings['start_time'],
        'end_time' => $settings['end_time'],
        'server_time' => $server_time,
        'duration' => $settings['duration_minutes'],
        'teams' => $teams
    ]);
    exit;
}

if ($action === 'start_global') {
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only']);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    $conn->query("UPDATE competition_settings SET status = 'running', start_time = '$now', end_time = NULL WHERE category = 'c_debug'");
    $conn->query("UPDATE c_debug_teams SET timer_status = 'running', start_time = '$now', end_time = NULL, stopped_by_id = NULL");

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'stop_global') {
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only']);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    $conn->query("UPDATE competition_settings SET status = 'finished', end_time = '$now' WHERE category = 'c_debug'");
    $conn->query("UPDATE c_debug_teams SET timer_status = 'stopped', end_time = '$now', stopped_by_id = $user_id WHERE timer_status = 'running'");

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'reset_global') {
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only']);
        exit;
    }

    $conn->query("UPDATE competition_settings SET status = 'not_started', start_time = NULL, end_time = NULL WHERE category = 'c_debug'");
    $conn->query("UPDATE c_debug_teams SET timer_status = 'not_started', start_time = NULL, end_time = NULL, stopped_by_id = NULL");

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'stop_team') {
    $team_id = (int)$_POST['team_id'];

    // Check if volunteer is assigned to this team or is admin
    $can_stop = false;
    if ($user_role === 'admin') {
        $can_stop = true;
    } else {
        $check = $conn->prepare("SELECT id FROM volunteer_assignments WHERE volunteer_id = ? AND team_id = ? AND status = 'accepted'");
        $check->bind_param("ii", $user_id, $team_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $can_stop = true;
        }
    }

    if (!$can_stop) {
        echo json_encode(['success' => false, 'message' => 'Not authorized for this team']);
        exit;
    }

    // New check: Global competition must be running
    $settings = $conn->query("SELECT status FROM competition_settings WHERE category = 'c_debug'")->fetch_assoc();
    if ($settings['status'] !== 'running') {
        echo json_encode(['success' => false, 'message' => 'Competition is not active']);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    // Ensure we track WHO stopped it
    $stmt = $conn->prepare("UPDATE c_debug_teams SET timer_status = 'stopped', end_time = ?, stopped_by_id = ? WHERE id = ?");
    $stmt->bind_param("sii", $now, $user_id, $team_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

// Reseting a specific team's timer (Admin only or Volunteer if permitted)
if ($action === 'reset_team') {
    $team_id = (int)$_POST['team_id'];

    // Check if team is currently stopped
    $check = $conn->query("SELECT timer_status FROM c_debug_teams WHERE id = $team_id")->fetch_assoc();
    if ($check['timer_status'] !== 'stopped') {
        echo json_encode(['success' => false, 'message' => 'Team timer must be stopped before resetting']);
        exit;
    }

    // Reset to global start time
    $settings = $conn->query("SELECT start_time FROM competition_settings WHERE category = 'c_debug'")->fetch_assoc();
    $global_start = $settings['start_time'];

    if (!$global_start) {
        echo json_encode(['success' => false, 'message' => 'Global timer has not started']);
        exit;
    }

    $conn->query("UPDATE c_debug_teams SET timer_status = 'running', end_time = NULL, stopped_by_id = NULL WHERE id = $team_id");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'volunteer_requests') {
    // For admin polling of volunteer requests
    if ($user_role !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only']);
        exit;
    }

    $sql = "SELECT a.id, v.username as volunteer_name, t.team_name, a.status 
            FROM volunteer_assignments a
            JOIN admin v ON a.volunteer_id = v.id
            JOIN c_debug_teams t ON a.team_id = t.id
            ORDER BY a.status ASC, v.username ASC";
    $result = $conn->query($sql);
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    echo json_encode(['success' => true, 'requests' => $requests]);
    exit;
}

if ($action === 'team_assignment_status') {
    // Returns team assignment status for real-time updates in volunteer portal
    $volunteer_id = (int)($_REQUEST['volunteer_id'] ?? 0);

    // Get all teams
    $teams = [];
    $res = $conn->query("SELECT id, team_name FROM c_debug_teams ORDER BY team_name ASC");
    while ($t = $res->fetch_assoc()) {
        $teams[$t['id']] = ['id' => $t['id'], 'team_name' => $t['team_name'], 'status' => 'available', 'taken_by' => null];
    }

    // Get all accepted assignments
    $res2 = $conn->query("SELECT a.team_id, a.volunteer_id, v.username as volunteer_name 
                          FROM volunteer_assignments a 
                          JOIN admin v ON a.volunteer_id = v.id 
                          WHERE a.status = 'accepted'");
    while ($row = $res2->fetch_assoc()) {
        if (isset($teams[$row['team_id']])) {
            if ($volunteer_id > 0 && (int)$row['volunteer_id'] === $volunteer_id) {
                $teams[$row['team_id']]['status'] = 'my_accepted';
            } else {
                $teams[$row['team_id']]['status'] = 'taken';
                $teams[$row['team_id']]['taken_by'] = $row['volunteer_name'];
            }
        }
    }

    // Get pending assignments for this volunteer
    if ($volunteer_id > 0) {
        $res3 = $conn->query("SELECT team_id FROM volunteer_assignments WHERE volunteer_id = $volunteer_id AND status = 'pending'");
        while ($row = $res3->fetch_assoc()) {
            if (isset($teams[$row['team_id']]) && $teams[$row['team_id']]['status'] === 'available') {
                $teams[$row['team_id']]['status'] = 'my_pending';
            }
        }
    }

    echo json_encode(['success' => true, 'teams' => array_values($teams)]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
