<?php
include '../includes/config.php';
include '../includes/db.php';
include '../includes/auth.php';

if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}


$data = json_decode(file_get_contents('php://input'), true);

$db = getDbConnection();
$result = updateBug($db, $data);

if ($data['comment']) {
    createComments($db, $data);
}

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

