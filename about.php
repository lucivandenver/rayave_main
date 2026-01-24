<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>

<?php include 'includes/header.php'; ?>

<style>
    .location-map iframe {
        border: 0;
        width: 100%;
        height: 100%;
    }

    .location-map .map-overlay {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.2);
        max-width: 230px;
    }

    .location-map .map-overlay h6 {
        margin-bottom: 0.25rem;
    }

    .location-map .rating {
        font-size: 0.85rem;
        color: #f0a500;
    }

    .location-map .map-overlay a {
        font-size: 0.8rem;
    }
</style>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="display-4 text-center mb-5">About RaymundoAvenue</h1>

            <div class="row mb-5">
                <div class="col-md-6">
                    <img src="assets/images/Picture1.png" alt="Our Flower Shop" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-6">
                    <h3 class="text-success">Our Story</h3>
                    <p>Founded in 2010, RaymundoAvenue. has been bringing joy and beauty to our customers' lives through
                        carefully crafted floral arrangements and exceptional customer service.</p>
                    <p>We believe that flowers have the power to transform spaces, uplift spirits, and create lasting
                        memories. That's why we source only the freshest, highest-quality flowers from trusted growers.
                    </p>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h3 class="text-success mb-4">Our Mission</h3>
                    <p class="lead">To spread happiness and create meaningful connections through the timeless beauty of
                        flowers, while providing exceptional service and sustainable practices.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 80px; height: 80px;">
                        <i class="fas fa-leaf fa-2x text-white"></i>
                    </div>
                    <h5>Sustainable</h5>
                    <p>We prioritize eco-friendly practices and support local growers.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 80px; height: 80px;">
                        <i class="fas fa-heart fa-2x text-white"></i>
                    </div>
                    <h5>Quality</h5>
                    <p>Only the freshest flowers carefully selected for your arrangements.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 80px; height: 80px;">
                        <i class="fas fa-truck fa-2x text-white"></i>
                    </div>
                    <h5>Fast Delivery</h5>
                    <p>Same-day delivery available for orders placed before 2 PM.</p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-lg-6 mx-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="text-success text-center mb-3">Visit Our Shop</h5>
                            <div class="ratio ratio-4x3 rounded overflow-hidden position-relative location-map">
                                <iframe
                                    src="https://www.google.com/maps?width=600&height=450&hl=en&q=RAYMUNDOAVENUE%20Boutique%20and%20Flower%20Shop%20Victoria%20Oriental%20Mindoro&t=k&z=17&ie=UTF8&iwloc=B&output=embed"
                                    loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen
                                    title="RaymundoAvenue location"></iframe>
                                <div class="map-overlay">
                                    <h6 class="mb-1">RaymundoAvenue</h6>
                                    <p class="mb-2 small">555P+4R2, Victoria, Oriental Mindoro</p>
                                    <div class="rating mb-2">★ ★ ★ ★ ★</div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="https://maps.app.goo.gl/KLd134UWLoQcKaRGA" target="_blank"
                                            rel="noopener" class="text-success text-decoration-none">Directions</a>
                                        <a href="https://maps.app.goo.gl/KLd134UWLoQcKaRGA" target="_blank"
                                            rel="noopener" class="text-success text-decoration-none">View larger map</a>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <a href="https://maps.app.goo.gl/KLd134UWLoQcKaRGA" target="_blank" rel="noopener"
                                    class="btn btn-outline-success btn-sm">Open in Google Maps</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>