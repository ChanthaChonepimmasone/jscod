<?php
// fix_images.php
echo "<h3>🔧 ตรวจสอบและแก้ไขปัญหาโฟลเดอร์ images</h3>";

$folder_path = "images";

// 1. สร้างโฟลเดอร์ถ้ายังไม่มี
if (!file_exists($folder_path)) {
    if (mkdir($folder_path, 0755, true)) {
        echo "✅ สร้างโฟลเดอร์ images สำเร็จ!<br>";
    } else {
        echo "❌ ไม่สามารถสร้างโฟลเดอร์ images ได้<br>";
        exit;
    }
} else {
    echo "✅ โฟลเดอร์ images มีอยู่แล้ว<br>";
}

// 2. ตรวจสอบสิทธิ์การเขียน
if (is_writable($folder_path)) {
    echo "✅ โฟลเดอร์ images มีสิทธิ์เขียน<br>";
} else {
    echo "❌ โฟลเดอร์ images ไม่มีสิทธิ์เขียน<br>";
    
    // พยายามเปลี่ยนสิทธิ์
    if (chmod($folder_path, 0755)) {
        echo "✅ เปลี่ยนสิทธิ์โฟลเดอร์เป็น 755 สำเร็จ<br>";
    } else {
        echo "❌ ไม่สามารถเปลี่ยนสิทธิ์โฟลเดอร์ได้<br>";
    }
}

// 3. สร้างไฟล์ทดสอบ
$test_file = $folder_path . "/test.txt";
if (file_put_contents($test_file, "Test file created at " . date('Y-m-d H:i:s'))) {
    echo "✅ สามารถเขียนไฟล์ในโฟลเดอร์ images ได้<br>";
    unlink($test_file); // ลบไฟล์ทดสอบ
} else {
    echo "❌ ไม่สามารถเขียนไฟล์ในโฟลเดอร์ images ได้<br>";
}

// 4. ตรวจสอบ path ที่แท้จริง
echo "📍 Path จริง: " . realpath($folder_path) . "<br>";
echo "📍 Working Directory: " . getcwd() . "<br>";

// 5. สร้างไฟล์ default.jpg สำหรับสินค้าที่ไม่มีรูป
$default_image = $folder_path . "/default.jpg";
if (!file_exists($default_image)) {
    // สร้างรูปภาพ default ง่ายๆ ด้วย GD
    if (function_exists('imagecreate')) {
        $im = imagecreate(100, 100);
        $bg = imagecolorallocate($im, 240, 240, 240);
        $text_color = imagecolorallocate($im, 150, 150, 150);
        imagestring($im, 2, 10, 45, "No Image", $text_color);
        imagejpeg($im, $default_image, 80);
        imagedestroy($im);
        echo "✅ สร้างไฟล์ default.jpg สำเร็จ<br>";
    } else {
        echo "⚠️ ไม่สามารถสร้าง default.jpg (GD not available)<br>";
    }
} else {
    echo "✅ ไฟล์ default.jpg มีอยู่แล้ว<br>";
}

echo "<hr><h4>🎯 สรุปสถานะ:</h4>";
echo "โฟลเดอร์ images พร้อมใช้งานแล้ว!";
?>