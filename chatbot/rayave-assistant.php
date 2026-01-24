<?php
require_once '../includes/config.php';

if (!defined('RAYAVE_CHATBOT_LOG')) {
    define('RAYAVE_CHATBOT_LOG', dirname(__DIR__) . '/storage/logs/chatbot.log');
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondError(405, 'Method not allowed');
}

$payload = json_decode(file_get_contents('php://input'), true);
$message = trim($payload['message'] ?? '');

if ($message === '') {
    respondError(400, 'Message is required');
}

if (shouldSendMapLink($message)) {
    $mapReply = buildMapReply();
    logChatbotEvent('Map link intent served', [
        'prompt' => mb_substr($message, 0, 160),
        'reply_preview' => mb_substr($mapReply, 0, 160)
    ]);
    respondSuccess([
        'reply' => $mapReply
    ]);
}

if (empty(GROQ_API_KEY)) {
    respondError(500, 'Groq API key is missing on the server');
}

try {
    $context = buildRayaveContextSnapshot($pdo);
} catch (Throwable $e) {
    error_log('Chatbot context error: ' . $e->getMessage());
    logChatbotEvent('Context generation failed', [
        'error' => $e->getMessage()
    ]);
    $context = 'No database context was available. Focus on general Raymundo Avenue assistance.';
}
logChatbotEvent('Context snapshot generated', [
    'length' => strlen($context),
    'preview' => mb_substr($context, 0, 200)
]);

$systemPrompt = <<<PROMPT
You are RayAve Assistant, a warm but concise concierge for Raymundo Avenue Flower Shop in Roxas.
Use ONLY the business context and inventory data provided below. If the user asks for something that is missing,
politely say you will forward the request to a florist instead of inventing details.

Business context:
$context

Guidelines:
- Use Peso currency formatting (₱) for prices.
- Offer to connect the user with a florist for complex customizations.
- Suggest specific products/categories from the context when relevant.
- Reference the flower & category dictionary to match the exact blooms or categories the user mentions.
- When a user asks for a specific category (e.g., Color Themes, Best Sellers), list at least two matching bouquets with names and prices before offering human handoff.
- When the user states an occasion (birthday, sympathy, wedding, graduation, surprise, romantic), quote bouquets from the Occasion cheat sheet so the reply feels tailored.
- Use the Priority category listings section to enumerate every available bouquet (up to six) that matches the requested category so users see the full in-stock set.
- When highlighting Best Sellers, enumerate every bouquet from the Best Sellers line-up (name + price + short descriptor) before suggesting other actions.
- Only mention social media links if the user specifically asks for them; otherwise focus on answering with in-stock bouquets or services.
- Remind users they can browse the site category pages (Products → choose category) if they want to see the full gallery after suggesting items.
- Keep responses under 140 words unless more detail is required.
- If customer asks about contact details, hours, or social media, reply with the exact info from the context and include full URLs for links.
- When sharing social profiles, format them as Markdown links so they are easy to tap.
PROMPT;

$requestBody = [
    'model' => GROQ_MODEL,
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $message]
    ],
    'temperature' => 0.35,
    'max_tokens' => 600
];

$ch = curl_init(rtrim(GROQ_API_BASE, '/') . '/chat/completions');

$caBundle = resolveCaBundlePath();
if ($caBundle) {
    curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
    CURLOPT_POSTFIELDS => json_encode($requestBody),
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);

if ($response === false) {
    $curlError = curl_error($ch);
    curl_close($ch);
    respondError(500, 'Unable to reach Groq. Please try again shortly.', [
        'curl_error' => $curlError
    ]);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode >= 400 || !isset($data['choices'][0]['message']['content'])) {
    respondError(500, $data['error']['message'] ?? 'Groq was unable to generate a reply.', [
        'http_code' => $httpCode,
        'response' => $response
    ]);
}

$reply = trim($data['choices'][0]['message']['content']);
logChatbotEvent('Groq reply delivered', [
    'prompt' => mb_substr($message, 0, 160),
    'reply_preview' => mb_substr($reply, 0, 160)
]);

respondSuccess([
    'reply' => $reply
]);

