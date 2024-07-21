<?php
include '../includes/config.php';
include '../includes/db.php';

$bug_id = $_GET['bug_id'];
$submitter_ip = $_SERVER['REMOTE_ADDR'];

$db = getDbConnection();

$bug  = getBugInfo($db, $bug_id,$submitter_ip);

$comment = getLatestComment($db, $bug_id);

if ($bug && $comment && strtotime($comment['created_at']) > strtotime($bug['last_updated'])) {
    echo json_encode([
        'update' => true,
        'message' => "Bug status: {$bug['status']}. Latest comment: {$comment['comment']}"
    ]);
} elseif ($bug) {
    echo json_encode([
        'update' => true,
        'message' => "Bug status: {$bug['status']}"
    ]);
} else {
    echo json_encode(['update' => false]);
}