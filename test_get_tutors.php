<?php
/**
 * 测试 get_tutors.php API
 * 用于诊断问题
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>get_tutors.php 测试</h1>";

echo "<h2>1. 测试直接输出</h2>";
echo "<p>访问: <a href='get_tutors.php?page=1&limit=12' target='_blank'>get_tutors.php?page=1&limit=12</a></p>";

echo "<h2>2. 测试JSON响应</h2>";
echo "<pre>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/get_tutors.php?page=1&limit=12');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n\n";
echo "Response Length: " . strlen($response) . " bytes\n\n";
echo "Response Content:\n";
echo htmlspecialchars($response);

echo "</pre>";

echo "<h2>3. 测试JSON解析</h2>";
$json = json_decode($response, true);
if ($json) {
    echo "<p style='color:green'>✅ JSON解析成功</p>";
    echo "<pre>" . print_r($json, true) . "</pre>";
} else {
    echo "<p style='color:red'>❌ JSON解析失败: " . json_last_error_msg() . "</p>";
}
?>

