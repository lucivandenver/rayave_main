<?php
require_once 'includes/config.php';

// Update the Arrangements category with the image
$stmt = $pdo->prepare("UPDATE categories SET image = ? WHERE name = ?");
$result = $stmt->execute(['arrangements.jfif', 'Arrangements']);

if ($result) {
    echo "✓ Successfully updated Arrangements category image to: arrangements.jfif\n";
} else {
    echo "✗ Error updating category image\n";
}

// Show all categories to confirm
echo "\nCurrent categories:\n";
$cats = $pdo->query('SELECT id, name, image FROM categories')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cats as $c) {
    echo "  - {$c['name']}: " . ($c['image'] ?? 'No image') . "\n";
}
?>
