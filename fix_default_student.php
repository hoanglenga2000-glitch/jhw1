<?php
/**
 * 修复default_student.png文件
 * 复制default_boy.png作为default_student.png
 */
header('Content-Type: text/html; charset=utf-8');

$baseDir = dirname(__DIR__);
$fromFile = $baseDir . '/assets/default_boy.png';
$toFile = $baseDir . '/assets/default_student.png';

echo "<h2>修复 default_student.png</h2>";

if (file_exists($fromFile)) {
    if (copy($fromFile, $toFile)) {
        echo "<p style='color:green;'>✅ 成功复制 default_boy.png → default_student.png</p>";
        echo "<p>文件大小: " . filesize($toFile) . " bytes</p>";
    } else {
        echo "<p style='color:red;'>❌ 复制失败</p>";
    }
} else {
    echo "<p style='color:red;'>❌ 源文件不存在: default_boy.png</p>";
}

echo "<p><a href='../index.html'>返回首页</a></p>";

