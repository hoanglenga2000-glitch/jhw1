<?php
/**
 * 公共API - 终极修复版
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

// ====== 统一响应函数 ======
function sendResponse($status, $message, $data = null) {
    while (ob_get_level() > 0) ob_end_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once '../config/db.php';
    
    if (!isset($conn) || $conn->connect_error) {
        sendResponse('error', '数据库连接失败', null);
    }

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action == 'get_latest_notices') {
        // 检查表是否存在
        $tableCheck = $conn->query("SHOW TABLES LIKE 'announcements'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $result = $conn->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY id DESC LIMIT 5");
            $list = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $list[] = $row;
                }
            }
            sendResponse('success', '获取成功', $list);
        } else {
            // 表不存在，返回空数组
            sendResponse('success', '获取成功', []);
        }
    } else {
        sendResponse('error', '未知操作', null);
    }

} catch (Exception $e) {
    sendResponse('error', '系统错误: ' . $e->getMessage(), null);
} catch (Error $e) {
    sendResponse('error', '系统错误: ' . $e->getMessage(), null);
} finally {
    if (isset($conn) && $conn) {
        @$conn->close();
    }
}
?>
