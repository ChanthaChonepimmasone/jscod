<?php
session_start();
require_once "config/database.php";

// ກວດສອບການລັອກອິນ
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$db = new Database();
$connection = $db->getConnection();

// ຟັງຊັ່ນຈັດຮູບແບບລາຄາ
function formatPrice($price) {
    return number_format($price, 0, '.', ',');
}

$success_message = "";
$error_message = "";

// ດຶງອໍເດີທັງໝົດ
$stmt = $connection->query("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(oi.product_id) as product_ids
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ອັບເດດສະຖານະອໍເດີ
if ($_POST && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $tracking_number = $_POST['tracking_number'] ?? '';
    $shipping_company = $_POST['shipping_company'] ?? '';
    
    try {
        $connection->beginTransaction();
        
        // ອັບເດດອໍເດີ
        $stmt = $connection->prepare("
            UPDATE orders 
            SET status = ?, tracking_number = ?, shipping_company = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$status, $tracking_number, $shipping_company, $order_id]);
        
        // ບັນທຶກປະຫວັດ
        $descriptions = [
            'pending' => 'ໄດ້ຮັບຄຳສັ່ງຊື້ແລ້ວ ລໍຖ້າການຢືນຢັນ',
            'confirmed' => 'ຢືນຢັນຄຳສັ່ງຊື້ແລ້ວ',
            'packing' => 'ກຳລັງຈັດການສິນຄ້າ',
            'shipped' => 'ຈັດສົ່ງສິນຄ້າແລ້ວ',
            'delivered' => 'ຈັດສົ່ງສຳເລັດ',
            'cancelled' => 'ຍົກເລີກຄຳສັ່ງຊື້'
        ];
        
        $stmt = $connection->prepare("
            INSERT INTO order_tracking (order_id, status, description) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$order_id, $status, $descriptions[$status] ?? 'ອັບເດດສະຖານະ']);
        
        $connection->commit();
        $success_message = "ອັບເດດສະຖານະອໍເດີສຳເລັດ!";
        
    } catch (Exception $e) {
        $connection->rollBack();
        $error_message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
    }
}

