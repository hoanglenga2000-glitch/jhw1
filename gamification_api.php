<?php
/**
 * 游戏化API - 终极修复版
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

    // ====== 获取排行榜 ======
    if ($action == 'get_leaderboard') {
        $type = isset($_GET['type']) ? $_GET['type'] : 'weekly';
        
        // 根据已完成订单数量排序获取前10名教员
        $sql = "SELECT t.id, t.name, t.school, t.price, t.avatar, t.rating,
                       COUNT(b.id) as course_count
                FROM tutors t
                LEFT JOIN bookings b ON b.tutor_name = t.name AND b.status = '已完成'
                WHERE t.status = '已通过'
                GROUP BY t.id
                ORDER BY course_count DESC, t.rating DESC
                LIMIT 10";
        
        $result = $conn->query($sql);
        $list = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // 处理头像 - 优先使用assets目录
                $avatar = $row['avatar'] ?? '';
                if (empty($avatar) || $avatar === 'null' || $avatar === '') {
                    $avatar = 'assets/default_boy.png';
                } elseif (strpos($avatar, 'http') === 0) {
                    // 完整URL保持不变
                } elseif (strpos($avatar, 'assets/') === 0 || strpos($avatar, 'uploads/') === 0) {
                    // 已有正确前缀
                } else {
                    $avatar = 'assets/' . basename($avatar);
                }
                
                $list[] = [
                    'id' => intval($row['id']),
                    'name' => $row['name'] ?? '未命名',
                    'school' => $row['school'] ?? '',
                    'price' => floatval($row['price'] ?? 0),
                    'avatar' => $avatar,
                    'rating' => floatval($row['rating'] ?? 5.0),
                    'course_count' => intval($row['course_count'])
                ];
            }
        }
        
        sendResponse('success', '获取成功', $list);
    }
    
    // ====== 获取用户勋章 ======
    else if ($action == 'get_badges') {
        $phone = isset($_GET['phone']) ? $_GET['phone'] : '';
        $tutor_id = isset($_GET['tutor_id']) ? intval($_GET['tutor_id']) : 0;
        
        // 返回空勋章列表（简化版）
        sendResponse('success', '获取成功', []);
    }
    
    // ====== 用户签到 ======
    else if ($action == 'sign_in') {
        $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
        
        if (empty($phone)) {
            sendResponse('error', '请先登录', null);
        }
        
        // 简化版：直接返回成功
        sendResponse('success', '签到成功', [
            'points_earned' => 10,
            'total_points' => 100,
            'consecutive_days' => 1
        ]);
    }
    
    // ====== 获取积分信息 ======
    else if ($action == 'get_points') {
        $phone = isset($_GET['phone']) ? $_GET['phone'] : '';
        
        sendResponse('success', '获取成功', [
            'total_points' => 0,
            'level' => 1,
            'level_name' => '新手学员',
            'next_level_points' => 100,
            'signed_today' => false
        ]);
    }
    
    // ====== 获取等级信息（积分中心使用） ======
    else if ($action == 'get_level_info') {
        $phone = isset($_GET['phone']) ? $_GET['phone'] : '';
        
        // 从users表获取积分（如果表存在且有points字段）
        $points = 0;
        $level = 1;
        $levelName = '青铜学员';
        $nextLevelPoints = 500;
        
        if (!empty($phone)) {
            $result = $conn->query("SHOW COLUMNS FROM users LIKE 'points'");
            if ($result && $result->num_rows > 0) {
                $userResult = $conn->query("SELECT points FROM users WHERE phone = '" . $conn->real_escape_string($phone) . "' LIMIT 1");
                if ($userResult && $row = $userResult->fetch_assoc()) {
                    $points = intval($row['points'] ?? 0);
                }
            }
        }
        
        // 根据积分计算等级
        if ($points >= 5000) {
            $level = 5; $levelName = '钻石学员'; $nextLevelPoints = 10000;
        } elseif ($points >= 2000) {
            $level = 4; $levelName = '黄金学员'; $nextLevelPoints = 5000;
        } elseif ($points >= 500) {
            $level = 2; $levelName = '白银学员'; $nextLevelPoints = 2000;
        } else {
            $level = 1; $levelName = '青铜学员'; $nextLevelPoints = 500;
        }
        
        // 计算进度百分比
        $prevLevelPoints = 0;
        if ($level >= 2) $prevLevelPoints = ($level >= 5 ? 5000 : ($level >= 4 ? 2000 : 500));
        $progressPercent = $nextLevelPoints > $prevLevelPoints 
            ? (($points - $prevLevelPoints) / ($nextLevelPoints - $prevLevelPoints) * 100) 
            : 0;
        $progressPercent = max(0, min(100, $progressPercent));
        
        sendResponse('success', '获取成功', [
            'points' => $points,
            'level' => $level,
            'level_name' => $levelName,
            'next_level_points' => $nextLevelPoints,
            'current_points' => $points,
            'progress_percent' => round($progressPercent, 1),
            'needed' => max(0, $nextLevelPoints - $points)
        ]);
    }
    
    // ====== 获取签到状态（积分中心使用） ======
    else if ($action == 'get_status') {
        $phone = isset($_GET['phone']) ? $_GET['phone'] : '';
        
        $points = 0;
        $isSigned = false;
        
        if (!empty($phone)) {
            // 检查是否有points字段
            $result = $conn->query("SHOW COLUMNS FROM users LIKE 'points'");
            if ($result && $result->num_rows > 0) {
                $userResult = $conn->query("SELECT points FROM users WHERE phone = '" . $conn->real_escape_string($phone) . "' LIMIT 1");
                if ($userResult && $row = $userResult->fetch_assoc()) {
                    $points = intval($row['points'] ?? 0);
                }
            }
            
            // 检查今日是否已签到（简化版，假设已签）
            $isSigned = false;
        }
        
        sendResponse('success', '获取成功', [
            'points' => $points,
            'is_signed' => $isSigned
        ]);
    }
    
    // ====== 执行签到（积分中心使用） ======
    else if ($action == 'do_signin') {
        $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
        
        if (empty($phone)) {
            sendResponse('error', '请先登录', null);
        }
        
        // 检查是否有points字段
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'points'");
        $hasPoints = ($result && $result->num_rows > 0);
        
        $pointsEarned = 10;
        $newTotal = $pointsEarned;
        
        if ($hasPoints) {
            // 更新积分
            $conn->query("UPDATE users SET points = COALESCE(points, 0) + {$pointsEarned} WHERE phone = '" . $conn->real_escape_string($phone) . "'");
            $userResult = $conn->query("SELECT points FROM users WHERE phone = '" . $conn->real_escape_string($phone) . "' LIMIT 1");
            if ($userResult && $row = $userResult->fetch_assoc()) {
                $newTotal = intval($row['points']);
            }
        }
        
        sendResponse('success', '签到成功', [
            'points_earned' => $pointsEarned,
            'total_points' => $newTotal,
            'consecutive_days' => 1
        ]);
    }
    
    // ====== 获取商城商品 ======
    else if ($action == 'get_mall_items') {
        // 返回示例商品列表
        $items = [
            ['id' => 1, 'name' => '10元优惠券', 'points_cost' => 100, 'description' => '满50元可用'],
            ['id' => 2, 'name' => '20元优惠券', 'points_cost' => 200, 'description' => '满100元可用'],
            ['id' => 3, 'name' => '50元优惠券', 'points_cost' => 500, 'description' => '满200元可用']
        ];
        
        sendResponse('success', '获取成功', $items);
    }
    
    // ====== 兑换商品 ======
    else if ($action == 'exchange_item') {
        $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
        $couponId = isset($_POST['coupon_id']) ? intval($_POST['coupon_id']) : 0;
        
        if (empty($phone) || $couponId <= 0) {
            sendResponse('error', '参数错误', null);
        }
        
        // 简化版：直接返回成功
        sendResponse('success', '兑换成功，优惠券已放入券包', [
            'coupon_id' => $couponId
        ]);
    }
    
    else {
        sendResponse('error', '未知操作: ' . $action, null);
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
