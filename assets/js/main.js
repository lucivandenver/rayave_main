// Main JavaScript for Bloomify Flower Shop

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Product quantity controls
    const quantityInputs = document.querySelectorAll('input[name="quantity"]');
    quantityInputs.forEach(function(input) {
        const minusBtn = input.previousElementSibling;
        const plusBtn = input.nextElementSibling;

        if (minusBtn && minusBtn.classList.contains('quantity-minus')) {
            minusBtn.addEventListener('click', function() {
                const currentValue = parseInt(input.value);
                if (currentValue > parseInt(input.min)) {
                    input.value = currentValue - 1;
                }
            });
        }

        if (plusBtn && plusBtn.classList.contains('quantity-plus')) {
            plusBtn.addEventListener('click', function() {
                const currentValue = parseInt(input.value);
                if (currentValue < parseInt(input.max)) {
                    input.value = currentValue + 1;
                }
            });
        }
    });

    // Add to cart animations
    const addToCartForms = document.querySelectorAll('form[action="add-to-cart.php"]');
    addToCartForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const button = form.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<span class="loading-spinner"></span> Adding...';
            button.disabled = true;
            
            setTimeout(function() {
                button.innerHTML = '<i class="fas fa-check"></i> Added!';
                setTimeout(function() {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
            }, 1000);
        });
    });

    // Search functionality
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                // Perform live search if needed
                console.log('Searching for:', searchInput.value);
            }, 500);
        });
    }

    // Price range filter
    const priceInputs = document.querySelectorAll('input[name="min_price"], input[name="max_price"]');
    priceInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const minPrice = document.querySelector('input[name="min_price"]').value;
            const maxPrice = document.querySelector('input[name="max_price"]').value;
            
            if (minPrice && maxPrice && parseFloat(minPrice) > parseFloat(maxPrice)) {
                alert('Minimum price cannot be greater than maximum price');
                input.value = '';
            }
        });
    });

    // Image lazy loading
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Form validation enhancement
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Scroll to first invalid field
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstInvalid.focus();
                }
            }
        });
    });

    // Cart count update
    function updateCartCount() {
        fetch('get-cart-count.php')
            .then(response => response.json())
            .then(data => {
                const cartBadge = document.querySelector('.cart-count-badge');
                if (cartBadge) {
                    cartBadge.textContent = data.count;
                    if (data.count > 0) {
                        cartBadge.style.display = 'inline-block';
                    } else {
                        cartBadge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error updating cart count:', error));
    }

    // Update cart count every 30 seconds
    setInterval(updateCartCount, 30000);

    // Newsletter subscription
    const newsletterForm = document.querySelector('form[action*="newsletter"]');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            submitButton.innerHTML = '<span class="loading-spinner"></span> Subscribing...';
            submitButton.disabled = true;

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    submitButton.innerHTML = '<i class="fas fa-check"></i> Subscribed!';
                    this.reset();
                } else {
                    submitButton.innerHTML = '<i class="fas fa-times"></i> Error';
                }
                
                setTimeout(() => {
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                submitButton.innerHTML = '<i class="fas fa-times"></i> Error';
                setTimeout(() => {
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                }, 3000);
            });
        });
    }

    // Product image zoom
    const productImages = document.querySelectorAll('.product-card img');
    productImages.forEach(img => {
        img.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
        });
        
        img.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    console.log('RaymundoAvenue Flower Shop initialized successfully!');
});

// Utility functions
function formatPrice(price) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}