<?php
/**
 * 智慧家教桥 - 全面系统检测工具
 * 检测所有API、数据库表和图片资源
 */
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>系统检测</title>";
echo "<style>body{font-family:system-ui,-apple-system,sans-serif;background:#0F172A;color:#E2E8F0;padding:20px;max-width:1000px;margin:0 auto;}";
echo "h1{color:#6366F1;}h2{color:#F59E0B;margin-top:30px;}";
echo ".ok{color:#10B981;}.err{color:#EF4444;}.warn{color:#F59E0B;}";
echo ".card{background:#1E293B;border-radius:12px;padding:20px;margin:15px 0;}";
echo "table{width:100%;border-collapse:collapse;}th,td{padding:10px;text-align:left;border-bottom:1px solid #334155;}";
echo "th{background:#0F172A;color:#94A3B8;}tr:hover{background:#0F172A;}";
echo ".btn{background:#6366F1;color:white;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-block;margin:5px;}";
echo ".btn:hover{background:#4F46E5;}</style></head><body>";

echo "<h1>🔍 智慧家教桥 - 系统检测工具</h1>";
echo "<p>检测时间：" . date('Y-m-d H:i:s') . "</p>";

// ============ 1. 检测PHP环境 ============
echo "<div class='card'><h2>📦 PHP环境检测</h2><table>";
echo "<tr><th>项目</th><th>状态</th><th>详情</th></tr>";

$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '7.0.0', '>=');
echo "<tr><td>PHP版本</td><td class='" . ($phpOk ? 'ok' : 'err') . "'>" . ($phpOk ? '✅ OK' : '❌ 需要升级') . "</td><td>{$phpVersion}</td></tr>";

$extensions = ['mysqli', 'json', 'mbstring', 'session'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<tr><td>{$ext} 扩展</td><td class='" . ($loaded ? 'ok' : 'err') . "'>" . ($loaded ? '✅ 已加载' : '❌ 未加载') . "</td><td></td></tr>";
}
echo "</table></div>";

// ============ 2. 检测数据库连接 ============
echo "<div class='card'><h2>🗄️ 数据库检测</h2><table>";
echo "<tr><th>项目</th><th>状态</th><th>详情</th></tr>";

$dbOk = false;
$conn = null;
try {
    require_once '../config/db.php';
    if (isset($conn) && !$conn->connect_error) {
        $dbOk = true;
        echo "<tr><td>数据库连接</td><td class='ok'>✅ 连接成功</td><td></td></tr>";
        
        // 检测关键表
        $tables = ['students', 'tutors', 'bookings', 'reviews', 'messages'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            $exists = $result && $result->num_rows > 0;
            echo "<tr><td>数据表: {$table}</td><td class='" . ($exists ? 'ok' : 'warn') . "'>" . ($exists ? '✅ 存在' : '⚠️ 不存在') . "</td><td></td></tr>";
        }
        
        // 检测tutors表数据
        $result = $conn->query("SELECT COUNT(*) as cnt FROM tutors WHERE status='已通过'");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<tr><td>已通过的教员数量</td><td class='ok'>✅</td><td>{$row['cnt']} 位</td></tr>";
        }
    } else {
        echo "<tr><td>数据库连接</td><td class='err'>❌ 连接失败</td><td>" . ($conn ? $conn->connect_error : '未知错误') . "</td></tr>";
    }
} catch (Exception $e) {
    echo "<tr><td>数据库连接</td><td class='err'>❌ 异常</td><td>{$e->getMessage()}</td></tr>";
}
echo "</table></div>";

// ============ 3. 检测API接口 ============
echo "<div class='card'><h2>🔌 API接口检测</h2><table>";
echo "<tr><th>接口</th><th>状态</th><th>响应</th></tr>";

$apis = [
    'get_tutors.php?page=1&limit=3' => '教员列表',
    'get_tutor_detail.php?id=1' => '教员详情',
    'gamification_api.php?action=get_leaderboard' => '排行榜',
    'public_api.php?action=get_latest_notices' => '公告',
    'login.php' => '登录接口',
];

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI'])) . '/api/';

