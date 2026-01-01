<?php
/**
 * 聊天API - 终极修复版
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

    // ====== 获取未读消息数 ======
    if ($action == 'get_unread_count') {
        $phone = isset($_GET['phone']) ? $_GET['phone'] : '';
        $count = 0;
        
        // 检查表是否存在
        $tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
        if ($tableCheck && $tableCheck->num_rows > 0 && !empty($phone)) {
            $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM messages WHERE receiver_phone = ? AND is_read = 0");
            if ($stmt) {
                $stmt->bind_param("s", $phone);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $count = intval($result['cnt'] ?? 0);
                $stmt->close();
            }
        }
        
        // 统一返回格式
        sendResponse('success', '获取成功', ['count' => $count]);
    }
    
    // ====== 发送消息 ======
    else if ($action == 'send_message') {
        $sender = isset($_POST['sender_phone']) ? $_POST['sender_phone'] : '';
        $receiver = isset($_POST['receiver_phone']) ? $_POST['receiver_phone'] : '';
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        
        if (empty($sender) || empty($receiver) || empty($content)) {
            sendResponse('error', '参数不完整', null);
        }
        
        // 检查表是否存在，不存在则创建
        $conn->query("CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_phone VARCHAR(20),
            receiver_phone VARCHAR(20),
            content TEXT,
            is_read TINYINT DEFAULT 0,
            create_time DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $stmt = $conn->prepare("INSERT INTO messages (sender_phone, receiver_phone, content) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $sender, $receiver, $content);
            if ($stmt->execute()) {
                $stmt->close();
                sendResponse('success', '发送成功', null);
            }
            $stmt->close();
        }
        
        sendResponse('error', '发送失败', null);
    }
    
    // ====== 获取消息列表 ======
    else if ($action == 'get_messages' || $action == 'get_history') {
        $phone = isset($_GET['phone']) ? $_GET['phone'] : (isset($_GET['me']) ? $_GET['me'] : '');
        $target = isset($_GET['target']) ? $_GET['target'] : (isset($_GET['other']) ? $_GET['other'] : '');
        
        // 检查表是否存在
        $tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
        if ($tableCheck && $tableCheck->num_rows > 0 && !empty($phone) && !empty($target)) {
            $stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_phone = ? AND receiver_phone = ?) OR (sender_phone = ? AND receiver_phone = ?) ORDER BY create_time ASC LIMIT 100");
            if ($stmt) {
                $stmt->bind_param("ssss", $phone, $target, $target, $phone);
                $stmt->execute();
                $result = $stmt->get_result();
                $list = [];
                while ($row = $result->fetch_assoc()) {
                    $list[] = $row;
                }
                $stmt->close();
                sendResponse('success', '获取成功', $list);
            }
        }
        
        sendResponse('success', '获取成功', []);
    }
    
    // ====== 获取联系人列表 ======
    else if ($action == 'get_contacts') {
        $phone = isset($_GET['phone']) ? $_GET['phone'] : '';
        $role = isset($_GET['role']) ? $_GET['role'] : 'student';
        
        if (empty($phone)) {
            sendResponse('error', '参数错误', []);
        }
        
        // 检查表是否存在
        $tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
        $contacts = [];
        
        if ($tableCheck && $tableCheck->num_rows > 0) {
            // 获取与当前用户有消息往来的联系人
            $sql = "SELECT DISTINCT 
                    CASE 
                        WHEN sender_phone = ? THEN receiver_phone 
                        ELSE sender_phone 
                    END as contact_phone
                    FROM messages 
                    WHERE sender_phone = ? OR receiver_phone = ?";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sss", $phone, $phone, $phone);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $contactPhones = [];
                while ($row = $result->fetch_assoc()) {
                    if ($row['contact_phone'] != $phone) {
                        $contactPhones[] = $row['contact_phone'];
                    }
                }
                $stmt->close();
                
                // 根据角色获取联系人信息
                if ($role == 'student') {
                    // 学生端：获取教员信息
                    if (count($contactPhones) > 0) {
                        $placeholders = str_repeat('?,', count($contactPhones) - 1) . '?';
                        $sql = "SELECT phone, name, school, avatar FROM tutors WHERE phone IN ($placeholders)";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param(str_repeat('s', count($contactPhones)), ...$contactPhones);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()) {
                                $avatar = $row['avatar'] ?? '';
                                if (empty($avatar) || $avatar === 'null') {
                                    $avatar = 'assets/default_boy.png';
                                } elseif (strpos($avatar, 'http') !== 0 && strpos($avatar, 'assets/') !== 0 && strpos($avatar, 'uploads/') !== 0) {
                                    $avatar = 'assets/' . basename($avatar);
                                }
                                $contacts[] = [
                                    'phone' => $row['phone'],
                                    'name' => $row['name'] ?? '未命名',
                                    'school' => $row['school'] ?? '',
                                    'avatar' => $avatar
                                ];
                            }
                            $stmt->close();
                        }
                    }
                } else {
                    // 教员端：获取学生信息
                    if (count($contactPhones) > 0) {
                        $placeholders = str_repeat('?,', count($contactPhones) - 1) . '?';
                        $sql = "SELECT phone, username as name, '' as school, '' as avatar FROM users WHERE phone IN ($placeholders)";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param(str_repeat('s', count($contactPhones)), ...$contactPhones);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()) {
                                $contacts[] = [
                                    'phone' => $row['phone'],
                                    'name' => $row['name'] ?? '学生',
                                    'school' => '',
                                    'avatar' => 'assets/default_student.png'
                                ];
                            }
                            $stmt->close();
                        }
                    }
                }
            }
        }
        
        sendResponse('success', '获取成功', $contacts);
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
