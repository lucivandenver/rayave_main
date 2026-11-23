<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'messages' => ['Invalid request method.']]);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'messages' => ['Please log in to request a shipping quote.']]);
    exit;
}

$addressId = isset($_POST['address_id']) ? (int) $_POST['address_id'] : 0;
$fulfillmentMethod = $_POST['fulfillment_method'] ?? 'delivery';

if ($fulfillmentMethod !== 'delivery') {
    echo json_encode([
        'success' => true,
        'fee' => 0.00,
        'messages' => ['Store pickup selected. No shipping fee applied.'],
        'address_id' => null,
        'distance_text' => null,
        'duration_text' => null
    ]);
    exit;
}

if ($addressId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'messages' => ['Select a delivery address to calculate shipping.']]);
    exit;
}

$address = getUserAddressById($addressId, $_SESSION['user_id']);
if (!$address) {
    http_response_code(404);
    echo json_encode(['success' => false, 'messages' => ['Address not found. Please refresh and try again.']]);
    exit;
}

$quote = calculateShippingQuote($address, 'delivery');
$quote['address_id'] = $addressId;

if (!$quote['success']) {
    http_response_code(400);
}

echo json_encode($quote);
