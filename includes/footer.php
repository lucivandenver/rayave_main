<!-- Footer -->
<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="text-success">
                    <i class="fas fa-seedling"></i> RaymundoAvenue
                </h5>
                <p class="text-muted">Beautiful flowers and handcrafted gifts to spread love and happiness.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/share/1Ga1qx2prf/" class="text-light me-3" target="_blank"
                        rel="noopener"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/raymundo.avenue?igsh=NmpjbGJwaTVvcGF6" class="text-light me-3"
                        target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.facebook.com/share/1Ga1qx2prf/" class="text-light" target="_blank"
                        rel="noopener"><i class="fab fa-facebook-messenger"></i></a>
                </div>
            </div>
            <div class="col-lg-2 mb-4">
                <h6>Quick Links</h6>
                <ul class="list-unstyled footer-link-group">
                    <li><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                    <li><a href="products.php" class="text-muted text-decoration-none">Products</a></li>
                    <li><a href="about.php" class="text-muted text-decoration-none">About Us</a></li>
                    <li><a href="contact.php" class="text-muted text-decoration-none">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-3 mb-4">
                <h6>Customer Service</h6>
                <ul class="list-unstyled footer-link-group">
                    <li><a href="shipping.php" class="text-muted text-decoration-none">Shipping Info</a></li>
                    <li><a href="returns.php" class="text-muted text-decoration-none">Returns</a></li>
                    <li><a href="faq.php" class="text-muted text-decoration-none">FAQ</a></li>
                    <li><a href="privacy.php" class="text-muted text-decoration-none">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="col-lg-3 mb-4">
                <h6>Newsletter</h6>
                <p class="text-muted">Subscribe to get updates on new products and special offers.</p>
                <form method="POST" action="newsletter-subscribe.php" class="d-flex">
                    <input type="email" name="email" class="form-control me-2" placeholder="Your email" required>
                    <button type="submit" class="btn btn-success">Subscribe</button>
                </form>
            </div>
        </div>
        <hr class="bg-secondary">
        <div class="row">
            <div class="col-md-6">
                <p class="text-muted mb-0">&copy; 2024 RaymundoAvenue. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <img src="assets/images/products/image-2024-09-10-T12-22-58-769-Z.png" alt="Payment Methods" class="img-fluid"
                    style="height: 30px;">
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="assets/js/main.js"></script>
<?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
    <script src="assets/js/rayave-chatbot.js" defer></script>
<?php endif; ?>
</body>

</html>