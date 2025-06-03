<?php
session_start();
include 'config.php';

try {
    $stmt = $pdo->query("SELECT * FROM products WHERE is_top_rated = FALSE");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM products WHERE is_top_rated = TRUE LIMIT 6");
    $related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

$categoryDisplayNames = [
    'solar' => 'Solar Products',
    'Inverters' => 'Inverters',
    'smart_wify' => 'Smart WiFi',
    'water_pump' => 'Water Pumps',
    'heat_pumps' => 'Heat Pumps',
    'Tools' => 'Tools',
    'VFDs' => 'VFDs',
    'Electrical' => 'Electricals',
    'Sensors' => 'Sensors',
    'adds' => 'Miscellaneous'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TECHKNOWGREEN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="stylesheet.css">
    <style>
        /* Existing styles remain unchanged, updating cart modal styles */
        .product-grid {
            display: flex;
            flex-wrap: nowrap;
            gap: 15px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            width: 100%;
            padding-bottom: 20px;
        }
        .product-grid::-webkit-scrollbar {
            height: 8px;
        }
        .product-grid::-webkit-scrollbar-thumb {
            background: #0ebbee;
            border-radius: 4px;
        }
        .product-card {
            flex: 0 0 auto;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            width: 220px;
            scroll-snap-align: start;
            cursor: pointer;
        }
        .product-card.hidden {
            display: none;
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        .product-image {
            height: 160px;
            background-size: cover;
            background-position: center;
            image-rendering: crisp-edges;
        }
        .product-details {
            padding: 15px;
        }
        .product-title {
            font-size: 16px;
            margin-bottom: 8px;
        }
        .product-description {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-price {
            color: #13aede;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 12px;
        }
        .btn-cart {
            display: block;
            text-align: center;
            background-color: #daeefc;
            color: #333;
            padding: 8px;
            border-radius: 5px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-cart:hover {
            background-color: #0ebbee;
            color: white;
        }
        .top-rated {
            background-color: #f1f1f1;
            padding: 60px 5%;
            width: 100%;
        }
        .top-rated-grid {
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 30px;
            width: 100%;
        }
        .featured-products {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding-bottom: 20px;
        }
        .featured-products .product-card {
            width: 100%;
        }
        .sidebar-title {
            font-size: 24px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        .sidebar-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #0ebbee;
        }
        .sidebar-product {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .sidebar-product:hover {
            transform: scale(1.05);
        }
        .sidebar-product-image {
            width: 80px;
            height: 80px;
            background-size: cover;
            background-position: center;
            margin-right: 15px;
            border-radius: 5px;
            image-rendering: crisp-edges;
        }
        .sidebar-product-details h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .sidebar-product-details .price {
            color: #0ebbee;
            font-weight: 14px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            gap: 15px;
        }
        .cart-item-image {
            width: 150px;
            height: 150px;
            background-size: cover;
            background-position: center;
            margin-right: 15px;
            border-radius: 5px;
        }
        .cart-item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .cart-item-details h4 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }
        .cart-item-details p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        .quantity-decrease, .quantity-increase, .remove-item {
            background: #0ebbee;
            color: #fff;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        .quantity-decrease:hover, .quantity-increase:hover, .remove-item:hover {
            background: #0a93b9;
        }
        /* Updated Cart Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background-color: #fff;
            border-radius: 12px;
            max-width: 800px; /* Increased width */
            width: 95%;
            max-height: 90vh;
            padding: 30px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow-y: auto;
            animation: slideIn 0.3s ease;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #555;
            transition: color 0.2s;
        }
        .modal-close:hover {
            color: #0ebbee;
        }
        .modal-image {
            width: 100%;
            height: 350px;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .modal-title {
            font-size: 26px;
            margin-bottom: 15px;
            color: #333;
            font-weight: 600;
        }
        .modal-description {
            font-size: 15px;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .modal-price {
            font-size: 22px;
            color: #13aede;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .btn-cart-modal {
            display: inline-block;
            background-color: #0ebbee;
            color: white;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn-cart-modal:hover {
            background-color: #0a93b9;
            transform: scale(1.05);
        }
        .related-products {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .related-products h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }
        .related-products-grid {
            display: flex;
            flex-wrap: nowrap;
            gap: 15px;
            overflow-x: auto;
            max-height: 250px;
            padding-bottom: 10px;
        }
        .related-products-grid::-webkit-scrollbar {
            height: 6px;
        }
        .related-products-grid::-webkit-scrollbar-thumb {
            background: #0ebbee;
            border-radius: 3px;
        }
        .related-product-card {
            flex: 0 0 180px;
            background-color: #f9f9f9;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s;
        }
        .related-product-card:hover {
            transform: translateY(-5px);
        }
        .related-product-image {
            height: 120px;
            background-size: cover;
            background-position: center;
        }
        .related-product-details {
            padding: 12px;
        }
        .related-product-title {
            font-size: 14px;
            margin-bottom: 8px;
            color: #333;
        }
        .related-product-price {
            font-size: 13px;
            color: #13aede;
            font-weight: bold;
        }
        /* New Cart Modal Specific Styles */
        #cartModal .modal-content {
            max-width: 900px; /* Larger cart modal */
            padding: 40px;
        }
        #cartModal .modal-title {
            font-size: 28px;
            margin-bottom: 25px;
            text-align: center;
        }
        #cartModal .cart-items-container {
            margin-bottom: 30px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }
        #cartModal .cart-items-container::-webkit-scrollbar {
            width: 6px;
        }
        #cartModal .cart-items-container::-webkit-scrollbar-thumb {
            background: #0ebbee;
            border-radius: 3px;
        }
        #cartModal .cart-total {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            text-align: right;
            margin: 20px 0;
        }
        #cartModal .cart-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        #cartModal .btn-primary,
        #cartModal .btn-clear-cart {
            flex: 1;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 150px;
        }
        #cartModal .btn-primary {
            background-color: #0ebbee;
            color: white;
            border: none;
        }
        #cartModal .btn-primary:hover {
            background-color: #0a93b9;
            transform: scale(1.05);
        }
        #cartModal .btn-clear-cart {
            background-color: #ff4d4d;
            color: white;
            border: none;
        }
        #cartModal .btn-clear-cart:hover {
            background-color: #cc0000;
            transform: scale(1.05);
        }
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        /* Responsive Adjustments */
        @media (min-width: 1025px) {
            .product-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                flex-wrap: wrap;
                overflow-x: visible;
                gap: 30px;
            }
            .product-card {
                width: 100%;
            }
        }
        @media (max-width: 1024px) {
            .top-rated-grid {
                grid-template-columns: 1fr;
            }
            .featured-products {
                grid-template-columns: repeat(2, 1fr);
            }
            #cartModal .modal-content {
                width: 95%;
                padding: 25px;
            }
            #cartModal .cart-buttons {
                flex-direction: column;
                gap: 10px;
            }
        }
        @media (max-width: 768px) {
            .product-card {
                width: 160px;
            }
            .product-image {
                height: 120px;
            }
            .product-details {
                padding: 12px;
            }
            .product-title {
                font-size: 14px;
            }
            .product-price {
                font-size: 16px;
            }
            .btn-cart {
                font-size: 12px;
            }
            .featured-products {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .modal-image {
                height: 300px;
            }
            .modal-title {
                font-size: 22px;
            }
            .modal-description {
                font-size: 14px;
            }
            .modal-price {
                font-size: 20px;
            }
            .btn-cart-modal {
                font-size: 14px;
                padding: 10px 20px;
            }
            .related-product-card {
                flex: 0 0 150px;
            }
            .related-product-image {
                height: 100px;
            }
            #cartModal .modal-content {
                padding: 20px;
            }
            #cartModal .modal-title {
                font-size: 24px;
            }
            #cartModal .cart-total {
                font-size: 18px;
            }
            #cartModal .btn-primary,
            #cartModal .btn-clear-cart {
                font-size: 14px;
                padding: 10px 15px;
            }
        }
        @media (max-width: 480px) {
            .product-card {
                width: 140px;
            }
            .product-image {
                height: 100px;
            }
            .product-details {
                padding: 10px;
            }
            .product-title {
                font-size: 12px;
            }
            .product-price {
                font-size: 14px;
            }
            .btn-cart {
                font-size: 11px;
            }
            .modal-content {
                padding: 15px;
            }
            .modal-image {
                height: 00px;
            }
            .modal-title {
                font-size: 18px;
            }
            .modal-description {
                font-size: 13px;
            }
            .modal-price {
                font-size: 16px;
            }
            .btn-cart-modal {
                font-size: 12px;
                padding: 8px 15px;
            }
            .related-products h3 {
                font-size: 16px;
            }
            .related-product-card {
                flex: 0 0 120px;
            }
            .related-product-image {
                height: 80px;
            }
            .related-product-title {
                font-size: 12px;
            }
            .related-product-price {
                font-size: 11px;
            }
            #cartModal .modal-content {
                padding: 15px;
            }
            #cartModal .modal-title {
                font-size: 20px;
            }
            #cartModal .cart-total {
                font-size: 16px;
            }
            #cartModal .btn-primary,
            #cartModal .btn-clear-cart {
                font-size: 13px;
                padding: 8px 12px;
                min-width: 120px;
            }
        }
    </style>
