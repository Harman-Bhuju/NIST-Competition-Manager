<?php
require_once '../session_config.php'; // Persistent login
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? ''; // 'c_debug' or 'ui_ux'
    $team_id = (int)($_POST['team_id'] ?? 0);
    $status = (int)($_POST['status'] ?? 0);

    $user_id = $_SESSION['admin_id'];
    if ($type === 'c_debug') {
        $stmt = $conn->prepare("UPDATE c_debug_teams SET attendance = ?, attendance_updated_by_id = ? WHERE id = ?");
    } else if ($type === 'ui_ux') {
        $stmt = $conn->prepare("UPDATE uiux_teams SET attendance = ?, attendance_updated_by_id = ? WHERE id = ?");
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
        exit;
    }

    $stmt->bind_param("iii", $status, $user_id, $team_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Poll for changes
    $type = $_GET['type'] ?? 'c_debug';
    if ($type === 'c_debug') {
        $result = $conn->query("SELECT t.id, t.attendance, a.username as attendance_by 
                               FROM c_debug_teams t 
                               LEFT JOIN admin a ON t.attendance_updated_by_id = a.id");
    } else {
        $result = $conn->query("SELECT t.id, t.attendance, a.username as attendance_by 
                               FROM uiux_teams t 
                               LEFT JOIN admin a ON t.attendance_updated_by_id = a.id");
    }

    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        $attendance[$row['id']] = [
            'status' => (int)$row['attendance'],
            'by' => $row['attendance_by']
        ];
    }
    echo json_encode(['success' => true, 'attendance' => $attendance]);
}
