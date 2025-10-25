<?php
session_start();
require_once "config/database.php";

// ຟັງຊັ່ນຈັດຮູບແບບລາຄາ
function formatPrice($price) {
    return number_format($price, 0, '.', ',');
}

$db = new Database();
$connection = $db->getConnection();

$order = null;
$tracking_history = [];
$error_message = "";

// ຄົ້ນຫາອໍເດີ
if ($_POST && isset($_POST['search_order'])) {
    $order_number = trim($_POST['order_number']);
    $customer_phone = trim($_POST['customer_phone']);
    
    if (!empty($order_number) && !empty($customer_phone)) {
        try {
            $stmt = $connection->prepare("
                SELECT * FROM orders 
                WHERE order_number = ? AND customer_phone = ?
            ");
            $stmt->execute([$order_number, $customer_phone]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                // ດຶງປະຫວັດການຕິດຕາມ
                $tracking_stmt = $connection->prepare("
                    SELECT * FROM order_tracking 
                    WHERE order_id = ? 
                    ORDER BY created_at ASC
                ");
                $tracking_stmt->execute([$order['id']]);
                $tracking_history = $tracking_stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $error_message = "ບໍ່ພົບຂໍ້ມູນອໍເດີ ກະລຸນາກວດສອບເລກທີອໍເດີ ແລະ ເບີໂທລະສັບຄືນໃໝ່";
            }
        } catch (Exception $e) {
            $error_message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
        }
    } else {
        $error_message = "ກະລຸນາກອກເລກທີອໍເດີ ແລະ ເບີໂທລະສັບ";
    }
}

function getStatusText($status) {
    $texts = [
        'pending' => 'ລໍຖ້າດຳເນີນການ',
        'confirmed' => 'ຢືນຢັນແລ້ວ',
        'packing' => 'ກຳລັງຈັດການ',
        'shipped' => 'ຈັດສົ່ງແລ້ວ',
        'delivered' => 'ຈັດສົ່ງສຳເລັດ',
        'cancelled' => 'ຍົກເລີກ'
    ];
    return $texts[$status] ?? $status;
}

function getStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'packing' => 'primary',
        'shipped' => 'secondary',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return $badges[$status] ?? 'secondary';
}

function getStatusIcon($status) {
    $icons = [
        'pending' => 'fas fa-clock',
        'confirmed' => 'fas fa-check-circle',
        'packing' => 'fas fa-box',
        'shipped' => 'fas fa-shipping-fast',
        'delivered' => 'fas fa-check-double',
        'cancelled' => 'fas fa-times-circle'
    ];
    return $icons[$status] ?? 'fas fa-info-circle';
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຕິດຕາມອໍເດີ - JS.Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --success-color: #059669;
        }
        
        body {
            font-family: 'Noto Sans Lao', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .tracking-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .search-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
        }
        
        .tracking-timeline {
            position: relative;
            padding: 2rem 0;
        }
        
        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 25px;
            top: 40px;
            bottom: -2rem;
            width: 2px;
            background: #e2e8f0;
        }
        
        .timeline-item:last-child::before {
            display: none;
        }
        
        .timeline-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            z-index: 2;
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .status-active .timeline-icon {
            background: var(--success-color);
            color: white;
        }
        
        .status-pending .timeline-icon {
            background: #e2e8f0;
            color: #64748b;
        }
        
        .order-status-badge {
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
        }
        
        .price-kip {
            font-weight: 700;
            color: var(--success-color);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>JS & COD
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>ໜ້າຫຼັກ
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="tracking-card">
                    <!-- Search Section -->
                    <div class="search-section">
                        <div class="text-center mb-4">
                            <h1><i class="fas fa-truck me-2"></i>ຕິດຕາມອໍເດີ</h1>
                            <p class="mb-0">ກວດສອບສະຖານະການສັ່ງຊື້ຂອງທ່ານ</p>
                        </div>
                        
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="order_number" class="form-label text-white">ເລກທີອໍເດີ</label>
                                    <input type="text" class="form-control form-control-lg" 
                                           id="order_number" name="order_number" 
                                           placeholder="ຕົວຢ່າງ: ORD202312010001" 
                                           value="<?php echo $_POST['order_number'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="customer_phone" class="form-label text-white">ເບີໂທລະສັບ</label>
                                    <input type="tel" class="form-control form-control-lg" 
                                           id="customer_phone" name="customer_phone" 
                                           placeholder="ຕົວຢ່າງ: 02012345678" 
                                           value="<?php echo $_POST['customer_phone'] ?? ''; ?>" required>
                                </div>
                                <div class="col-12 text-center">
                                    <button type="submit" name="search_order" class="btn btn-light btn-lg px-5">
                                        <i class="fas fa-search me-2"></i>ຄົ້ນຫາອໍເດີ
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Results Section -->
                    <div class="p-4">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($order): ?>
                            <!-- Order Information -->
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>ຂໍ້ມູນອໍເດີ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>ເລກທີອໍເດີ:</strong> <?php echo $order['order_number']; ?></p>
                                            <p><strong>ຊື່ລູກຄ້າ:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                            <p><strong>ເບີໂທລະສັບ:</strong> <?php echo $order['customer_phone']; ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>ວັນທີສັ່ງຊື້:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                            <p><strong>ຍອດລວມ:</strong> <span class="price-kip">₭<?php echo formatPrice($order['total_amount']); ?></span></p>
                                            <p><strong>ສະຖານະປະຈຸບັນ:</strong> 
                                                <span class="badge order-status-badge bg-<?php echo getStatusBadge($order['status']); ?>">
                                                    <?php echo getStatusText($order['status']); ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if ($order['tracking_number']): ?>
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-shipping-fast me-2"></i>
                                            <strong>ຂໍ້ມູນການຈັດສົ່ງ:</strong><br>
                                            ເລກພັດສະດຸ: <strong><?php echo $order['tracking_number']; ?></strong><br>
                                            ບໍລິສັດຂົນສົ່ງ: <strong><?php echo $order['shipping_company']; ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Tracking Timeline -->
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>ປະຫວັດການຕິດຕາມ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="tracking-timeline">
                                        <?php if (empty($tracking_history)): ?>
                                            <div class="text-center py-4 text-muted">
                                                <i class="fas fa-history fa-3x mb-3"></i>
                                                <p>ຍັງບໍ່ມີປະຫວັດການຕິດຕາມ</p>
                                            </div>
                                        <?php else: ?>
                                            <?php 
                                            $current_status = $order['status'];
                                            $status_sequence = ['pending', 'confirmed', 'packing', 'shipped', 'delivered'];
                                            $current_index = array_search($current_status, $status_sequence);
                                            ?>
                                            
                                            <?php foreach($tracking_history as $index => $tracking): ?>
                                                <?php 
                                                $is_active = $index === count($tracking_history) - 1;
                                                $status_index = array_search($tracking['status'], $status_sequence);
                                                $is_completed = $status_index !== false && $status_index <= $current_index;
                                                ?>
                                                <div class="timeline-item <?php echo $is_active ? 'status-active' : 'status-pending'; ?>">
                                                    <div class="timeline-icon <?php echo $is_completed ? 'bg-success' : 'bg-light'; ?>">
                                                        <i class="<?php echo getStatusIcon($tracking['status']); ?> <?php echo $is_completed ? 'text-white' : 'text-muted'; ?>"></i>
                                                    </div>
                                                    <div class="timeline-content">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <h6 class="mb-1"><?php echo getStatusText($tracking['status']); ?></h6>
                                                                <p class="text-muted mb-1"><?php echo $tracking['description']; ?></p>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    <?php echo date('d/m/Y H:i', strtotime($tracking['created_at'])); ?>
                                                                </small>
                                                            </div>
                                                            <?php if ($is_active): ?>
                                                                <span class="badge bg-success">ປະຈຸບັນ</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <?php
                            $items_stmt = $connection->prepare("
                                SELECT oi.*, p.name 
                                FROM order_items oi 
                                JOIN products p ON oi.product_id = p.id 
                                WHERE oi.order_id = ?
                            ");
                            $items_stmt->execute([$order['id']]);
                            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            
                            <div class="card mt-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>ລາຍການສິນຄ້າ</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ສິນຄ້າ</th>
                                                    <th class="text-center">ຈຳນວນ</th>
                                                    <th class="text-end">ລາຄາ</th>
                                                    <th class="text-end">ລວມ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($items as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                    <td class="text-end price-kip">₭<?php echo formatPrice($item['price']); ?></td>
                                                    <td class="text-end price-kip">₭<?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>ຍອດລວມ:</strong></td>
                                                    <td class="text-end"><strong class="price-kip">₭<?php echo formatPrice($order['total_amount']); ?></strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Empty State -->
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">ຍັງບໍ່ມີຂໍ້ມູນອໍເດີ</h4>
                                <p class="text-muted">ກະລຸນາກອກເລກທີອໍເດີ ແລະ ເບີໂທລະສັບເພື່ອຕິດຕາມອໍເດີ</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Support Information -->
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h6><i class="fas fa-headset me-2"></i>ຕິດຕໍ່ພວກເຮົາ</h6>
                        <p class="mb-2">ຖ້າທ່ານມີຄຳຖາມ ຫຼື ຕ້ອງການຄວາມຊ່ວຍເຫຼືອ</p>
                        <div class="d-flex justify-content-center gap-3">
                            <span class="badge bg-primary">
                                <i class="fas fa-phone me-1"></i> 020 1234 5678
                            </span>
                            <span class="badge bg-success">
                                <i class="fas fa-envelope me-1"></i> support@jsshop.la
                            </span>
                        </div>
                    </div>
                </div>
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
        <a href="track_order.php" class="nav-item-mobile active">
            <i class="fas fa-truck nav-icon"></i>
            ຕິດຕາມ
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile navigation
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('.nav-item-mobile');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    navItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Auto focus on search input
            document.getElementById('order_number').focus();
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const orderNumber = document.getElementById('order_number').value.trim();
            const phone = document.getElementById('customer_phone').value.trim();
            
            if (orderNumber === '') {
                alert('❌ ກະລຸນາກອກເລກທີອໍເດີ');
                e.preventDefault();
                return;
            }
            
            if (phone === '') {
                alert('❌ ກະລຸນາກອກເບີໂທລະສັບ');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>