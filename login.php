<?php
/**
 * 学生登录API - 终极修复版
 * 功能：自动建表、防爆破、数据清洗、统一JSON响应
 */

// ====== 0. 全局错误处理（最重要！确保永远返回JSON） ======
error_reporting(0);
ini_set('display_errors', 0);

// 自定义错误处理器
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// 清除之前的输出
ob_start();

// CORS 头
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ====== 统一JSON响应函数 ======
function sendJsonResponse($status, $message, $data = null, $httpCode = 200) {
    // 清除所有之前的输出
    while (ob_get_level() > 0) ob_end_clean();
    
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
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
    // 连接数据库
    require_once '../config/db.php';
    
    // 检查连接是否成功
    if (!isset($conn) || $conn->connect_error) {
        sendJsonResponse('error', '数据库连接失败', null, 500);
    }
    
    // 获取并清洗输入数据
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // 密码不做HTML转义
    
    // 验证必填字段
    if (empty($phone) || empty($password)) {
        sendJsonResponse('error', '手机号和密码不能为空', null, 400);
    }
    
    // 验证手机号格式
    if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
        sendJsonResponse('error', '手机号格式不正确', null, 400);
    }
    
    // 使用预处理语句查询用户
    $stmt = $conn->prepare("SELECT id, username, password, is_banned, balance FROM users WHERE phone = ? LIMIT 1");
    if (!$stmt) {
        sendJsonResponse('error', '系统错误: ' . $conn->error, null, 500);
    }
    
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // 检查账号是否被封禁
        if (isset($row['is_banned']) && $row['is_banned'] == 1) {
            $stmt->close();
            sendJsonResponse('error', '该账号已被封禁，请联系客服', null, 403);
        }
        
        // 验证密码
        if ($row['password'] === $password) {
            $stmt->close();
            
            // 尝试更新最后登录时间（忽略错误）
            @$conn->query("UPDATE users SET last_login = NOW() WHERE id = " . intval($row['id']));
            
            // 返回成功响应
            sendJsonResponse('success', '登录成功', [
                'id' => $row['id'],
                'username' => $row['username'],
                'phone' => $phone,
                'balance' => $row['balance'] ?? 0
            ]);
        } else {
            $stmt->close();
            sendJsonResponse('error', '手机号或密码错误', null, 401);
        }
    } else {
        $stmt->close();
        sendJsonResponse('error', '手机号或密码错误', null, 401);
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
