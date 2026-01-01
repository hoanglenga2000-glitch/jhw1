<?php
/**
 * 只测试数据库连接和查询
 */
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

require_once dirname(__DIR__) . '/config/db.php';

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => '数据库连接失败: ' . $conn->connect_error], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = $conn->query("SELECT COUNT(*) as total FROM tutors WHERE status='已通过'");
if ($result) {
    $row = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'total' => intval($row['total'])], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['status' => 'error', 'message' => '查询失败: ' . $conn->error], JSON_UNESCAPED_UNICODE);
}

