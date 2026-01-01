<?php
/**
 * 完整诊断脚本 - 检查所有可能的问题
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>API 诊断工具</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        pre { background: #000; padding: 10px; border: 1px solid #333; overflow: auto; }
    </style>
</head>
<body>
    <h1>🔍 API 完整诊断</h1>
    
    <?php
    echo "<h2>1. PHP 环境检查</h2>";
    echo "<p class='success'>✅ PHP 版本: " . PHP_VERSION . "</p>";
    echo "<p class='success'>✅ 错误报告: " . (error_reporting() ? '已启用' : '已禁用') . "</p>";
    
    echo "<h2>2. 数据库连接检查</h2>";
    try {
        require_once dirname(__DIR__) . '/config/db.php';
        
        if (!isset($conn)) {
            echo "<p class='error'>❌ 数据库连接对象未创建</p>";
        } elseif ($conn->connect_error) {
            echo "<p class='error'>❌ 数据库连接失败: " . htmlspecialchars($conn->connect_error) . "</p>";
        } else {
            echo "<p class='success'>✅ 数据库连接成功</p>";
            
            // 测试查询
            $testQuery = $conn->query("SELECT COUNT(*) as total FROM tutors WHERE status='已通过'");
            if ($testQuery) {
                $row = $testQuery->fetch_assoc();
                echo "<p class='success'>✅ 查询测试成功，找到 " . intval($row['total']) . " 位已通过审核的教员</p>";
            } else {
                echo "<p class='error'>❌ 查询失败: " . htmlspecialchars($conn->error) . "</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ 异常: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h2>3. get_tutors.php 文件检查</h2>";
    $filePath = dirname(__FILE__) . '/get_tutors.php';
    if (file_exists($filePath)) {
        echo "<p class='success'>✅ 文件存在: $filePath</p>";
        echo "<p class='success'>✅ 文件大小: " . filesize($filePath) . " bytes</p>";
        
        // 检查语法
        $output = [];
        $return = 0;
        exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $return);
        if ($return === 0) {
            echo "<p class='success'>✅ PHP 语法检查通过</p>";
        } else {
            echo "<p class='error'>❌ PHP 语法错误:</p>";
            echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
        }
    } else {
        echo "<p class='error'>❌ 文件不存在: $filePath</p>";
    }
    
    echo "<h2>4. 直接执行测试</h2>";
    echo "<p>尝试直接执行 get_tutors.php...</p>";
    
    // 使用 include 来执行，但捕获输出
    ob_start();
    try {
        $_GET = ['page' => 1, 'limit' => 12];
        include $filePath;
        $output = ob_get_clean();
        
        if (empty($output)) {
            echo "<p class='error'>❌ 脚本执行后没有输出任何内容</p>";
        } else {
            echo "<p class='success'>✅ 脚本有输出，长度: " . strlen($output) . " bytes</p>";
            echo "<p>输出内容（前500字符）:</p>";
            echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
            
            // 尝试解析JSON
            $json = json_decode($output, true);
            if ($json) {
                echo "<p class='success'>✅ JSON 解析成功</p>";
                echo "<pre>" . json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<p class='error'>❌ JSON 解析失败: " . json_last_error_msg() . "</p>";
            }
        }
    } catch (Throwable $e) {
        ob_end_clean();
        echo "<p class='error'>❌ 执行异常: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p class='error'>文件: " . htmlspecialchars($e->getFile()) . " 行: " . $e->getLine() . "</p>";
    }
    
    echo "<h2>5. 建议</h2>";
    echo "<ul>";
    echo "<li>如果数据库连接失败，检查 config/db.php 中的配置</li>";
    echo "<li>如果查询失败，检查 tutors 表是否存在</li>";
    echo "<li>如果脚本没有输出，检查 PHP 错误日志</li>";
    echo "<li>如果 Service Worker 缓存了空响应，清除浏览器缓存或禁用 Service Worker</li>";
    echo "</ul>";
    ?>
    
    <h2>6. 快速测试链接</h2>
    <ul>
        <li><a href="test_direct.php" target="_blank">test_direct.php</a> - 测试基础 PHP 功能</li>
        <li><a href="test_db_only.php" target="_blank">test_db_only.php</a> - 测试数据库查询</li>
        <li><a href="get_tutors_minimal.php" target="_blank">get_tutors_minimal.php</a> - 最简化版本（应该能工作）</li>
        <li><a href="get_tutors.php?page=1&limit=12" target="_blank">get_tutors.php</a> - 完整版本</li>
    </ul>
</body>
</html>

