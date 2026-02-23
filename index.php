<?php
// ดึงค่าจาก Environment Variables
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

// 1. เชื่อมต่อฐานข้อมูล (ใช้ MySQLi)
$conn = new mysqli($host, $user, $pass, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// 2. คำสั่ง SQL สร้างตาราง (โจทย์บอกต้องมีส่วนนี้ 5 คะแนน)
$sql = "CREATE TABLE IF NOT EXISTS trees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tree_name VARCHAR(255) NOT NULL,
    species VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "<h1>เชื่อมต่อสำเร็จ! และตาราง 'trees' ถูกสร้าง/ตรวจสอบแล้ว</h1>";
} else {
    echo "เกิดข้อผิดพลาดในการสร้างตาราง: " . $conn->error;
}

$conn->close();
?>
