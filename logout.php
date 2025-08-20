<?php
session_start();
require_once 'controllers/config.php';
require_once 'models/user.php';
require_once 'controllers/auth.php';

$auth = new AuthController();

// ตรวจสอบว่ามีการส่ง POST request หรือ GET request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    // Logout ผ่าน POST
    $auth->logout();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Logout ผ่าน GET (คลิกลิงก์โดยตรง)
    $auth->logout();
} else {
    // ถ้าไม่ใช่ทั้งสองกรณี ให้ redirect ไปหน้า login
    header('Location: login.php');
    exit();
}
?>