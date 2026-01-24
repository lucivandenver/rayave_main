<?php
require_once 'config.php';

function getProducts($category_id = null, $featured = false, $limit = null)
{
    global $pdo;

    ensureProductAvailabilityColumn();

    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE 1=1";
    $params = [];

    if ($category_id) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
    }

    if ($featured) {
        $sql .= " AND p.is_featured = 1";
    }

    $sql .= " ORDER BY p.created_at DESC";

    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = (int) $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProduct($id)
{
    global $pdo;

    ensureProductAvailabilityColumn();

    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getCategories()
{
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addToCart($user_id, $product_id, $quantity = 1)
{
    global $pdo;

    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$quantity, $user_id, $product_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $product_id, $quantity]);
    }
}

function getCartItems($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image, p.stock_quantity 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCartTotal($user_id)
{
    $items = getCartItems($user_id);
    $total = 0;

    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    return $total;
}

function getProductReviews($product_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT pr.*, u.username, u.full_name 
                          FROM product_reviews pr 
                          JOIN users u ON pr.user_id = u.id 
                          WHERE pr.product_id = ? 
                          ORDER BY pr.created_at DESC");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAverageRating($product_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                          FROM product_reviews 
                          WHERE product_id = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addProductReview($product_id, $user_id, $rating, $review_text)
{
    global $pdo;

    // Check if user already reviewed this product
    $stmt = $pdo->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?");
    $stmt->execute([$product_id, $user_id]);

    if ($stmt->fetch()) {
        return false; // User already reviewed
    }

    $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, review_text) 
                          VALUES (?, ?, ?, ?)");
    return $stmt->execute([$product_id, $user_id, $rating, $review_text]);
}

function getOrderTracking($order_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM order_tracking 
                          WHERE order_id = ? 
                          ORDER BY created_at DESC");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateOrderStatus($order_id, $status, $description = '', $manageTransaction = true)
{
    global $pdo;

    $shouldManageTransaction = $manageTransaction && !$pdo->inTransaction();

    try {
        if ($shouldManageTransaction) {
            $pdo->beginTransaction();
        }

        $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            if ($shouldManageTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }

        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);

        $stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description) 
                              VALUES (?, ?, ?)");
        $stmt->execute([$order_id, $status, $description]);

        if ($shouldManageTransaction) {
            $pdo->commit();
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction() && $shouldManageTransaction) {
            $pdo->rollBack();
        }
        error_log('Order status update failed: ' . $e->getMessage());
        if (!$manageTransaction) {
            throw $e;
        }
        return false;
    }

    $title = 'Order #' . $order_id . ' ' . ucfirst($status);
    $messageText = $description ?: 'Your order status changed to ' . ucfirst($status) . '.';
    createNotification($order['user_id'], $title, $messageText, 'order', $order_id);

    return true;
}

function autoCancelPendingOrders($minHours = AUTO_CANCEL_PENDING_MIN_HOURS, $maxHours = AUTO_CANCEL_PENDING_MAX_HOURS)
{
    global $pdo;

    static $autoCancelRan = false;
    if ($autoCancelRan) {
        return;
    }
    $autoCancelRan = true;

    $minHours = max(1, (int) $minHours);
    $maxHours = max($minHours, (int) $maxHours);

    try {
        $stmt = $pdo->prepare(
            "SELECT id FROM orders 
             WHERE status = 'pending' 
               AND TIMESTAMPDIFF(HOUR, created_at, NOW()) >= :cutoff
             LIMIT 50"
        );
        $stmt->execute([':cutoff' => $maxHours]);
        $staleOrders = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($staleOrders as $staleOrderId) {
            updateOrderStatus(
                $staleOrderId,
                'cancelled',
                'Order automatically cancelled after pending for more than ' . $maxHours . ' hours without admin confirmation.'
            );
        }
    } catch (PDOException $e) {
        error_log('Auto cancel pending orders failed: ' . $e->getMessage());
    }
}

// ----------------------------
// Coupon helpers
// ----------------------------

function ensureOrderCouponColumns()
{
    global $pdo;

    static $couponColumnsEnsured = false;
    if ($couponColumnsEnsured) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'coupon_code'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD coupon_code VARCHAR(100) NULL AFTER payment_method");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'coupon_discount'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD coupon_discount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER coupon_code");
        }
    } catch (PDOException $e) {
        error_log('Ensure order coupon columns failed: ' . $e->getMessage());
    }

    $couponColumnsEnsured = true;
}

