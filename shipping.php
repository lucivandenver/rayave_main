<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>

<?php
ensureShippingSettingsTable();
$shippingSettings = getShippingSettings();
$perKmRate = isset($shippingSettings['per_km_rate']) ? (float) $shippingSettings['per_km_rate'] : 0;
$perMileRate = isset($shippingSettings['per_mile_rate']) ? (float) $shippingSettings['per_mile_rate'] : 0;
$baseFee = isset($shippingSettings['base_fee']) ? (float) $shippingSettings['base_fee'] : 0;
$minimumFee = isset($shippingSettings['minimum_fee']) ? (float) $shippingSettings['minimum_fee'] : 0;
$unit = $shippingSettings['calculation_unit'] ?? 'km';
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="display-4 text-center mb-5">Shipping Information</h1>

            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Distance-Based Delivery</h5>
                </div>
                <div class="card-body">
                    <p>
                        Every delivery fee is now calculated automatically through Google Maps. We measure the driving
                        distance from our Bagumbayan, Gloria floral studio to your chosen address and then apply the
                        rates below.
                    </p>
                    <ul class="list-unstyled mb-3">
                        <li class="mb-2"><strong>Base fee:</strong> ₱<?php echo number_format($baseFee, 2); ?></li>
                        <li class="mb-2"><strong>Per <?php echo $unit === 'mile' ? 'mile' : 'kilometer'; ?> rate:</strong>
                            ₱<?php echo number_format($unit === 'mile' ? $perMileRate : $perKmRate, 2); ?>
                        </li>
                        <li class="mb-2"><strong>Minimum delivery fee:</strong> ₱<?php echo number_format($minimumFee, 2); ?></li>
                        <li><strong>Coverage:</strong> Any drivable route Google Maps can calculate from our studio.</li>
                    </ul>
                    <div class="alert alert-light border">
                        Store pickup remains free. Select pickup during checkout if you prefer to collect your flowers
                        in person.
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Same-Day Delivery</h5>
                </div>
                <div class="card-body">
                    <p>As long as Google Maps can compute a route to your address, we can dispatch on the same day for
                        orders placed before 2:00 PM (subject to product availability). The delivery fee shown at
                        checkout already factors in distance, traffic estimates, and our configured base charges.</p>
                    <div class="alert alert-info">
                        <strong>Tip:</strong> Save your delivery address inside your account profile so we can geocode it
                        ahead of time and quote shipping instantly at checkout.
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Delivery Areas</h5>
                </div>
                <div class="card-body">
                    <p>Our fleet dispatches from Bagumbayan, Gloria, Oriental Mindoro. If Google Maps can find a
                        drivable route to your location, we can cover it. For remote barrios or gated compounds, please
                        ensure your saved address includes landmarks so our courier can navigate smoothly.</p>
                    <ul>
                        <li>Standard window: 3–5 business days</li>
                        <li>Priority window: 1–2 business days (higher demand seasons may affect slots)</li>
                        <li>Same-day drop-offs for orders confirmed before 2:00 PM, subject to courier availability</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">International Shipping</h5>
                </div>
                <div class="card-body">
                    <p>International shipments are limited and quoted manually. Contact us so we can prepare a custom
                        route plan, transit time estimate, and carrier-specific fees.</p>
                    <p>All customs duties or clearance charges remain the responsibility of the recipient.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>