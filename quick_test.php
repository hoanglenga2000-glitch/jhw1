<?php
/**
 * 快速测试 - 验证 get_tutors.php 是否能正常工作
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>快速测试 get_tutors.php</h1>";

echo "<h2>测试1: 直接访问</h2>";
echo "<p><a href='get_tutors.php?page=1&limit=12' target='_blank'>点击这里测试</a></p>";

echo "<h2>测试2: 直接读取文件内容</h2>";
$filePath = dirname(__FILE__) . '/get_tutors.php';
if (file_exists($filePath)) {
    $fileContent = file_get_contents($filePath);
    echo "<p>文件存在，大小: " . strlen($fileContent) . " bytes</p>";
    echo "<p>前200字符: <pre>" . htmlspecialchars(substr($fileContent, 0, 200)) . "</pre></p>";
} else {
    echo "<p style='color:red'>文件不存在: $filePath</p>";
}

echo "<h2>测试3: 使用 cURL 测试（HTTPS）</h2>";
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/get_tutors.php?page=1&limit=12';
echo "<p>URL: <code>$url</code></p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Response Length:</strong> " . strlen($response) . " bytes</p>";

if ($error) {
    echo "<p style='color:red'><strong>cURL Error:</strong> $error</p>";
}

echo "<h3>响应内容（前500字符）:</h3>";
echo "<pre style='background:#f5f5f5; padding:10px; border:1px solid #ddd; max-height:300px; overflow:auto;'>";
echo htmlspecialchars(substr($response, 0, 500));
echo "</pre>";

echo "<h3>JSON解析测试:</h3>";
$json = json_decode($response, true);
if ($json) {
    echo "<p style='color:green'>✅ JSON解析成功</p>";
    echo "<pre style='background:#f5f5f5; padding:10px; border:1px solid #ddd; max-height:400px; overflow:auto;'>";
    echo json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo "</pre>";
    
    if (isset($json['status']) && $json['status'] === 'success') {
        echo "<p style='color:green'>✅ API返回成功状态</p>";
        echo "<p>找到 " . count($json['data'] ?? []) . " 位教员</p>";
    } else {
        echo "<p style='color:orange'>⚠️ API返回错误: " . ($json['message'] ?? '未知错误') . "</p>";
    }
} else {
    echo "<p style='color:red'>❌ JSON解析失败: " . json_last_error_msg() . "</p>";
}

?>

