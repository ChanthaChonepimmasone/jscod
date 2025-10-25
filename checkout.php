<?php
session_start();
require_once "config/database.php";

// ຟັງຊັ່ນຈັດຮູບແບບລາຄາ
function formatPrice($price) {
    return number_format($price, 0, '.', ',');
}

if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$connection = $db->getConnection();

$total = 0;
$error = "";

// ກວດສອບ stock ກ່ອນສັ່ງຊື້
foreach ($_SESSION['cart'] as $product_id => $item) {
    $stmt = $connection->prepare("SELECT stock, name FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $error = "ສິນຄ້າບາງລາຍການບໍ່ພົບໃນລະບົບ";
        break;
    }
    
    if ($product['stock'] < $item['quantity']) {
        $error = "ສິນຄ້າ '{$product['name']}' ມີໃນສະຕ໊ອກແຕ່ {$product['stock']} ອັນ ແຕ່ທ່ານສັ່ງ {$item['quantity']} ອັນ";
        break;
    }
    
    $total += $item['price'] * $item['quantity'];
}

$totalFormatted = formatPrice($total);

// ສັ່ງຊື້
if ($_POST && isset($_POST['place_order']) && !$error) {
    $order_number = 'ORD' . date('YmdHis') . rand(100, 999);
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $shipping_address = trim($_POST['shipping_address']);
    
    try {
        $connection->beginTransaction();
        
        // ບັນທຶກອໍເດີ
        $stmt = $connection->prepare("
            INSERT INTO orders (order_number, customer_name, customer_phone, shipping_address, total_amount, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$order_number, $customer_name, $customer_phone, $shipping_address, $total]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("ບໍ່ສາມາດບັນທຶກອໍເດີໄດ້");
        }
        
        $order_id = $connection->lastInsertId();
        
        // ບັນທຶກລາຍການສິນຄ້າ ແລະ ອັບເດດສະຕ໊ອກ
        foreach ($_SESSION['cart'] as $product_id => $item) {
            // ກວດສອບ stock ອີກຄັ້ງກ່ອນອັບເດດ
            $stmt = $connection->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product['stock'] < $item['quantity']) {
                throw new Exception("ສິນຄ້າ ID {$product_id} ມີໃນສະຕ໊ອກບໍ່ພຽງພໍ");
            }
            
            // ບັນທຶກລາຍການສິນຄ້າ
            $stmt = $connection->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, $product_id, $item['quantity'], $item['price']]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("ບໍ່ສາມາດບັນທຶກລາຍການສິນຄ້າໄດ້");
            }
            
            // ຫຼຸດສະຕ໊ອກ
            $stmt = $connection->prepare("
                UPDATE products SET stock = stock - ? WHERE id = ?
            ");
            $stmt->execute([$item['quantity'], $product_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("ບໍ່ສາມາດອັບເດດສະຕ໊ອກໄດ້");
            }
        }
        
        // ບັນທຶກປະຫວັດການຕິດຕາມ
        $stmt = $connection->prepare("
            INSERT INTO order_tracking (order_id, status, description) 
            VALUES (?, 'pending', 'ໄດ້ຮັບຄຳສັ່ງຊື້ແລ້ວ ລໍຖ້າການຢືນຢັນ')
        ");
        $stmt->execute([$order_id]);
        
        $connection->commit();
        
        // ລວບລວມຂໍ້ມູນສຳລັບ session
        $order_data = [
            'order_number' => $order_number,
            'customer_name' => $customer_name,
            'total_amount' => $total,
            'items' => $_SESSION['cart']
        ];
        
        // ລ້າງກະຕ່າ ແລະ ບັນທຶກເລກອໍເດີ
        $_SESSION['cart'] = [];
        $_SESSION['last_order'] = $order_data;
        
        // ໂອນໜ້າໄປຍັງໜ້າສຳເລັດ
        header("Location: order_success.php");
        exit();
        
    } catch (Exception $e) {
        $connection->rollBack();
        $error = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
        
        // ບັນທຶກ error log
        error_log("Order Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ດຳເນີນການສັ່ງຊື້ - JS.Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Noto Sans Lao', sans-serif;
            background-color: #f8fafc;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .price-kip {
            color: #059669;
            font-weight: bold;
        }
        .btn-success {
            background: linear-gradient(135deg, #059669, #047857);
            border: none;
        }
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        .page-header {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            padding: 2rem 0;
            border-radius: 0 0 20px 20px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #059669, #047857);">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>JS & COD
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="cart.php">
                    <i class="fas fa-arrow-left me-1"></i>ກັບໄປກະຕ່າ
                </a>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="text-center mb-2"><i class="fas fa-credit-card me-2"></i>ດຳເນີນການສັ່ງຊື້</h1>
            <p class="text-center mb-0">ກະລຸນາກອກຂໍ້ມູນຂອງທ່ານໃຫ້ຖືກຕ້ອງ</p>
        </div>
    </div>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <?php if ($error): ?>
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>ຂໍ້ຜິດພາດ</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo $error; ?></p>
                        <a href="cart.php" class="btn btn-primary">ກັບໄປກະຕ່າສິນຄ້າ</a>
                    </div>
                </div>
                <?php else: ?>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>ຂໍ້ມູນຜູ້ຮັບ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="customer_name" class="form-label">ຊື່-ນາມສະກຸນ *</label>
                                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                                   value="<?php echo $_POST['customer_name'] ?? ''; ?>" required>
                                            <div class="form-text">ກະລຸນາກອກຊື່ ແລະ ນາມສະກຸນຂອງທ່ານ</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="customer_phone" class="form-label">ເບີໂທລະສັບ *</label>
                                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                                   value="<?php echo $_POST['customer_phone'] ?? ''; ?>" required>
                                            <div class="form-text">ເບີໂທລະສັບທີ່ສາມາດຕິດຕໍ່ໄດ້</div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="shipping_address" class="form-label">ທີ່ຢູ່ຈັດສົ່ງ *</label>
                                        <textarea class="form-control" id="shipping_address" name="shipping_address" 
                                                  rows="4" required placeholder="ກະລຸນາລະບຸທີ່ຢູ່ຈັດສົ່ງຢ່າງລະອຽດ..."><?php echo $_POST['shipping_address'] ?? ''; ?></textarea>
                                        <div class="form-text">ກະລຸນາລະບຸທີ່ຢູ່ໃຫ້ຊັດເຈນ ເມືອງ ແຂວງ ແລະ ລະຫັດໄປສະນີ (ຖ້າມີ)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>ສະຫຼຸບຄຳສັ່ງຊື້</h5>
                                </div>
                                <div class="card-body">
                                    <h6>ລາຍການສິນຄ້າ</h6>
                                    <?php foreach($_SESSION['cart'] as $product_id => $item): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <small><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></small>
                                            <small class="price-kip">₭<?php echo formatPrice($item['price'] * $item['quantity']); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <hr>
                                    <div class="d-flex justify-content-between mb-3">
                                        <strong>ຍອດລວມ:</strong>
                                        <strong class="text-success">
                                            ₭<?php echo $totalFormatted; ?>
                                        </strong>
                                    </div>
                                    
                                    <button type="submit" name="place_order" class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-credit-card me-2"></i>ຢືນຢັນການສັ່ງຊື້
                                    </button>
                                    
                                    <div class="mt-3 text-center">
                                        <small class="text-muted">
                                            <i class="fas fa-truck me-1"></i>
                                            ຈ່າຍເງິນປາຍທາງ (COD) - ສົ່ງຟຣີ
                                        </small>
                                    </div>

                                    <div class="mt-3">
                                        <div class="alert alert-info small">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <strong>ຂໍ້ມູນການສັ່ງຊື້:</strong><br>
                                            ຫຼັງຈາກສັ່ງຊື້ສຳເລັດ ພວກເຮົາຈະຕິດຕໍ່ທ່ານຜ່ານເບີໂທລະສັບເພື່ອຢືນຢັນການສັ່ງຊື້
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav d-lg-none">
        <a href="index.php" class="nav-item-mobile">
            <i class="fas fa-home nav-icon"></i>
            ໜ້າຫຼັກ
        </a>
        <a href="cart.php" class="nav-item-mobile">
            <i class="fas fa-shopping-cart nav-icon"></i>
            ກະຕ່າ
        </a>
        <a href="track_order.php" class="nav-item-mobile">
            <i class="fas fa-truck nav-icon"></i>
            ຕິດຕາມ
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('customer_name').value.trim();
            const phone = document.getElementById('customer_phone').value.trim();
            const address = document.getElementById('shipping_address').value.trim();
            
            if (name === '') {
                alert('❌ ກະລຸນາກອກຊື່-ນາມສະກຸນ');
                e.preventDefault();
                return;
            }
            
            if (phone === '') {
                alert('❌ ກະລຸນາກອກເບີໂທລະສັບ');
                e.preventDefault();
                return;
            }
            
            if (address === '') {
                alert('❌ ກະລຸນາກອກທີ່ຢູ່ຈັດສົ່ງ');
                e.preventDefault();
                return;
            }
            
            // Phone number validation (basic)
            const phoneRegex = /^[0-9+\-\s()]{8,}$/;
            if (!phoneRegex.test(phone)) {
                alert('❌ ກະລຸນາກອກເບີໂທລະສັບໃຫ້ຖືກຕ້ອງ');
                e.preventDefault();
                return;
            }
            
            console.log('✅ ຟອມພ້ອມສົ່ງແລ້ວ');
        });

        // Mobile navigation
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('.nav-item-mobile');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    navItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>