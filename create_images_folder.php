<?php
// create_images_folder.php
$folder_path = "images";

// สร้างโฟลเดอร์ถ้ายังไม่มี
if (!file_exists($folder_path)) {
    if (mkdir($folder_path, 0777, true)) {
        echo "✅ สร้างโฟลเดอร์ images สำเร็จแล้ว!<br>";
        
        // สร้างไฟล์ .htaccess เพื่อป้องกันการเข้าถึงโดยตรง
        $htaccess_content = "Options -Indexes\nDeny from all";
        file_put_contents($folder_path . "/.htaccess", $htaccess_content);
        echo "✅ สร้างไฟล์ .htaccess สำเร็จแล้ว!<br>";
        
        // สร้างไฟล์ index.html ว่างเพื่อป้องกัน directory listing
        file_put_contents($folder_path . "/index.html", "");
        echo "✅ สร้างไฟล์ index.html สำเร็จแล้ว!<br>";
        
    } else {
        echo "❌ ไม่สามารถสร้างโฟลเดอร์ images ได้<br>";
    }
} else {
    echo "✅ โฟลเดอร์ images มีอยู่แล้ว<br>";
}

// ตรวจสอบสิทธิ์การเขียน
if (is_writable($folder_path)) {
    echo "✅ โฟลเดอร์ images มีสิทธิ์เขียน<br>";
} else {
    echo "❌ โฟลเดอร์ images ไม่มีสิทธิ์เขียน<br>";
    echo "กรุณาเปลี่ยนสิทธิ์โฟลเดอร์เป็น 755 หรือ 777<br>";
}
?>