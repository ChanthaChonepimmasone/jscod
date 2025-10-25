<?php
session_start();
require_once "config/database.php";

// ຟັງຊັ່ນຈັດຮູບແບບລາຄາ
function formatPrice($price) {
    return number_format($price, 0, '.', ',');
}

if (!isset($_SESSION['last_order'])) {
    header("Location: index.php");
    exit();
}

$order = $_SESSION['last_order'];
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ສຳເລັດການສັ່ງຊື້ - JS.Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --success-color: #059669;
            --primary-color: #2563eb;
        }
        
        body {
            font-family: 'Noto Sans Lao', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .success-header {
            background: linear-gradient(135deg, var(--success-color), #047857);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .checkmark {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: var(--success-color);
            font-size: 2.5rem;
        }
        
        .order-details {
            padding: 2rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .price-kip {
            font-weight: 700;
            color: var(--success-color);
        }
        
        .order-items {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .action-buttons {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .whats-next {
            background: #f0f9ff;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="success-card">
                    <!-- Header -->
                    <div class="success-header">
                        <div class="checkmark">
                            <i class="fas fa-check"></i>
                        </div>
                        <h2 class="mb-2">✅ ສຳເລັດການສັ່ງຊື້!</h2>
                        <p class="mb-0">ຂໍ້ມູນການສັ່ງຊື້ຂອງທ່ານໄດ້ຖືກບັນທຶກແລ້ວ</p>
                    </div>
                    
                    <!-- Order Details -->
                    <div class="order-details">
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-receipt fa-2x me-3 text-primary"></i>
                                <div>
                                    <h5 class="mb-1">ເລກທີອໍເດີ: <strong><?php echo $order['order_number']; ?></strong></h5>
                                    <p class="mb-0">ຈົດໝາຍສະບັບນີ້ໄວ້ເພື່ອອ້າງອີງໃນອະນາຄົດ</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-user me-2"></i>ຂໍ້ມູນຜູ້ຮັບ</h6>
                                <div class="detail-item">
                                    <span>ຊື່-ນາມສະກຸນ:</span>
                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                </div>
                                <div class="detail-item">
                                    <span>ຍອດລວມ:</span>
                                    <strong class="price-kip">₭<?php echo formatPrice($order['total_amount']); ?></strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-shopping-bag me-2"></i>ລາຍການສິນຄ້າ</h6>
                                <div class="order-items">
                                    <?php foreach($order['items'] as $product_id => $item): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <small class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></small>
                                                <br>
                                                <small class="text-muted">x<?php echo $item['quantity']; ?> ອັນ</small>
                                            </div>
                                            <small class="price-kip">₭<?php echo formatPrice($item['price'] * $item['quantity']); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>ລວມທັງໝົດ:</strong>
                                        <strong class="price-kip">₭<?php echo formatPrice($order['total_amount']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- What's Next -->
                        <div class="whats-next">
                            <h6 class="mb-3"><i class="fas fa-clock me-2"></i>ຂັ້ນຕອນຕໍ່ໄປ</h6>
                            <div class="row text-center">
                                <div class="col-md-3 mb-3">
                                    <div class="text-primary">
                                        <i class="fas fa-phone fa-2x mb-2"></i>
                                        <p class="mb-1 small">ຂັ້ນຕອນທີ 1</p>
                                        <p class="mb-0 fw-bold">ຢືນຢັນອໍເດີ</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-info">
                                        <i class="fas fa-box fa-2x mb-2"></i>
                                        <p class="mb-1 small">ຂັ້ນຕອນທີ 2</p>
                                        <p class="mb-0 fw-bold">ຈັດການສິນຄ້າ</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-warning">
                                        <i class="fas fa-shipping-fast fa-2x mb-2"></i>
                                        <p class="mb-1 small">ຂັ້ນຕອນທີ 3</p>
                                        <p class="mb-0 fw-bold">ຈັດສົ່ງສິນຄ້າ</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="text-success">
                                        <i class="fas fa-check-double fa-2x mb-2"></i>
                                        <p class="mb-1 small">ຂັ້ນຕອນທີ 4</p>
                                        <p class="mb-0 fw-bold">ຮັບສິນຄ້າ</p>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-light mt-3">
                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                <strong>ຂໍ້ມູນສຳຄັນ:</strong> ພວກເຮົາຈະຕິດຕໍ່ທ່ານຜ່ານເບີໂທລະສັບໃນການຢືນຢັນການສັ່ງຊື້ ແລະ ແຈ້ງວັນທີຈັດສົ່ງ
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="track_order.php" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>ຕິດຕາມອໍເດີ
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="index.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-home me-2"></i>ກັບໄປໜ້າຫຼັກ
                                </a>
                            </div>
                            <div class="col-md-4">
                                <button onclick="window.print()" class="btn btn-outline-info w-100">
                                    <i class="fas fa-print me-2"></i>ພິມຢັງຮັບ
                                </button>
                            </div>
                        </div>
                        
                        <!-- Support Information -->
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                ຖ້າມີຄຳຖາມ ກະລຸນາຕິດຕໍ່ພວກເຮົາ: 
                                <strong>020 1234 5678</strong> | 
                                <strong>support@jsshop.la</strong>
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Print Section (Hidden by default) -->
                <div class="d-none d-print-block">
                    <div class="text-center mb-4">
                        <h3>JS.Shop - ຢັງຮັບການສັ່ງຊື້</h3>
                        <p>ເລກທີອໍເດີ: <strong><?php echo $order['order_number']; ?></strong></p>
                        <p>ວັນທີ: <?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <strong>ຂໍ້ມູນຜູ້ຮັບ:</strong><br>
                            <?php echo htmlspecialchars($order['customer_name']); ?>
                        </div>
                        <div class="col-6 text-end">
                            <strong>ຍອດລວມ:</strong><br>
                            ₭<?php echo formatPrice($order['total_amount']); ?>
                        </div>
                    </div>
                    <hr>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>ສິນຄ້າ</th>
                                <th class="text-center">ຈຳນວນ</th>
                                <th class="text-end">ລາຄາ</th>
                                <th class="text-end">ລວມ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($order['items'] as $product_id => $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end">₭<?php echo formatPrice($item['price']); ?></td>
                                <td class="text-end">₭<?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>ລວມທັງໝົດ:</strong></td>
                                <td class="text-end"><strong>₭<?php echo formatPrice($order['total_amount']); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                    <hr>
                    <div class="text-center small text-muted">
                        <p>ຂໍຂອບໃຈທີ່ໃຊ້ບໍລິການກັບພວກເຮົາ</p>
                        <p>JS.Shop - ຮ້ານຄ້າອອນລາຍ</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Auto redirect to home page after 30 seconds
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 30000);

        // Add some celebration effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add confetti effect (simple version)
            function createConfetti() {
                const confetti = document.createElement('div');
                confetti.innerHTML = '🎉';
                confetti.style.position = 'fixed';
                confetti.style.fontSize = Math.random() * 20 + 10 + 'px';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-50px';
                confetti.style.zIndex = '9999';
                confetti.style.pointerEvents = 'none';
                confetti.style.animation = `fall ${Math.random() * 3 + 2}s linear forwards`;
                
                document.body.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
            
            // Add CSS animation for confetti
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fall {
                    to {
                        transform: translateY(100vh) rotate(360deg);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
            
            // Create some confetti
            for (let i = 0; i < 20; i++) {
                setTimeout(createConfetti, i * 100);
            }
            
            // Add success sound (if allowed by browser)
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 523.25; // C5
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0, audioContext.currentTime);
                gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.1);
                gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.5);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
            } catch (e) {
                console.log('Audio not supported');
            }
        });
    </script>
</body>
</html>