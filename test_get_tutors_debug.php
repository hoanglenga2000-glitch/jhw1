<?php
// 诊断文件 - 测试每一步
header('Content-Type: text/html; charset=utf-8');
echo "<h1>get_tutors.php 诊断</h1>";

echo "<h2>1. 测试数据库连接</h2>";
require_once dirname(__DIR__) . '/config/db.php';
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}
echo "✓ 数据库连接成功<br>";

echo "<h2>2. 测试基础查询</h2>";
$result = $conn->query("SELECT id, name, school FROM tutors WHERE status='已通过' LIMIT 3");
if ($result) {
    echo "✓ 查询成功，找到 " . $result->num_rows . " 条记录<br>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Name: {$row['name']}, School: {$row['school']}<br>";
    }
} else {
    echo "✗ 查询失败: " . $conn->error . "<br>";
}

echo "<h2>3. 测试 SELECT * 查询</h2>";
$result2 = $conn->query("SELECT * FROM tutors WHERE status='已通过' LIMIT 1");
if ($result2) {
    $row = $result2->fetch_assoc();
    echo "✓ SELECT * 查询成功<br>";
    echo "字段列表: " . implode(', ', array_keys($row)) . "<br>";
    echo "字段数量: " . count($row) . "<br>";
} else {
    echo "✗ SELECT * 查询失败: " . $conn->error . "<br>";
}

echo "<h2>4. 测试 preg_split</h2>";
$testSubject = "数学,英语,物理";
$subjects = preg_split('/[,，、\s]+/', $testSubject);
if (is_array($subjects)) {
    echo "✓ preg_split 成功: " . implode(' | ', $subjects) . "<br>";
} else {
    echo "✗ preg_split 失败<br>";
}

echo "<h2>5. 测试 strtotime</h2>";
$testTime = "2025-12-31 23:59:59";
$expireTime = strtotime($testTime);
if ($expireTime) {
    echo "✓ strtotime 成功: $expireTime<br>";
} else {
    echo "✗ strtotime 失败<br>";
}

echo "<h2>6. 测试 mb_substr</h2>";
if (function_exists('mb_substr')) {
    $test = mb_substr("这是一个测试字符串", 0, 5, 'UTF-8');
    echo "✓ mb_substr 可用: $test<br>";
} else {
    echo "✗ mb_substr 不可用，使用 substr<br>";
}

echo "<h2>7. 测试完整 JSON 输出</h2>";
echo "<pre>";
$testData = [
    'status' => 'success',
    'message' => '测试',
    'data' => [['id' => 1, 'name' => '测试']]
];
echo json_encode($testData, JSON_UNESCAPED_UNICODE);
echo "</pre>";
echo "✓ JSON 输出正常<br>";

echo "<h2>诊断完成</h2>";

