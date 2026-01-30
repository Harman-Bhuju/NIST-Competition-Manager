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
    $field = $_POST['field'] ?? ''; // 'payment' or 'laptop'
    $value = $_POST['value'] ?? '';

    // Validate type and field
    if ($type === 'c_debug' && $field === 'laptop') {
        $table = 'c_debug_teams';
    } else if ($type === 'ui_ux' && $field === 'payment') {
        $table = 'uiux_teams';
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid update parameters']);
        exit;
    }

    // Use prepared statement to update
    $stmt = $conn->prepare("UPDATE $table SET $field = ? WHERE id = ?");
    $stmt->bind_param("si", $value, $team_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
}
