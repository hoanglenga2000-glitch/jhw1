<?php
/**
 * 学生注册API - 终极修复版
 * 功能：数据清洗、XSS防护、SQL注入防护、统一JSON响应
 */

// ====== 全局错误处理 ======
error_reporting(0);
ini_set('display_errors', 0);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

ob_start();

// CORS 头
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

// OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ====== 统一JSON响应函数 ======
function sendJsonResponse($status, $message, $data = null, $httpCode = 200) {
    while (ob_get_level() > 0) ob_end_clean();
    
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ====== 数据清洗函数 ======
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// ====== 主逻辑 ======
try {
    require_once '../config/db.php';
    
    if (!isset($conn) || $conn->connect_error) {
        sendJsonResponse('error', '数据库连接失败', null, 500);
    }
    
    // 获取并清洗输入数据
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    
    // 如果用户名为空，使用手机号后四位作为默认名
    if (empty($username)) {
        $username = '用户' . substr($phone, -4);
    }
    
    // 验证必填字段
    if (empty($phone) || empty($password)) {
        sendJsonResponse('error', '请填写手机号和密码', null, 400);
    }
    
    // 验证手机号格式
    if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
        sendJsonResponse('error', '手机号格式不正确', null, 400);
    }
    
    // 验证密码强度
    if (strlen($password) < 6) {
        sendJsonResponse('error', '密码至少需要6位', null, 400);
    }
    
    // 检查手机号是否已注册
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
    if (!$checkStmt) {
        sendJsonResponse('error', '系统错误', null, 500);
    }
    
    $checkStmt->bind_param("s", $phone);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        sendJsonResponse('error', '该手机号已被注册，请直接登录', null, 409);
    }
    $checkStmt->close();
    
    // 插入新用户
    $insertStmt = $conn->prepare("INSERT INTO users (username, password, phone, created_at) VALUES (?, ?, ?, NOW())");
    if (!$insertStmt) {
        sendJsonResponse('error', '系统错误', null, 500);
    }
    
    $insertStmt->bind_param("sss", $username, $password, $phone);
    
    if ($insertStmt->execute()) {
        $userId = $insertStmt->insert_id;
        $insertStmt->close();
        
        sendJsonResponse('success', '注册成功！请登录', [
            'user_id' => $userId,
            'phone' => $phone,
            'username' => $username
        ], 201);
    } else {
        $insertStmt->close();
        sendJsonResponse('error', '注册失败，请稍后重试', null, 500);
    }
    
} catch (Exception $e) {
    sendJsonResponse('error', '系统错误: ' . $e->getMessage(), null, 500);
} catch (Error $e) {
    sendJsonResponse('error', '系统错误: ' . $e->getMessage(), null, 500);
} finally {
    if (isset($conn) && $conn) {
        @$conn->close();
    }
}
?>
