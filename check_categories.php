<?php
require_once 'includes/config.php';

$cats = $pdo->query('SELECT id, name, image FROM categories')->fetchAll(PDO::FETCH_ASSOC);

echo "Categories in database:\n";
echo "======================\n";
foreach ($cats as $c) {
    echo "ID: {$c['id']} | Name: {$c['name']} | Image: {$c['image']}\n";
}
?>