function getCouponByCode($code)
{
    global $pdo;

    if (trim($code) === '') {
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE UPPER(code) = UPPER(?) LIMIT 1");
    $stmt->execute([$code]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function hasUserUsedCoupon($couponId, $userId)
{
    global $pdo;

    if (!$couponId || !$userId) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
    $stmt->execute([$couponId, $userId]);

    return (int) $stmt->fetchColumn() > 0;
}

function calculateCouponDiscount($coupon, $orderSubtotal)
{
    if (!$coupon || $orderSubtotal <= 0) {
        return 0;
    }

    $discount = 0;

    if ($coupon['discount_type'] === 'percentage') {
        $discount = $orderSubtotal * ((float) $coupon['discount_value'] / 100);
        if (!empty($coupon['max_discount_amount']) && $coupon['max_discount_amount'] > 0) {
            $discount = min($discount, (float) $coupon['max_discount_amount']);
        }
    } else {
        $discount = (float) $coupon['discount_value'];
    }

    return max(0, min($discount, $orderSubtotal));
}

/**
 * @return array{valid:bool,error:string,coupon:?array,discount_amount:float}
 */
function validateCouponForOrder($code, $userId, $orderSubtotal): array
{
    $result = [
        'valid' => false,
        'error' => '',
        'coupon' => null,
        'discount_amount' => 0
    ];

    $coupon = getCouponByCode($code);

    if (!$coupon || !(int) $coupon['is_active']) {
        $result['error'] = 'Coupon code is invalid or inactive.';
        return $result;
    }

    $now = new DateTime();

    if (!empty($coupon['valid_from']) && $now < new DateTime($coupon['valid_from'])) {
        $result['error'] = 'Coupon is not yet valid.';
        return $result;
    }

    if (!empty($coupon['valid_until']) && $now > new DateTime($coupon['valid_until'])) {
        $result['error'] = 'Coupon has already expired.';
        return $result;
    }

    if (!empty($coupon['usage_limit']) && $coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
        $result['error'] = 'Coupon usage limit has been reached.';
        return $result;
    }

    if ($userId && hasUserUsedCoupon($coupon['id'], $userId)) {
        $result['error'] = 'You have already used this coupon.';
        return $result;
    }

    if (!empty($coupon['min_order_amount']) && $orderSubtotal < $coupon['min_order_amount']) {
        $result['error'] = 'Minimum order amount for this coupon is ₱' . number_format($coupon['min_order_amount'], 2);
        return $result;
    }

    $discount = calculateCouponDiscount($coupon, $orderSubtotal);

    if ($discount <= 0) {
        $result['error'] = 'Coupon does not apply to this order.';
        return $result;
    }

    $result['valid'] = true;
    $result['coupon'] = $coupon;
    $result['discount_amount'] = $discount;

    return $result;
}

function recordCouponUsage($couponId, $userId, $orderId)
{
    global $pdo;

    if (!$couponId || !$userId || !$orderId) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id, used_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$couponId, $userId, $orderId]);

        $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$couponId]);

        return true;
    } catch (PDOException $e) {
        error_log('Record coupon usage error: ' . $e->getMessage());
        return false;
    }
}

function getCoupons($onlyActive = false)
{
    global $pdo;

    $sql = "SELECT * FROM coupons";
    if ($onlyActive) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCouponById($couponId)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$couponId]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function couponCodeExists($code, $excludeId = null)
{
    global $pdo;

    $params = [$code];
    $sql = "SELECT COUNT(*) FROM coupons WHERE UPPER(code) = UPPER(?)";

    if ($excludeId) {
        $sql .= " AND id <> ?";
        $params[] = $excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function saveCoupon(array $data, $couponId = null)
{
    global $pdo;

    $fields = [
        'code' => strtoupper(trim($data['code'] ?? '')),
        'discount_type' => $data['discount_type'] ?? 'percentage',
        'discount_value' => (float) ($data['discount_value'] ?? 0),
        'min_order_amount' => (float) ($data['min_order_amount'] ?? 0),
        'max_discount_amount' => (float) ($data['max_discount_amount'] ?? 0),
        'usage_limit' => (int) ($data['usage_limit'] ?? 0),
        'valid_from' => $data['valid_from'] ?? null,
        'valid_until' => $data['valid_until'] ?? null,
        'is_active' => !empty($data['is_active']) ? 1 : 0
    ];

    if ($couponId) {
        $stmt = $pdo->prepare("UPDATE coupons SET code = ?, discount_type = ?, discount_value = ?, min_order_amount = ?, max_discount_amount = ?, usage_limit = ?, valid_from = ?, valid_until = ?, is_active = ? WHERE id = ?");
        return $stmt->execute([
            $fields['code'],
            $fields['discount_type'],
            $fields['discount_value'],
            $fields['min_order_amount'],
            $fields['max_discount_amount'],
            $fields['usage_limit'],
            $fields['valid_from'],
            $fields['valid_until'],
            $fields['is_active'],
            $couponId
        ]);
    }

    $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, valid_from, valid_until, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $fields['code'],
        $fields['discount_type'],
        $fields['discount_value'],
        $fields['min_order_amount'],
        $fields['max_discount_amount'],
        $fields['usage_limit'],
        $fields['valid_from'],
        $fields['valid_until'],
        $fields['is_active']
    ]);
}

