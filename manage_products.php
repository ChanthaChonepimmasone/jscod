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

// ດຶງຂໍ້ມູນສິນຄ້າທັງໝົດ
try {
    $stmt = $connection->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "ບໍ່ສາມາດດຶງຂໍ້ມູນສິນຄ້າໄດ້: " . $e->getMessage();
    $products = [];
}

// ລຶບສິນຄ້າ
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    try {
        // ກວດສອບວ່າມີອໍເດີທີ່ໃຊ້ສິນຄ້ານີ້ຢູ່ຫຼືບໍ່
        $check_stmt = $connection->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $check_stmt->execute([$product_id]);
        $order_count = $check_stmt->fetchColumn();
        
        if ($order_count > 0) {
            $error_message = "ບໍ່ສາມາດລຶບສິນຄ້າໄດ້ ເພາະມີອໍເດີທີ່ໃຊ້ສິນຄ້ານີ້ຢູ່";
        } else {
            $stmt = $connection->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            
            if ($stmt->rowCount() > 0) {
                $success_message = "ລຶບສິນຄ້າສຳເລັດແລ້ວ!";
                // ລີເຟຣຊໜ້າເພື່ອສະແດງຂໍ້ມູນລ່າສຸດ
                header("Location: manage_products.php?success=" . urlencode($success_message));
                exit();
            } else {
                $error_message = "ບໍ່ພົບສິນຄ້າທີ່ຕ້ອງການລຶບ";
            }
        }
    } catch (Exception $e) {
        $error_message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
    }
}

