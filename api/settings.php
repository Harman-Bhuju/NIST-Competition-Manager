<?php
require_once '../session_config.php'; // Persistent login
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_permission') {
        $category = $_POST['category'] ?? 'c_debug';
        $value = (int)$_POST['value'];

        $stmt = $conn->prepare("UPDATE competition_settings SET volunteer_can_mark = ? WHERE category = ?");
        $stmt->bind_param("is", $value, $category);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        exit;
    }

    if ($action === 'update_volunteer_permission') {
        $v_id = (int)$_POST['volunteer_id'];
        $value = (int)$_POST['value'];

        $stmt = $conn->prepare("UPDATE admin SET can_enter_marks = ? WHERE id = ?");
        $stmt->bind_param("ii", $value, $v_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
