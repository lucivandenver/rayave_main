<?php
require_once __DIR__ . '/../includes/config.php';
ensureSecurityTables();

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');

$lastActivityId = isset($_GET['last_activity_id']) ? (int) $_GET['last_activity_id'] : 0;
$lastFlaggedId = isset($_GET['last_flagged_id']) ? (int) $_GET['last_flagged_id'] : 0;

$activityStmt = $pdo->prepare("SELECT al.*, u.username FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id WHERE al.id > ? ORDER BY al.id ASC LIMIT 25");
$activityStmt->execute([$lastActivityId]);
$activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

$flaggedStmt = $pdo->prepare("SELECT fm.*, u.username FROM flagged_messages fm LEFT JOIN users u ON fm.user_id = u.id WHERE fm.id > ? ORDER BY fm.id ASC LIMIT 25");
$flaggedStmt->execute([$lastFlaggedId]);
$flagged = $flaggedStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'activities' => $activities,
    'flagged' => $flagged,
]);