// ສະແດງຂໍ້ຄວາມສຳເລັດຈາກ URL parameter
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// ສະແດງຂໍ້ຄວາມຜິດພາດຈາກ URL parameter
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການສິນຄ້າ - ລະບົບຫຼັງບ້ານ</title>
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
            border-radius: 0;
        }
        .sidebar .nav-link:hover {
            background-color: #495057;
        }
        .sidebar .nav-link.active {
            background-color: #dc2626;
        }
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stats-card {
            border-left: 4px solid #dc2626;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .price-kip {
            font-size: 0.85em;
            color: #059669;
            font-weight: bold;
        }
        .stock-low {
            background-color: #fef3c7;
            color: #92400e;
        }
        .stock-out {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .stock-good {
            background-color: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar p-0">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white p-3">
                        <h5>🛍️ ລະບົບຫຼັງບ້ານ</h5>
                        <small>ຍິນດີຕ້ອນຮັບ, <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">
                                <i class="fas fa-chart-bar me-2"></i>ໜ້າຫຼັກ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add_product.php">
                                <i class="fas fa-plus me-2"></i>ເພີ່ມສິນຄ້າ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_products.php">
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
                                <i class="fas fa-store me-2"></i>ເບິ່ງໜ້າຮ້ານ
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="admin_logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>ອອກຈາກລະບົບ
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 col-lg-10 px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-boxes me-2"></i>ຈັດການສິນຄ້າ</h2>
                    <a href="add_product.php" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i> ເພີ່ມສິນຄ້າໃໝ່
                    </a>
                </div>

                <!-- ສະແດງຂໍ້ຄວາມແຈ້ງເຕືອນ -->
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
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="text-primary"><?php echo count($products); ?></h4>
                                        <p class="card-text mb-0">ສິນຄ້າທັງໝົດ</p>
                                    </div>
                                    <i class="fas fa-box text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card" style="border-left-color: #28a745;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="text-success">
                                            <?php 
                                            $in_stock = array_filter($products, function($p) { 
                                                return $p['stock'] > 0; 
                                            });
                                            echo count($in_stock); 
                                            ?>
                                        </h4>
                                        <p class="card-text mb-0">ສິນຄ້າມີສະຕ໊ອກ</p>
                                    </div>
                                    <i class="fas fa-check-circle text-success fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card" style="border-left-color: #dc3545;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="text-danger">
                                            <?php 
                                            $out_of_stock = array_filter($products, function($p) { 
                                                return $p['stock'] == 0; 
                                            });
                                            echo count($out_of_stock); 
                                            ?>
                                        </h4>
                                        <p class="card-text mb-0">ສິນຄ້າຫມົດ</p>
                                    </div>
                                    <i class="fas fa-times-circle text-danger fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stats-card" style="border-left-color: #ffc107;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="text-warning">
                                            <?php 
                                            $low_stock = array_filter($products, function($p) { 
                                                return $p['stock'] < 5 && $p['stock'] > 0; 
                                            });
                                            echo count($low_stock); 
                                            ?>
                                        </h4>
                                        <p class="card-text mb-0">ສິນຄ້າເຫຼືອນ້ອຍ</p>
                                    </div>
                                    <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>ລາຍຊື່ສິນຄ້າທັງໝົດ</h5>
                        <span class="badge bg-light text-dark fs-6"><?php echo count($products); ?> ລາຍຊື່</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">ຍັງບໍ່ມີສິນຄ້າ</h4>
                                <p class="text-muted mb-4">ເລີ່ມຕົ້ນໂດຍການເພີ່ມສິນຄ້າທຳອິດຂອງທ່ານ</p>
                                <a href="add_product.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus me-2"></i>ເພີ່ມສິນຄ້າທຳອິດ
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="80" class="text-center">ຮູບພາບ</th>
                                            <th>ຊື່ສິນຄ້າ</th>
                                            <th>ປະເພດ</th>
                                            <th class="text-center">ລາຄາ</th>
                                            <th class="text-center">ສະຕ໊ອກ</th>
                                            <th class="text-center">ວັນທີເພີ່ມ</th>
                                            <th width="120" class="text-center">ການດຳເນີນການ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($products as $product): ?>
                                        <tr>
                                            <td class="text-center">
                                                <div class="product-img text-muted mx-auto">
                                                    <?php 
                                                    $icons = ['📱', '💻', '🎧', '⌚', '📸', '🖥️'];
                                                    $icon_index = $product['id'] % count($icons);
                                                    echo $icons[$icon_index];
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <strong class="mb-1"><?php echo htmlspecialchars($product['name']); ?></strong>
                                                    <small class="text-muted"><?php echo htmlspecialchars($product['description']); ?></small>
                                                    <div class="mt-1">
                                                        <?php if ($product['stock'] == 0): ?>
                                                            <span class="badge bg-danger">ສິນຄ້າຫມົດ</span>
                                                        <?php elseif ($product['stock'] < 5): ?>
                                                            <span class="badge bg-warning">ເຫຼືອນ້ອຍ</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">ມີສິນຄ້າ</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category']); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <strong class="text-success">₭<?php echo formatPrice($product['price']); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold <?php 
                                                    echo $product['stock'] == 0 ? 'text-danger' : 
                                                         ($product['stock'] < 5 ? 'text-warning' : 'text-success'); 
                                                ?>">
                                                    <?php echo number_format($product['stock']); ?> ອັນ
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?></small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="ແກ້ໄຂສິນຄ້າ">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="manage_products.php?delete=<?php echo $product['id']; ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບສິນຄ້າ \"<?php echo addslashes($product['name']); ?>\" ?\nການກະທຳນີ້ບໍ່ສາມາດຍ້ອນກັບໄດ້!')"
                                                       title="ລຶບສິນຄ້າ">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination (ຖ້າມີສິນຄ້າຈຳນວນຫຼາຍ) -->
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1">ກ່ອນໜ້າ</a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">ຖັດໄປ</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ການດຳເນີນການດ່ວນ -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>ການດຳເນີນການດ່ວນ</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4 mb-3">
                                        <a href="add_product.php" class="btn btn-outline-primary btn-lg w-100 py-3">
                                            <i class="fas fa-plus fa-2x mb-2"></i><br>
                                            ເພີ່ມສິນຄ້າໃໝ່
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="manage_orders.php" class="btn btn-outline-success btn-lg w-100 py-3">
                                            <i class="fas fa-shopping-cart fa-2x mb-2"></i><br>
                                            ຈັດການອໍເດີ
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="index.php" target="_blank" class="btn btn-outline-info btn-lg w-100 py-3">
                                            <i class="fas fa-store fa-2x mb-2"></i><br>
                                            ເບິ່ງໜ້າຮ້ານຄ້າ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

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

        // Confirm before delete
        function confirmDelete(productName) {
            return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບສິນຄ້າ "' + productName + '" ?\nການກະທຳນີ້ບໍ່ສາມາດຍ້ອນກັບໄດ້!');
        }

        // Search functionality
        function searchProducts() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('productsTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const tdName = tr[i].getElementsByTagName('td')[1];
                const tdCategory = tr[i].getElementsByTagName('td')[2];
                if (tdName || tdCategory) {
                    const txtValueName = tdName.textContent || tdName.innerText;
                    const txtValueCategory = tdCategory.textContent || tdCategory.innerText;
                    if (txtValueName.toLowerCase().indexOf(filter) > -1 || 
                        txtValueCategory.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>