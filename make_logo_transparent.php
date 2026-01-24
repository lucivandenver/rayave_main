<?php
// Simple script to convert white (or near-white) background to transparent using GD
$src = __DIR__ . '/assets/images/products/FB_IMG_1762963948008.jpg';
$dst = __DIR__ . '/assets/images/products/FB_IMG_1762963948008_transparent.png';

if (!file_exists($src)) {
    echo "Source file not found: $src";
    exit;
}

// Load source image
$img = @imagecreatefromjpeg($src);
if (!$img) {
    echo "Failed to load JPEG. Make sure GD is enabled and file is a valid JPEG.";
    exit;
}

$w = imagesx($img);
$h = imagesy($img);

// Create destination image with alpha
$dstImg = imagecreatetruecolor($w, $h);
imagesavealpha($dstImg, true);
$transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
imagefill($dstImg, 0, 0, $transparent);

// Threshold for near-white detection (0-255). Lower is stricter.
$threshold = 245;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        // If pixel is near white, make it transparent
        if ($r >= $threshold && $g >= $threshold && $b >= $threshold) {
            // leave as transparent
            // nothing to do, background already transparent
        } else {
            // copy pixel
            $color = imagecolorallocatealpha($dstImg, $r, $g, $b, 0);
            imagesetpixel($dstImg, $x, $y, $color);
        }
    }
}

// Save PNG
if (imagepng($dstImg, $dst)) {
    echo "Saved transparent PNG: " . basename($dst);
} else {
    echo "Failed to save PNG.";
}

imagedestroy($img);
imagedestroy($dstImg);
?>
