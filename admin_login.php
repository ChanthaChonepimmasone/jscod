<?php
session_start();

// ກວດສອບການລັອກອິນ
if ($_POST && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // ຕົວຢ່າງການລັອກອິນແບບງ່າຍ (ຄວນໃຊ້ລະບົບທີ່ປອດໄພກວ່າໃນການນຳໃຊ້ຈິງ)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error_message = "ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ";
    }
}

// ຖ້າລັອກອິນຢູ່ແລ້ວ ໃຫ້ redirect ໄປໜ້າ admin_dashboard
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລັອກອິນ - ລະບົບຫຼັງບ້ານ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans Lao', sans-serif;
        }
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="login-container d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="card login-card">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <h2 class="text-primary">🔒 ລະບົບຫຼັງບ້ານ</h2>
                                <p class="text-muted">ກະລຸນາລັອກອິນເພື່ອເຂົ້າສູ່ລະບົບ</p>
                            </div>

                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">ຊື່ຜູ້ໃຊ້</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="admin" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">ລະຫັດຜ່ານ</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           value="admin123" required>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="login" class="btn btn-primary btn-lg">
                                        ເຂົ້າສູ່ລະບົບ
                                    </button>
                                </div>
                            </form>

                            <div class="mt-4 text-center">
                                <small class="text-muted">
                                    <strong>ຂໍ້ມູນລັອກອິນສຳລັບການທົດສອບ:</strong><br>
                                    ຊື່ຜູ້ໃຊ້: <code>admin</code><br>
                                    ລະຫັດຜ່ານ: <code>admin123</code>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>