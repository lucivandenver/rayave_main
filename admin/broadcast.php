<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

ensureUserSecurityColumns();
ensureEmailCampaignLogsTable();

$errors = [];
$successMessage = null;
$segmentsSelected = $_POST['segments'] ?? ['customers'];
$subject = trim($_POST['subject'] ?? 'Fresh blooms and new deals');
$headline = trim($_POST['headline'] ?? 'See what is blooming this week');
$messageBody = trim($_POST['message'] ?? "We just stocked new arrangements and exclusive bundles."
    . "\n\nTap the button below to see what is new and reserve stocks ahead of the rush.");
$ctaLabel = trim($_POST['cta_label'] ?? 'Shop the collection');
$ctaUrl = trim($_POST['cta_url'] ?? appUrl('products.php'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($subject === '') {
        $subject = '';
        $errors[] = 'Subject is required.';
    }

    if ($headline === '') {
        $errors[] = 'Headline is required.';
    }

    if ($messageBody === '') {
        $errors[] = 'Message body cannot be empty.';
    }

    if (empty($segmentsSelected) || !is_array($segmentsSelected)) {
        $errors[] = 'Select at least one audience segment.';
    }

    if ($ctaLabel !== '' && filter_var($ctaUrl, FILTER_VALIDATE_URL) === false) {
        $errors[] = 'CTA URL must be a valid link.';
    }

    if ($ctaLabel === '' && $ctaUrl !== '') {
        $errors[] = 'Provide a button label for your CTA link.';
    }

    if (empty($errors)) {
        $recipients = collectBroadcastRecipients($segmentsSelected);
        if (empty($recipients)) {
            $errors[] = 'No recipients matched the selected segments.';
        } else {
            $bodyHtml = '<p style="font-size:18px;font-weight:600;margin-top:0;">'
                . htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p style="margin:16px 0 0 0;">'
                . nl2br(htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8'))
                . '</p>';
            $ctaLabelSafe = $ctaLabel !== '' ? $ctaLabel : null;
            $ctaUrlSafe = $ctaLabelSafe ? $ctaUrl : null;
            $emailHtml = renderBrandEmail($subject, $bodyHtml, $ctaLabelSafe, $ctaUrlSafe);
            $emailText = $headline . "\n\n" . $messageBody;
            if ($ctaLabelSafe && $ctaUrlSafe) {
                $emailText .= "\n\n" . $ctaLabelSafe . ': ' . $ctaUrlSafe;
            }

            $sent = 0;
            $failed = 0;
            foreach ($recipients as $recipient) {
                $result = sendEmailMessage($recipient['email'], $recipient['name'], $subject, $emailHtml, $emailText);
                if ($result['success']) {
                    $sent++;
                } else {
                    $failed++;
                    error_log('Broadcast email failed for ' . $recipient['email'] . ': ' . ($result['message'] ?? 'unknown error'));
                }
            }

            $segmentsLabel = implode(',', $segmentsSelected);
            $logStmt = $pdo->prepare('INSERT INTO email_campaign_logs (admin_id, subject, segments, sent_count, fail_count) VALUES (?, ?, ?, ?, ?)');
            $logStmt->execute([$_SESSION['user_id'], $subject, $segmentsLabel, $sent, $failed]);

            $successMessage = "Campaign sent to {$sent} recipient" . ($sent === 1 ? '' : 's') . ($failed ? " ({$failed} failed)." : '.');
        }
    }
}

try {
    $logsStmt = $pdo->query('SELECT e.*, u.username FROM email_campaign_logs e LEFT JOIN users u ON e.admin_id = u.id ORDER BY e.created_at DESC LIMIT 6');
    $recentLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($e->getCode() === '42S02') {
        ensureEmailCampaignLogsTable();
        $logsStmt = $pdo->query('SELECT e.*, u.username FROM email_campaign_logs e LEFT JOIN users u ON e.admin_id = u.id ORDER BY e.created_at DESC LIMIT 6');
        $recentLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        throw $e;
    }
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Email Broadcasts</h2>
            <p class="text-muted mb-0">Send news, coupons, and product highlights straight to your customers' inboxes.</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Compose Announcement</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Subject</label>
                            <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($subject); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Headline</label>
                            <input type="text" name="headline" class="form-control" value="<?php echo htmlspecialchars($headline); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Message</label>
                            <textarea name="message" class="form-control" rows="8" required><?php echo htmlspecialchars($messageBody); ?></textarea>
                            <div class="form-text">Plain text is fine. We'll format it for email automatically.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">CTA Button Label</label>
                                <input type="text" name="cta_label" class="form-control" value="<?php echo htmlspecialchars($ctaLabel); ?>" placeholder="Optional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">CTA URL</label>
                                <input type="url" name="cta_url" class="form-control" value="<?php echo htmlspecialchars($ctaUrl); ?>" placeholder="https://">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="form-label fw-semibold">Audience</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="segmentCustomers" name="segments[]" value="customers" <?php echo in_array('customers', $segmentsSelected, true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="segmentCustomers">Registered customers (opted-in)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="segmentNewsletter" name="segments[]" value="newsletter" <?php echo in_array('newsletter', $segmentsSelected, true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="segmentNewsletter">Newsletter subscribers</label>
                            </div>
                        </div>
                        <div class="mt-4 d-flex justify-content-end gap-3">
                            <button type="reset" class="btn btn-outline-secondary">Clear</button>
                            <button type="submit" class="btn btn-success">Send campaign</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Recent Campaigns</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentLogs)): ?>
                        <div class="p-4 text-muted">No campaigns sent yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Audience</th>
                                        <th>Sent</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLogs as $log): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($log['subject']); ?></td>
                                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($log['segments']); ?></span></td>
                                            <td><?php echo (int) $log['sent_count']; ?><?php echo $log['fail_count'] ? ' / ' . (int) $log['fail_count'] . ' failed' : ''; ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php
function collectBroadcastRecipients(array $segments)
{
    global $pdo;

    $recipients = [];
    $normalizedSegments = array_map('strtolower', $segments);

    if (in_array('customers', $normalizedSegments, true)) {
        $stmt = $pdo->query("SELECT full_name, username, email FROM users WHERE role = 'customer' AND email <> '' AND email_opt_in = 1");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $email = strtolower($row['email']);
            $name = $row['full_name'] ?: $row['username'];
            $recipients[$email] = ['email' => $row['email'], 'name' => $name];
        }
    }

    if (in_array('newsletter', $normalizedSegments, true)) {
        try {
            $stmt = $pdo->query('SELECT email FROM newsletter_subscriptions WHERE is_active = 1');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $email = strtolower($row['email']);
                if (!isset($recipients[$email])) {
                    $name = ucfirst(strtok($row['email'], '@'));
                    $recipients[$email] = ['email' => $row['email'], 'name' => $name];
                }
            }
        } catch (PDOException $e) {
            error_log('Failed to fetch newsletter subscribers: ' . $e->getMessage());
        }
    }

    return array_values($recipients);
}
?>
