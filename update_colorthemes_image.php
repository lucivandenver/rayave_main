<?php
require_once 'includes/config.php';

// Update the Color Themes category with the image
$stmt = $pdo->prepare("UPDATE categories SET image = ? WHERE name = ?");
$result = $stmt->execute(['colorthemes.webp', 'Color Themes']);

if ($result) {
    echo "✓ Successfully updated Color Themes category image to: colorthemes.webp\n";
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
