<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../vendor/autoload.php';

$data = json_decode(file_get_contents('php://input'), true);

$db = getDbConnection();

$result = createBug($db, $data);

if ($result) {
    $bug_id = $db->lastInsertRowID();
    
    // Trigger Pusher event for real-time update
    $pusher = new Pusher\Pusher(PUSHER_KEY, PUSHER_SECRET, PUSHER_APP_ID, ['cluster' => PUSHER_CLUSTER]);
    
    $pusher->trigger('bug-channel', 'new-bug', [
        'id' => $bug_id,
        'title' => $data['title'],
        'urgency' => $data['urgency'],
        'status' => 'New'
    ]);
    
    echo json_encode(['success' => true, 'bug_id' => $bug_id]);
} else {
    echo json_encode(['success' => false]);
}