<?php
// api/demand_api.php - 需求大厅核心接口
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

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ================== 学生端功能 ==================

// 1. 发布需求
if ($action == 'post_demand') {
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $subject = isset($_POST['subject']) ? $conn->real_escape_string($_POST['subject']) : '';
    $grade = isset($_POST['grade']) ? $conn->real_escape_string($_POST['grade']) : '';
    $budget = isset($_POST['budget']) ? floatval($_POST['budget']) : 0;
    $req = isset($_POST['requirement']) ? $conn->real_escape_string($_POST['requirement']) : '';
    
    if (empty($phone) || empty($subject) || empty($grade)) {
        echo json_encode(["status"=>"error", "message"=>"请填写完整信息"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $sql = "INSERT INTO demands (student_phone, subject, grade, budget, requirement, status, create_time) 
            VALUES ('$phone', '$subject', '$grade', '$budget', '$req', 'open', NOW())";
            
    if ($conn->query($sql)) {
        echo json_encode(["status"=>"success", "message"=>"发布成功"], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["status"=>"error", "message"=>$conn->error], JSON_UNESCAPED_UNICODE);
    }
}

// 2. 获取我的需求 (含应聘人数)
else if ($action == 'get_my_demands') {
    $phone = isset($_GET['phone']) ? $_GET['phone'] : '';
    
    if (empty($phone)) {
        echo json_encode(["status"=>"error", "message"=>"参数错误", "data"=>[]], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 检查表是否存在
    $tableCheck = $conn->query("SHOW TABLES LIKE 'demands'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // 统计每个需求的应聘人数
        $sql = "SELECT d.*, (SELECT COUNT(*) FROM demand_applies da WHERE da.demand_id = d.id) as apply_count 
                FROM demands d 
                WHERE d.student_phone='$phone' 
                ORDER BY d.create_time DESC";
        $res = $conn->query($sql);
        $list = [];
        if($res) {
            while($r = $res->fetch_assoc()) {
                $list[] = $r;
            }
        }
        echo json_encode(["status"=>"success", "data"=>$list], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["status"=>"success", "data"=>[]], JSON_UNESCAPED_UNICODE);
    }
}

// 3. 查看某需求的应聘老师列表
else if ($action == 'get_appliers') {
    $did = isset($_GET['demand_id']) ? intval($_GET['demand_id']) : 0;
    
    if ($did <= 0) {
        echo json_encode(["status"=>"error", "message"=>"参数错误", "data"=>[]], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 检查表是否存在
    $tableCheck = $conn->query("SHOW TABLES LIKE 'demand_applies'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $sql = "SELECT da.*, t.id as tutor_id, t.name, t.school, t.major, t.avatar, t.price 
                FROM demand_applies da 
                JOIN tutors t ON da.tutor_id = t.id 
                WHERE da.demand_id='$did' AND da.status='pending'";
        $res = $conn->query($sql);
        $list = [];
        if($res) {
            while($r = $res->fetch_assoc()) {
                // 处理头像
                if (empty($r['avatar']) || $r['avatar'] === 'null') {
                    $r['avatar'] = 'assets/default_boy.png';
                } elseif (strpos($r['avatar'], 'http') !== 0 && strpos($r['avatar'], 'assets/') !== 0 && strpos($r['avatar'], 'uploads/') !== 0) {
                    $r['avatar'] = 'assets/' . basename($r['avatar']);
                }
                $list[] = $r;
            }
        }
        echo json_encode(["status"=>"success", "data"=>$list], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["status"=>"success", "data"=>[]], JSON_UNESCAPED_UNICODE);
    }
}

// 4. 录用老师 (自动生成订单)
else if ($action == 'accept_tutor') {
    $apply_id = isset($_POST['apply_id']) ? intval($_POST['apply_id']) : 0;
    
    if ($apply_id <= 0) {
        echo json_encode(["status"=>"error", "message"=>"参数错误"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 检查表是否存在
    $tableCheck = $conn->query("SHOW TABLES LIKE 'demand_applies'");
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        echo json_encode(["status"=>"error", "message"=>"功能暂未开放"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 获取应聘信息
    $apply = $conn->query("SELECT da.*, d.student_phone, d.budget, t.name as tutor_name, t.phone as tutor_phone 
                           FROM demand_applies da 
                           JOIN demands d ON da.demand_id = d.id 
                           JOIN tutors t ON da.tutor_id = t.id 
                           WHERE da.id='$apply_id'");
                           
    if ($apply && $apply->num_rows > 0) {
        $apply = $apply->fetch_assoc();
        $conn->begin_transaction();
        try {
            // 1. 标记需求为已关闭
            $conn->query("UPDATE demands SET status='closed' WHERE id='".$apply['demand_id']."'");
            // 2. 标记该应聘为已录用
            $conn->query("UPDATE demand_applies SET status='accepted' WHERE id='$apply_id'");
            // 3. 生成待支付订单
            $lesson_time = "协商时间"; // 需求单通常不指定具体时间，需后续沟通
            $price = $apply['budget'];
            $sql = "INSERT INTO bookings (user_phone, tutor_name, lesson_time, status, price, create_time) 
                    VALUES ('".$apply['student_phone']."', '".$apply['tutor_name']."', '$lesson_time', '已通过', '$price', NOW())";
            $conn->query($sql);
            
            // 4. 通知老师（如果notifications表存在）
            $notifCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
            if ($notifCheck && $notifCheck->num_rows > 0) {
                $conn->query("INSERT INTO notifications (user_phone, content) VALUES ('".$apply['tutor_phone']."', '恭喜！您的应聘已被录用，请等待学生支付。')");
            }
            
            $conn->commit();
            echo json_encode(["status"=>"success", "message"=>"录用成功"], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["status"=>"error", "message"=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(["status"=>"error", "message"=>"记录不存在"], JSON_UNESCAPED_UNICODE);
    }
}

// ================== 教员端功能 ==================

// 5. 获取需求大厅列表 (仅显示 open 的)
else if ($action == 'get_hall_list') {
    $tutor_id = isset($_GET['tutor_id']) ? intval($_GET['tutor_id']) : 0;
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'demands'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // 获取所有开启的需求，并标记当前老师是否已申请
        $sql = "SELECT d.*, 
                (SELECT COUNT(*) FROM demand_applies da WHERE da.demand_id = d.id AND da.tutor_id = '$tutor_id') as has_applied
                FROM demands d 
                WHERE d.status='open' 
                ORDER BY d.create_time DESC";
                
        $res = $conn->query($sql);
        $list = [];
        if($res) {
            while($r = $res->fetch_assoc()) {
                $list[] = $r;
            }
        }
        echo json_encode(["status"=>"success", "data"=>$list], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["status"=>"success", "data"=>[]], JSON_UNESCAPED_UNICODE);
    }
}

// 6. 抢单/应聘
else if ($action == 'apply_demand') {
    $tid = isset($_POST['tutor_id']) ? intval($_POST['tutor_id']) : 0;
    $did = isset($_POST['demand_id']) ? intval($_POST['demand_id']) : 0;
    
    if ($tid <= 0 || $did <= 0) {
        echo json_encode(["status"=>"error", "message"=>"参数错误"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'demand_applies'");
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        echo json_encode(["status"=>"error", "message"=>"功能暂未开放"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 检查是否重复
    $check = $conn->query("SELECT id FROM demand_applies WHERE demand_id='$did' AND tutor_id='$tid'");
    if($check && $check->num_rows > 0) {
        echo json_encode(["status"=>"error", "message"=>"已抢过此单"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $sql = "INSERT INTO demand_applies (demand_id, tutor_id, status, create_time) VALUES ('$did', '$tid', 'pending', NOW())";
    if($conn->query($sql)) {
        echo json_encode(["status"=>"success", "message"=>"应聘成功"], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["status"=>"error", "message"=>$conn->error], JSON_UNESCAPED_UNICODE);
    }
}

// 未知操作
else {
    echo json_encode(["status"=>"error", "message"=>"未知操作: " . $action], JSON_UNESCAPED_UNICODE);
}

if (isset($conn) && $conn) {
    @$conn->close();
}
?>