function buildRayaveContextSnapshot(PDO $pdo): string
{
    $sections = [];

    $sections[] = buildBusinessProfile();
    $sections[] = buildProductShowcase($pdo);
    $sections[] = buildProductInsights($pdo);
    $sections[] = buildBestSellerShowcase($pdo);
    $sections[] = buildCategoryGlossary($pdo);
    $sections[] = buildCategoryFocusSections($pdo);
    $sections[] = buildOccasionCheatSheet($pdo);

    $categories = getCategories();
    if ($categories) {
        $sections[] = 'Categories available: ' . implode(', ', array_map(fn($cat) => sanitizeContextText($cat['name']), array_slice($categories, 0, 10)));
    }

    $sections[] = buildCouponSnapshot($pdo);
    $sections[] = buildFeedbackSnapshot($pdo);

    return implode("\n\n", array_filter($sections));
}

function buildBusinessProfile(): string
{
    $address = "Bagumbayan, Roxas, Oriental Mindoro, 5212";
    $plusCode = "HGV9+36 Roxas, Oriental Mindoro";
    $mapUrl = "https://maps.app.goo.gl/B22Q3eNuzost4Evo8";
    $phone = "+63 974-7385-789";
    $email = "raymundoavenue@gmail.com";
    $hours = [
        'Mon-Fri' => '9AM-7PM',
        'Sat' => '10AM-6PM',
        'Sun' => '11AM-5PM'
    ];
    $social = [
        'Facebook' => 'https://www.facebook.com/share/1Ga1qx2prf/',
        'Instagram' => 'https://www.instagram.com/raymundo.avenue?igsh=NmpjbGJwaTVvcGF6'
    ];

    $lines = [];
    $lines[] = 'Address: ' . $address;
    $lines[] = 'Plus code: ' . $plusCode;
    $lines[] = 'Map pin: ' . $mapUrl;
    $lines[] = 'Phone: ' . $phone;
    $lines[] = 'Email: ' . $email;
    $lines[] = 'Hours: Mon-Fri ' . $hours['Mon-Fri'] . ', Sat ' . $hours['Sat'] . ', Sun ' . $hours['Sun'];
    foreach ($social as $label => $url) {
        $lines[] = $label . ' URL: ' . $url;
    }

    return "Business info:\n- " . implode("\n- ", $lines);
}

function buildProductShowcase(PDO $pdo): string
{
    try {
        $stmt = $pdo->query("SELECT p.name, p.price, p.stock_quantity, p.is_featured, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.is_featured DESC, p.updated_at DESC, p.created_at DESC LIMIT 6");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Product showcase query failed: ' . $e->getMessage());
        return '';
    }

    if (!$products) {
        return '';
    }

    $lines = [];
    foreach ($products as $product) {
        $price = isset($product['price']) ? formatCurrencyPHP($product['price']) : 'Price upon request';
        $stock = isset($product['stock_quantity'])
            ? ((int) $product['stock_quantity'] > 8 ? 'Plenty of stock' : 'Limited stock')
            : 'Stock unspecified';
        $label = ((int)($product['is_featured'] ?? 0) === 1) ? 'Featured' : 'Popular';
        $lines[] = sprintf('%s (%s, %s) - %s. %s.',
            sanitizeContextText($product['name'] ?? 'Unnamed Bouquet'),
            sanitizeContextText($product['category_name'] ?? 'General'),
            $label,
            $price,
            $stock
        );
    }

    return "Bouquet highlights:\n- " . implode("\n- ", $lines);
}

function buildBestSellerShowcase(PDO $pdo): string
{
    try {
        $stmt = $pdo->prepare("SELECT p.name, p.price, p.description FROM products p JOIN categories c ON c.id = p.category_id WHERE c.name = ? ORDER BY p.is_featured DESC, p.updated_at DESC, p.created_at DESC LIMIT 10");
        $stmt->execute(['Best Sellers']);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Best seller showcase query failed: ' . $e->getMessage());
        return '';
    }

    if (!$products) {
        return '';
    }

    $lines = [];
    foreach ($products as $product) {
        $name = sanitizeContextText($product['name'] ?? 'Bouquet');
        $price = isset($product['price']) ? formatCurrencyPHP($product['price']) : 'Price upon request';
        $desc = sanitizeContextText($product['description'] ?? '');
        $entry = $name . ' - ' . $price;
        if ($desc !== '') {
            $entry .= ' — ' . $desc;
        }
        $lines[] = $entry;
    }

    return "Best Sellers line-up:\n- " . implode("\n- ", $lines);
}

