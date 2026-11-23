<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get the slug from query parameter or URL path
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;

// If no slug provided, redirect to home
if (!$slug) {
    redirect('index.php');
}

// Fetch the page
$page = getPageBySlug($slug);

if (!$page) {
    http_response_code(404);
    $_SESSION['error'] = 'Page not found.';
    redirect('index.php');
}

$_PAGE_TITLE = htmlspecialchars($page['title']);

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <article>
                <header class="mb-4">
                    <h1 class="mb-2"><?php echo htmlspecialchars($page['title']); ?></h1>
                    <small class="text-muted">Last updated: <?php echo htmlspecialchars($page['updated_at']); ?></small>
                </header>

                <div class="page-content">
                    <?php echo nl2br(htmlspecialchars($page['content'])); ?>
                </div>
            </article>

            <hr class="my-5">

            <div class="text-center">
                <a href="index.php" class="btn btn-outline-primary">Back to Home</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
