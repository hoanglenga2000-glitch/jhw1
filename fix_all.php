<?php
/**
 * 一键修复所有问题
 */
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>一键修复</title>";
echo "<style>body{font-family:system-ui;background:#0F172A;color:#E2E8F0;padding:20px;max-width:800px;margin:0 auto;}";
echo ".ok{color:#10B981;}.err{color:#EF4444;}.warn{color:#F59E0B;}";
echo ".card{background:#1E293B;border-radius:12px;padding:20px;margin:15px 0;}";
echo "</style></head><body>";
echo "<h1>🔧 一键修复工具</h1>";

$baseDir = dirname(__DIR__);
$assetsDir = $baseDir . '/assets';
$uploadsDir = $baseDir . '/uploads';

echo "<div class='card'><h2>📁 修复图片资源</h2><ul>";

// 1. 创建default_student.png
if (!file_exists($assetsDir . '/default_student.png')) {
    if (file_exists($assetsDir . '/default_boy.png')) {
        if (copy($assetsDir . '/default_boy.png', $assetsDir . '/default_student.png')) {
            echo "<li class='ok'>✅ 创建 default_student.png 成功</li>";
        } else {
            echo "<li class='err'>❌ 创建 default_student.png 失败</li>";
        }
    } else {
        echo "<li class='warn'>⚠️ default_boy.png 不存在，无法创建 default_student.png</li>";
    }
} else {
    echo "<li class='ok'>✅ default_student.png 已存在</li>";
}

// 2. 确保uploads目录存在
if (!is_dir($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) {
        echo "<li class='ok'>✅ 创建 uploads 目录成功</li>";
    } else {
        echo "<li class='err'>❌ 创建 uploads 目录失败</li>";
    }
} else {
    echo "<li class='ok'>✅ uploads 目录已存在</li>";
}

echo "</ul></div>";

// 3. 验证文件
echo "<div class='card'><h2>✅ 验证修复结果</h2><ul>";

$checkFiles = [
    $assetsDir . '/default_boy.png' => '默认男头像',
    $assetsDir . '/default_girl.png' => '默认女头像',
    $assetsDir . '/default_student.png' => '默认学生头像',
    $assetsDir . '/logo-header.png' => 'Logo',
];

foreach ($checkFiles as $file => $name) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<li class='ok'>✅ {$name}: " . number_format($size) . " bytes</li>";
    } else {
        echo "<li class='err'>❌ {$name}: 不存在</li>";
    }
}

echo "</ul></div>";

echo "<div class='card'><h2>🎯 下一步</h2>";
echo "<p>1. 访问 <a href='check_all.php' style='color:#6366F1;'>系统检测工具</a> 检查所有API</p>";
echo "<p>2. 访问 <a href='../index.html' style='color:#6366F1;'>网站首页</a> 测试功能</p>";
echo "</div>";

echo "</body></html>";

