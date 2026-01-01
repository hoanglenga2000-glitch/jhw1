<?php
// api/book_tutor.php - 安全加固版：预约时自动锁定价格（使用预处理语句）
error_reporting(0);
ini_set('display_errors', 0);

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

ob_start();
require '../config/db.php';

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => '数据库连接失败'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 安全的数据清洗
 */
function sanitize($conn, $data) {
    return htmlspecialchars($conn->real_escape_string(trim($data)), ENT_QUOTES, 'UTF-8');
}

try {
    $user_phone = isset($_POST['user_phone']) ? sanitize($conn, $_POST['user_phone']) : '';
    $tutor_name = isset($_POST['tutor_name']) ? sanitize($conn, $_POST['tutor_name']) : '';
    $lesson_time = isset($_POST['lesson_time']) ? sanitize($conn, $_POST['lesson_time']) : '';
    $class_type = isset($_POST['class_type']) ? sanitize($conn, $_POST['class_type']) : '线上教学';
    $requirement = isset($_POST['requirement']) ? sanitize($conn, $_POST['requirement']) : '';

    if (empty($user_phone) || empty($tutor_name)) {
        echo json_encode(["status" => "error", "message" => "参数缺失"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 1. 使用预处理语句查询该老师的当前价格
    $price = 0;
    $stmt = $conn->prepare("SELECT price FROM tutors WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $tutor_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $price = floatval($row['price']);
    }
    $stmt->close();

    if ($price <= 0) {
        echo json_encode(["status" => "error", "message" => "教员价格信息异常"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. 使用预处理语句插入订单
    $stmt2 = $conn->prepare("INSERT INTO bookings (user_phone, tutor_name, lesson_time, requirement, class_type, status, payment_status, price, create_time) 
            VALUES (?, ?, ?, ?, ?, '待确认', 'unpaid', ?, NOW())");
    $stmt2->bind_param("sssssd", $user_phone, $tutor_name, $lesson_time, $requirement, $class_type, $price);
    
    if ($stmt2->execute()) {
        echo json_encode(["status" => "success", "message" => "预约申请已提交"], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["status" => "error", "message" => "数据库错误: " . $stmt2->error], JSON_UNESCAPED_UNICODE);
    }
    $stmt2->close();
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "系统错误，请稍后重试"], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conn) && $conn) {
        @$conn->close();
    }
}
?>