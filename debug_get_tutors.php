<?php
/**
 * 调试版本 - 最简化的 get_tutors.php
 * 用于诊断问题
 */

// 关闭所有错误抑制，看真实错误
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 启动输出缓冲
ob_start();

// 设置响应头
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

try {
    // 测试1: PHP是否正常执行
    $test = ['step' => 1, 'message' => 'PHP执行正常'];
    
    // 测试2: 引入配置文件
    $configPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';
    if (!file_exists($configPath)) {
        throw new Exception('配置文件不存在: ' . $configPath);
    }
    require_once $configPath;
    $test['step'] = 2;
    $test['message'] = '配置文件加载成功';
    
    // 测试3: 数据库连接
    if (!isset($conn)) {
        throw new Exception('数据库连接对象未创建');
    }
    if ($conn->connect_error) {
        throw new Exception('数据库连接失败: ' . $conn->connect_error);
    }
    $test['step'] = 3;
    $test['message'] = '数据库连接成功';
    
    // 测试4: 检查表
    $tableCheck = $conn->query("SHOW TABLES LIKE 'tutors'");
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        throw new Exception('tutors表不存在');
    }
    $test['step'] = 4;
    $test['message'] = 'tutors表存在';
    
    // 测试5: 查询数据
    $result = $conn->query("SELECT COUNT(*) as total FROM tutors WHERE status='已通过'");
    if (!$result) {
        throw new Exception('查询失败: ' . $conn->error);
    }
    $row = $result->fetch_assoc();
    $test['step'] = 5;
    $test['total'] = intval($row['total']);
    $test['message'] = '查询成功，找到' . $test['total'] . '位教员';
    
    // 返回成功
    ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => '所有测试通过',
        'test' => $test
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

