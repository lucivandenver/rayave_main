<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$cart_items = getCartItems($_SESSION['user_id']);
$cart_total = getCartTotal($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $cart_id => $quantity) {
        if ($quantity <= 0) {
            // Remove item
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
        } else {
            // Update quantity
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
        }
    }
    redirect('cart.php');
}

if (isset($_GET['remove'])) {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['remove'], $_SESSION['user_id']]);
    redirect('cart.php');
}
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Shopping Cart</h2>

    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
            <h4>Your cart is empty</h4>
            <p>Start shopping to add items to your cart</p>
            <a href="products.php" class="btn btn-success">Continue Shopping</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="row align-items-center mb-4 pb-4 border-bottom">
                                    <div class="col-md-2">
                                        <img src="assets/images/products/<?php echo $item['image']; ?>"
                                            alt="<?php echo $item['name']; ?>" class="img-fluid rounded">
                                    </div>
                                    <div class="col-md-4">
                                        <h5><?php echo $item['name']; ?></h5>
                                        <p class="text-muted">₱<?php echo $item['price']; ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" name="quantities[<?php echo $item['id']; ?>]"
                                            value="<?php echo $item['quantity']; ?>" min="1"
                                            max="<?php echo $item['stock_quantity']; ?>" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <h5>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></h5>
                                    </div>
                                    <div class="col-md-1">
                                        <a href="cart.php?remove=<?php echo $item['id']; ?>"
                                            class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="d-flex justify-content-between">
                                <a href="products.php" class="btn btn-outline-success">
                                    Continue Shopping
                                </a>
                                <button type="submit" name="update_cart" class="btn btn-success">
                                    Update Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal:</span>
                                <span>₱<?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Shipping:</span>
                                <span>₱10.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Tax:</span>
                                <span>₱<?php echo number_format($cart_total * 0.1, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3 fw-bold">
                                <span>Total:</span>
                                <span>₱<?php echo number_format($cart_total + 10 + ($cart_total * 0.1), 2); ?></span>
                            </div>

                            <a href="checkout.php" class="btn btn-success w-100">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>