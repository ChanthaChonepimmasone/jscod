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

// ລຶບສິນຄ້າທັງໝົດ
try {
    $connection->query("DELETE FROM products");
    echo "ລຶບສິນຄ້າທັງໝົດສຳເລັດແລ້ວ!";
    
    // ກັບໄປໜ້າຈັດການສິນຄ້າ
    header("Location: manage_products.php?success=ລຶບສິນຄ້າທັງໝົດສຳເລັດແລ້ວ");
    exit();
    
} catch (Exception $e) {
    echo "ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
}
?>