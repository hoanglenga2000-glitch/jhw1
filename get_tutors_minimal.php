<?php
// 最简化版本 - 用于测试
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

require_once dirname(__DIR__) . '/config/db.php';

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => '数据库连接失败: ' . $conn->connect_error]);
    exit;
}

$result = $conn->query("SELECT id, name, school, price, rating, avatar FROM tutors WHERE status='已通过' LIMIT 12");
$list = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $avatar = $row['avatar'] ?? '';
        if (empty($avatar) || $avatar === 'null') {
            $avatar = 'assets/default_boy.png';
        } elseif (!preg_match('/^(http|assets\/|uploads\/)/', $avatar)) {
            $avatar = 'uploads/' . $avatar;
        }
        
        $list[] = [
            'id' => intval($row['id']),
            'name' => $row['name'] ?? '未命名',
            'school' => $row['school'] ?? '',
            'price' => floatval($row['price'] ?? 0),
            'rating' => floatval($row['rating'] ?? 5.0),
            'avatar' => $avatar
        ];
    }
}

echo json_encode([
    'status' => 'success',
    'message' => '获取成功',
    'data' => $list
], JSON_UNESCAPED_UNICODE);

