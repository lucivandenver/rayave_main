<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    if ($email) {
        if (subscribeToNewsletter($email)) {
            echo json_encode(['success' => true, 'message' => 'Successfully subscribed to newsletter!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Already subscribed or error occurred.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>