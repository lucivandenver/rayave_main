<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please login first to save your custom bouquet.']);
    exit;
}

ensureProductAvailabilityColumn();
ensureCustomizeProductColumns();
ensureCustomBouquetDesignsTable();

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request payload.']);
    exit;
}

$itemsInput = $payload['items'] ?? [];
$previewDataUrl = trim((string) ($payload['preview_data_url'] ?? ''));
$canvasJsonRaw = $payload['canvas_json'] ?? null;
$designNotes = trim((string) ($payload['notes'] ?? ''));
if (mb_strlen($designNotes) > 600) {
    $designNotes = mb_substr($designNotes, 0, 600);
}

if (!is_array($itemsInput) || empty($itemsInput)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please add items to your bouquet canvas first.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$customizeCategoryId = getCustomizeCategoryId();
$allowOutOfStockOrders = isOutOfStockOrderingEnabled();

// Aggregate quantities per product_id and track represented steps
$aggregatedItems = [];
$stepsPresent = [];

foreach ($itemsInput as $entry) {
    if (!is_array($entry)) {
        continue;
    }
    $productId = isset($entry['product_id']) ? (int) $entry['product_id'] : 0;
    $quantity = isset($entry['quantity']) ? (int) $entry['quantity'] : 1;
    $stepKey = isset($entry['step']) ? strtolower(trim((string) $entry['step'])) : '';

    if ($productId <= 0 || $quantity <= 0) {
        continue;
    }

    if (!isset($aggregatedItems[$productId])) {
        $aggregatedItems[$productId] = [
            'product_id' => $productId,
            'quantity' => 0,
            'step' => $stepKey
        ];
    }
    $aggregatedItems[$productId]['quantity'] += $quantity;
    if ($stepKey !== '') {
        $stepsPresent[$stepKey] = true;
    }
}

if (empty($aggregatedItems)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No valid bouquet items were provided.']);
    exit;
}

$requiredSteps = ['container' => 'Container', 'flowers' => 'Flowers', 'fillers' => 'Fillers'];
$missingLabels = [];
foreach ($requiredSteps as $reqKey => $reqLabel) {
    if (empty($stepsPresent[$reqKey])) {
        $missingLabels[] = $reqLabel;
    }
}

if (!empty($missingLabels)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Please add at least one item from: ' . implode(', ', $missingLabels) . '.'
    ]);
    exit;
}

// Validate products & stock from database
$validatedItems = [];
$totalPrice = 0.0;

foreach ($aggregatedItems as $productId => $entry) {
    $product = getProduct($productId);
    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'One of the selected items could not be found.']);
        exit;
    }

    if ($customizeCategoryId && (int) ($product['category_id'] ?? 0) !== (int) $customizeCategoryId) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Invalid item included in custom bouquet.']);
        exit;
    }

    if (($product['availability_status'] ?? 'available') !== 'available') {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => $product['name'] . ' is currently unavailable.']);
        exit;
    }

    $qty = (int) $entry['quantity'];
    $stockQty = (int) ($product['stock_quantity'] ?? 0);
    if (!$allowOutOfStockOrders && ($stockQty <= 0 || $stockQty < $qty)) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'error' => 'Not enough stock for ' . $product['name'] . ' (available: ' . max(0, $stockQty) . ').'
        ]);
        exit;
    }

    $unitPrice = (float) ($product['price'] ?? 0);
    $totalPrice += $unitPrice * $qty;

    $validatedItems[] = [
        'product_id' => (int) $product['id'],
        'name' => (string) $product['name'],
        'step' => $entry['step'],
        'quantity' => $qty,
        'price' => $unitPrice,
        'image' => (string) ($product['image'] ?? '')
    ];
}

// Validate & save PNG snapshot
if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=\r\n]+)$/', $previewDataUrl, $matches)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid bouquet canvas snapshot format.']);
    exit;
}

$binaryPng = base64_decode($matches[1], true);
if ($binaryPng === false || strlen($binaryPng) < 100 || strlen($binaryPng) > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bouquet snapshot image is empty or exceeds the 5MB limit.']);
    exit;
}

// Check PNG signature
if (substr($binaryPng, 0, 8) !== "\x89PNG\r\n\x1a\n") {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Uploaded snapshot is not a valid PNG image.']);
    exit;
}

$designsDir = __DIR__ . '/storage/custom_designs';
if (!is_dir($designsDir)) {
    @mkdir($designsDir, 0775, true);
}

$fileName = 'design_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.png';
$targetPath = $designsDir . '/' . $fileName;
$relativePath = 'storage/custom_designs/' . $fileName;

if (@file_put_contents($targetPath, $binaryPng) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save bouquet design image on the server.']);
    exit;
}

$canvasJsonEncoded = is_string($canvasJsonRaw)
    ? $canvasJsonRaw
    : json_encode($canvasJsonRaw, JSON_UNESCAPED_UNICODE);

try {
    $pdo->beginTransaction();

    // Remove existing customize-category items from cart so the cart bundle matches this canvas design 1:1
    if ($customizeCategoryId) {
        $clearStmt = $pdo->prepare("DELETE c FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? AND p.category_id = ?");
        $clearStmt->execute([$userId, $customizeCategoryId]);
    }

    // Mark any prior in-cart custom bouquet design as discarded
    $discardStmt = $pdo->prepare("UPDATE custom_bouquet_designs SET status = 'discarded' WHERE user_id = ? AND status = 'in_cart' AND order_id IS NULL");
    $discardStmt->execute([$userId]);

    // Add validated bouquet components to cart
    $insertCartStmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    foreach ($validatedItems as $item) {
        $insertCartStmt->execute([$userId, $item['product_id'], $item['quantity']]);
    }

    // Save design record
    $designStmt = $pdo->prepare(
        "INSERT INTO custom_bouquet_designs (user_id, order_id, status, preview_image, canvas_json, items_json, design_notes, total_price)
         VALUES (?, NULL, 'in_cart', ?, ?, ?, ?, ?)"
    );
    $designStmt->execute([
        $userId,
        $relativePath,
        $canvasJsonEncoded,
        json_encode($validatedItems, JSON_UNESCAPED_UNICODE),
        $designNotes !== '' ? $designNotes : null,
        $totalPrice
    ]);
    $designId = (int) $pdo->lastInsertId();

    $pdo->commit();

    // Compute updated cart badge count
    $countStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $cartCount = (int) $countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'design_id' => $designId,
        'preview_image' => $relativePath,
        'total_price' => round($totalPrice, 2),
        'cart_count' => $cartCount,
        'message' => 'Your custom bouquet design has been added to your cart!'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (file_exists($targetPath)) {
        @unlink($targetPath);
    }
    error_log('Save custom bouquet failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to save custom bouquet right now. Please try again.']);
}
