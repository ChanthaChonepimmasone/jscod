<?php
session_start();
require_once "config/database.php";

// เริ่ม session ตะกร้า
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$db = new Database();
$connection = $db->getConnection();

// ดึงข้อมูลสินค้า
$stmt = $connection->query("SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชันจัดรูปแบบราคา
function formatPrice($price) {
    return number_format($price, 0, '.', ',');
}

// เพิ่มสินค้าในตะกร้า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['quantity']);
    
    $stmt = $connection->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $quantity > 0 && $quantity <= $product['stock']) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'image' => $product['image']
            ];
        }
        $_SESSION['success'] = "ເພີ່ມ {$product['name']} ລົງກະຕ່າ​ສຳ​ເລັດ​ແລ້ວ!";
    } else {
        $_SESSION['error'] = "ບໍ່​ສາ​ມາດ​ເພີ່ມ​ສິນ​ຄ້າ​ໄດ້";
    }
    
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JS.Shop - ຮ້ານຄ້າອອນລາຍ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --blue-50: #eff6ff;
            --blue-100: #dbeafe;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;
            --blue-800: #1e40af;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
    font-family: 'Noto Sans Lao', 'Segoe UI', sans-serif;
    background-color: #ffffff; /* เปลี่ยนจาก #f8fafc เป็น #ffffff */
    line-height: 1.6;
}
        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 0.7rem;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            padding: 1rem 0;
        }
        
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 1rem;
                padding: 0.5rem;
            }
        }
        
        .product-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            border: 1px solid var(--blue-100);
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.1), 0 10px 10px -5px rgba(37, 99, 235, 0.04);
            border-color: var(--blue-300);
        }
        
       /* เปลี่ยนพื้นหลัง product image เป็นสีขาว */
.product-image {
    height: 180px;
    background: #ffffff !important; /* เปลี่ยนจาก gradient สีฟ้าเป็นสีขาว */
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    padding: 10px;
    border: 1px solid #e2e8f0; /* เพิ่มขอบเพื่อให้เห็นชัด */
}

/* เปลี่ยนพื้นหลัง modal product image เป็นสีขาว */
.modal-product-image {
    height: 300px;
    background: #ffffff !important; /* เปลี่ยนจาก gradient สีฟ้าเป็นสีขาว */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    border: 1px solid #e2e8f0; /* เพิ่มขอบเพื่อให้เห็นชัด */
}

/* เปลี่ยนพื้นหลัง search input เป็นสีขาว */
.search-input {
    border-radius: 25px;
    padding: 0.75rem 1.5rem;
    border: 2px solid #e2e8f0;
    font-size: 1rem;
    background: #ffffff !important; /* เปลี่ยนจาก var(--blue-50) เป็นสีขาว */
}

.search-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    background: #ffffff !important;
}

/* เปลี่ยนพื้นหลัง detail-item เป็นสีขาว */
.detail-item {
    text-align: center;
    padding: 1rem;
    background: #ffffff !important; /* เปลี่ยนจาก var(--blue-50) เป็นสีขาว */
    border-radius: 12px;
    border: 1px solid #e2e8f0; /* เปลี่ยนสีขอบให้เข้มขึ้น */
}