function deleteCoupon($couponId)
{
    global $pdo;

    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
    return $stmt->execute([$couponId]);
}

function getUserAddresses($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM user_addresses 
                          WHERE user_id = ? 
                          ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addUserAddress($user_id, $address_data)
{
    global $pdo;

    $address_type = $address_data['address_type'] ?? 'home';
    $street = $address_data['street_address'] ?? '';
    $city = $address_data['city'] ?? '';
    $state = $address_data['state'] ?? '';
    $zip = $address_data['zip_code'] ?? '';
    $country = $address_data['country'] ?? 'USA';
    $is_default = !empty($address_data['is_default']) ? 1 : 0;

    // If this is set as default, remove default from other addresses
    if ($is_default) {
        $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = FALSE WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }

    $stmt = $pdo->prepare("INSERT INTO user_addresses 
                          (user_id, address_type, street_address, city, state, zip_code, country, is_default) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    return $stmt->execute([
        $user_id,
        $address_type,
        $street,
        $city,
        $state,
        $zip,
        $country,
        $is_default
    ]);
}

function updateInventory($product_id, $change_quantity, $reason, $notes = '')
{
    global $pdo;

    // Get current quantity
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product)
        return false;

    $new_quantity = $product['stock_quantity'] + $change_quantity;

    // Update product stock
    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
    $stmt->execute([$new_quantity, $product_id]);

    // Log inventory change
    $stmt = $pdo->prepare("INSERT INTO inventory_logs 
                          (product_id, change_quantity, new_quantity, reason, notes) 
                          VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$product_id, $change_quantity, $new_quantity, $reason, $notes]);
}

function ensureProductAvailabilityColumn()
{
    global $pdo;

    static $availabilityChecked = false;
    if ($availabilityChecked) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'availability_status'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE products ADD availability_status VARCHAR(20) NOT NULL DEFAULT 'available' AFTER stock_quantity");
            $pdo->exec("UPDATE products SET availability_status = 'available' WHERE availability_status IS NULL OR availability_status = ''");
        }
    } catch (PDOException $e) {
        error_log('Availability status migration failed: ' . $e->getMessage());
    }

    $availabilityChecked = true;
}

function ensureLowStockThresholdColumn()
{
    global $pdo;

    static $thresholdChecked = false;
    if ($thresholdChecked) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'low_stock_threshold'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE products ADD low_stock_threshold INT NOT NULL DEFAULT 5");
            $pdo->exec("UPDATE products SET low_stock_threshold = 5 WHERE low_stock_threshold IS NULL");
        }
    } catch (PDOException $e) {
        error_log('Low stock threshold migration failed: ' . $e->getMessage());
    }

    $thresholdChecked = true;
}

