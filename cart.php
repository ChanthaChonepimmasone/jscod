<?php
session_start();
require_once "config/database.php";

// ຟັງຊັ່ນຈັດຮູບແບບລາຄາ
function formatPrice($price) {
    return number_format($price, 0, '.', ',');
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ลบสินค้า
if (isset($_GET['remove'])) {
    $product_id = $_GET['remove'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['success'] = "ລຶບສິນຄ້າອອກຈາກກະຕ່າ​ສຳ​ເລັດ​ແລ້ວ";
    }
    header("Location: cart.php");
    exit();
}

// อัพเดทจำนวน
if ($_POST && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $product_id => $quantity) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    $_SESSION['success'] = "ອັບເດດກະຕ່າ​ສຳ​ເລັດ​ແລ້ວ";
    header("Location: cart.php");
    exit();
}

$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

$totalFormatted = formatPrice($total);
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ກະຕ່າສິນຄ້າ - JS.Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color:rgb(12, 12, 12);
            --danger-color:rgb(0, 0, 0);
            --blue-50: #eff6ff;
            --blue-100: #dbeafe;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;
            --blue-800: #1e40af;
        }
        
        body {
            font-family: 'Noto Sans Lao', 'Segoe UI', sans-serif;
            background-color: #f8fafc;
            line-height: 1.6;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color),rgb(33, 37, 255));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }
        
        .nav-link {
            color: white !important;
            font-weight: 500;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color),rgb(32, 29, 255));
            color: white;
            padding: 2rem 0;
            border-radius: 0 0 20px 20px;
            margin-bottom: 2rem;
        }
        
        .cart-item {
            transition: all 0.3s ease;
            border-radius: 12px;
        }
        
        .cart-item:hover {
            background-color: #f8fafc;
            transform: translateY(-2px);
        }
        
        .product-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
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
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #b91c1c);
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        .price-kip {
            font-weight: 700;
            color: var(--success-color);
        }
        
        .total-section {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: white;
            border-radius: 16px;
            padding: 2rem;
        }
        
        /* Mobile Bottom Navigation */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            display: none;
            z-index: 1000;
        }
        
        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: flex;
            }
            
            body {
                padding-bottom: 80px;
            }
            
            .container {
                padding-bottom: 1rem;
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
        
        .nav-icon {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .cart-badge-mobile {
            position: absolute;
            top: 5px;
            right: 35%;
            transform: translateX(50%);
            font-size: 0.6rem;
            min-width: 18px;
            height: 18px;
        }
        
        /* Empty Cart State */
        .empty-cart {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-cart-icon {
            font-size: 4rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Desktop Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>JS & COD
            </a>
            <div class="navbar-nav ms-auto d-none d-lg-flex">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>ໜ້າຫຼັກ
                </a>
            </div>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index.php" class="nav-item-mobile">
            <i class="fas fa-home nav-icon"></i>
            ໜ້າຫຼັກ
        </a>
        <a href="cart.php" class="nav-item-mobile active position-relative">
            <i class="fas fa-shopping-cart nav-icon"></i>
            ກະຕ່າ
            <?php if (!empty($_SESSION['cart'])): ?>
                <span class="cart-badge-mobile badge bg-danger rounded-pill">
                    <?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="track_order.php" class="nav-item-mobile">
            <i class="fas fa-truck nav-icon"></i>
            ຕິດຕາມ
        </a>
    </div>

    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="mb-2"><i class="fas fa-shopping-cart me-2"></i>ກະຕ່າສິນຄ້າ</h1>
                    <p class="mb-0">ຈັດການສິນຄ້າໃນກະຕ່າຂອງທ່ານ</p>
                </div>
                <div class="col-auto">
                    <a href="index.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>ກັບໄປຊື້ຕໍ່
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="text-muted mb-3">ກະຕ່າສິນຄ້າວ່າງ</h3>
                <p class="text-muted mb-4">ທ່ານຍັງບໍ່ມີສິນຄ້າໃນກະຕ່າ</p>
                <a href="index.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>ໄປຊື້ສິນຄ້າກັນເລີຍ!
                </a>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="update_cart" value="1">
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    ລາຍການສິນຄ້າ (<?php echo count($_SESSION['cart']); ?> ລາຍການ)
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach($_SESSION['cart'] as $product_id => $item): ?>
                                    <div class="list-group-item cart-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center">
                                                    <div class="product-icon me-3">
                                                        <?php 
                                                        $icons = ['', '', '', '', '', ''];
                                                        $icon_index = $product_id % count($icons);
                                                        echo $icons[$icon_index];
                                                        ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                        <small class="text-muted">ລະຫັດ: #<?php echo $product_id; ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="row align-items-center">
                                                    <div class="col-4 text-center">
                                                        <span class="price-kip">₭<?php echo formatPrice($item['price']); ?></span>
                                                    </div>
                                                    <div class="col-4">
                                                        <input type="number" 
                                                               name="quantities[<?php echo $product_id; ?>]" 
                                                               value="<?php echo $item['quantity']; ?>" 
                                                               min="1" 
                                                               class="form-control quantity-input">
                                                    </div>
                                                    <div class="col-3 text-center">
                                                        <strong class="price-kip">₭<?php echo formatPrice($item['price'] * $item['quantity']); ?></strong>
                                                    </div>
                                                    <div class="col-1 text-end">
                                                        <a href="cart.php?remove=<?php echo $product_id; ?>" 
                                                           class="btn btn-outline-danger btn-sm"
                                                           onclick="return confirm('ທ່ານ​ແນ່​ໃຈ​ບໍ່​ທີ່​ຈະ​ລຶບ​ສິນ​ຄ້າ​ນີ້​ອອກ​ຈາກ​ກະ​ຕ່າ?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <a href="index.php" class="btn btn-outline-primary flex-fill">
                                <i class="fas fa-arrow-left me-2"></i>ຊື້ສິນຄ້າຕໍ່
                            </a>
                            <button type="submit" class="btn btn-warning flex-fill">
                                <i class="fas fa-sync-alt me-2"></i>ອັບເດດກະຕ່າ
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mt-4 mt-lg-0">
                        <div class="total-section">
                            <h4 class="mb-3"><i class="fas fa-receipt me-2"></i>ສະຫຼຸບຄຳສັ່ງຊື້</h4>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>ຈຳນວນສິນຄ້າ:</span>
                                    <span><?php echo count($_SESSION['cart']); ?> ລາຍການ</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>ຈຳນວນທັງໝົດ:</span>
                                    <span><?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?> ອັນ</span>
                                </div>
                                <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>ຍອດທັງໝົດ:</strong>
                                    <h3 class="mb-0 text-success">₭<?php echo $totalFormatted; ?></h3>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <a href="checkout.php" class="btn btn-success w-100 btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>ດຳເນີນການສັ່ງຊື້
                                </a>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <small class="text-light">
                                    <i class="fas fa-truck me-1"></i>
                                    ຈ່າຍເງິນປາຍທາງ (COD) - ອານຸສິດເທົ່ານັ້ນ
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile navigation active state
        document.querySelectorAll('.nav-item-mobile').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item-mobile').forEach(i => {
                    i.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
        
        // Auto update cart when quantity changes (optional)
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                // Optional: Auto submit form when quantity changes
                // this.form.submit();
            });
        });
        
        // Confirm before remove
        function confirmRemove(productName) {
            return confirm('ທ່ານ​ແນ່​ໃຈ​ບໍ່​ທີ່​ຈະ​ລຶບ​ "' + productName + '" ​ອອກ​ຈາກ​ກະ​ຕ່າ?');
        }
    </script>
</body>
</html>