/* เปลี่ยนพื้นหลัง modal header เป็นสีขาว */
.product-modal .modal-header {
    border-bottom: 1px solid #e2e8f0;
    padding: 1.5rem;
    background: #ffffff !important; /* เปลี่ยนจาก var(--blue-50) เป็นสีขาว */
}
        
        @media (max-width: 768px) {
            .product-image {
                height: 120px; /* ຫຼຸດລົງສຳລັບໜ້າຈໍມືຖື */
            }
        }
        
        .product-image img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain; /* ປ່ຽນຈາກ cover ເປັນ contain ເພື່ອເບິ່ງຮູບເຕັມຮູບ */
            transition: transform 0.3s ease;
            border-radius: 8px;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-icon {
            font-size: 2.5rem; /* ຫຼຸດຂະໜາດ icon ເລັກນ້ອຍ */
            color: var(--blue-500);
            opacity: 0.7;
        }
        
        @media (max-width: 768px) {
            .product-icon {
                font-size: 1.8rem;
            }
        }
        
        .product-body {
            padding: 1.25rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.8em; /* ຮັກສາຄວາມສູງຂອງຊື່ສິນຄ້າ */
        }
        
        @media (max-width: 768px) {
            .product-title {
                font-size: 0.95rem;
                min-height: 2.6em;
            }
        }
        
        .product-description {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.4em; /* ຮັກສາຄວາມສູງຂອງລາຍລະອຽດ */
        }
        
        @media (max-width: 768px) {
            .product-description {
                font-size: 0.8rem;
                -webkit-line-clamp: 2;
                min-height: 2.2em;
            }
        }
        
        .product-price {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--success-color);
            margin-bottom: 0.5rem;
        }
        
        .product-price-symbol {
            font-size: 0.9em;
            margin-right: 2px;
        }
        
        @media (max-width: 768px) {
            .product-price {
                font-size: 1.1rem;
            }
        }
        
        .product-stock {
            font-size: 0.85rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .stock-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }
        
        .stock-low {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .stock-out {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .add-to-cart-form {
            margin-top: auto;
        }
        
        .quantity-input {
            width: 80px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }
        
        .quantity-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .add-to-cart-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            color: white;
        }
        
        .add-to-cart-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(235, 235, 235, 0.3);
            color: white;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        }
        
        .add-to-cart-btn:active {
            transform: translateY(0);
        }
        
        /* Header Section */
        .page-header {
            text-align: center;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .page-title {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .page-subtitle {
                font-size: 1rem;
            }
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
        }
        
        /* Modal Styles */
        .product-modal .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        
        .product-modal .modal-header {
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem;
            background: var(--blue-50);
        }
        
        .product-modal .modal-body {
            padding: 0;
        }
        
        .modal-product-image {
            height: 300px;
            background: linear-gradient(135deg, var(--blue-50), var(--blue-100));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-product-image img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 10px;
        }
        
        .modal-product-info {
            padding: 2rem;
        }
        
        .modal-product-title {
            font-weight: 700;
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 1rem;
        }
        
        .modal-product-price {
            font-weight: 700;
            font-size: 2rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }
        
        .modal-product-description {
            color: var(--secondary-color);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .product-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .detail-item {
            text-align: center;
            padding: 1rem;
            background: var(--blue-50);
            border-radius: 12px;
            border: 1px solid var(--blue-100);
        }
        
        .detail-label {
            font-size: 0.85rem;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .detail-value {
            font-weight: 600;
            color: #1e293b;
        }
        
        .modal-add-to-cart {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            color: white;
        }
        
        .modal-add-to-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: white;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        }
        
        /* Bottom Navigation for Mobile */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            display: none;
            z-index: 1000;
            border-top: 1px solid var(--blue-100);
        }
        
        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: flex;
            }
            
            body {
                padding-bottom: 80px;
            }
        }
        
        .nav-item-mobile {
            flex: 1;
            text-align: center;
            padding: 0.75rem 0;
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .nav-item-mobile.active {
            color: var(--primary-color);
        }
        
        .nav-item-mobile.active .nav-icon {
            color: var(--primary-color);
        }
        
        .nav-icon {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
            display: block;
            color: var(--secondary-color);
            transition: all 0.3s ease;
        }
        
        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(37, 99, 235, 0.4);
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        }
        
        /* Search Bar */
        .search-container {
            max-width: 500px;
            margin: 0 auto 2rem;
            padding: 0 1rem;
        }
        
        .search-input {
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            border: 2px solid #e2e8f0;
            font-size: 1rem;
            background: var(--blue-50);
        }
        
        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
        }

        /* Laos Kip Symbol */
        .laos-kip {
            font-family: 'Noto Sans Lao', sans-serif;
        }

        /* Blue theme enhancements */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
        }

        .badge.bg-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)) !important;
        }

        /* Cart badge */
        .cart-badge.badge {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        }

        /* Loading animations */
        .product-card {
            position: relative;
            overflow: hidden;
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .product-card:hover::before {
            left: 100%;
        }
    </style>
    <!-- Load Noto Sans Lao font -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Desktop Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>JS & COD
            </a>
            <div class="navbar-nav ms-auto d-none d-lg-flex">
                <a class="nav-link position-relative" href="cart.php">
                    <i class="fas fa-shopping-cart me-1"></i>ກະຕ່າສິນຄ້າ
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="cart-badge badge bg-warning rounded-pill">
                            <?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="track_order.php">
                    <i class="fas fa-truck me-1"></i>ຕິດຕາມອໍເດີ
                </a>
            </div>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index.php" class="nav-item-mobile active">
            <i class="fas fa-home nav-icon"></i>
            ໜ້າຫຼັກ
        </a>
        <a href="cart.php" class="nav-item-mobile position-relative">
            <i class="fas fa-shopping-cart nav-icon"></i>
            ກະຕ່າ
            <?php if (!empty($_SESSION['cart'])): ?>
                <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                    <?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="track_order.php" class="nav-item-mobile">
            <i class="fas fa-truck nav-icon"></i>
            ຕິດຕາມ
        </a>
    </div>

    <!-- Floating Action Button -->
    <button class="fab d-lg-none" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </button>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title">JS & COD</h1>
            <p class="page-subtitle">ຮ້ານຄ້າອອນລາຍ ຊື້ສິນຄ້າງ່າຍ ຈ່າຍເງິນປາຍທາງ</p>
        </div>
    </div>

    <div class="container">
        <!-- Search Bar -->
        <div class="search-container">
            <input type="text" class="form-control search-input" placeholder="🔍 ຄົ້ນຫາສິນຄ້າ...">
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="product-grid">
            <?php foreach($products as $product): ?>
            <div class="product-card" onclick="openProductModal(<?php echo $product['id']; ?>)">
                <div class="product-image">
                    <?php
                    $image_path = "images/" . $product['image'];
                    if (file_exists($image_path) && $product['image'] !== 'default.jpg') {
                        echo '<img src="' . $image_path . '" class="img-fluid" alt="' . $product['name'] . '" 
                              onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\'">';
                        echo '<div class="product-icon" style="display: none;">';
                        $icons = ['', '', '', '', '', ''];
                        $icon_index = $product['id'] % count($icons);
                        echo $icons[$icon_index];
                        echo '</div>';
                    } else {
                        $icons = ['', '', '', '', '', ''];
                        $icon_index = $product['id'] % count($icons);
                        echo '<div class="product-icon">' . $icons[$icon_index] . '</div>';
                    }
                    ?>
                </div>
                
                <div class="product-body">
                    <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="product-price">
                            <span class="product-price-symbol">₭</span><?php echo formatPrice($product['price']); ?>
                        </span>
                        <?php if ($product['stock'] < 5 && $product['stock'] > 0): ?>
                            <span class="stock-badge stock-low">ເຫຼືອນ້ອຍ</span>
                        <?php elseif ($product['stock'] == 0): ?>
                            <span class="stock-badge stock-out">ສິນຄ້າຫມົດ</span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="product-stock">
                        <i class="fas fa-box me-1"></i>ຍັງເຫຼືອ: <?php echo $product['stock']; ?> ອັນ
                    </p>
                    
                    <form method="POST" class="add-to-cart-form" onclick="event.stopPropagation()">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="d-flex gap-2">
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" 
                                   class="form-control form-control-sm quantity-input" 
                                   <?php echo $product['stock'] == 0 ? 'disabled' : ''; ?>>
                            <button type="submit" name="add_to_cart" class="btn add-to-cart-btn"
                                    <?php echo $product['stock'] == 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus me-1"></i>
                                <span class="d-none d-sm-inline">ເພີ່ມ</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">ບໍ່ມີສິນຄ້າ</h4>
                <p class="text-muted">ກະລຸນາກັບມາໃໝ່ພາຍຫຼັງ</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Product Detail Modal -->
    <div class="modal fade product-modal" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ລາຍລະອຽດສິນຄ້າ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-product-image" id="modalProductImage">
                        <!-- Product image will be loaded here -->
                    </div>
                    <div class="modal-product-info">
                        <h2 class="modal-product-title" id="modalProductTitle"></h2>
                        <div class="modal-product-price" id="modalProductPrice"></div>
                        <p class="modal-product-description" id="modalProductDescription"></p>
                        
                        <div class="product-details-grid">
                            <div class="detail-item">
                                <div class="detail-label">ປະເພດ</div>
                                <div class="detail-value" id="modalProductCategory"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">ສະຕ໊ອກ</div>
                                <div class="detail-value" id="modalProductStock"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">ສະຖານະ</div>
                                <div class="detail-value" id="modalProductStatus"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">ລະຫັດ</div>
                                <div class="detail-value" id="modalProductId"></div>
                            </div>
                        </div>
                        
                        <form method="POST" id="modalAddToCartForm">
                            <input type="hidden" name="product_id" id="modalProductIdInput">
                            <div class="row align-items-center">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <label class="form-label fw-bold">ຈຳນວນ:</label>
                                    <input type="number" name="quantity" value="1" min="1" max="1" 
                                           class="form-control form-control-lg quantity-input" 
                                           id="modalQuantityInput">
                                </div>
                                <div class="col-md-8">
                                    <button type="submit" name="add_to_cart" class="btn modal-add-to-cart" id="modalAddToCartBtn">
                                        <i class="fas fa-cart-plus me-2"></i>
                                        ເພີ່ມໃນກະຕ່າສິນຄ້າ
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Product data from PHP
        const products = <?php echo json_encode($products); ?>;
        
        // Function to format price
        function formatPrice(price) {
            return parseFloat(price).toLocaleString('en-US', {
                maximumFractionDigits: 0
            });
        }
        
        // Open product modal
        function openProductModal(productId) {
            const product = products.find(p => p.id == productId);
            if (!product) return;
            
            // Update modal content
            document.getElementById('modalProductTitle').textContent = product.name;
            document.getElementById('modalProductPrice').innerHTML = 
                '<span class="product-price-symbol">₭</span>' + formatPrice(product.price);
            document.getElementById('modalProductDescription').textContent = product.description;
            document.getElementById('modalProductCategory').textContent = product.category;
            document.getElementById('modalProductStock').textContent = product.stock + ' ອັນ';
            document.getElementById('modalProductId').textContent = '#' + product.id;
            document.getElementById('modalProductIdInput').value = product.id;
            
            // Update product status
            const statusElement = document.getElementById('modalProductStatus');
            if (product.stock == 0) {
                statusElement.textContent = 'ສິນຄ້າຫມົດ';
                statusElement.className = 'detail-value text-danger';
            } else if (product.stock < 5) {
                statusElement.textContent = 'ເຫຼືອນ້ອຍ';
                statusElement.className = 'detail-value text-warning';
            } else {
                statusElement.textContent = 'ມີສິນຄ້າ';
                statusElement.className = 'detail-value text-success';
            }
            
            // Update product image
            const imageContainer = document.getElementById('modalProductImage');
            const imagePath = 'images/' + product.image;
            
            // Check if image exists
            fetch(imagePath)
                .then(response => {
                    if (response.ok) {
                        imageContainer.innerHTML = `<img src="${imagePath}" class="img-fluid" alt="${product.name}">`;
                    } else {
                        const icons = ['📱', '💻', '🎧', '⌚', '📸', '🖥️'];
                        const iconIndex = product.id % icons.length;
                        imageContainer.innerHTML = `<div class="product-icon" style="font-size: 4rem;">${icons[iconIndex]}</div>`;
                    }
                })
                .catch(() => {
                    const icons = ['📱', '💻', '🎧', '⌚', '📸', '🖥️'];
                    const iconIndex = product.id % icons.length;
                    imageContainer.innerHTML = `<div class="product-icon" style="font-size: 4rem;">${icons[iconIndex]}</div>`;
                });
            
            // Update quantity input
            const quantityInput = document.getElementById('modalQuantityInput');
            quantityInput.max = product.stock;
            quantityInput.disabled = product.stock == 0;
            
            // Update add to cart button
            const addToCartBtn = document.getElementById('modalAddToCartBtn');
            if (product.stock == 0) {
                addToCartBtn.disabled = true;
                addToCartBtn.textContent = 'ສິນຄ້າຫມົດ';
                addToCartBtn.className = 'btn btn-secondary modal-add-to-cart';
            } else {
                addToCartBtn.disabled = false;
                addToCartBtn.innerHTML = '<i class="fas fa-cart-plus me-2"></i>ເພີ່ມໃນກະຕ່າສິນຄ້າ';
                addToCartBtn.className = 'btn modal-add-to-cart';
            }
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        }
        
        // Scroll to top function
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Search functionality
        document.querySelector('.search-input').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const productCards = document.querySelectorAll('.product-card');
            
            productCards.forEach(card => {
                const title = card.querySelector('.product-title').textContent.toLowerCase();
                const description = card.querySelector('.product-description').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Add to cart animation
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
            
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!this.disabled) {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check me-1"></i>ເພີ່ມແລ້ວ';
                        this.classList.add('btn-success');
                        
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.classList.remove('btn-success');
                        }, 2000);
                    }
                });
            });
            
            // Modal add to cart
            document.getElementById('modalAddToCartForm').addEventListener('submit', function(e) {
                const button = document.getElementById('modalAddToCartBtn');
                if (!button.disabled) {
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-check me-2"></i>ເພີ່ມແລ້ວ!';
                    button.classList.add('btn-success');
                    
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.classList.remove('btn-success');
                    }, 2000);
                }
            });
        });
        
        // Show/hide FAB based on scroll position
        window.addEventListener('scroll', function() {
            const fab = document.querySelector('.fab');
            if (window.scrollY > 300) {
                fab.style.display = 'flex';
            } else {
                fab.style.display = 'none';
            }
        });
        
        // Mobile navigation active state
        document.querySelectorAll('.nav-item-mobile').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item-mobile').forEach(i => {
                    i.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>