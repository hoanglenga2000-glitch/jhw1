<?php
/**
 * 修复资源文件脚本
 * 在服务器上运行一次即可
 */
header('Content-Type: text/html; charset=utf-8');

$baseDir = dirname(__DIR__);
$assetsDir = $baseDir . '/assets';
$uploadsDir = $baseDir . '/uploads';

echo "<h2>资源文件修复工具</h2>";

// 确保uploads目录存在
if (!is_dir($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) {
        echo "<p>✅ 创建 uploads 目录成功</p>";
    } else {
        echo "<p>❌ 创建 uploads 目录失败</p>";
    }
} else {
    echo "<p>✅ uploads 目录已存在</p>";
}

// 需要复制的文件映射
$filesToCopy = [
    // 默认头像 - 尝试多个可能的源文件
    ['from' => $assetsDir . '/default_boy.png', 'to' => $assetsDir . '/default_student.png', 'optional' => true],
    ['from' => $assetsDir . '/default_boy (1).png', 'to' => $assetsDir . '/default_boy.png', 'optional' => true],
    ['from' => $assetsDir . '/default_girl (1).png', 'to' => $assetsDir . '/default_girl.png', 'optional' => true],
    ['from' => $assetsDir . '/default_boy (1).png', 'to' => $uploadsDir . '/default_boy.png'],
    ['from' => $assetsDir . '/default_girl (1).png', 'to' => $uploadsDir . '/default_girl.png'],
    
    // Logo
    ['from' => $assetsDir . '/icons/logo-square-master.png.png', 'to' => $assetsDir . '/logo-header.png'],
    
    // 复制tutor图片到uploads
    ['from' => $assetsDir . '/avt_1766460048.jpg', 'to' => $uploadsDir . '/avt_1766460048.jpg'],
    ['from' => $assetsDir . '/avt_1766482474_622.jpg', 'to' => $uploadsDir . '/avt_1766482474_622.jpg'],
    ['from' => $assetsDir . '/avt_1766483969_460.jpg', 'to' => $uploadsDir . '/avt_1766483969_460.jpg'],
    ['from' => $assetsDir . '/tutor_1766298831.jpg', 'to' => $uploadsDir . '/tutor_1766298831.jpg'],
    ['from' => $assetsDir . '/stu_1766471632.jpg', 'to' => $uploadsDir . '/stu_1766471632.jpg'],
    ['from' => $assetsDir . '/id_1766458388_106.jpg', 'to' => $uploadsDir . '/id_1766458388_106.jpg'],
    ['from' => $assetsDir . '/id_1766459569_153.png', 'to' => $uploadsDir . '/id_1766459569_153.png'],
    ['from' => $assetsDir . '/idcard_1766458630_5978.jpg', 'to' => $uploadsDir . '/idcard_1766458630_5978.jpg'],
    ['from' => $assetsDir . '/idcard_1766458653_3897.jpg', 'to' => $uploadsDir . '/idcard_1766458653_3897.jpg'],
    ['from' => $assetsDir . '/idcard_1766458830_8805.jpg', 'to' => $uploadsDir . '/idcard_1766458830_8805.jpg'],
    ['from' => $assetsDir . '/cert_111_1766578448.jpg', 'to' => $uploadsDir . '/cert_111_1766578448.jpg'],
];

echo "<h3>复制文件：</h3><ul>";

foreach ($filesToCopy as $file) {
    $from = $file['from'];
    $to = $file['to'];
    $fromName = basename($from);
    $toName = basename($to);
    $optional = isset($file['optional']) && $file['optional'];
    
    if (file_exists($from)) {
        if (file_exists($to)) {
            echo "<li>⏩ {$toName} 已存在，跳过</li>";
        } else {
            if (copy($from, $to)) {
                echo "<li>✅ 复制 {$fromName} → {$toName} 成功</li>";
            } else {
                echo "<li>❌ 复制 {$fromName} → {$toName} 失败</li>";
            }
        }
    } else {
        if ($optional) {
            echo "<li>⚠️ 可选源文件不存在: {$fromName}（跳过）</li>";
        } else {
            echo "<li>⚠️ 源文件不存在: {$fromName}</li>";
        }
    }
}

// 特别处理：如果default_student.png不存在，尝试从default_boy.png复制
if (!file_exists($assetsDir . '/default_student.png')) {
    if (file_exists($assetsDir . '/default_boy.png')) {
        if (copy($assetsDir . '/default_boy.png', $assetsDir . '/default_student.png')) {
            echo "<li>✅ 复制 default_boy.png → default_student.png 成功</li>";
        }
    }
}

echo "</ul>";

// 检查结果
echo "<h3>验证文件：</h3><ul>";
$checkFiles = [
    $assetsDir . '/default_boy.png',
    $assetsDir . '/default_girl.png',
    $assetsDir . '/default_student.png',
    $assetsDir . '/logo-header.png',
    $uploadsDir . '/default_boy.png',
    $uploadsDir . '/default_girl.png',
];

foreach ($checkFiles as $file) {
    $name = str_replace($baseDir, '', $file);
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<li>✅ {$name} ({$size} bytes)</li>";
    } else {
        echo "<li>❌ {$name} 不存在</li>";
    }
}

echo "</ul>";

echo "<p><strong>修复完成！</strong> 请刷新网站页面查看效果。</p>";
echo "<p><a href='../index.html'>返回首页</a></p>";

