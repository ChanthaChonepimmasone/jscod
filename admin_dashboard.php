<?php
session_start();
require_once "config/database.php";

// ຟັງຊັ່ນຈັດຮູບແບບລາຄາ
function formatPrice($price) {
    return number_format($price, 0, '.', ',');
}

// ກວດສອບການລັອກອິນ
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$db = new Database();
$connection = $db->getConnection();

// ດຶງສະຖິຕິ
$total_products = $connection->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $connection->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $connection->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'delivered'")->fetchColumn();
$pending_orders = $connection->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

$total_revenue_formatted = formatPrice($total_revenue);

// ອໍເດີລ່າສຸດ
$recent_orders = $connection->query("
    SELECT * FROM orders 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ສິນຄ້າຂາຍດີ
$popular_products = $connection->query("
    SELECT p.name, p.category, SUM(oi.quantity) as total_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ຟັງຊັ່ນແປງສະຖານະເປັນພາສາລາວ
function getStatusText($status) {
    $statuses = [
        'pending' => 'ລໍຖ້າການດຳເນີນການ',
        'confirmed' => 'ຢືນຢັນແລ້ວ',
        'packing' => 'ກຳລັງຈັດເກັບສິນຄ້າ',
        'shipped' => 'ຈັດສົ່ງແລ້ວ',
        'delivered' => 'ຈັດສົ່ງສຳເລັດ',
        'cancelled' => 'ຍົກເລີກ'
    ];
    return $statuses[$status] ?? $status;
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
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ລະບົບຫຼັງບ້ານ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
        }
        
        body {
            font-family: 'Noto Sans Lao', 'Segoe UI', sans-serif;
            background-color: #f8fafc;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e293b, #334155);
        }
        
        .sidebar .nav-link {
            color: #e2e8f0;
            padding: 12px 20px;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .stats-card {
            border-radius: 12px;
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .dashboard-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .card-header {
            border-bottom: 1px solid #e2e8f0;
        }
        
        .quick-action-btn {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            border-color: var(--primary-color);
        }
        
        .revenue-amount {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--success-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white p-3 border-bottom border-secondary">
                        <h5 class="mb-2">JS & COD</h5>
                        <small class="text-light">ລະບົບຫຼັງບ້ານ</small>
                        <div class="mt-2">
                            <small class="text-light">ຍິນດີຕ້ອນຮັບ, <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></small>
                        </div>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_dashboard.php">
                                <i class="fas fa-chart-bar me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add_product.php">
                                <i class="fas fa-plus me-2"></i>ເພີ່ມສິນຄ້າ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_products.php">
                                <i class="fas fa-boxes me-2"></i>ຈັດການສິນຄ້າ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>ຈັດການອໍເດີ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php" target="_blank">
                                <i class="fas fa-store me-2"></i>ເບິ່ງຫນ້າຮ້ານ
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="admin_logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>ອອກຈາກລະບົບ
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-dark"><i class="fas fa-chart-bar me-2"></i>Dashboard</h2>
                    <small class="text-muted">ອັບເດດຫຼ້າສຸດ: <?php echo date('d/m/Y H:i:s'); ?></small>
                </div>

                <!-- ສະຖິຕິຫຼັກ -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $total_products; ?></h3>
                                        <p class="mb-0">ສິນຄ້າທັງໝົດ</p>
                                    </div>
                                    <div class="dashboard-icon">
                                        <i class="fas fa-box"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $total_orders; ?></h3>
                                        <p class="mb-0">ອໍເດີທັງໝົດ</p>
                                    </div>
                                    <div class="dashboard-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="revenue-amount">₭<?php echo $total_revenue_formatted; ?></div>
                                        <p class="mb-0">ລາຍຮັບທັງໝົດ</p>
                                    </div>
                                    <div class="dashboard-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $pending_orders; ?></h3>
                                        <p class="mb-0">ລໍຖ້າດຳເນີນການ</p>
                                    </div>
                                    <div class="dashboard-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- ອໍເດີລ່າສຸດ -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-clock me-2"></i>ອໍເດີຫຼ້າສຸດ</h6>
                                <span class="badge bg-light text-dark"><?php echo count($recent_orders); ?> ອໍເດີ</span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_orders)): ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                        <p class="mb-0">ຍັງບໍ່ມີອໍເດີ</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach($recent_orders as $order): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <strong class="text-truncate" style="max-width: 150px;"><?php echo $order['order_number']; ?></strong>
                                                    <span class="badge bg-<?php echo getStatusBadge($order['status']); ?> ms-2">
                                                        <?php echo getStatusText($order['status']); ?>
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['customer_name']); ?></small>
                                                    <strong class="text-success">₭<?php echo formatPrice($order['total_amount']); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ສິນຄ້າຂາຍດີ -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>ສິນຄ້າຂາຍດີ</h6>
                                <span class="badge bg-light text-dark"><?php echo count($popular_products); ?> ສິນຄ້າ</span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($popular_products)): ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                                        <p class="mb-0">ຍັງບໍ່ມີຂໍ້ມູນການຂາຍ</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach($popular_products as $product): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <div class="fw-medium text-truncate"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <small class="text-muted"><?php echo $product['category']; ?></small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill">
                                                ຂາຍແລ້ວ <?php echo $product['total_sold']; ?> ອັນ
                                            </span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ສະຖິຕິເພີ່ມເຕີມ -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>ສະຖິຕິສະຕ໊ອກ</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $low_stock = $connection->query("SELECT COUNT(*) FROM products WHERE stock < 5 AND stock > 0")->fetchColumn();
                                $out_of_stock = $connection->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();
                                $in_stock = $connection->query("SELECT COUNT(*) FROM products WHERE stock >= 5")->fetchColumn();
                                ?>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="text-success">
                                            <h4><?php echo $in_stock; ?></h4>
                                            <small>ສະຕ໊ອກພຽງພໍ</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-warning">
                                            <h4><?php echo $low_stock; ?></h4>
                                            <small>ເຫຼືອນ້ອຍ</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-danger">
                                            <h4><?php echo $out_of_stock; ?></h4>
                                            <small>ສິນຄ້າຫມົດ</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0"><i class="fas fa-truck me-2"></i>ສະຖິຕິອໍເດີ</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $delivered_orders = $connection->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
                                $shipped_orders = $connection->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn();
                                $cancelled_orders = $connection->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn();
                                ?>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="text-success">
                                            <h4><?php echo $delivered_orders; ?></h4>
                                            <small>ສົ່ງສຳເລັດ</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-info">
                                            <h4><?php echo $shipped_orders; ?></h4>
                                            <small>ກຳລັງສົ່ງ</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-danger">
                                            <h4><?php echo $cancelled_orders; ?></h4>
                                            <small>ຍົກເລີກ</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ການດຳເນີນການດ່ວນ -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>ການດຳເນີນການດ່ວນ</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-3">
                                        <a href="add_product.php" class="btn btn-outline-primary btn-lg w-100 py-3 quick-action-btn">
                                            <i class="fas fa-plus fa-2x mb-2"></i><br>
                                            ເພີ່ມສິນຄ້າ
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="manage_products.php" class="btn btn-outline-success btn-lg w-100 py-3 quick-action-btn">
                                            <i class="fas fa-boxes fa-2x mb-2"></i><br>
                                            ຈັດການສິນຄ້າ
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="manage_orders.php" class="btn btn-outline-warning btn-lg w-100 py-3 quick-action-btn">
                                            <i class="fas fa-shopping-cart fa-2x mb-2"></i><br>
                                            ຈັດການອໍເດີ
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="index.php" target="_blank" class="btn btn-outline-info btn-lg w-100 py-3 quick-action-btn">
                                            <i class="fas fa-store fa-2x mb-2"></i><br>
                                            ເບິ່ງຫນ້າຮ້ານ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh dashboard every 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add click animation to stats cards
            const statsCards = document.querySelectorAll('.stats-card');
            statsCards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });

            // Update time every minute
            function updateTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('lo-LA');
                const dateString = now.toLocaleDateString('lo-LA');
                document.querySelector('.text-muted').textContent = `ອັບເດດຫຼ້າສຸດ: ${dateString} ${timeString}`;
            }
            
            setInterval(updateTime, 60000);
        });
    </script>
</body>
</html>