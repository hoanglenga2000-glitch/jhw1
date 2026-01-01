<?php
/**
 * 获取教员详情API - 终极修复版
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
        'data' => $data,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once '../config/db.php';
    
    if (!isset($conn) || $conn->connect_error) {
        sendResponse('error', '数据库连接失败', null);
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        sendResponse('error', '参数错误', null);
    }

    // 查询教员基本信息
    $stmt = $conn->prepare("SELECT * FROM tutors WHERE id = ? AND status = '已通过' LIMIT 1");
    if (!$stmt) {
        sendResponse('error', '系统错误', null);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tutor = $result->fetch_assoc();
    $stmt->close();

    if (!$tutor) {
        sendResponse('error', '教员不存在或已下架', null);
    }

    // 处理头像路径 - 优先使用assets目录
    $avatar = $tutor['avatar'] ?? '';
    if (empty($avatar) || $avatar === 'null' || $avatar === '') {
        $avatar = 'assets/default_boy.png';
    } elseif (strpos($avatar, 'http') === 0) {
        // 完整URL保持不变
    } elseif (strpos($avatar, 'assets/') === 0 || strpos($avatar, 'uploads/') === 0) {
        // 已有正确前缀
    } else {
        $avatar = 'assets/' . basename($avatar);
    }

    // 构建标签数组
    $tags = [];
    if (!empty($tutor['school'])) $tags[] = $tutor['school'];
    if (!empty($tutor['major'])) $tags[] = $tutor['major'];
    if (!empty($tutor['subject'])) {
        $subjects = preg_split('/[,，、\s]+/', $tutor['subject']);
        foreach ($subjects as $sub) {
            if (!empty(trim($sub))) $tags[] = trim($sub);
        }
    }

    // 获取评价统计
    $avgRating = 5.0;
    $teachingYears = 1;
    $teachingHours = 0;
    
    // 尝试获取评价
    $reviewResult = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE tutor_id = $id");
    if ($reviewResult) {
        $reviewStats = $reviewResult->fetch_assoc();
        if ($reviewStats['avg_rating']) {
            $avgRating = round(floatval($reviewStats['avg_rating']), 1);
        }
    }
    
    // 尝试获取授课时长
    $bookingResult = $conn->query("SELECT COUNT(*) as completed FROM bookings WHERE tutor_name = '" . $conn->real_escape_string($tutor['name']) . "' AND status = '已完成'");
    if ($bookingResult) {
        $bookingStats = $bookingResult->fetch_assoc();
        $teachingHours = intval($bookingStats['completed']) * 2;
    }

    // 获取忙碌时段
    $busySlots = [];
    $busyResult = $conn->query("SELECT lesson_time FROM bookings WHERE tutor_name = '" . $conn->real_escape_string($tutor['name']) . "' AND status IN ('待确认', '已支付', '进行中') AND lesson_time > NOW()");
    if ($busyResult) {
        while ($row = $busyResult->fetch_assoc()) {
            $busySlots[] = $row['lesson_time'];
        }
    }

    // 组装返回数据
    $data = [
        'id' => intval($tutor['id']),
        'name' => $tutor['name'] ?? '未命名',
        'avatar' => $avatar,
        'school' => $tutor['school'] ?? '',
        'major' => $tutor['major'] ?? '',
        'subject' => $tutor['subject'] ?? '',
        'price' => floatval($tutor['price'] ?? 0),
        'rating' => floatval($tutor['rating'] ?? 5.0),
        'intro' => $tutor['intro'] ?? '暂无介绍',
        'honors' => $tutor['honors'] ?? '暂无成功案例',
        'tags' => array_unique($tags),
        'stats' => [
            'avg_rating' => $avgRating,
            'teaching_years' => $teachingYears,
            'teaching_hours' => $teachingHours
        ],
        'busy_slots' => $busySlots,
        'badges' => []
    ];

    sendResponse('success', '获取成功', $data);

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
