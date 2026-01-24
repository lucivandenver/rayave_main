<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="display-4 text-center mb-5">Shipping Information</h1>

            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Delivery Options</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="text-center">
                                <i class="fas fa-shipping-fast fa-3x text-success mb-3"></i>
                                <h5>Standard Delivery</h5>
                                <p class="text-muted">3-5 business days</p>
                                <p class="fw-bold text-success">₱10.00</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="text-center">
                                <i class="fas fa-rocket fa-3x text-warning mb-3"></i>
                                <h5>Express Delivery</h5>
                                <p class="text-muted">1-2 business days</p>
                                <p class="fw-bold text-success">₱20.00</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Same-Day Delivery</h5>
                </div>
                <div class="card-body">
                    <p>We offer same-day delivery for orders placed before 2:00 PM local time. Orders placed after 2:00
                        PM will be delivered the next business day.</p>
                    <div class="alert alert-info">
                        <strong>Note:</strong> Same-day delivery is subject to availability and delivery location.
                        Additional fees may apply.
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Delivery Areas</h5>
                </div>
                <div class="card-body">
                    <p>We currently deliver to the following areas:</p>
                    <div class="row">
                        <div class="col-md-6">
                            <ul>
                                <li>Metro City Center</li>
                                <li>North Suburbs</li>
                                <li>South District</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul>
                                <li>East Borough</li>
                                <li>West County</li>
                                <li>Central Heights</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">International Shipping</h5>
                </div>
                <div class="card-body">
                    <p>We currently ship to select international destinations. Please contact us for international
                        shipping rates and delivery times.</p>
                    <p>Additional customs fees and import duties may apply for international orders and are the
                        responsibility of the recipient.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>