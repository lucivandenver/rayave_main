<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="display-4 text-center mb-5">Frequently Asked Questions</h1>

            <div class="accordion" id="faqAccordion">
                <!-- Ordering Questions -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseOne">
                            How do I place an order?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            You can place an order through our website by browsing our products, adding items to your
                            cart, and proceeding through checkout. You'll need to create an account or log in to
                            complete your purchase.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseTwo">
                            What payment methods do you accept?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            We accept all major credit cards (Visa, MasterCard, American Express), PayPal, and Apple
                            Pay. We also offer cash on delivery for local orders.
                        </div>
                    </div>
                </div>

                <!-- Delivery Questions -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseThree">
                            Do you offer same-day delivery?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes! We offer same-day delivery for orders placed before 2:00 PM local time. Orders placed
                            after 2:00 PM will be delivered the next business day.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseFour">
                            What are your delivery hours?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            We deliver from 9:00 AM to 7:00 PM, Monday through Friday, and 10:00 AM to 6:00 PM on
                            Saturdays. We do not deliver on Sundays.
                        </div>
                    </div>
                </div>

                <!-- Product Questions -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFive">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseFive">
                            How fresh are your flowers?
                        </button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            We source our flowers daily from local growers and international markets. All arrangements
                            are made fresh on the day of delivery to ensure maximum freshness and longevity.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingSix">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseSix">
                            Can I customize my flower arrangement?
                        </button>
                    </h2>
                    <div id="collapseSix" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Absolutely! You can add a custom message with any order. For completely custom arrangements,
                            please contact us directly at least 48 hours in advance.
                        </div>
                    </div>
                </div>

                <!-- Account Questions -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingSeven">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseSeven">
                            How do I track my order?
                        </button>
                    </h2>
                    <div id="collapseSeven" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            You can track your order by logging into your account and visiting the "Order Tracking"
                            page. You'll receive email updates at key stages of the delivery process.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingEight">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseEight">
                            What if I'm not home for delivery?
                        </button>
                    </h2>
                    <div id="collapseEight" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            If you're not home, our delivery driver will attempt to leave the package in a safe, shaded
                            location. For perishable items, we may contact you to arrange an alternative delivery time.
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <p class="lead">Still have questions?</p>
                <a href="contact.php" class="btn btn-success btn-lg">Contact Us</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>