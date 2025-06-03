```php
<?php
require_once 'config.php';

// Fetch unique categories for the product form dropdown
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'category'");
    if ($stmt->rowCount() == 0) {
        error_log("Error: 'category' column not found in products table.");
        $categories = [];
        echo "<script>alert('Database error: Category column missing. Please contact the administrator.');</script>";
    } else {
        $stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
    echo "<script>alert('Error fetching categories: " . htmlspecialchars($e->getMessage()) . "');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - TechKnowGreen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f8f8;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .admin-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .admin-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .admin-header h1 {
            font-size: 2.5rem;
            color: #0ebbee;
            margin: 0;
        }
        .admin-form, .admin-table {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .admin-form h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 1rem;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-group button {
            background-color: #0ebbee;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.9rem;
            transition: background-color 0.3s;
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .form-group button:hover {
            background-color: #0a93b9;
        }
        .form-group button.loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid white;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 10px;
        }
        .admin-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table th, .admin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 0.9rem;
        }
        .admin-table th {
            background-color: #0ebbee;
            color: white;
        }
        .admin-table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .admin-table .actions a, .admin-table .actions button {
            margin-right: 10px;
            color: #0ebbee;
            cursor: pointer;
            text-decoration: none;
            background: none;
            border: none;
            font-size: 0.9rem;
        }
        .admin-table .actions a:hover, .admin-table .actions button:hover {
            color: #0a93b9;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
            font-size: 0.9rem;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .manage-btn, .close-btn {
            background-color: #0ebbee;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 20px;
            margin-right: 10px;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .close-btn {
            background-color: #dc3545;
        }
        .manage-btn:hover {
            background-color: #0a93b9;
        }
        .close-btn:hover {
            background-color: #c82333;
        }
        .no-products, .no-categories {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 1rem;
        }
        .category-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .category-input-group select, .category-input-group input {
            flex: 1;
        }
        .category-input-group input {
            display: none;
        }
        .category-input-group input.active {
            display: block;
        }
        .inline-edit-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
            width: 150px;
        }
        .inline-edit-buttons button {
            padding: 8px;
            margin-right: 5px;
            font-size: 0.8rem;
        }
        .inline-edit-buttons .save-btn {
            background-color: #28a745;
        }
        .inline-edit-buttons .save-btn:hover {
            background-color: #218838;
        }
        .inline-edit-buttons .cancel-btn {
            background-color: #6c757d;
        }
        .inline-edit-buttons .cancel-btn:hover {
            background-color: #5a6268;
        }
        .modal, .confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content, .confirm-modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .modal-content {
            margin-bottom: 20px;
        }
        .confirm-modal-content {
            max-width: 400px;
            text-align: center;
        }
        .modal-content h2, .confirm-modal-content h2 {
            margin-top: 0;
            font-size: 1.5rem;
            color: #0ebbee;
        }
        .modal-content .form-group {
            margin-bottom: 15px;
        }
        .modal-content .form-group label {
            font-weight: 500;
            margin-bottom: 5px;
            display: block;
        }
        .modal-content .form-group input,
        .modal-content .form-group textarea,
        .modal-content .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .modal-content .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .modal-content .form-group button {
            margin-right: 10px;
        }
        .modal-content .form-group .cancel-btn {
            background-color: #dc3545;
        }
        .modal-content .form-group .cancel-btn:hover {
            background-color: #c82333;
        }
        .modal-content .category-input-group {
            display: flex;
            gap: 10px;
        }
        .modal-content .category-input-group select,
        .modal-content .category-input-group input {
            flex: 1;
        }
        .modal-content .category-input-group input {
            display: none;
        }
        .modal-content .category-input-group input.active {
            display: block;
        }
        .confirm-modal-content p {
            margin: 15px 0;
            font-size: 1rem;
        }
        .confirm-modal-content .form-group button {
            margin: 0 10px;
        }
        .search-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            max-width: 400px;
            position: relative;
            background: linear-gradient(145deg, #ffffff, #e6e6e6);
            border-radius: 25px;
            padding: 5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .search-input {
            flex: 1;
            padding: 12px 15px 12px 20px;
            border: none;
            border-radius: 20px 0 0 20px;
            font-size: 0.9rem;
            background: transparent;
            outline: none;
            color: #333;
        }
        .search-input::placeholder {
            color: #999;
        }
        .search-input:focus {
            box-shadow: inset 0 0 5px rgba(14, 187, 238, 0.2);
        }
        .search-button {
            background-color: #0ebbee;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 0 20px 20px 0;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s, transform 0.2s;
        }
        .search-button:hover {
            background-color: #0a93b9;
            transform: scale(1.05);
        }
        .clear-search {
            position: absolute;
            right: 70px;
            top: 50%;
            transform: translateY(-50%);
            background: #f8d7da;
            border: none;
            color: #721c24;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 0.8rem;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
        .clear-search:hover {
            background-color: #dc3545;
            color: white;
        }
        .clear-search .active {
            display: flex;
        }
        .view-modal-content {
            max-width: 700px;
            text-align: center;
        }
        .view-modal-content img {
            width: 100%;
            max-width: 400px;
            height: auto;
            object-fit: contain;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .view-modal-content .product-details {
            text-align: left;
            margin-top: 20px;
        }
        .view-modal-content .product-details p {
            margin: 10px 0;
            font-size: 0.9rem;
        }
        .view-modal-content .product-details strong {
            color: #0ebbee;
        }
        /* Enhanced Loading Animation */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .loading-overlay.active {
            display: flex;
        }
        .loading-spinner {
            text-align: center;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #0ebbee;
            border-top: 4px solid transparent;
            border-radius: 50%;
            animation: spin 0.5s linear infinite;
            margin: 0 auto 10px;
        }
        .table-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .table-spinner.active {
            display: block;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
                margin: 10px;
            }
            .admin-header h1 {
                font-size: 1.8rem;
            }
            .form-group label {
                font-size: 0.9rem;
            }
            .form-group input, .form-group textarea, .form-group select {
                font-size: 0.85rem;
                padding: 8px;
            }
            .form-group button, .manage-btn, .close-btn {
                padding: 8px 15px;
                font-size: 0.85rem;
            }
            .admin-table th, .admin-table td {
                font-size: 0.85rem;
                padding: 8px;
            }
            .admin-table img {
                width: 40px;
                height: 40px;
            }
            .category-input-group {
                flex-direction: column;
                gap: 5px;
            }
            .category-input-group select, .category-input-group input {
                width: 100%;
            }
            .inline-edit-input {
                width: 100px;
            }
            .modal-content, .view-modal-content, .confirm-modal-content {
                width: 95%;
                padding: 15px;
            }
            .modal-content h2, .view-modal-content h2, .confirm-modal-content h2 {
                font-size: 1.2rem;
            }
            .search-container {
                max-width: 100%;
            }
            .search-input {
                padding: 10px 15px 10px 10px;
            }
            .search-button {
                padding: 10px 15px;
                font-size: 0.85rem;
            }
            .clear-search {
                right: 60px;
                width: 20px;
                height: 20px;
                font-size: 0.75rem;
            }
            .view-modal-content img {
                max-width: 300px;
            }
        }
        @media (max-width: 480px) {
            .admin-header h1 {
                font-size: 1.5rem;
            }
            .admin-form, .admin-table {
                padding: 15px;
            }
            .admin-table table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            .admin-table th, .admin-table td {
                min-width: 100px;
            }
            .form-group button, .manage-btn, .close-btn {
                width: 100%;
                margin-bottom: 10px;
            }
            .admin-table .actions a, .admin-table .actions button {
                display: inline-block;
                margin-bottom: 5px;
            }
            .inline-edit-input {
                width: 80px;
            }
            .inline-edit-buttons button {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
            .modal-content, .view-modal-content, .confirm-modal-content {
                width: 98%;
                padding: 10px;
            }
            .modal-content .form-group button {
                width: 100%;
                margin-bottom: 10px;
            }
            .search-container {
                max-width: 100%;
                padding: 4px;
            }
            .search-input {
                padding: 8px 10px;
                font-size: 0.8rem;
            }
            .search-button {
                padding: 8px 12px;
                font-size: 0.8rem;
            }
            .clear-search {
                right: 50px;
                width: 18px;
                height: 18px;
                font-size: 0.7rem;
            }
            .view-modal-content img {
                max-width: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>TechKnowGreen Admin Panel</h1>
        </div>
        <div class="message" id="message"></div>

        <!-- Loading Overlay -->
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p id="loadingMessage">Processing...</p>
            </div>
        </div>

        <!-- Product Management -->
        <div class="admin-form">
            <h2 id="productFormTitle">Add New Product</h2>
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" id="productId" name="id">
                <div class="form-group">
                    <label for="productTitle">Title</label>
                    <input type="text" id="productTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="productDescription">Description</label>
                    <textarea id="productDescription" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="productPrice">Price (KSh)</label>
                    <input type="number" id="productPrice" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="productCategory">Category</label>
                    <div class="category-input-group">
                        <select id="productCategory" name="categorySelect">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="new">Add New Category</option>
                        </select>
                        <input type="text" id="newCategoryInput" name="newCategory" placeholder="Enter new category">
                    </div>
                </div>
                <div class="form-group">
                    <label for="productImage">Image</label>
                    <input type="file" id="productImage" name="image" accept="image/*" required>
                    <input type="hidden" id="productExistingImage" name="existing_image">
                </div>
                <div class="form-group">
                    <label for="isTopRated">Top Rated</label>
                    <input type="checkbox" id="isTopRated" name="is_top_rated">
                </div>
                <div class="form-group">
                    <button type="submit" id="addProductBtn">Add Product</button>
                </div>
            </form>
        </div>
        <div class="admin-table">
            <h2>Manage Products</h2>
            <div class="search-container">
                <input type="text" class="search-input" id="productSearch" placeholder="Search products by title or description...">
                <button class="clear-search" id="clearSearch"><i class="fas fa-times"></i></button>
                <button class="search-button" id="searchBtn"><i class="fas fa-search"></i> Search</button>
            </div>
            <button class="manage-btn" id="loadProductsBtn" onclick="loadProducts()">Load Products</button>
            <button class="close-btn" onclick="clearProducts()" style="display: none;" id="closeProductsBtn">Close Products</button>
            <div class="table-spinner" id="productTableSpinner">
                <div class="spinner"></div>
                <p>Loading products...</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Category</th>
                        <th>Image</th>
                        <th>Top Rated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productTable">
                    <tr><td colspan="7" class="no-products">Click "Load Products" to view products.</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Edit Product Modal -->
        <div class="modal" id="editProductModal">
            <div class="modal-content">
                <h2>Edit Product</h2>
                <form id="editProductForm" enctype="multipart/form-data">
                    <input type="hidden" id="editProductId" name="id">
                    <div class="form-group">
                        <label for="editProductTitle">Title</label>
                        <input type="text" id="editProductTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="editProductDescription">Description</label>
                        <textarea id="editProductDescription" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="editProductPrice">Price (KSh)</label>
                        <input type="number" id="editProductPrice" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="editProductCategory">Category</label>
                       [PROOFSection>
                        <div class="category-input-group">
                            <select id="editProductCategory" name="categorySelect">
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>">
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="new">Add New Category</option>
                            </select>
                            <input type="text" id="editNewCategoryInput" name="newCategory" placeholder="Enter new category">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editProductImage">Image</label>
                        <input type="file" id="editProductImage" name="image" accept="image/*">
                        <input type="hidden" id="editProductExistingImage" name="existing_image">
                        <img id="editProductImagePreview" src="" alt="Current Image" style="display: none; width: 100px; height: 100px; margin-top: 10px; border-radius: 5px;">
                    </div>
                    <div class="form-group">
                        <label for="editIsTopRated">Top Rated</label>
                        <input type="checkbox" id="editIsTopRated" name="is_top_rated">
                    </div>
                    <div class="form-group">
                        <button type="submit" id="editProductBtn">Save Changes</button>
                        <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Product Modal -->
        <div class="modal" id="viewProductModal">
            <div class="modal-content view-modal-content">
                <h2>Product Details</h2>
                <img id="viewProductImage" src="" alt="Product Image">
                <div class="product-details">
                    <p><strong>Title:</strong> <span id="viewProductTitle"></span></p>
                    <p><strong>Description:</strong> <span id="viewProductDescription"></span></p>
                    <p><strong>Price:</strong> KSh <span id="viewProductPrice"></span></p>
                    <p><strong>Category:</strong> <span id="viewProductCategory"></span></p>
                    <p><strong>Top Rated:</strong> <span id="viewIsTopRated"></span></p>
                </div>
                <div class="form-group">
                    <button type="button" class="cancel-btn" onclick="closeViewModal()">Close</button>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div class="confirm-modal" id="confirmModal">
            <div class="confirm-modal-content">
                <h2>Confirm Action</h2>
                <p id="confirmMessage"></p>
                <div class="form-group">
                    <button type="button" id="confirmYes" class="manage-btn">Yes</button>
                    <button type="button" id="confirmNo" class="cancel-btn">No</button>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <div class="confirm-modal" id="successModal">
            <div class="confirm-modal-content">
                <h2>Success</h2>
                <p id="successMessage"></p>
                <div class="form-group">
                    <button type="button" id="successOk" class="manage-btn">OK</button>
                </div>
            </div>
        </div>

        <!-- Category Management -->
        <div class="admin-table">
            <h2>Manage Categories</h2>
            <button class="manage-btn" id="loadCategoriesBtn" onclick="loadCategories()">Load Categories</button>
            <button class="close-btn" onclick="clearCategories()" style="display: none;" id="closeCategoriesBtn">Close Categories</button>
            <div class="table-spinner" id="categoryTableSpinner">
                <div class="spinner"></div>
                <p>Loading categories...</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Product Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="categoryTable">
                    <tr><td colspan="3" class="no-categories">Click "Load Categories" to view categories.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        // Utility: Debounce function
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

        // Show message
        function showMessage(message, type) {
            const msgDiv = document.getElementById('message');
            msgDiv.textContent = message;
            msgDiv.className = `message ${type}`;
            msgDiv.style.display = 'block';
            setTimeout(() => msgDiv.style.display = 'none', 3000);
        }

        // Show loading overlay
        function showLoading(message = 'Processing...') {
            const overlay = document.getElementById('loadingOverlay');
            const messageEl = document.getElementById('loadingMessage');
            messageEl.textContent = message;
            overlay.classList.add('active');
        }

        // Hide loading overlay
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        // Show confirmation modal
        function showConfirmModal(message, onConfirm) {
            const modal = document.getElementById('confirmModal');
            const confirmMessage = document.getElementById('confirmMessage');
            const confirmYes = document.getElementById('confirmYes');
            const confirmNo = document.getElementById('confirmNo');

            confirmMessage.textContent = message;
            modal.style.display = 'flex';

            confirmYes.onclick = () => {
                onConfirm();
                modal.style.display = 'none';
            };
            confirmNo.onclick = () => {
                modal.style.display = 'none';
            };
        }

        // Show success modal
        function showSuccessModal(message) {
            const modal = document.getElementById('successModal');
            const successMessage = document.getElementById('successMessage');
            const successOk = document.getElementById('successOk');

            successMessage.textContent = message;
            modal.style.display = 'flex';

            successOk.onclick = () => {
                modal.style.display = 'none';
            };
        }

        // Compress image client-side
        function compressImage(input, callback) {
            const file = input.files[0];
            if (!file) return callback(null);
            const img = new Image();
            img.src = URL.createObjectURL(file);
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const maxWidth = 600;
                const scale = maxWidth / img.width;
                canvas.width = maxWidth;
                canvas.height = img.height * scale;
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                canvas.toBlob((blob) => {
                    const compressedFile = new File([blob], file.name, { type: 'image/webp', lastModified: Date.now() });
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(compressedFile);
                    input.files = dataTransfer.files;
                    callback(compressedFile);
                }, 'image/webp', 0.8);
            };
            img.onerror = () => callback(null);
        }

        // Product Management
        let allProducts = [];

        function loadProducts(query = '') {
            const tableBody = document.getElementById('productTable');
            const closeBtn = document.getElementById('closeProductsBtn');
            const spinner = document.getElementById('productTableSpinner');
            const loadBtn = document.getElementById('loadProductsBtn');
            tableBody.innerHTML = '';
            spinner.classList.add('active');
            closeBtn.style.display = 'inline-block';
            loadBtn.disabled = true;

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            fetch('products.php?action=list', { signal: controller.signal })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    spinner.classList.remove('active');
                    loadBtn.disabled = false;
                    tableBody.innerHTML = '';
                    if (data.status === 'success' && data.data.length > 0) {
                        allProducts = data.data;
                        const filteredProducts = query
                            ? allProducts.filter(product =>
                                  product.title.toLowerCase().includes(query.toLowerCase()) ||
                                  product.description.toLowerCase().includes(query.toLowerCase())
                              )
                            : allProducts;

                        if (filteredProducts.length > 0) {
                            filteredProducts.forEach((product, index) => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${index + 1}</td>
                                    <td>${product.title}</td>
                                    <td>KSh ${parseFloat(product.price).toFixed(2)}</td>
                                    <td>${product.category}</td>
                                    <td><img src="${product.image}" alt="${product.title}"></td>
                                    <td>${product.is_top_rated ? 'Yes' : 'No'}</td>
                                    <td class="actions">
                                        <a href="#" onclick="viewProduct(${product.id}); return false;"><i class="fas fa-eye"></i> View</a>
                                        <a href="#" onclick="editProduct(${product.id}); return false;"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="#" onclick="deleteProduct(${product.id}, '${product.title}'); return false;"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                `;
                                tableBody.appendChild(row);
                            });
                        } else {
                            tableBody.innerHTML = '<tr><td colspan="7" class="no-products">No products match your search.</td></tr>';
                        }
                    } else {
                        allProducts = [];
                        tableBody.innerHTML = '<tr><td colspan="7" class="no-products">No products found.</td></tr>';
                    }
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    spinner.classList.remove('active');
                    loadBtn.disabled = false;
                    console.error('Error loading products:', error);
                    tableBody.innerHTML = '<tr><td colspan="7" class="no-products">Failed to load products: ' + error.message + '</td></tr>';
                    showMessage('Failed to load products: ' + error.message, 'error');
                });
        }

        function clearProducts() {
            const tableBody = document.getElementById('productTable');
            const closeBtn = document.getElementById('closeProductsBtn');
            const searchInput = document.getElementById('productSearch');
            const clearSearchBtn = document.getElementById('clearSearch');
            tableBody.innerHTML = '<tr><td colspan="7" class="no-products">Click "Load Products" to view products.</td></tr>';
            closeBtn.style.display = 'none';
            searchInput.value = '';
            clearSearchBtn.classList.remove('active');
            allProducts = [];
        }

        // Search Functionality
        const searchInput = document.getElementById('productSearch');
        const clearSearchBtn = document.getElementById('clearSearch');
        const searchButton = document.getElementById('searchBtn');

        function performSearch() {
            const query = searchInput.value.trim();
            clearSearchBtn.classList.toggle('active', query.length > 0);
            loadProducts(query);
        }

        searchInput.addEventListener('input', debounce(performSearch, 300));
        searchButton.addEventListener('click', performSearch);

        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            clearSearchBtn.classList.remove('active');
            loadProducts();
            searchInput.focus();
        });

        function viewProduct(id) {
            showLoading('Fetching product...');
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            fetch(`products.php?action=get&id=${id}`, { signal: controller.signal })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    hideLoading();
                    if (data.status === 'success') {
                        const product = data.data;
                        document.getElementById('viewProductImage').src = product.image;
                        document.getElementById('viewProductImage').alt = product.title;
                        document.getElementById('viewProductTitle').textContent = product.title;
                        document.getElementById('viewProductDescription').textContent = product.description;
                        document.getElementById('viewProductPrice').textContent = parseFloat(product.price).toFixed(2);
                        document.getElementById('viewProductCategory').textContent = product.category;
                        document.getElementById('viewIsTopRated').textContent = product.is_top_rated ? 'Yes' : 'No';
                        document.getElementById('viewProductModal').style.display = 'flex';
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    clearTimeout(timeoutId);
                    console.error('Error fetching product:', error);
                    showMessage('Failed to fetch product: ' + error.message, 'error');
                });
        }

        function closeViewModal() {
            document.getElementById('viewProductModal').style.display = 'none';
        }

        function editProduct(id) {
            showLoading('Fetching product...');
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            fetch(`products.php?action=get&id=${id}`, { signal: controller.signal })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    hideLoading();
                    if (data.status === 'success') {
                        const product = data.data;
                        document.getElementById('editProductId').value = product.id;
                        document.getElementById('editProductTitle').value = product.title;
                        document.getElementById('editProductDescription').value = product.description;
                        document.getElementById('editProductPrice').value = product.price;
                        document.getElementById('editProductCategory').value = product.category;
                        document.getElementById('editNewCategoryInput').classList.remove('active');
                        document.getElementBy Resolve('editNewCategoryInput').value = '';
                        document.getElementById('editProductExistingImage').value = product.image;
                        document.getElementById('editProductImagePreview').src = product.image;
                        document.getElementById('editProductImagePreview').style.display = 'block';
                        document.getElementById('editIsTopRated').checked = product.is_top_rated == 1;
                        document.getElementById('editProductImage').required = false;
                        document.getElementById('editProductModal').style.display = 'flex';
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    clearTimeout(timeoutId);
                    console.error('Error fetching product:', error);
                    showMessage('Failed to fetch product: ' + error.message, 'error');
                });
        }

        function closeModal() {
            document.getElementById('editProductModal').style.display = 'none';
            document.getElementById('editProductForm').reset();
            document.getElementById('editProductImagePreview').style.display = 'none';
            document.getElementById('editNewCategoryInput').classList.remove('active');
            document.getElementById('editProductBtn').classList.remove('loading');
        }

        document.getElementById('editProductForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const categorySelect = document.getElementById('editProductCategory');
            const newCategoryInput = document.getElementById('editNewCategoryInput');
            const editBtn = document.getElementById('editProductBtn');
            let category = categorySelect.value;
            if (category === 'new') {
                category = newCategoryInput.value.trim();
                if (!category) {
                    showMessage('Please enter a new category name', 'error');
                    return;
                }
            } else if (!category) {
                showMessage('Please select a category', 'error');
                return;
            }
            formData.set('category', category);
            showLoading('Updating product...');
            editBtn.classList.add('loading');
            editBtn.disabled = true;

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            fetch('products.php?action=edit', {
                method: 'POST',
                body: formData,
                signal: controller.signal
            })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    hideLoading();
                    editBtn.classList.remove('loading');
                    editBtn.disabled = false;
                    if (data.status === 'success') {
                        showSuccessModal('Product updated successfully!');
                        loadProducts(searchInput.value.trim());
                        closeModal();
                        updateCategoryDropdown();
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    clearTimeout(timeoutId);
                    editBtn.classList.remove('loading');
                    editBtn.disabled = false;
                    console.error('Error updating product:', error);
                    showMessage('Failed to update product: ' + error.message, 'error');
                });
        });

        function deleteProduct(id, title) {
            showConfirmModal(`Are you sure you want to delete the product "${title}"?`, () => {
                showLoading('Deleting product...');
                const formData = new FormData();
                formData.append('id', id);

                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);

                fetch('products.php?action=delete', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                })
                    .then(response => {
                        clearTimeout(timeoutId);
                        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        hideLoading();
                        if (data.status === 'success') {
                            showSuccessModal('Product deleted successfully!');
                            loadProducts(searchInput.value.trim());
                        } else {
                            showMessage(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        clearTimeout(timeoutId);
                        console.error('Error deleting product:', error);
                        showMessage('Failed to delete product: ' + error.message, 'error');
                    });
            });
        }

        document.getElementById('productForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const id = document.getElementById('productId').value;
            const url = id ? 'products.php?action=edit' : 'products.php?action=add';
            const addBtn = document.getElementById('addProductBtn');

            // Handle category
            const categorySelect = document.getElementById('productCategory');
            const newCategoryInput = document.getElementById('newCategoryInput');
            let category = categorySelect.value;
            if (category === 'new') {
                category = newCategoryInput.value.trim();
                if (!category) {
                    showMessage('Please enter a new category name', 'error');
                    return;
                }
            } else if (!category) {
                showMessage('Please select a category', 'error');
                return;
            }
            formData.append('category', category);
            showLoading(id ? 'Updating product...' : 'Adding product...');
            showBtn.classList('loading');
            showBtn.disabled = true;

            const controller = new AbortController();
            showTimeout(timeoutId => setTimeout(() => controller.abort(), '10000'));

            fetch(url, {
                method: 'POST',
                body: formData,
                signal: controller.signal
            })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    hideLoading();
                    addBtn.classList.remove('loading');
                    addBtn.disabled = false;
                    if (data.status === 'success') {
                        showSuccessModal(id ? 'Product updated successfully!' : 'Product added successfully!');
                        loadProducts(searchInput.value.trim());
                        e.target.reset();
                        document.getElementById('productFormTitle').textContent = 'Add New Product';
                        document.getElementById('productId').value = '';
                        document.getElementById('productExistingImage').value = '';
                        document.getElementById('newCategoryInput').classList.remove('active');
                        document.getElementById('productCategory').value = '';
                        document.getElementById('productImage').required = true;
                        document.getElementById('isTopRated').checked = false;
                        updateCategoryDropdown();
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    clearTimeout(timeoutId);
                    addBtn.classList.remove('loading');
                    addBtn.disabled = false;
                    console.error('Error submitting form:', error);
                    showMessage('Failed to process request: ' + error.message, 'error');
                });
        });

        // Image compression for product form
        document.getElementById('productImage').addEventListener('change', (e) => {
            compressImage(e.target, (file) => {
                if (!file) {
                    showMessage('Failed to compress image', 'error');
                    e.target.value = '';
                }
            });
        });

        // Image compression for edit form
        document.getElementById('editProductImage').addEventListener('change', (e) => {
            compressImage(e.target, (file) => {
                if (!file) {
                    showMessage('Failed to compress image', 'error');
                    e.target.value = '';
                }
            });
        });

        // Category Management
        let editingCategory = null;

        function loadCategories() {
            const tableBody = document.getElementById('categoryTable');
            const closeBtn = document.getElementById('closeCategoriesBtn');
            const spinner = document.getElementById('categoryTableSpinner');
            const loadBtn = document.getElementById('loadCategoriesBtn');
            tableBody.innerHTML = '';
            spinner.classList.add('active');
            closeBtn.style.display = 'inline-block';
            loadBtn.disabled = true;

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            fetch('products.php?action=list_categories', { signal: controller.signal })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    spinner.classList.remove('active');
                    loadBtn.disabled = false;
                    tableBody.innerHTML = '';
                    if (data.status === 'success' && data.data.length > 0) {
                        data.data.forEach(category => {
                            fetch('products.php?action=list', { signal: controller.signal })
                                .then(res => res.json())
                                .then(productData => {
                                    const productCount = productData.data.filter(p => p.category === category).length;
                                    const row = document.createElement('tr');
                                    row.setAttribute('data-category', category);
                                    row.innerHTML = `
                                        <td class="category-name">${category}</td>
                                        <td>${productCount}</td>
                                        <td class="actions">
                                            <button onclick="editCategory('${category}', this)"><i class="fas fa-edit"></i> Edit</button>
                                            <button onclick="deleteCategory('${category}')"><i class="fas fa-trash"></i> Delete</button>
                                            <button onclick="viewCategory('${category}', ${productCount})"><i class="fas fa-eye"></i> View</button>
                                        </td>
                                    `;
                                    tableBody.appendChild(row);
                                });
                        });
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="3" class="no-categories">No categories found.</td></tr>';
                    }
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    spinner.classList.remove('active');
                    loadBtn.disabled = false;
                    console.error('Error loading categories:', error);
                    tableBody.innerHTML = '<tr><td colspan="3" class="no-categories">Failed to load categories: ' + error.message + '</td></tr>';
                    showMessage('Failed to load categories: ' + error.message, 'error');
                });
        }

        function clearCategories() {
            const tableBody = document.getElementById('categoryTable');
            const closeBtn = document.getElementById('closeCategoriesBtn');
            tableBody.innerHTML = '<tr><td colspan="3" class="no-categories">Click "Load Categories" to view categories.</td></tr>';
            closeBtn.style.display = 'none';
            editingCategory = null;
        }

        function editCategory(category, button) {
            if (editingCategory) {
                showMessage('Please save or cancel the current edit before starting a new one.', 'error');
                return;
            }
            editingCategory = category;
            const row = button.closest('tr');
            const nameCell = row.querySelector('.category-name');
            const actionsCell = row.querySelector('.actions');
            const originalName = nameCell.textContent;

            nameCell.innerHTML = `<input type="text" class="inline-edit-input" value="${originalName}" />`;
            actionsCell.innerHTML = `
                <div class="inline-edit-buttons">
                    <button class="save-btn" id="saveCategoryBtn" onclick="saveCategoryEdit('${originalName}', this)">Save</button>
                    <button class="cancel-btn" onclick="cancelCategoryEdit('${originalName}', this)">Cancel</button>
                </div>
            `;
        }

        function saveCategoryEdit(oldCategory, button) {
            const row = button.closest('tr');
            const input = row.querySelector('.inline-edit-input');
            const newCategory = input.value.trim();
            const saveBtn = document.getElementById('saveCategoryBtn');

            if (!newCategory) {
                showMessage('Category name is required', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('old_category', oldCategory);
            formData.append('new_category', newCategory);
            showLoading('Updating category...');
            saveBtn.classList.add('loading');
            saveBtn.disabled = true;

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            fetch('products.php?action=edit_category', {
                method: 'POST',
                body: formData,
                signal: controller.signal
            })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    hideLoading();
                    saveBtn.classList.remove('loading');
                    saveBtn.disabled = false;
                    if (data.status === 'success') {
                        showSuccessModal('Category updated successfully!');
                        loadCategories();
                        updateCategoryDropdown();
                    } else {
                        showMessage(data.message, 'error');
                        cancelCategoryEdit(oldCategory, button);
                    }
                    editingCategory = null;
                })
                .catch(error => {
                    hideLoading();
                    clearTimeout(timeoutId);
                    saveBtn.classList.remove('loading');
                    saveBtn.disabled = false;
                    console.error('Error updating category:', error);
                    showMessage('Failed to update category: ' + error.message, 'error');
                    cancelCategoryEdit(oldCategory, button);
                    editingCategory = null;
                });
        }

        function cancelCategoryEdit(originalName, button) {
            const row = button.closest('tr');
            const nameCell = row.querySelector('.category-name');
            const actionsCell = row.querySelector('.actions');

            nameCell.textContent = originalName;
            actionsCell.innerHTML = `
                <button onclick="editCategory('${originalName}', this)"><i class="fas fa-edit"></i> Edit</button>
                <button onclick="deleteCategory('${originalName}')"><i class="fas fa-trash"></i> Delete</button>
                <button onclick="viewCategory('${originalName}', ${row.querySelector('td:nth-child(2)').textContent})"><i class="fas fa-eye"></i> View</button>
            `;
            editingCategory = null;
        }

        function deleteCategory(category) {
            showConfirmModal(`Are you sure you want to delete the category "${category}"?`, () => {
                showLoading('Deleting category...');
                const formData = new FormData();
                formData.append('category', category);

                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);

                fetch('products.php?action=delete_category', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                })
                    .then(response => {
                        clearTimeout(timeoutId);
                        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        hideLoading();
                        if (data.status === 'success') {
                            showSuccessModal('Category deleted successfully!');
                            loadCategories();
                            updateCategoryDropdown();
                        } else {
                            showMessage(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        clearTimeout(timeoutId);
                        console.error('Error deleting category:', error);
                        showMessage('Failed to delete category: ' + error.message, 'error');
                    });
            });
        }

        function viewCategory(category, productCount) {
            alert(`Category: ${category}\nProducts: ${productCount}`);
        }

        // Update category dropdown dynamically
        function updateCategoryDropdown() {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            fetch('products.php?action=list_categories', { signal: controller.signal })
                .then(response => {
                    clearTimeout(timeoutId);
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    const categorySelect = document.getElementById('productCategory');
                    const edit