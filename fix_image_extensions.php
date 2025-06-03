```php
<?php
require_once 'config.php';

try {
    // Fetch all products with image paths
    $stmt = $pdo->query("SELECT id, image FROM products WHERE image IS NOT NULL");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Starting image extension normalization...\n";

    foreach ($products as $product) {
        $id = $product['id'];
        $image_path = $product['image'];
        $extension = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));

        // Check if the extension needs normalization
        if (in_array($extension, ['jpg', 'jpeg'])) {
            $new_extension = 'jpg';
            $new_image_path = preg_replace('/\.(jpg|jpeg|JPG|JPEG)$/i', '.' . $new_extension, $image_path);

            // Update file on disk
            if ($image_path !== $new_image_path && file_exists($image_path)) {
                if (rename($image_path, $new_image_path)) {
                    echo "Renamed file: $image_path -> $new_image_path\n";
                } else {
                    echo "Failed to rename file: $image_path\n";
                    continue; // Skip database update if file rename fails
                }
            }

            // Update database
            if ($image_path !== $new_image_path) {
                $update_stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
                $update_stmt->execute([$new_image_path, $id]);
                echo "Updated database for product ID $id: $image_path -> $new_image_path\n";
            }
        }
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    error_log("Migration error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>
```