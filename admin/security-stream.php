<?php
require_once __DIR__ . '/../includes/config.php';
ensureSecurityTables();

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

// Server-Sent Events stream for activity and flagged messages
set_time_limit(0);
while (ob_get_level()) ob_end_flush();
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$lastActivityId = isset($_GET['last_activity_id']) ? (int)$_GET['last_activity_id'] : 0;
$lastFlaggedId = isset($_GET['last_flagged_id']) ? (int)$_GET['last_flagged_id'] : 0;

$start = time();
$maxRun = 300; // seconds

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function sendEvent($event, $id, $data)
{
    echo "id: " . (int)$id . "\n";
    echo "event: {$event}\n";
    echo "data: " . $data . "\n\n";
    @ob_flush();
    @flush();
}

while (true) {
    try {
        // check for new activity logs
        $stmt = $pdo->prepare("SELECT al.*, u.username FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id WHERE al.id > ? ORDER BY al.id ASC");
        $stmt->execute([$lastActivityId]);
        $newActs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($newActs as $a) {
            $lastActivityId = max($lastActivityId, (int)$a['id']);
            $payload = json_encode($a, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            sendEvent('activity', $a['id'], $payload);
        }

        // check for new flagged messages
        $stmt = $pdo->prepare("SELECT fm.*, u.username FROM flagged_messages fm LEFT JOIN users u ON fm.user_id = u.id WHERE fm.id > ? ORDER BY fm.id ASC");
        $stmt->execute([$lastFlaggedId]);
        $newFlags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($newFlags as $f) {
            $lastFlaggedId = max($lastFlaggedId, (int)$f['id']);
            $payload = json_encode($f, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            sendEvent('flagged', $f['id'], $payload);
        }
    } catch (Exception $e) {
        // ignore transient DB errors and continue
    }

    if ((time() - $start) > $maxRun) {
        break;
    }

    // sleep briefly and continue
    sleep(2);
}

// close the stream politely
echo "event: end\n";
echo "data: {}\n\n";
@ob_flush();
@flush();

exit;
?>