// ສະແດງຂໍ້ຄວາມສຳເລັດຈາກ URL parameter
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
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
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການອໍເດີ - ລະບົບຫຼັງບ້ານ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Noto Sans Lao', sans-serif;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: #fff;
            padding: 12px 20px;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
        }
        .sidebar .nav-link.active {
            background-color: #dc2626;
        }
        .order-details {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
        }
        .price-kip {
            font-size: 0.85em;
            color: #059669;
            font-weight: bold;
        }
        .order-item {
            border-left: 4px solid #007bff;
            padding-left: 15px;
        }
        .stats-card {
            border-left: 4px solid #dc2626;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white p-3">
                        <h5>🛍️ ລະບົບຫຼັງບ້ານ</h5>
                        <small>ຍິນດີຕ້ອນຮັບ, <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">
                                📊 ໜ້າຫຼັກ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add_product.php">
                                ➕ ເພີ່ມສິນຄ້າ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_products.php">
                                📦 ຈັດການສິນຄ້າ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_orders.php">
                                🛒 ຈັດການອໍເດີ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php" target="_blank">
                                🌐 ເບິ່ງໜ້າຮ້ານ
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="admin_logout.php">
                                🚪 ອອກຈາກລະບົບ
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>🛒 ຈັດການອໍເດີ</h2>
                    <span class="badge bg-primary fs-6"><?php echo count($orders); ?> ອໍເດີ</span>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- ສະຖິຕິ -->
                <div class="row mb-4">
                    <div class="col-md-2 mb-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <h4 class="text-primary"><?php echo count($orders); ?></h4>
                                <p class="mb-0">ທັງໝົດ</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card stats-card" style="border-left-color: #ffc107;">
                            <div class="card-body text-center">
                                <h4 class="text-warning">
                                    <?php 
                                    $pending = array_filter($orders, function($o) { 
                                        return $o['status'] == 'pending'; 
                                    });
                                    echo count($pending); 
                                    ?>
                                </h4>
                                <p class="mb-0">ລໍຖ້າ</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card stats-card" style="border-left-color: #17a2b8;">
                            <div class="card-body text-center">
                                <h4 class="text-info">
                                    <?php 
                                    $confirmed = array_filter($orders, function($o) { 
                                        return $o['status'] == 'confirmed'; 
                                    });
                                    echo count($confirmed); 
                                    ?>
                                </h4>
                                <p class="mb-0">ຢືນຢັນ</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card stats-card" style="border-left-color: #6c757d;">
                            <div class="card-body text-center">
                                <h4 class="text-secondary">
                                    <?php 
                                    $shipped = array_filter($orders, function($o) { 
                                        return $o['status'] == 'shipped'; 
                                    });
                                    echo count($shipped); 
                                    ?>
                                </h4>
                                <p class="mb-0">ຈັດສົ່ງ</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card stats-card" style="border-left-color: #28a745;">
                            <div class="card-body text-center">
                                <h4 class="text-success">
                                    <?php 
                                    $delivered = array_filter($orders, function($o) { 
                                        return $o['status'] == 'delivered'; 
                                    });
                                    echo count($delivered); 
                                    ?>
                                </h4>
                                <p class="mb-0">ສຳເລັດ</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <div class="card stats-card" style="border-left-color: #dc3545;">
                            <div class="card-body text-center">
                                <h4 class="text-danger">
                                    <?php 
                                    $cancelled = array_filter($orders, function($o) { 
                                        return $o['status'] == 'cancelled'; 
                                    });
                                    echo count($cancelled); 
                                    ?>
                                </h4>
                                <p class="mb-0">ຍົກເລີກ</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">ລາຍຊື່ອໍເດີທັງໝົດ</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">ຍັງບໍ່ມີອໍເດີ</h5>
                                <p class="text-muted">ເມື່ອມີລູກຄ້າສັ່ງຊື້ ອໍເດີຈະປາກົດຢູ່ນີ້</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ເລກທີອໍເດີ</th>
                                            <th>ລູກຄ້າ</th>
                                            <th>ຈຳນວນສິນຄ້າ</th>
                                            <th>ຍອດລວມ</th>
                                            <th>ສະຖານະ</th>
                                            <th>ວັນທີສັ່ງຊື້</th>
                                            <th width="200">ການດຳເນີນການ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $order['order_number']; ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo $order['customer_phone']; ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $order['item_count']; ?> ອັນ</span>
                                            </td>
                                            <td>
                                                <strong class="text-success">₭<?php echo formatPrice($order['total_amount']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusBadge($order['status']); ?>">
                                                    <?php echo getStatusText($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                                        <i class="fas fa-eye"></i> ເບິ່ງ
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#statusModal<?php echo $order['id']; ?>">
                                                        <i class="fas fa-edit"></i> ແກ້ໄຂ
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal ສຳລັບເບິ່ງລາຍລະອຽດອໍເດີ -->
    <?php foreach($orders as $order): ?>
    <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">ລາຍລະອຽດອໍເດີ: <?php echo $order['order_number']; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>ຂໍ້ມູນລູກຄ້າ</h6>
                            <p><strong>ຊື່:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p><strong>ໂທລະສັບ:</strong> <?php echo $order['customer_phone']; ?></p>
                            <p><strong>ທີ່ຢູ່:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>ຂໍ້ມູນອໍເດີ</h6>
                            <p><strong>ສະຖານະ:</strong> 
                                <span class="badge bg-<?php echo getStatusBadge($order['status']); ?>">
                                    <?php echo getStatusText($order['status']); ?>
                                </span>
                            </p>
                            <p><strong>ຍອດລວມ:</strong> 
                                <span class="text-success">₭<?php echo formatPrice($order['total_amount']); ?></span>
                            </p>
                            <p><strong>ວັນທີສັ່ງ:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                            <?php if ($order['tracking_number']): ?>
                                <p><strong>ເລກພັດສະດຸ:</strong> <?php echo $order['tracking_number']; ?></p>
                                <p><strong>ບໍລິສັດຂົນສົ່ງ:</strong> <?php echo $order['shipping_company']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>ລາຍຊື່ສິນຄ້າ</h6>
                    <?php
                    $items_stmt = $connection->prepare("
                        SELECT oi.*, p.name, p.image 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?
                    ");
                    $items_stmt->execute([$order['id']]);
                    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ສິນຄ້າ</th>
                                    <th>ຈຳນວນ</th>
                                    <th>ລາຄາ</th>
                                    <th>ລວມ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>
                                        <span class="price-kip">₭<?php echo formatPrice($item['price']); ?></span>
                                    </td>
                                    <td>
                                        <span class="price-kip">₭<?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>ລວມທັງໝົດ:</strong></td>
                                    <td>
                                        <strong class="text-success">₭<?php echo formatPrice($order['total_amount']); ?></strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal ສຳລັບແກ້ໄຂສະຖານະ -->
    <div class="modal fade" id="statusModal<?php echo $order['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">ແກ້ໄຂສະຖານະອໍເດີ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">ເລກທີອໍເດີ</label>
                            <input type="text" class="form-control" value="<?php echo $order['order_number']; ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ສະຖານະ</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>ລໍຖ້າດຳເນີນການ</option>
                                <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>ຢືນຢັນແລ້ວ</option>
                                <option value="packing" <?php echo $order['status'] == 'packing' ? 'selected' : ''; ?>>ກຳລັງຈັດການ</option>
                                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>ຈັດສົ່ງແລ້ວ</option>
                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>ຈັດສົ່ງສຳເລັດ</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>ຍົກເລີກ</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ເລກພັດສະດຸ (ຖ້າມີ)</label>
                            <input type="text" name="tracking_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ບໍລິສັດຂົນສົ່ງ (ຖ້າມີ)</label>
                            <input type="text" name="shipping_company" class="form-control" 
                                   value="<?php echo htmlspecialchars($order['shipping_company'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ປິດ</button>
                        <button type="submit" name="update_status" class="btn btn-success">ອັບເດດສະຖານະ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Filter orders by status
        function filterOrders(status) {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    const statusBadge = row.querySelector('.badge');
                    if (statusBadge && statusBadge.textContent.includes(status)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }
    </script>
</body>
</html>