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

// ກວດສອບແລະສ້າງໂຟນເດີ images ຖ້າຍັງບໍ່ມີ
$upload_dir = "images/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// ເພີ່ມສິນຄ້າໃໝ່
if ($_POST && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category = trim($_POST['category']);
    
    try {
        // ອັບໂຫຼດຮູບພາບ
        $image = "default.jpg"; // ໃຊ້ຮູບ default ຖ້າບໍ່ມີຮູບ
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['image']['name'];
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_size = $_FILES['image']['size'];
            $file_error = $_FILES['image']['error'];
            
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            // ກວດສອບປະເພດໄຟລ໌
            if (!in_array($file_extension, $allowed_extensions)) {
                $error_message = "ພຽງແຕ່ໄຟລ໌ຮູບພາບ (JPG, JPEG, PNG, GIF, WEBP) ເທົ່ານັ້ນ";
            } 
            // ກວດສອບຂະໜາດໄຟລ໌ (ບໍ່ເກີນ 5MB)
            elseif ($file_size > 5 * 1024 * 1024) {
                $error_message = "ຂະໜາດໄຟລ໌ຕ້ອງບໍ່ເກີນ 5MB";
            }
            // ກວດສອບວ່າເປັນໄຟລ໌ຮູບພາບທີ່ຖືກຕ້ອງ
            elseif (!getimagesize($file_tmp)) {
                $error_message = "ໄຟລ໌ທີ່ອັບໂຫຼດບໍ່ແມ່ນຮູບພາບທີ່ຖືກຕ້ອງ";
            }
            else {
                // ສ້າງຊື່ໄຟລ໌ໃໝ່
                $image = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $image;
                
                // ຍ້າຍໄຟລ໌
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // ສຳເລັດ
                    echo "<script>console.log('ອັບໂຫຼດໄຟລ໌ສຳເລັດ: $upload_path');</script>";
                } else {
                    $error_message = "ບໍ່ສາມາດອັບໂຫຼດໄຟລ໌ໄດ້: " . error_get_last()['message'];
                    $image = "default.jpg"; // ໃຊ້ຮູບ default ແທນ
                }
            }
        }
        
        if (!$error_message) {
            // ກວດສອບຂໍ້ມູນກ່ອນບັນທຶກ
            if (empty($name) || $price <= 0 || $stock < 0) {
                $error_message = "ກະລຸນາກອກຂໍ້ມູນໃຫ້ຖືກຕ້ອງ";
            } else {
                $query = "INSERT INTO products (name, description, price, stock, image, category) 
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($query);
                $stmt->execute([$name, $description, $price, $stock, $image, $category]);
                
                $success_message = "ເພີ່ມສິນຄ້າ '$name' ສຳເລັດແລ້ວ!";
                
                // ລີເຊັດຟອມ
                $_POST = array();
            }
        }
        
    } catch (Exception $e) {
        $error_message = "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເພີ່ມສິນຄ້າ - ລະບົບຫຼັງບ້ານ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        .preview-container {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 5px;
        }
        .file-info {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .price-display {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 5px;
            font-weight: bold;
            color: #059669;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
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
                            <a class="nav-link active" href="add_product.php">
                                ➕ ເພີ່ມສິນຄ້າ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_products.php">
                                📦 ຈັດການສິນຄ້າ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_orders.php">
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
                    <h2>➕ ເພີ່ມສິນຄ້າໃໝ່</h2>
                    <a href="manage_products.php" class="btn btn-secondary">← ກັບສູ່ໜ້າຈັດການສິນຄ້າ</a>
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

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">📝 ຂໍ້ມູນສິນຄ້າ</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="productForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">ຊື່ສິນຄ້າ *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                               required maxlength="255">
                                        <div class="form-text">ຊື່ສິນຄ້າຕ້ອງບໍ່ເກີນ 255 ຕົວອັກສອນ</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="category" class="form-label">ປະເພດ *</label>
                                        <select class="form-select" id="category" name="category" required>
                                            <option value="">ເລືອກປະເພດ</option>
                                            <option value="ໂທລະສັບ" <?php echo (($_POST['category'] ?? '') == 'ໂທລະສັບ') ? 'selected' : ''; ?>>ໂທລະສັບ</option>
                                            <option value="ຄອມພິວເຕີ" <?php echo (($_POST['category'] ?? '') == 'ຄອມພິວເຕີ') ? 'selected' : ''; ?>>ຄອມພິວເຕີ</option>
                                            <option value="ແທັບເລັດ" <?php echo (($_POST['category'] ?? '') == 'ແທັບເລັດ') ? 'selected' : ''; ?>>ແທັບເລັດ</option>
                                            <option value="ອຸປະກອນເສີມ" <?php echo (($_POST['category'] ?? '') == 'ອຸປະກອນເສີມ') ? 'selected' : ''; ?>>ອຸປະກອນເສີມ</option>
                                            <option value="ອື່ນໆ" <?php echo (($_POST['category'] ?? '') == 'ອື່ນໆ') ? 'selected' : ''; ?>>ອື່ນໆ</option>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="price" class="form-label">ລາຄາ (ກີບ) *</label>
                                                <input type="number" class="form-control" id="price" name="price" 
                                                       step="1" min="0" value="<?php echo $_POST['price'] ?? ''; ?>" required>
                                                <div class="form-text">ກະລຸນາກອກລາຄາເປັນຕົວເລກ</div>
                                                <div class="price-display" id="priceDisplay">
                                                    ລາຄາ: <span id="kipPrice">0</span> ກີບ
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="stock" class="form-label">ຈຳນວນໃນສະຕ໊ອກ *</label>
                                                <input type="number" class="form-control" id="stock" name="stock" 
                                                       min="0" value="<?php echo $_POST['stock'] ?? '0'; ?>" required>
                                                <div class="form-text">ກະລຸນາກອກຈຳນວນເປັນຕົວເລກ</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">ລາຍລະອຽດສິນຄ້າ</label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="4" placeholder="ອະທິບາຍລາຍລະອຽດສິນຄ້າ..."
                                                  maxlength="1000"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                        <div class="form-text">ອະທິບາຍລາຍລະອຽດສິນຄ້າ (ບໍ່ເກີນ 1000 ຕົວອັກສອນ)</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="image" class="form-label">ຮູບພາບສິນຄ້າ</label>
                                        <input type="file" class="form-control" id="image" name="image" 
                                               accept="image/*">
                                        <div class="form-text">
                                            ຮອງຮັບໄຟລ໌: JPG, JPEG, PNG, GIF, WEBP (ຂະໜາດບໍ່ເກີນ 5MB)
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="preview-container">
                                            <div id="imagePreview">
                                                <div class="text-muted">
                                                    <i class="fas fa-image fa-3x mb-3"></i>
                                                    <p>ຍັງບໍ່ມີຮູບພາບ<br><small>ເລືອກຮູບພາບເພື່ອສະແດງຕົວຢ່າງ</small></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="fileInfo" class="file-info" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-md-2" onclick="resetPreview()">ລ້າງຟອມ</button>
                                <button type="submit" name="add_product" class="btn btn-success btn-lg">
                                    <i class="fas fa-plus me-2"></i>ເພີ່ມສິນຄ້າ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // ອັບເດດການສະແດງລາຄາ
        function updatePriceDisplay() {
            const priceInput = document.getElementById('price');
            const kipPriceElement = document.getElementById('kipPrice');
            const price = parseFloat(priceInput.value) || 0;
            
            kipPriceElement.textContent = price.toLocaleString('en-US', {
                maximumFractionDigits: 0
            });
        }
        
        // ເລີ່ມຕົ້ນອັບເດດລາຄາ
        document.addEventListener('DOMContentLoaded', function() {
            updatePriceDisplay();
            
            // ຕິດຕາມການປ່ຽນແປງລາຄາ
            document.getElementById('price').addEventListener('input', updatePriceDisplay);
        });

        // ສະແດງຕົວຢ່າງຮູບພາບກ່ອນອັບໂຫຼດ
        document.getElementById('image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const fileInfo = document.getElementById('fileInfo');
            const file = e.target.files[0];
            
            if (file) {
                // ກວດສອບຂະໜາດໄຟລ໌
                if (file.size > 5 * 1024 * 1024) {
                    alert('❌ ຂະໜາດໄຟລ໌ຕ້ອງບໍ່ເກີນ 5MB');
                    this.value = '';
                    resetPreview();
                    return;
                }
                
                // ກວດສອບປະເພດໄຟລ໌
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('❌ ພຽງແຕ່ໄຟລ໌ຮູບພາບ (JPG, JPEG, PNG, GIF, WEBP) ເທົ່ານັ້ນ');
                    this.value = '';
                    resetPreview();
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" class="preview-image" alt="Preview">
                    `;
                    
                    fileInfo.style.display = 'block';
                    fileInfo.innerHTML = `
                        <strong>ຂໍ້ມູນໄຟລ໌:</strong><br>
                        📄 ຊື່: ${file.name}<br>
                        📊 ຂະໜາດ: ${(file.size/1024/1024).toFixed(2)} MB<br>
                        🖼️ ປະເພດ: ${file.type}
                    `;
                }
                reader.readAsDataURL(file);
            } else {
                resetPreview();
            }
        });

        // ລີເຊັດການສະແດງຕົວຢ່າງ
        function resetPreview() {
            const preview = document.getElementById('imagePreview');
            const fileInfo = document.getElementById('fileInfo');
            
            preview.innerHTML = `
                <div class="text-muted">
                    <i class="fas fa-image fa-3x mb-3"></i>
                    <p>ຍັງບໍ່ມີຮູບພາບ<br><small>ເລືອກຮູບພາບເພື່ອສະແດງຕົວຢ່າງ</small></p>
                </div>
            `;
            fileInfo.style.display = 'none';
        }

        // Auto-format ລາຄາ
        document.getElementById('price').addEventListener('blur', function(e) {
            const value = parseFloat(e.target.value);
            if (!isNaN(value) && value >= 0) {
                e.target.value = Math.round(value);
                updatePriceDisplay();
            }
        });

        // ກວດສອບຟອມກ່ອນສົ່ງ
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            const stock = parseInt(document.getElementById('stock').value);
            const category = document.getElementById('category').value;
            
            if (name === '') {
                alert('❌ ກະລຸນາກອກຊື່ສິນຄ້າ');
                e.preventDefault();
                return;
            }
            
            if (isNaN(price) || price <= 0) {
                alert('❌ ກະລຸນາກອກລາຄາທີ່ຖືກຕ້ອງ (ຫຼາຍກວ່າ 0)');
                e.preventDefault();
                return;
            }
            
            if (isNaN(stock) || stock < 0) {
                alert('❌ ກະລຸນາກອກຈຳນວນສະຕ໊ອກທີ່ຖືກຕ້ອງ');
                e.preventDefault();
                return;
            }
            
            if (category === '') {
                alert('❌ ກະລຸນາເລືອກປະເພດ');
                e.preventDefault();
                return;
            }
            
            console.log('✅ ຟອມພ້ອມສົ່ງແລ້ວ');
        });
    </script>
</body>
</html>