function buildProductInsights(PDO $pdo): string
{
    $sections = [];

    try {
        $rangeStmt = $pdo->query("SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM products WHERE price IS NOT NULL AND price > 0");
        $range = $rangeStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if (!empty($range['min_price']) && !empty($range['max_price'])) {
            $sections[] = sprintf('Price range: %s - %s', formatCurrencyPHP($range['min_price']), formatCurrencyPHP($range['max_price']));
        }
    } catch (Throwable $e) {
        error_log('Price range query failed: ' . $e->getMessage());
    }

    try {
        $cheapStmt = $pdo->query("SELECT p.name, p.price, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.price IS NOT NULL AND p.price > 0 ORDER BY p.price ASC LIMIT 4");
        $cheapest = $cheapStmt->fetchAll(PDO::FETCH_ASSOC);
        if ($cheapest) {
            $lines = [];
            foreach ($cheapest as $item) {
                $lines[] = sprintf('%s (%s) - %s',
                    sanitizeContextText($item['name']),
                    sanitizeContextText($item['category_name'] ?? 'General'),
                    formatCurrencyPHP($item['price'])
                );
            }
            $sections[] = 'Budget-friendly picks: ' . implode('; ', $lines);
        }
    } catch (Throwable $e) {
        error_log('Cheapest query failed: ' . $e->getMessage());
    }

    try {
        $stmt = $pdo->query("SELECT c.name AS category_name, p.name, p.price FROM products p JOIN categories c ON c.id = p.category_id WHERE p.price IS NOT NULL AND p.price > 0 ORDER BY c.name, p.price ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $categoryCheapest = [];
        foreach ($rows as $row) {
            $category = $row['category_name'] ?? 'General';
            if (!isset($categoryCheapest[$category])) {
                $categoryCheapest[$category] = sprintf('%s - %s',
                    sanitizeContextText($row['name']),
                    formatCurrencyPHP($row['price'])
                );
            }
            if (count($categoryCheapest) >= 6) {
                break;
            }
        }

        if ($categoryCheapest) {
            $parts = [];
            foreach ($categoryCheapest as $cat => $details) {
                $parts[] = sprintf('%s: %s', sanitizeContextText($cat), $details);
            }
            $sections[] = 'Cheapest per category: ' . implode('; ', $parts);
        }
    } catch (Throwable $e) {
        error_log('Category insights failed: ' . $e->getMessage());
    }

    return implode("\n", array_filter($sections));
}

function buildCategoryGlossary(PDO $pdo): string
{
    $maxCategories = 20;
    $maxItemsPerCategory = 5;

    try {
        $stmt = $pdo->query("SELECT c.name AS category_name, p.name, p.description, p.price\n            FROM categories c\n            LEFT JOIN products p ON p.category_id = c.id\n            ORDER BY c.name ASC, p.is_featured DESC, p.updated_at DESC, p.created_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Category glossary query failed: ' . $e->getMessage());
        return '';
    }

    if (!$rows) {
        return '';
    }

    $catalog = [];
    foreach ($rows as $row) {
        $category = sanitizeContextText($row['category_name'] ?? 'General');
        if ($category === '') {
            $category = 'General';
        }

        if (!isset($catalog[$category])) {
            if (count($catalog) >= $maxCategories) {
                continue;
            }
            $catalog[$category] = [];
        }

        if (count($catalog[$category]) >= $maxItemsPerCategory) {
            continue;
        }

        $productName = sanitizeContextText($row['name'] ?? 'Bouquet');
        $description = sanitizeContextText($row['description'] ?? '');
        $price = isset($row['price']) ? formatCurrencyPHP($row['price']) : 'Price upon request';
        $entry = $productName . ' (' . $price . ')';
        if ($description !== '') {
            $entry .= ' — ' . $description;
        }

        $catalog[$category][] = $entry;
    }

    if (!$catalog) {
        return '';
    }

    $lines = [];
    foreach ($catalog as $category => $items) {
        $lines[] = $category . ': ' . implode('; ', $items);
    }

    return "Flower & category dictionary:\n- " . implode("\n- ", $lines);
}

function buildCategoryFocusSections(PDO $pdo): string
{
    $focusCategories = [
        'Best Sellers',
        'Flower Types',
        'Color Themes',
        'Arrangements',
        'Occasions',
        'Customize'
    ];

    if (empty($focusCategories)) {
        return '';
    }

    $placeholders = implode(',', array_fill(0, count($focusCategories), '?'));

    try {
        $stmt = $pdo->prepare(
            "SELECT c.name AS category_name, p.name, p.price, p.description\n             FROM categories c\n             JOIN products p ON p.category_id = c.id\n             WHERE c.name IN ($placeholders)\n             ORDER BY FIELD(c.name, $placeholders), p.is_featured DESC, p.updated_at DESC, p.created_at DESC"
        );
        $stmt->execute(array_merge($focusCategories, $focusCategories));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Category focus query failed: ' . $e->getMessage());
        return '';
    }

    if (!$rows) {
        return '';
    }

    $catalog = [];
    $limitPerCategory = 6;

    foreach ($rows as $row) {
        $category = sanitizeContextText($row['category_name'] ?? 'Category');
        if ($category === '') {
            continue;
        }

        if (!isset($catalog[$category])) {
            $catalog[$category] = [];
        }

        if (count($catalog[$category]) >= $limitPerCategory) {
            continue;
        }

        $name = sanitizeContextText($row['name'] ?? 'Bouquet');
        $price = isset($row['price']) ? formatCurrencyPHP($row['price']) : 'Price upon request';
        $description = sanitizeContextText($row['description'] ?? '');

        $entry = $name . ' (' . $price . ')';
        if ($description !== '') {
            $entry .= ' — ' . $description;
        }

        $catalog[$category][] = $entry;
    }

    $lines = [];
    foreach ($focusCategories as $category) {
        if (empty($catalog[$category])) {
            continue;
        }
        $lines[] = $category . ': ' . implode('; ', $catalog[$category]);
    }

    if (!$lines) {
        return '';
    }

    return "Priority category listings:\n- " . implode("\n- ", $lines);
}

function buildOccasionCheatSheet(PDO $pdo): string
{
    $keywordMap = [
        'Birthday surprises' => '/birthday|bday|cake|party|celebration/i',
        'Sympathy & memorial' => '/sympathy|memorial|condolence|wake|funeral|serenity|comfort/i',
        'Romantic & Valentine' => '/romantic|valentine|love|anniversary|heart/i',
        'Wedding & bridal' => '/bridal|wedding|bride|groom|entourage/i',
        'Graduation & milestones' => '/graduation|milestone|success|achievement|toga|medal/i',
        'Surprise deliveries' => '/surprise|secret/i'
    ];
    $limitPerLabel = 3;

    try {
        $stmt = $pdo->query("SELECT name, description, price FROM products ORDER BY is_featured DESC, updated_at DESC, created_at DESC LIMIT 80");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Occasion cheat sheet query failed: ' . $e->getMessage());
        return '';
    }

    if (!$products) {
        return '';
    }

    $buckets = array_fill_keys(array_keys($keywordMap), []);

    foreach ($products as $product) {
        $haystack = mb_strtolower(trim(($product['name'] ?? '') . ' ' . ($product['description'] ?? '')), 'UTF-8');
        if ($haystack === '') {
            continue;
        }

        foreach ($keywordMap as $label => $pattern) {
            if (preg_match($pattern, $haystack)) {
                if (count($buckets[$label]) >= $limitPerLabel) {
                    continue;
                }

                $entry = sanitizeContextText($product['name'] ?? 'Bouquet');
                $price = isset($product['price']) ? formatCurrencyPHP($product['price']) : 'Price upon request';
                $desc = sanitizeContextText($product['description'] ?? '');
                $snippet = $entry . ' (' . $price . ')';
                if ($desc !== '') {
                    $snippet .= ' — ' . $desc;
                }
                $buckets[$label][] = $snippet;
            }
        }
    }

    $lines = [];
    foreach ($buckets as $label => $items) {
        if (!$items) {
            continue;
        }
        $lines[] = $label . ': ' . implode('; ', $items);
    }

    if (!$lines) {
        return '';
    }

    return "Occasion cheat sheet:\n- " . implode("\n- ", $lines);
}

function buildCouponSnapshot(PDO $pdo): string
{
    try {
        $stmt = $pdo->query("SELECT code, discount_type, discount_value, description, min_order_amount FROM coupons WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Coupon snapshot error: ' . $e->getMessage());
        $coupons = [];
    }

    if (!$coupons) {
        return '';
    }

    $lines = [];
    foreach ($coupons as $coupon) {
        $value = ($coupon['discount_type'] === 'percentage')
            ? $coupon['discount_value'] . '%'
            : '₱' . number_format((float) $coupon['discount_value'], 2);
        $minOrder = !empty($coupon['min_order_amount']) ? 'Min order ₱' . number_format((float) $coupon['min_order_amount'], 2) : 'No minimum';
        $lines[] = sprintf('%s: %s off. %s. %s',
            strtoupper($coupon['code']),
            $value,
            $minOrder,
            sanitizeContextText($coupon['description'] ?? 'No description')
        );
    }

    return "Active coupons:\n- " . implode("\n- ", $lines);
}

function buildFeedbackSnapshot(PDO $pdo): string
{
    if (!function_exists('getCustomerFeedback')) {
        return '';
    }

    $feedback = getCustomerFeedback(3, true);
    if (!$feedback) {
        return '';
    }

    $lines = [];
    foreach ($feedback as $item) {
        $name = sanitizeContextText($item['full_name'] ?? $item['username'] ?? 'Customer');
        $product = sanitizeContextText($item['product_name'] ?? 'bouquet');
        $comment = sanitizeContextText($item['comment'] ?? 'Loved the flowers!');
        $lines[] = sprintf('%s on %s: "%s"', $name, $product, $comment);
    }

    return "Recent feedback:\n- " . implode("\n- ", $lines);
}

function sanitizeContextText(?string $text): string
{
    $clean = trim((string) $text);
    $clean = preg_replace('/\s+/', ' ', $clean);
    return mb_substr($clean, 0, 160);
}

function formatCurrencyPHP($value): string
{
    if ($value === null || $value === '') {
        return 'Price upon request';
    }
    return '₱' . number_format((float) $value, 2);
}

function resolveCaBundlePath(): ?string
{
    $candidates = [
        getenv('CURL_CA_BUNDLE'),
        ini_get('curl.cainfo'),
        'C:/laragon/etc/ssl/cacert.pem',
        'C:\\laragon\\etc\\ssl\\cacert.pem',
        dirname(__DIR__) . '/storage/certs/cacert.pem'
    ];

    foreach ($candidates as $candidate) {
        if ($candidate && @is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function shouldSendMapLink(string $message): bool
{
    $normalized = mb_strtolower($message, 'UTF-8');
    $keywordPatterns = [
        '/google\s*maps?/u',
        '/\bmap\b/u',
        '/\blocation\b/u',
        '/\baddress\b/u',
        '/\bdirections?\b/u',
        '/\bpin\b/u',
        '/\bloc\b/u',
        '/\blokasyon\b/u',
        '/\bsaan\b/u',
        '/\bnasan\b/u',
        '/\bpunta\b/u'
    ];

    foreach ($keywordPatterns as $pattern) {
        if (preg_match($pattern, $normalized)) {
            return true;
        }
    }

    return preg_match('/\bwhere\b/u', $normalized) && preg_match('/\b(shop|store|branch|located|location|kayo|nyo|you)\b/u', $normalized);
}

function buildMapReply(): string
{
    $address = 'Bagumbayan, Roxas, Oriental Mindoro 5212';
    $plusCode = 'HGV9+36 Roxas, Oriental Mindoro';
    $mapUrl = 'https://maps.app.goo.gl/B22Q3eNuzost4Evo8';
    $phone = '+63 974-7385-789';

    return 'You can find RayAve at **' . $address . '** (map pin **' . $plusCode . '**). Tap [Open RayAve on Google Maps](' . $mapUrl . ') for a live pin, directions, or to share the spot. Need help coordinating pickup or delivery? Call us at ' . $phone . ' and we will guide you.';
}

function respondError(int $status, string $message, array $context = []): void
{
    logChatbotEvent('ERROR: ' . $message, ['status' => $status] + $context);
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

function respondSuccess(array $payload): void
{
    http_response_code(200);
    echo json_encode(array_merge([
        'success' => true
    ], $payload));
    exit;
}

function logChatbotEvent(string $message, array $context = []): void
{
    if (!defined('RAYAVE_CHATBOT_LOG')) {
        return;
    }

    $dir = dirname(RAYAVE_CHATBOT_LOG);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $line .= PHP_EOL;
    @file_put_contents(RAYAVE_CHATBOT_LOG, $line, FILE_APPEND);
}