</head>
<body>
    <!-- Loader -->
    <div class="loader-wrapper" id="loader">
        <div class="loader-logo">
            TECH<span style="color: #0ebbee;">KNOW</span>GREEN
        </div>
        <div class="loader">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>

    <!-- Top bar with contact info -->
    <div class="topbar">
        <div class="contact-info">
            <span><i class="fas fa-phone"></i> Hotline: <a href="tel:+254707077709">(+254)707-077-709</a></span>
            <span><i class="fas fa-envelope"></i> Email us: <a href="mailto:info@techknowgreen.co.ke">info@techknowgreen.co.ke</a></span>
            <span><i class="fas fa-clock"></i> Mon to Fri: 8:30am-5pm</span>
            <span><i class="fas fa-clock"></i> Sat: 8:30am-1pm</span>
        </div>
        <div class="social-icons">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-whatsapp"></i></a>
            <a href="#"><i class="fab fa-x-twitter"></i></a>
        </div>
    </div>

    <!-- Main Header with Navigation -->
    <header class="main-header">
        <div class="logo">
            <a href="index.php">
                <h1>TECH<span style="color: #0ebbee;">KNOW</span>GREEN</h1>
            </a>
        </div>
        <nav class="main-nav">
            <a href="#">HOME</a>
            <a href="about.html">ABOUT US</a>
            <div class="dropdown">
                <a href="service.html">SERVICES</a>
                <div class="dropdown-content">
                    <a href="heatpump.html">HEAT PUMPS</a>
                    <a href="water pump.html">WATER PUMPS</a>
                    <a href="solar.html">SOLAR SOLUTIONS</a>
                    <a href="water purification.html">WATER PURIFICATION</a>
                    <a href="training.html">TRAINING</a>
                    <a href="consultation.html">CONSULTATION</a>
                </div>
            </div>
            <div class="dropdown">
                <a href="#">PRODUCT CATEGORIES</a>
                <div class="dropdown-content">
                    <a href="#" data-category="solar">Solar Products</a>
                    <a href="#" data-category="Inverters">Inverters</a>
                    <a href="#" data-category="smart_wify">Smart WiFi</a>
                    <a href="#" data-category="water_pump">Water Pumps</a>
                    <a href="#" data-category="heat_pumps">Heat Pumps</a>
                    <a href="#" data-category="Tools">Tools</a>
                    <a href="#" data-category="VFDs">VFDs</a>
                    <a href="#" data-category="Electrical">Electricals</a>
                </div>
            </div>
            <a href="project.html">PROJECTS</a>
            <a href="#" class="nav-highlight">OUR SHOP</a>
            <a href="news.html">NEWS</a>
            <a href="contact.html">CONTACT</a>
        </nav>
        <div class="search-container">
            <input type="text" class="search-box" placeholder="Search products...">
            <button class="search-button"><i class="fas fa-search"></i></button>
            <div class="search-results"></div>
        </div>
        <div class="hamburger-menu" id="hamburgerMenu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </header>

    <!-- Mobile Navigation -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
    <nav class="mobile-nav" id="mobileNav">
        <a href="#" class="nav-highlight">HOME</a>
        <a href="#">ABOUT US</a>
        <div class="mobile-dropdown">
            <a href="#" class="mobile-dropdown-toggle">OUR OFFERS</a>
            <div class="mobile-submenu">
                <a href="service.html">HEAT PUMPS</a>
                <a href="service.html">WATER PUMPS</a>
                <a href="service.html">SOLAR SOLUTIONS</a>
                <a href="service.html">WATER PURIFICATION</a>
                <a href="service.html">TRAINING</a>
                <a href="service.html">CONSULTATION</a>
            </div>
        </div>
        <div class="mobile-dropdown">
            <a href="#" class="mobile-dropdown-toggle">PRODUCT CATEGORIES</a>
            <div class="mobile-submenu">
                <a href="#" data-category="solar">Solar Products</a>
                <a href="#" data-category="Inverters">Inverters</a>
                <a href="#" data-category="smart_wify">Smart WiFi</a>
                <a href="#" data-category="water_pump">Water Pumps</a>
                <a href="#" data-category="heat_pumps">Heat Pumps</a>
                <a href="#" data-category="Tools">Tools</a>
                <a href="#" data-category="VFDs">VFDs</a>
                <a href="#" data-category="Electrical">Electricals</a>
            </div>
        </div>
        <a href="projects.html">PROJECTS</a>
        <a href="shop.html">OUR SHOP</a>
        <a href="news.html">NEWS</a>
        <a href="contact.html">CONTACT</a>
    </nav>

    <!-- Search Modal -->
    <div class="modal" id="searchModal">
        <div class="modal-content">
            <span class="modal-close" id="modalClose">×</span>
            <div class="modal-image" id="modalImage"></div>
            <h2 class="modal-title" id="modalTitle"></h2>
            <p class="modal-description" id="modalDescription"></p>
            <p class="modal-price" id="modalPrice"></p>
            <a href="#" class="modal-link" id="modalLink">View Details</a>
            <button class="btn-cart-modal" id="modalAddToCart">ADD TO CART <i class="fas fa-arrow-right"></i></button>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="modal" id="cartModal">
        <div class="modal-content">
            <span class="modal-close" id="cartModalClose">×</span>
            <h2 class="modal-title">Your Cart</h2>
            <div class="cart-items-container" id="cartItems"></div>
            <p class="cart-total" id="cartTotal">Total: KSh 0.00</p>
            <div class="cart-buttons">
                <button class="btn-primary" id="checkoutButton">Proceed to Checkout</button>
                <button class="btn-clear-cart" id="clearCartButton">Clear Cart</button>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <span class="modal-close" id="productModalClose">×</span>
            <div class="modal-image" id="productModalImage"></div>
            <h2 class="modal-title" id="productModalTitle"></h2>
            <p class="modal-description" id="productModalDescription"></p>
            <p class="modal-price" id="productModalPrice"></p>
            <button class="btn-cart-modal" id="productModalAddToCart">ADD TO CART <i class="fas fa-arrow-right"></i></button>
            <div class="related-products">
                <h3>Related Products</h3>
                <div class="related-products-grid" id="relatedProductsGrid"></div>
            </div>
        </div>
    </div>

    <!-- Promotional Banner -->
    <section class="promo-banner">
        <h2>Special Offer: Up to 20% Off on Solar Products!</h2>
        <p>Upgrade to renewable energy today and save big. Limited time offer!</p>
        <a href="#products" class="btn-primary">Shop Solar Deals</a>
    </section>

    <!-- Featured Products -->
    <section class="products" id="products">
        <h2 class="section-title">Featured Products</h2>
        <div class="category-filter">
            <button class="category-btn active" data-category="all">All</button>
            <?php foreach ($categories as $category): ?>
                <button class="category-btn" data-category="<?php echo htmlspecialchars($category); ?>">
                    <?php echo htmlspecialchars($categoryDisplayNames[$category] ?? ucwords(str_replace('-', ' ', $category))); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="product-grid">
            <?php if (empty($featured_products)): ?>
                <p>No featured products available.</p>
            <?php else: ?>
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card" data-category="<?php echo htmlspecialchars($product['category']); ?>" data-id="<?php echo $product['id']; ?>">
                        <div class="product-image" style="background-image: url('<?php echo htmlspecialchars($product['image']); ?>');"></div>
                        <div class="product-details">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <p class="product-price"><?php echo $product['price'] ? 'KSh ' . number_format($product['price'], 2) : 'Contact for pricing'; ?></p>
                            <a href="#" class="btn-cart" data-id="<?php echo $product['id']; ?>">ADD TO CART <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits">
        <h2 class="section-title">Why Choose TechKnowGreen?</h2>
        <div class="benefits-grid">
            <div class="benefit-item">
                <i class="fas fa-leaf"></i>
                <h3>Eco-Friendly Solutions</h3>
                <p>Our products are designed to reduce environmental impact while maximizing efficiency.</p>
            </div>
            <div class="benefit-item">
                <i class="fas fa-tools"></i>
                <h3>Expert Installation</h3>
                <p>Professional installation services ensure your systems work flawlessly from day one.</p>
            </div>
            <div class="benefit-item">
                <i class="fas fa-headset"></i>
                <h3>24/7 Support</h3>
                <p>Our team is always ready to assist with technical support and product inquiries.</p>
            </div>
            <div class="benefit-item">
                <i class="fas fa-shield-alt"></i>
                <h3>Quality Assurance</h3>
                <p>All products come with warranties and are tested for durability and performance.</p>
            </div>
        </div>
    </section>

    <!-- Top Rated Section -->
    <section class="top-rated">
        <div class="top-rated-grid">
            <div class="featured-products">
                <?php if (empty($related_products)) { ?>
                    <p>No top-rated products available.</p>
                <?php } else { ?>
                    <?php foreach ($related_products as $product) { ?>
                        <div class="product-card" data-category="<?php echo htmlspecialchars($product['category']); ?>" data-id="<?php echo $product['id']; ?>">
                            <div class="product-image" style="background-image: url('<?php echo htmlspecialchars($product['image']); ?>');"></div>
                            <div class="product-details">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <p class="product-price"><?php echo $product['price'] ? 'KSh ' . number_format($product['price'], 2) : 'Contact for pricing'; ?></p>
                                <a href="#" class="btn-cart" data-id="<?php echo $product['id']; ?>">ADD TO CART <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
            <div class="top-products-sidebar">
                <h3 class="sidebar-title">TOP RATED PRODUCTS</h3>
                <?php if (empty($related_products)) { ?>
                    <p>No top-rated products available.</p>
                <?php } else { ?>
                    <?php foreach ($related_products as $product) { ?>
                        <div class="sidebar-product">
                            <div class="sidebar-product-image" style="background-image: url('<?php echo htmlspecialchars($product['image']); ?>');"></div>
                            <div class="sidebar-product-details">
                                <h4><?php echo htmlspecialchars($product['title']); ?></h4>
                                <span class="price"><?php echo $product['price'] ? 'KSh ' . number_format($product['price'], 2) : 'Contact for pricing'; ?></span>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Featured Video -->
    <section class="featured-video">
        <h2 class="section-title">FEATURED VIDEO</h2>
        <div class="video-container">
            <iframe src="https://www.youtube.com/embed/4glPsQAYqBw" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
    </section>

    <!-- FAQs Section -->
    <section class="faqs">
        <h2 class="section-title">Frequently Asked Questions</h2>
        <div class="faq-item">
            <div class="faq-question">How long does it take to install a water pump system?</div>
            <div class="faq-answer">Installation time varies depending on the system complexity, but typically takes 1-3 days for standard residential water pump installations.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">What is the warranty period for your products?</div>
            <div class="faq-answer">Most of our products come with a 1-5 year warranty, depending on the specific product. Contact us for detailed warranty information.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">What solar energy solutions does TechKnowGreen provide?</div>
            <div class="faq-answer">We offer solar panel installations, hybrid and off-grid systems, solar water heaters, and complete energy storage solutions for residential, commercial, and agricultural use.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Can you help with water pumping using solar power?</div>
            <div class="faq-answer">Yes, we specialize in solar-powered water pumping systems, ideal for boreholes, irrigation, and livestock farming in off-grid or remote areas.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Do you offer energy audits or system assessments?</div>
            <div class="faq-answer">Absolutely. We conduct detailed energy audits to analyze your current consumption and recommend tailored renewable solutions that maximize efficiency and cost savings.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">What is the typical installation time for a solar system?</div>
            <div class="faq-answer">Installation time varies based on system size and complexity. Most residential systems are installed within 2–4 days, while larger commercial projects may take longer.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Does TechKnowGreen provide maintenance and monitoring?</div>
            <div class="faq-answer">Yes, we offer ongoing maintenance services and remote monitoring to ensure your solar and water systems operate at peak performance.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Are your products covered by warranty?</div>
            <div class="faq-answer">All our solar components and systems come with warranties ranging from 5 to 25 years, depending on the product. We partner with top-tier manufacturers for quality assurance.</div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-about">
                <div class="footer-logo">
                    <h2>TECH<span style="color: #0ebbee;">KNOW</span>GREEN</h2>
                </div>
                <p>At TechKnowGreen, we integrate sustainable practices into every project. From harnessing solar energy for water pumping to comprehensive energy solutions, we help you adopt environmentally friendly approaches to water and energy management.</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
                <div class="footer-newsletter">
                    <form>
                        <input type="email" placeholder="Enter your email" required>
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
            <div class="footer-quick-links">
                <h3 class="footer-title">Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#">Our Return Policy</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Our Project Gallery</a></li>
                    <li><a href="#">Our Offers</a></li>
                    <li><a href="#">Write to Us</a></li>
                </ul>
            </div>
            <div class="footer-contact-info">
                <h3 class="footer-title">Contact Information</h3>
                <ul class="footer-contact">
                    <li><i class="fas fa-map-marker-alt"></i> AMAL Plaza, Links Rd, Mombasa</li>
                    <li><i class="fas fa-phone"></i> <a href="tel:+254711312029">(+254) 711-312-029</a></li>
                    <li><i class="fab fa-whatsapp"></i> <a href="tel:+254710220398">(+254) 710-220-398</a></li>
                    <li><i class="fas fa-envelope"></i> <a href="mailto:info@techknowgreen.co.ke">info@techknowgreen.co.ke</a></li>
                    <li><i class="fas fa-globe"></i> <a href="https://techknowgreen.co.ke">techknowgreen.co.ke</a></li>
                    <li><i class="fas fa-lock"></i> <a href="#">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>© 2025 <a href="#">TechKnowGreen</a>. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Floating Buttons -->
    <a href="#" class="chat-btn"><i class="fas fa-comment"></i></a>
    <a href="#" class="whatsapp-btn"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a>
    <div class="cart-btn">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count">0</span>
    </div>
    <div class="back-to-top"><i class="fas fa-arrow-up"></i></div>

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

        // Initialize cart from localStorage
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartCountDisplay = document.querySelector('.cart-count');
        const cartModal = document.querySelector('#cartModal');
        const cartItemsContainer = document.querySelector('#cartItems');
        const cartTotalDisplay = document.querySelector('#cartTotal');
        const cartModalClose = document.querySelector('#cartModalClose');
        const cartBtn = document.querySelector('.cart-btn');
        const checkoutButton = document.querySelector('#checkoutButton');
        const clearCartButton = document.querySelector('#clearCartButton');

        // Store products for related products functionality
        const allProducts = [
            ...<?php echo json_encode($featured_products); ?>,
            ...<?php echo json_encode($related_products); ?>
        ];

        // Update cart display
        function updateCart() {
            cartCountDisplay.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
            cartItemsContainer.innerHTML = '';
            let total = 0;

            if (cart.length === 0) {
                cartItemsContainer.innerHTML = '<p>Your cart is empty.</p>';
            } else {
                cart.forEach(item => {
                    const itemElement = document.createElement('div');
                    itemElement.classList.add('cart-item');
                    itemElement.innerHTML = `
                        <div class="cart-item-image" style="background-image: url('${item.image}');"></div>
                        <div class="cart-item-details">
                            <h4>${item.title}</h4>
                            <p>Price: KSh ${parseFloat(item.price).toFixed(2)}</p>
                            <p>Quantity: 
                                <button class="quantity-decrease" data-id="${item.id}">-</button>
                                <span>${item.quantity}</span>
                                <button class="quantity-increase" data-id="${item.id}">+</button>
                            </p>
                            <button class="remove-item" data-id="${item.id}">Remove</button>
                        </div>
                    `;
                    cartItemsContainer.appendChild(itemElement);
                    total += item.price * item.quantity;
                });
            }

            cartTotalDisplay.textContent = `Total: KSh ${total.toFixed(2)}`;
            localStorage.setItem('cart', JSON.stringify(cart));
        }

        // Add to cart
        function addToCart(product) {
            const existingItem = cart.find(item => item.id === product.id);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({ ...product, quantity: 1 });
            }
            updateCart();
        }

        // Remove from cart
        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== parseInt(id));
            updateCart();
        }

        // Clear cart
        function clearCart() {
            cart = [];
            updateCart();
        }

        // Update quantity
        function updateQuantity(id, change) {
            const item = cart.find(item => item.id === parseInt(id));
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    removeFromCart(id);
                } else {
                    updateCart();
                }
            }
        }

        // Event listeners for cart buttons
        document.querySelectorAll('.btn-cart').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const id = button.getAttribute('data-id');
                const productCard = button.closest('.product-card');
                const product = {
                    id: parseInt(id),
                    title: productCard.querySelector('.product-title').textContent,
                    price: parseFloat(productCard.querySelector('.product-price').textContent.replace('KSh ', '').replace(',', '') || 0),
                    image: productCard.querySelector('.product-image').style.backgroundImage.slice(5, -2),
                    description: productCard.querySelector('.product-description').textContent
                };
                addToCart(product);
            });
        });

        cartBtn.addEventListener('click', () => {
            cartModal.classList.add('active');
            updateCart();
        });

        cartModalClose.addEventListener('click', () => {
            cartModal.classList.remove('active');
        });

        cartModal.addEventListener('click', (e) => {
            if (e.target === cartModal) {
                cartModal.classList.remove('active');
            }
        });

        cartItemsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('quantity-increase')) {
                updateQuantity(e.target.getAttribute('data-id'), 1);
            } else if (e.target.classList.contains('quantity-decrease')) {
                updateQuantity(e.target.getAttribute('data-id'), -1);
            } else if (e.target.classList.contains('remove-item')) {
                removeFromCart(e.target.getAttribute('data-id'));
            }
        });

        // Clear Cart Button Event
        clearCartButton.addEventListener('click', () => {
            if (confirm('Are you sure you want to clear all items from the cart?')) {
                clearCart();
            }
        });

        // WhatsApp Checkout
        checkoutButton.addEventListener('click', () => {
            if (cart.length === 0) {
                alert('Your cart is empty. Please add items to proceed.');
                return;
            }

            let message = "Hello TechKnowGreen, I would like to order the following items:\n\n";
            let total = 0;
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                message += `Product: ${item.title}\n`;
                message += `Quantity: ${item.quantity}\n`;
                message += `Price: KSh ${parseFloat(item.price).toFixed(2)}\n`;
                message += `Subtotal: KSh ${parseFloat(itemTotal).toFixed(2)}\n\n`;
                total += itemTotal;
            });
            message += `Total: KSh ${total.toFixed(2)}\n`;
            message += "Please provide further details on how to proceed with this order.";

            const encodedMessage = encodeURIComponent(message);
            const whatsappNumber = "+254711312029";
            const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
            window.open(whatsappUrl, '_blank');
        });

        // Search Functionality
        const searchBox = document.querySelector('.search-box');
        const searchResults = document.querySelector('.search-results');
        const searchModal = document.querySelector('#searchModal');
        const modalClose = document.querySelector('#modalClose');
        const modalImage = document.querySelector('#modalImage');
        const modalTitle = document.querySelector('#modalTitle');
        const modalDescription = document.querySelector('#modalDescription');
        const modalPrice = document.querySelector('#modalPrice');
        const modalLink = document.querySelector('#modalLink');
        const modalAddToCart = document.querySelector('#modalAddToCart');

        searchBox.addEventListener('input', debounce(() => {
            const query = searchBox.value.trim();
            searchResults.innerHTML = '';

            if (query.length === 0) {
                searchResults.classList.remove('active');
                return;
            }

            fetch(`search.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        searchResults.innerHTML = '<div class="no-results">Error: ' + data.error + '</div>';
                        searchResults.classList.add('active');
                        return;
                    }

                    if (data.length === 0) {
                        searchResults.innerHTML = '<div class="no-results">No results found</div>';
                        searchResults.classList.add('active');
                        return;
                    }

                    data.forEach(item => {
                        const resultItem = document.createElement('a');
                        resultItem.href = '#';
                        resultItem.textContent = item.title + (item.price ? ` - KSh ${parseFloat(item.price).toFixed(2)}` : '');
                        resultItem.setAttribute('aria-label', `View details for ${item.title}`);
                        resultItem.addEventListener('click', (e) => {
                            e.preventDefault();
                            modalImage.style.backgroundImage = `url(${item.image})`;
                            modalTitle.textContent = item.title;
                            modalDescription.textContent = item.description;
                            modalPrice.textContent = item.price ? `KSh ${parseFloat(item.price).toFixed(2)}` : 'Contact for pricing';
                            modalLink.href = item.url;
                            modalAddToCart.setAttribute('data-id', item.id);
                            searchModal.classList.add('active');
                            searchModal.querySelector('.modal-content').focus();
                            searchResults.classList.remove('active');
                        });
                        searchResults.appendChild(resultItem);
                    });

                    searchResults.classList.add('active');
                })
                .catch(error => {
                    searchResults.innerHTML = '<div class="no-results">Error fetching results</div>';
                    searchResults.classList.add('active');
                });
        }, 300));

        document.addEventListener('click', (e) => {
            if (!searchBox.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('active');
            }
        });

        searchBox.addEventListener('focus', () => {
            if (searchBox.value.trim().length > 0) {
                searchResults.classList.add('active');
            }
        });

        modalClose.addEventListener('click', () => {
            searchModal.classList.remove('active');
        });

        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) {
                searchModal.classList.remove('active');
            }
        });

        modalAddToCart.addEventListener('click', () => {
            const id = modalAddToCart.getAttribute('data-id');
            const product = {
                id: parseInt(id),
                title: modalTitle.textContent,
                price: modalPrice.textContent.includes('Contact') ? 0 : parseFloat(modalPrice.textContent.replace('KSh ', '').replace(',', '')),
                image: modalImage.style.backgroundImage.slice(5, -2),
                description: modalDescription.textContent
            };
            addToCart(product);
            searchModal.classList.remove('active');
        });

        // Product Modal Functionality
        const productModal = document.querySelector('#productModal');
        const productModalClose = document.querySelector('#productModalClose');
        const productModalImage = document.querySelector('#productModalImage');
        const productModalTitle = document.querySelector('#productModalTitle');
        const productModalDescription = document.querySelector('#productModalDescription');
        const productModalPrice = document.querySelector('#productModalPrice');
        const productModalAddToCart = document.querySelector('#productModalAddToCart');
        const relatedProductsGrid = document.querySelector('#relatedProductsGrid');

        function showProductModal(product) {
            productModalImage.style.backgroundImage = `url(${product.image})`;
            productModalTitle.textContent = product.title;
            productModalDescription.textContent = product.description;
            productModalPrice.textContent = product.price ? `KSh ${parseFloat(product.price).toFixed(2)}` : 'Contact for pricing';
            productModalAddToCart.setAttribute('data-id', product.id);

            relatedProductsGrid.innerHTML = '';
            const relatedProducts = allProducts.filter(p => p.category === product.category && p.id != product.id);
            if (relatedProducts.length === 0) {
                relatedProductsGrid.innerHTML = '<p>No related products available.</p>';
            } else {
                relatedProducts.forEach(rp => {
                    const relatedCard = document.createElement('div');
                    relatedCard.classList.add('related-product-card');
                    relatedCard.setAttribute('data-id', rp.id);
                    relatedCard.innerHTML = `
                        <div class="related-product-image" style="background-image: url('${rp.image}');"></div>
                        <div class="related-product-details">
                            <h4 class="related-product-title">${rp.title}</h4>
                            <p class="related-product-price">${rp.price ? 'KSh ' + parseFloat(rp.price).toFixed(2) : 'Contact for pricing'}</p>
                        </div>
                    `;
                    relatedCard.addEventListener('click', () => {
                        const newProduct = allProducts.find(p => p.id == rp.id);
                        if (newProduct) {
                            showProductModal(newProduct);
                        }
                    });
                    relatedProductsGrid.appendChild(relatedCard);
                });
            }

            productModal.classList.add('active');
            productModal.querySelector('.modal-content').focus();
        }

        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.classList.contains('btn-cart')) return;
                const id = parseInt(card.getAttribute('data-id'));
                const product = allProducts.find(p => p.id == id);
                if (product) {
                    showProductModal(product);
                }
            });
        });

        productModalClose.addEventListener('click', () => {
            productModal.classList.remove('active');
        });

        productModal.addEventListener('click', (e) => {
            if (e.target === productModal) {
                productModal.classList.remove('active');
            }
        });

        productModalAddToCart.addEventListener('click', () => {
            const id = parseInt(productModalAddToCart.getAttribute('data-id'));
            const product = allProducts.find(p => p.id == id);
            if (product) {
                addToCart({
                    id: parseInt(id),
                    title: productModalTitle.textContent,
                    price: productModalPrice.textContent.includes('Contact') ? 0 : parseFloat(productModalPrice.textContent.replace('KSh ', '')),
                    image: productModalImage.style.backgroundImage.slice(5, -2),
                    description: productModalDescription.textContent
                });
                productModal.classList.remove('active');
            }
        });

        // Mobile Navigation
        const hamburger = document.querySelector('#hamburgerMenu');
        const mobileNav = document.querySelector('#mobileNav');
        const mobileNavOverlay = document.querySelector('#mobileNavOverlay');

        if (hamburger && mobileNav && mobileNavOverlay) {
            hamburger.addEventListener('click', () => {
                const isExpanded = hamburger.getAttribute('aria-expanded') === 'true';
                hamburger.setAttribute('aria-expanded', !isExpanded);
                hamburger.classList.toggle('active');
                mobileNav.classList.toggle('open');
                mobileNavOverlay.classList.toggle('show');
            });

            mobileNavOverlay.addEventListener('click', () => {
                hamburger.setAttribute('aria-expanded', 'false');
                hamburger.classList.remove('active');
                mobileNav.classList.remove('open');
                mobileNavOverlay.classList.remove('show');
            });
        }

        // Mobile Dropdowns
        const mobileDropdowns = document.querySelectorAll('.mobile-dropdown-toggle');
        mobileDropdowns.forEach(toggle => {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                const submenu = toggle.nextElementSibling;
                submenu.classList.toggle('open');
                toggle.setAttribute('aria-expanded', submenu.classList.contains('open'));
            });
        });

        // Back to Top
        const backToTop = document.querySelector('.back-to-top');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // FAQs
        const faqQuestions = document.querySelectorAll('.faq-question');
        faqQuestions.forEach(question => {
            question.setAttribute('aria-expanded', 'false');
            question.addEventListener('click', () => {
                const answer = question.nextElementSibling;
                const isActive = question.classList.contains('active');
                faqQuestions.forEach(q => {
                    q.classList.remove('active');
                    q.nextElementSibling.classList.remove('active');
                    q.setAttribute('aria-expanded', 'false');
                });
                if (!isActive) {
                    question.classList.add('active');
                    answer.classList.add('active');
                    question.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // Category Filter
        const categoryButtons = document.querySelectorAll('.category-btn');
        const productCards = document.querySelectorAll('.product-card');

        categoryButtons.forEach(button => {
            button.addEventListener('click', () => {
                const category = button.getAttribute('data-category');
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                productCards.forEach(card => {
                    if (category === 'all' || card.getAttribute('data-category') === category) {
                        card.classList.remove('hidden');
                        card.style.display = 'block';
                    } else {
                        card.classList.add('hidden');
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Loader
        window.addEventListener('load', () => {
            const loader = document.getElementById('loader');
            if (loader) {
                setTimeout(() => {
                    loader.style.opacity = '0';
                    setTimeout(() => {
                        loader.style.display = 'none';
                    }, 500);
                }, 1000);
            }
            updateCart();
        });
    </script>
</body>
</html>