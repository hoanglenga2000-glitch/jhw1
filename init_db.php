<?php
/**
 * 数据库初始化脚本
 * 自动创建所有必需的表
 */

error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../config/db.php';
    
    if (!isset($conn) || $conn->connect_error) {
        echo json_encode(["status" => "error", "message" => "数据库连接失败"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $results = [];
    
    // 1. 用户表
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(100) NOT NULL,
        phone VARCHAR(20) UNIQUE NOT NULL,
        avatar VARCHAR(255) DEFAULT 'assets/default_student.png',
        balance DECIMAL(10,2) DEFAULT 0,
        points INT DEFAULT 0,
        is_banned TINYINT DEFAULT 0,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $results['users'] = $conn->query($sql) ? 'OK' : $conn->error;
    
    // 2. 教员表
    $sql = "CREATE TABLE IF NOT EXISTS tutors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        phone VARCHAR(20) UNIQUE NOT NULL,
        password VARCHAR(100) NOT NULL,
        avatar VARCHAR(255) DEFAULT 'assets/default_boy.png',
        school VARCHAR(100),
        major VARCHAR(100),
        subject VARCHAR(200),
        price DECIMAL(10,2) DEFAULT 100,
        rating DECIMAL(3,2) DEFAULT 5.0,
        intro TEXT,
        honors TEXT,
        status ENUM('待审核', '已通过', '已拒绝') DEFAULT '待审核',
        is_banned TINYINT DEFAULT 0,
        is_vip TINYINT DEFAULT 0,
        vip_expire_time DATETIME,
        balance DECIMAL(10,2) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $results['tutors'] = $conn->query($sql) ? 'OK' : $conn->error;
    
    // 3. 预约订单表
    $sql = "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_phone VARCHAR(20),
        tutor_name VARCHAR(50),
        lesson_time DATETIME,
        class_type VARCHAR(50) DEFAULT '线上教学',
        requirement TEXT,
        price DECIMAL(10,2),
        status ENUM('待确认', '已支付', '进行中', '已完成', '待评价', '已拒绝', '已取消') DEFAULT '待确认',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        paid_at DATETIME,
        completed_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $results['bookings'] = $conn->query($sql) ? 'OK' : $conn->error;
    
    // 4. 评价表
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tutor_id INT,
        user_phone VARCHAR(20),
        booking_id INT,
        rating INT DEFAULT 5,
        content TEXT,
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $results['reviews'] = $conn->query($sql) ? 'OK' : $conn->error;
    
    // 5. 收藏表
    $sql = "CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_phone VARCHAR(20),
        tutor_id INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_fav (user_phone, tutor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $results['favorites'] = $conn->query($sql) ? 'OK' : $conn->error;
    
    // 6. 消息表
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_phone VARCHAR(20),
        receiver_phone VARCHAR(20),
        content TEXT,
        is_read TINYINT DEFAULT 0,
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $results['messages'] = $conn->query($sql) ? 'OK' : $conn->error;
    
    // 7. 公告表
    $sql = "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200),
        content TEXT,
        is_active TINYINT DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $results['announcements'] = $conn->query($sql) ? 'OK' : $conn->error;
    
    // 8. 资源表
    $sql = "CREATE TABLE IF NOT EXISTS resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200),
        description TEXT,
        file_path VARCHAR(255),
        uploader_phone VARCHAR(20),
        subject VARCHAR(100),
        price DECIMAL(10,2) DEFAULT 0,
        download_count INT DEFAULT 0,
        status ENUM('待审核', '已通过', '已拒绝') DEFAULT '待审核',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $results['resources'] = $conn->query($sql) ? 'OK' : $conn->error;
    
    // 9. 提现表
    $sql = "CREATE TABLE IF NOT EXISTS withdrawals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20),
        amount DECIMAL(10,2),
        bank_info VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $results['withdrawals'] = $conn->query($sql) ? 'OK' : $conn->error;
    
    // 10. 退款表
    $sql = "CREATE TABLE IF NOT EXISTS refunds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT,
        user_phone VARCHAR(20),
        reason TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $results['refunds'] = $conn->query($sql) ? 'OK' : $conn->error;
    
    // 11. 通知表
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_phone VARCHAR(20),
        content TEXT,
        is_read TINYINT DEFAULT 0,
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $results['notifications'] = $conn->query($sql) ? 'OK' : $conn->error;
    
    // 插入测试数据（如果表为空）
    $testData = [];
    
    // 检查是否有用户
    $userCheck = $conn->query("SELECT COUNT(*) as c FROM users");
    if ($userCheck && $userCheck->fetch_assoc()['c'] == 0) {
        $conn->query("INSERT INTO users (username, password, phone) VALUES ('测试用户', '123456', '13800138000')");
        $testData['test_user'] = '已创建测试用户: 13800138000 / 123456';
    }
    
    // 检查是否有教员
    $tutorCheck = $conn->query("SELECT COUNT(*) as c FROM tutors");
    if ($tutorCheck && $tutorCheck->fetch_assoc()['c'] == 0) {
        $conn->query("INSERT INTO tutors (name, phone, password, school, major, subject, price, intro, status) VALUES 
            ('张老师', '13900139000', '123456', '北京大学', '数学系', '高等数学,线性代数', 150, '985高校研究生，3年家教经验', '已通过'),
            ('李老师', '13900139001', '123456', '清华大学', '物理系', '高中物理,大学物理', 180, '211高校博士生，擅长物理竞赛辅导', '已通过'),
            ('王老师', '13900139002', '123456', '复旦大学', '英语系', '英语口语,雅思托福', 200, '海归硕士，专业八级', '已通过')
        ");
        $testData['test_tutors'] = '已创建3位测试教员';
    }
    
    echo json_encode([
        "status" => "success",
        "message" => "数据库初始化完成",
        "tables" => $results,
        "test_data" => $testData
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "初始化失败: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