function getLowStockProducts($limit = null)
{
    global $pdo;

    ensureLowStockThresholdColumn();

    $sql = "SELECT p.*, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock_quantity <= p.low_stock_threshold
            ORDER BY p.stock_quantity ASC, p.name ASC";

    if ($limit !== null) {
        $limit = max(0, (int) $limit);
        $sql .= " LIMIT $limit";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOutOfStockProducts()
{
    global $pdo;

    ensureLowStockThresholdColumn();

    $stmt = $pdo->query("SELECT p.*, c.name AS category_name
                          FROM products p
                          LEFT JOIN categories c ON p.category_id = c.id
                          WHERE p.stock_quantity <= 0
                          ORDER BY p.name ASC");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInventoryLogsByReason($reason, $limit = 10)
{
    global $pdo;

    $limit = max(1, (int) $limit);

    $stmt = $pdo->prepare("SELECT l.*, p.name AS product_name
                           FROM inventory_logs l
                           LEFT JOIN products p ON l.product_id = p.id
                           WHERE l.reason = ?
                           ORDER BY l.id DESC
                           LIMIT $limit");
    $stmt->execute([$reason]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInventoryOverview()
{
    global $pdo;

    ensureLowStockThresholdColumn();

    $overview = [
        'total_skus' => 0,
        'total_units' => 0,
        'low_stock' => 0,
        'out_of_stock' => 0
    ];

    $stmt = $pdo->query("SELECT COUNT(*) AS total_skus,
                                 COALESCE(SUM(stock_quantity), 0) AS total_units,
                                 SUM(CASE WHEN stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) AS low_stock,
                                 SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock
                          FROM products");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $overview = $result;
    }

    return $overview;
}

function subscribeToNewsletter($email)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("INSERT INTO newsletter_subscriptions (email) VALUES (?) 
                              ON DUPLICATE KEY UPDATE is_active = TRUE");
        return $stmt->execute([$email]);
    } catch (PDOException $e) {
        return false;
    }
}

function getRelatedProducts($product_id, $category_id, $limit = 4)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM products 
                              WHERE category_id = ? AND id != ? AND status = 'active' 
                              ORDER BY RAND() 
                              LIMIT ?");
        $stmt->execute([$category_id, $product_id, (int) $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Related products error: " . $e->getMessage());
        return []; // Return empty array on error
    }
}

function getBestSellingProducts($limit = 8)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT p.*, SUM(oi.quantity) as total_sold 
                              FROM products p 
                              JOIN order_items oi ON p.id = oi.product_id 
                              JOIN orders o ON oi.order_id = o.id 
                              WHERE o.status != 'cancelled' 
                              GROUP BY p.id 
                              ORDER BY total_sold DESC 
                              LIMIT ?");
        $stmt->execute([(int) $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Best selling products error: " . $e->getMessage());
        return [];
    }
}

function getNewArrivals($limit = 8)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM products 
                              ORDER BY created_at DESC 
                              LIMIT ?");
        $stmt->execute([(int) $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("New arrivals error: " . $e->getMessage());
        return [];
    }
}

// ----------------------------
// Support messaging helpers
// ----------------------------

function ensureSupportMessagingTables()
{
    global $pdo;

    static $supportTablesEnsured = false;
    if ($supportTablesEnsured) {
        return;
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_threads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            status ENUM('open','pending','closed') NOT NULL DEFAULT 'open',
            last_message_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_status (status),
            CONSTRAINT fk_contact_thread_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_thread_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            thread_id INT NOT NULL,
            sender_type ENUM('customer','admin') NOT NULL,
            sender_id INT NULL,
            message TEXT NOT NULL,
            is_read_by_customer TINYINT(1) NOT NULL DEFAULT 0,
            is_read_by_admin TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_thread (thread_id),
            INDEX idx_sender (sender_type),
            CONSTRAINT fk_contact_thread_messages_thread FOREIGN KEY (thread_id) REFERENCES contact_threads(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $supportTablesEnsured = true;
    } catch (PDOException $e) {
        error_log('Support messaging table creation failed: ' . $e->getMessage());
    }
}

function getContactThreadByUser($userId)
{
    global $pdo;

    if (!$userId) {
        return null;
    }

    ensureSupportMessagingTables();

    $stmt = $pdo->prepare("SELECT * FROM contact_threads WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function getContactThread($threadId)
{
    global $pdo;

    ensureSupportMessagingTables();

    $stmt = $pdo->prepare("SELECT * FROM contact_threads WHERE id = ?");
    $stmt->execute([$threadId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function createContactThread($userId, $name, $email, $subject)
{
    global $pdo;

    ensureSupportMessagingTables();

    $subject = trim($subject) === '' ? 'General Inquiry' : trim($subject);
    $name = trim($name);
    $email = trim($email);

    $stmt = $pdo->prepare("INSERT INTO contact_threads (user_id, name, email, subject)
                           VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $name,
        $email,
        $subject
    ]);

    return getContactThread($pdo->lastInsertId());
}

function addContactThreadMessage($threadId, $senderType, $senderId, $message)
{
    global $pdo;

    ensureSupportMessagingTables();

    $senderType = $senderType === 'admin' ? 'admin' : 'customer';
    $message = trim($message);

    if (!$threadId || $message === '') {
        return false;
    }

    $isReadCustomer = $senderType === 'customer' ? 1 : 0;
    $isReadAdmin = $senderType === 'admin' ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO contact_thread_messages
            (thread_id, sender_type, sender_id, message, is_read_by_customer, is_read_by_admin)
            VALUES (?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $threadId,
        $senderType,
        $senderId,
        $message,
        $isReadCustomer,
        $isReadAdmin
    ]);

    if ($result) {
        $status = $senderType === 'customer' ? 'open' : 'pending';
        $stmt = $pdo->prepare("UPDATE contact_threads
                               SET status = ?, last_message_at = NOW(), updated_at = NOW()
                               WHERE id = ?");
        $stmt->execute([$status, $threadId]);

        if ($senderType === 'customer') {
            $thread = getContactThread($threadId);
            if ($thread) {
                $title = 'New message from ' . $thread['name'];
                $body = substr($message, 0, 140);
                notifyAdmins($title, $body, 'support', $threadId);
            }
        }
    }

    return $result;
}

function getContactThreadMessages($threadId, $limit = 0)
{
    global $pdo;
    ensureSupportMessagingTables();

    $sql = "SELECT * FROM contact_thread_messages WHERE thread_id = ? ORDER BY created_at ASC";
    if ($limit > 0) {
        $sql .= " LIMIT " . (int) $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$threadId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markThreadMessagesRead($threadId, $viewerType)
{
    global $pdo;
    ensureSupportMessagingTables();

    if ($viewerType === 'customer') {
        $stmt = $pdo->prepare("UPDATE contact_thread_messages
                               SET is_read_by_customer = 1
                               WHERE thread_id = ?");
        return $stmt->execute([$threadId]);
    }

    if ($viewerType === 'admin') {
        $stmt = $pdo->prepare("UPDATE contact_thread_messages
                               SET is_read_by_admin = 1
                               WHERE thread_id = ?");
        return $stmt->execute([$threadId]);
    }

    return false;
}

function getContactThreads($status = null)
{
    global $pdo;
    ensureSupportMessagingTables();

    $sql = "SELECT ct.*, (
                SELECT COUNT(*) FROM contact_thread_messages m
                WHERE m.thread_id = ct.id AND m.sender_type = 'customer' AND m.is_read_by_admin = 0
            ) AS unread_for_admin
            FROM contact_threads ct";
    $params = [];

    if ($status && in_array($status, ['open', 'pending', 'closed'], true)) {
        $sql .= " WHERE ct.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY ct.last_message_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateContactThreadStatus($threadId, $status)
{
    global $pdo;
    ensureSupportMessagingTables();

    $allowed = ['open', 'pending', 'closed'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE contact_threads SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $threadId]);
}

function countContactThreadsByStatus()
{
    global $pdo;
    ensureSupportMessagingTables();

    $counts = [
        'all' => 0,
        'open' => 0,
        'pending' => 0,
        'closed' => 0
    ];

    $stmt = $pdo->query("SELECT status, COUNT(*) AS total FROM contact_threads GROUP BY status");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($counts[$row['status']])) {
            $counts[$row['status']] = (int) $row['total'];
            $counts['all'] += (int) $row['total'];
        }
    }

    return $counts;
}

// ----------------------------
// Sales analytics helper data
// ----------------------------

function getDailyRevenueSeries($days = 7)
{
    global $pdo;

    $days = max(1, min(30, (int) $days));
    $end = new DateTime('today');
    $start = (clone $end)->modify('-' . ($days - 1) . ' days');

    $stmt = $pdo->prepare("SELECT DATE(created_at) AS day_label, COALESCE(SUM(total_amount), 0) AS revenue
        FROM orders
        WHERE status IN ('delivered','completed') AND created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)");
    $stmt->execute([
        $start->format('Y-m-d 00:00:00'),
        $end->format('Y-m-d 23:59:59')
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $row) {
        $map[$row['day_label']] = (float) $row['revenue'];
    }

    $series = ['labels' => [], 'values' => []];
    $cursor = clone $start;
    while ($cursor <= $end) {
        $key = $cursor->format('Y-m-d');
        $series['labels'][] = $cursor->format('M j');
        $series['values'][] = isset($map[$key]) ? $map[$key] : 0;
        $cursor->modify('+1 day');
    }

    return $series;
}

function getMonthlyRevenueSeries($year = null)
{
    global $pdo;

    $year = $year ? (int) $year : (int) date('Y');
    $start = new DateTime($year . '-01-01 00:00:00');
    $end = new DateTime($year . '-12-31 23:59:59');

    $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_label, COALESCE(SUM(total_amount), 0) AS revenue
        FROM orders
        WHERE status IN ('delivered','completed') AND created_at BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')");
    $stmt->execute([
        $start->format('Y-m-d H:i:s'),
        $end->format('Y-m-d H:i:s')
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $row) {
        $map[$row['month_label']] = (float) $row['revenue'];
    }

    $series = ['labels' => [], 'values' => []];
    for ($m = 1; $m <= 12; $m++) {
        $key = sprintf('%04d-%02d', $year, $m);
        $series['labels'][] = date('M', mktime(0, 0, 0, $m, 1));
        $series['values'][] = isset($map[$key]) ? $map[$key] : 0;
    }

    return $series;
}

function getYearlyRevenueSeries($years = 5)
{
    global $pdo;

    $years = max(1, min(10, (int) $years));
    $endYear = (int) date('Y');
    $startYear = $endYear - ($years - 1);

    $stmt = $pdo->prepare("SELECT YEAR(created_at) AS year_label, COALESCE(SUM(total_amount), 0) AS revenue
        FROM orders
        WHERE status IN ('delivered','completed') AND created_at BETWEEN ? AND ?
        GROUP BY YEAR(created_at)");
    $stmt->execute([
        $startYear . '-01-01 00:00:00',
        $endYear . '-12-31 23:59:59'
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $row) {
        $map[$row['year_label']] = (float) $row['revenue'];
    }

    $series = ['labels' => [], 'values' => []];
    for ($year = $startYear; $year <= $endYear; $year++) {
        $series['labels'][] = (string) $year;
        $series['values'][] = isset($map[$year]) ? $map[$year] : 0;
    }

    return $series;
}

function getTopSellingProductsSeries($limit = 5)
{
    global $pdo;

    $limit = max(1, min(10, (int) $limit));

    $sql = "SELECT p.name, SUM(oi.quantity) AS units_sold
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.status IN ('delivered','completed')
            GROUP BY p.id
            ORDER BY units_sold DESC
            LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $labels = [];
    $values = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $labels[] = $row['name'];
        $values[] = (int) $row['units_sold'];
    }

    return ['labels' => $labels, 'values' => $values];
}

// ----------------------------
// Reporting helper utilities
// ----------------------------

function resolveReportRange($period = 'monthly', $customStart = null, $customEnd = null)
{
    $end = new DateTime('today');
    $start = clone $end;
    $label = 'This Month';

    switch ($period) {
        case 'daily':
            $label = 'Today';
            break;
        case 'weekly':
            $start = (clone $end)->modify('-6 days');
            $label = 'Last 7 Days';
            break;
        case 'monthly':
            $start = (clone $end)->modify('first day of this month');
            $label = $end->format('F Y');
            break;
        case 'yearly':
            $start = new DateTime($end->format('Y') . '-01-01');
            $label = $end->format('Y');
            break;
        case 'custom':
            if ($customStart && $customEnd) {
                try {
                    $start = new DateTime($customStart);
                    $end = new DateTime($customEnd);
                    $label = $start->format('M j, Y') . ' - ' . $end->format('M j, Y');
                } catch (Exception $e) {
                    $start = (clone $end)->modify('first day of this month');
                    $label = $end->format('F Y');
                }
            }
            break;
        default:
            $start = (clone $end)->modify('first day of this month');
            $label = $end->format('F Y');
            break;
    }

    if ($end < $start) {
        $tmp = $start;
        $start = $end;
        $end = $tmp;
    }

    $startDate = (clone $start)->setTime(0, 0, 0);
    $endDate = (clone $end)->setTime(23, 59, 59);

    return [
        'start' => $startDate->format('Y-m-d H:i:s'),
        'end' => $endDate->format('Y-m-d H:i:s'),
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
        'label' => $label
    ];
}

function getSalesReportData($startDate, $endDate)
{
    global $pdo;

    $summary = [
        'total_revenue' => 0,
        'total_orders' => 0,
        'unique_customers' => 0,
        'avg_order_value' => 0
    ];

    $stmt = $pdo->prepare("SELECT
            COALESCE(SUM(total_amount), 0) AS total_revenue,
            COUNT(*) AS total_orders,
            COUNT(DISTINCT user_id) AS unique_customers
        FROM orders
        WHERE status IN ('delivered','completed')
            AND created_at BETWEEN ? AND ?");
    $stmt->execute([$startDate, $endDate]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $summary['total_revenue'] = (float) $row['total_revenue'];
        $summary['total_orders'] = (int) $row['total_orders'];
        $summary['unique_customers'] = (int) $row['unique_customers'];
        $summary['avg_order_value'] = $summary['total_orders'] > 0
            ? $summary['total_revenue'] / $summary['total_orders']
            : 0;
    }

    $stmt = $pdo->prepare("SELECT status, COUNT(*) as total
        FROM orders
        WHERE created_at BETWEEN ? AND ?
        GROUP BY status");
    $stmt->execute([$startDate, $endDate]);
    $statusRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $ordersStmt = $pdo->prepare("SELECT o.*, COALESCE(NULLIF(u.full_name,''), u.username) AS customer_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('delivered','completed')
            AND o.created_at BETWEEN ? AND ?
        ORDER BY o.created_at DESC");
    $ordersStmt->execute([$startDate, $endDate]);
    $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'summary' => $summary,
        'orders' => $orders,
        'status_breakdown' => $statusRows
    ];
}

function getProductPerformanceReport($startDate, $endDate, $limit = null)
{
    global $pdo;

    $sql = "SELECT p.name,
                SUM(oi.quantity) AS units_sold,
                SUM(oi.quantity * oi.price) AS revenue,
                COUNT(DISTINCT o.id) AS order_count
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.status IN ('delivered','completed')
                AND o.created_at BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY units_sold DESC";

    if ($limit !== null) {
        $limit = max(1, (int) $limit);
        $sql .= " LIMIT $limit";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCustomerInsightsReport($startDate, $endDate, $limit = null)
{
    global $pdo;

    $sql = "SELECT COALESCE(NULLIF(u.full_name,''), u.username) AS customer_name,
                COUNT(o.id) AS order_count,
                SUM(o.total_amount) AS total_spent,
                AVG(o.total_amount) AS avg_order_value,
                MAX(o.created_at) AS last_order_date
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.status IN ('delivered','completed')
                AND o.created_at BETWEEN ? AND ?
            GROUP BY u.id
            ORDER BY total_spent DESC";

    if ($limit !== null) {
        $limit = max(1, (int) $limit);
        $sql .= " LIMIT $limit";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCancelledRefundedOrdersReport($startDate, $endDate)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT o.*, COALESCE(NULLIF(u.full_name,''), u.username) AS customer_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('cancelled','refunded')
            AND o.created_at BETWEEN ? AND ?
        ORDER BY o.created_at DESC");
    $stmt->execute([$startDate, $endDate]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchProducts($query, $category_id = null, $min_price = null, $max_price = null, $sort = 'name')
{
    global $pdo;

    ensureProductAvailabilityColumn();

    try {
        $sql = "SELECT p.*, c.name as category_name FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE (p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
        $params = ["%$query%", "%$query%", "%$query%"];

        if ($category_id) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category_id;
        }

        if ($min_price !== null) {
            $sql .= " AND p.price >= ?";
            $params[] = $min_price;
        }

        if ($max_price !== null) {
            $sql .= " AND p.price <= ?";
            $params[] = $max_price;
        }

        // Sorting
        $sort_options = [
            'name' => 'p.name ASC',
            'name_desc' => 'p.name DESC',
            'price' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'newest' => 'p.created_at DESC',
            'rating' => '(SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id) DESC'
        ];

        $sql .= " ORDER BY " . ($sort_options[$sort] ?? 'p.name ASC');

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Search products error: " . $e->getMessage());
        return [];
    }
}

// Helper function to check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getUserById($userId)
{
    global $pdo;

    $userId = (int) $userId;
    if ($userId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Helper function to redirect
function redirect($url)
{
    header("Location: $url");
    exit();
}

// Helper function to display price format
function formatPrice($price)
{
    return '$' . number_format($price, 2);
}

// Helper function to get cart item count
function getCartItemCount($user_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Cart item count error: " . $e->getMessage());
        return 0;
    }
}

// Helper function to check if product exists
function productExists($product_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Product exists check error: " . $e->getMessage());
        return false;
    }
}

// Helper function to get category name
function getCategoryName($category_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['name'] ?? 'Unknown Category';
    } catch (PDOException $e) {
        error_log("Get category name error: " . $e->getMessage());
        return 'Unknown Category';
    }
}

function getCustomerFeedback($limit = 6, $onlyFeatured = true)
{
    global $pdo;

    try {
        $limit = max(1, (int) $limit); // ensure a safe numeric limit

        $sql = "SELECT cf.*, p.name AS product_name, u.full_name, u.username, oi.price
                FROM customer_feedback cf
                JOIN products p ON cf.product_id = p.id
                JOIN users u ON cf.user_id = u.id
                LEFT JOIN order_items oi ON cf.order_item_id = oi.id
                WHERE cf.status = 'published'";

        if ($onlyFeatured) {
            $sql .= " AND cf.is_featured = 1";
        }

        $sql .= " ORDER BY cf.created_at DESC LIMIT $limit";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get customer feedback error: ' . $e->getMessage());
        return [];
    }
}

function getRecentFeedbackForAdmin($limit = 10)
{
    global $pdo;

    try {
        $limit = max(1, (int) $limit);

        $sql = "SELECT cf.*, p.name AS product_name, u.full_name
            FROM customer_feedback cf
            JOIN products p ON cf.product_id = p.id
            JOIN users u ON cf.user_id = u.id
            ORDER BY cf.created_at DESC
            LIMIT $limit";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get recent feedback error: ' . $e->getMessage());
        return [];
    }
}

function updateFeedbackStatus($feedbackId, $status, $isFeatured = null)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE customer_feedback SET status = ? WHERE id = ?");
        $stmt->execute([$status, $feedbackId]);

        if ($isFeatured !== null) {
            $stmt = $pdo->prepare("UPDATE customer_feedback SET is_featured = ? WHERE id = ?");
            $stmt->execute([(int) $isFeatured, $feedbackId]);
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Update feedback status error: ' . $e->getMessage());
        return false;
    }
}

function saveCustomerFeedback($orderId, $orderItemId, $productId, $userId, $rating, $comment)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("INSERT INTO customer_feedback
            (order_id, order_item_id, product_id, user_id, rating, comment)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), status = 'pending', updated_at = CURRENT_TIMESTAMP");
        return $stmt->execute([$orderId, $orderItemId, $productId, $userId, $rating, $comment]);
    } catch (PDOException $e) {
        error_log('Save customer feedback error: ' . $e->getMessage());
        return false;
    }
}

function getFeedbackForProduct($productId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT cf.*, u.full_name, u.username
            FROM customer_feedback cf
            JOIN users u ON cf.user_id = u.id
            WHERE cf.product_id = ? AND cf.status = 'published'
            ORDER BY cf.created_at DESC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get feedback for product error: ' . $e->getMessage());
        return [];
    }
}

function getFeedbackForOrderItem($orderItemId, $userId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM customer_feedback WHERE order_item_id = ? AND user_id = ?");
        $stmt->execute([$orderItemId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get feedback for order item error: ' . $e->getMessage());
        return null;
    }
}

// ----------------------------
// Notification helper methods
// ----------------------------

function ensureNotificationTypeSupports($type)
{
    global $pdo;

    static $ensuredTypes = [];
    if ($type === '' || isset($ensuredTypes[$type])) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'type'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($column && !empty($column['Type'])) {
            if (strpos($column['Type'], "'{$type}'") === false) {
                preg_match_all("/'([^']+)'/", $column['Type'], $matches);
                $allowed = $matches[1] ?? [];
                $allowed[] = $type;
                $allowed = array_values(array_unique($allowed));
                $enumList = "'" . implode("','", array_map('addslashes', $allowed)) . "'";
                $pdo->exec("ALTER TABLE notifications MODIFY type ENUM($enumList) DEFAULT 'system'");
            }
        }
    } catch (PDOException $e) {
        error_log('Ensure notification type enum failed: ' . $e->getMessage());
    }

    $ensuredTypes[$type] = true;
}

function getAdminUserIds()
{
    global $pdo;

    static $adminIds = null;
    if ($adminIds !== null) {
        return $adminIds;
    }

    try {
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
        $adminIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (PDOException $e) {
        error_log('Get admin IDs error: ' . $e->getMessage());
        $adminIds = [];
    }

    return $adminIds;
}

function createNotification($userId, $title, $message, $type = 'system', $relatedId = null)
{
    global $pdo;

    if (empty($userId) || empty($title) || empty($message)) {
        return false;
    }

    ensureNotificationTypeSupports($type);

    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, related_id)
                               VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $title, $message, $type, $relatedId]);
    } catch (PDOException $e) {
        error_log('Create notification error: ' . $e->getMessage());
        return false;
    }
}

function notifyAdmins($title, $message, $type = 'system', $relatedId = null)
{
    ensureNotificationTypeSupports($type);

    $adminIds = getAdminUserIds();
    if (empty($adminIds)) {
        return false;
    }

    $result = true;
    foreach ($adminIds as $adminId) {
        $result = createNotification($adminId, $title, $message, $type, $relatedId) && $result;
    }

    return $result;
}

function getUserNotifications($userId, $onlyUnread = false, $limit = 0)
{
    global $pdo;

    if (empty($userId)) {
        return [];
    }

    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$userId];

    if ($onlyUnread) {
        $sql .= " AND is_read = 0";
    }

    $sql .= " ORDER BY created_at DESC";

    if ($limit > 0) {
        $sql .= " LIMIT " . (int) $limit;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get user notifications error: ' . $e->getMessage());
        return [];
    }
}

function markNotificationRead($notificationId, $userId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    } catch (PDOException $e) {
        error_log('Mark notification read error: ' . $e->getMessage());
        return false;
    }
}

function markAllNotificationsRead($userId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log('Mark all notifications read error: ' . $e->getMessage());
        return false;
    }
}

function deleteNotification($notificationId, $userId)
{
    global $pdo;

    $notificationId = (int) $notificationId;
    $userId = (int) $userId;

    if ($notificationId <= 0 || $userId <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    } catch (PDOException $e) {
        error_log('Delete notification error: ' . $e->getMessage());
        return false;
    }
}

function deleteAllNotifications($userId)
{
    global $pdo;

    $userId = (int) $userId;

    if ($userId <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log('Delete all notifications error: ' . $e->getMessage());
        return false;
    }
}

function getUnreadNotificationCount($userId)
{
    global $pdo;

    if (empty($userId)) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Unread notification count error: ' . $e->getMessage());
        return 0;
    }
}
?>