foreach ($apis as $api => $name) {
    $url = $baseUrl . $api;
    $response = false;
    
    // 优先使用curl测试（更可靠）
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = @curl_exec($ch);
        curl_close($ch);
    } else {
        // 回退到file_get_contents
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        $response = @file_get_contents($url, false, $context);
    }
    
    if ($response !== false) {
        $json = @json_decode($response, true);
        if ($json !== null) {
            $status = isset($json['status']) ? $json['status'] : '无status字段';
            $statusClass = ($status === 'success') ? 'ok' : 'warn';
            echo "<tr><td>{$name}<br><small style='color:#64748B'>{$api}</small></td><td class='{$statusClass}'>" . ($status === 'success' ? '✅ 正常' : '⚠️ ' . $status) . "</td><td>" . mb_substr($response, 0, 100) . "...</td></tr>";
        } else {
            echo "<tr><td>{$name}<br><small style='color:#64748B'>{$api}</small></td><td class='err'>❌ JSON解析失败</td><td>" . mb_substr($response, 0, 100) . "...</td></tr>";
        }
    } else {
        echo "<tr><td>{$name}<br><small style='color:#64748B'>{$api}</small></td><td class='err'>❌ 请求失败</td><td>无法连接</td></tr>";
    }
}
echo "</table></div>";

// ============ 4. 检测图片资源 ============
echo "<div class='card'><h2>🖼️ 图片资源检测</h2><table>";
echo "<tr><th>文件</th><th>状态</th><th>大小</th></tr>";

$baseDir = dirname(__DIR__);
$images = [
    'assets/icons/logo-square-master.png.png' => 'Logo图片',
    'assets/icons/AppImages/ios/180.png' => 'iOS图标 180x180',
    'assets/icons/AppImages/android/android-launchericon-192-192.png' => 'Android图标 192x192',
    'assets/default_boy.png' => '默认男头像',
    'assets/default_girl.png' => '默认女头像',
    'assets/default_student.png' => '默认学生头像',
];

foreach ($images as $path => $name) {
    $fullPath = $baseDir . '/' . $path;
    $exists = file_exists($fullPath);
    $size = $exists ? filesize($fullPath) : 0;
    echo "<tr><td>{$name}<br><small style='color:#64748B'>{$path}</small></td><td class='" . ($exists ? 'ok' : 'err') . "'>" . ($exists ? '✅ 存在' : '❌ 不存在') . "</td><td>" . ($exists ? number_format($size) . ' bytes' : '-') . "</td></tr>";
}

// 检测uploads目录
$uploadsDir = $baseDir . '/uploads';
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    $fileCount = count(array_filter($files, function($f) { return $f !== '.' && $f !== '..'; }));
    echo "<tr><td>uploads目录</td><td class='" . ($fileCount > 0 ? 'ok' : 'warn') . "'>" . ($fileCount > 0 ? '✅ ' : '⚠️ ') . "{$fileCount} 个文件</td><td></td></tr>";
} else {
    echo "<tr><td>uploads目录</td><td class='err'>❌ 不存在</td><td></td></tr>";
}

echo "</table></div>";

// ============ 5. 快速修复按钮 ============
echo "<div class='card'><h2>🔧 快速修复工具</h2>";
echo "<p>如果检测到问题，可以使用以下工具修复：</p>";
echo "<a href='fix_assets.php' class='btn' target='_blank'>📁 修复图片资源</a>";
echo "<a href='init_db.php' class='btn' target='_blank'>🗄️ 初始化数据库</a>";
echo "<a href='../index.html' class='btn' target='_blank'>🏠 返回首页</a>";
echo "</div>";

echo "<p style='text-align:center;color:#64748B;margin-top:30px;'>智慧家教桥 - 系统检测工具 v1.0</p>";
echo "</body></html>";

