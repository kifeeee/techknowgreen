<?php
require_once 'config.php';
header('Content-Type: application/json');

function sendResponse($status, $message, $data = []) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

try {
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';

    if (empty($query)) {
        sendResponse('error', 'Search query is required', []);
    }

    // Prepare the search query to prevent SQL injection
    $searchTerm = '%' . $query . '%';
    $stmt = $pdo->prepare("SELECT id, title, description, price, category, image FROM products WHERE title LIKE ? OR description LIKE ?");
    $stmt->execute([$searchTerm, $searchTerm]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($products) {
        // Optionally add a URL field (e.g., for product detail pages)
        foreach ($products as &$product) {
            $product['url'] = '#'; // Placeholder; replace with actual product page URL if available
        }
        sendResponse('success', 'Products retrieved successfully', $products);
    } else {
        sendResponse('success', 'No products found', []);
    }

} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    sendResponse('error', 'Server error: Unable to process search', []);
}
?>