<?php
/**
 * 全面修复工具 - 检查和修复所有API接口
 */
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>全面修复工具</title>";
echo "<style>body{font-family:system-ui;background:#0F172A;color:#E2E8F0;padding:20px;max-width:1200px;margin:0 auto;}";
echo ".ok{color:#10B981;}.err{color:#EF4444;}.warn{color:#F59E0B;}";
echo ".card{background:#1E293B;border-radius:12px;padding:20px;margin:15px 0;}";
echo "table{width:100%;border-collapse:collapse;}th,td{padding:10px;text-align:left;border-bottom:1px solid #334155;}";
echo "th{background:#0F172A;color:#94A3B8;}tr:hover{background:#0F172A;}";
echo "</style></head><body>";
echo "<h1>🔧 全面修复工具</h1>";

$baseDir = dirname(__DIR__);

// 检查所有关键API文件
$requiredApis = [
    'get_tutors.php' => '教员列表API',
    'get_tutor_detail.php' => '教员详情API',
    'login.php' => '登录API',
    'register.php' => '注册API',
    'user_api.php' => '用户API',
    'gamification_api.php' => '游戏化API',
    'chat_api.php' => '聊天API',
    'demand_api.php' => '需求API',
    'resource_api.php' => '资源API',
    'public_api.php' => '公告API',
    'book_tutor.php' => '预约API',
    'teacher_api.php' => '教员API',
];

echo "<div class='card'><h2>📋 API文件检查</h2><table><tr><th>API文件</th><th>状态</th><th>说明</th></tr>";

foreach ($requiredApis as $file => $name) {
    $path = $baseDir . '/api/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        $content = file_get_contents($path);
        $hasCors = strpos($content, 'Access-Control-Allow-Origin') !== false;
        $hasJsonHeader = strpos($content, 'Content-Type: application/json') !== false;
        
        $status = "<span class='ok'>✅ 存在</span>";
        if (!$hasCors) $status .= " <span class='warn'>⚠️ 缺少CORS</span>";
        if (!$hasJsonHeader) $status .= " <span class='warn'>⚠️ 缺少JSON头</span>";
        
        echo "<tr><td>{$name}<br><small style='color:#64748B'>{$file}</small></td><td>{$status}</td><td>" . number_format($size) . " bytes</td></tr>";
    } else {
        echo "<tr><td>{$name}<br><small style='color:#64748B'>{$file}</small></td><td><span class='err'>❌ 不存在</span></td><td></td></tr>";
    }
}

echo "</table></div>";

// 检查前端调用的所有API action
echo "<div class='card'><h2>🔍 前端API调用检查</h2>";
echo "<p>检查student_center.html中调用的所有API action是否存在：</p>";

$apiActions = [
    'user_api.php' => [
        'get_my_bookings', 'apply_refund', 'pay_order', 'get_my_downloads',
        'check_unread', 'get_notifications', 'toggle_favorite', 'check_favorite',
        'get_my_favorites', 'get_info', 'update_profile', 'recharge',
        'get_wallet_history', 'get_my_coupons', 'get_tutor_reviews', 'check_booking_status'
    ],
    'gamification_api.php' => [
        'get_badges', 'get_level_info', 'get_status', 'get_mall_items',
        'do_signin', 'exchange_item', 'get_leaderboard'
    ],
    'chat_api.php' => [
        'get_contacts', 'get_history', 'send_message', 'get_unread_count'
    ],
    'demand_api.php' => [
        'post_demand', 'get_my_demands', 'get_appliers', 'accept_tutor'
    ],
    'resource_api.php' => [
        'get_download_url'
    ]
];

require_once $baseDir . '/config/db.php';

echo "<table><tr><th>API文件</th><th>Action</th><th>状态</th></tr>";

foreach ($apiActions as $apiFile => $actions) {
    $apiPath = $baseDir . '/api/' . $apiFile;
    if (!file_exists($apiPath)) {
        echo "<tr><td colspan='3'><span class='err'>❌ {$apiFile} 文件不存在</span></td></tr>";
        continue;
    }
    
    $content = file_get_contents($apiPath);
    
    foreach ($actions as $action) {
        // 检查action是否存在
        $hasAction = (
            strpos($content, "action == '{$action}'") !== false ||
            strpos($content, "action == \"{$action}\"") !== false ||
            strpos($content, "action='{$action}'") !== false ||
            strpos($content, "action=\"{$action}\"") !== false ||
            preg_match("/action\s*[=!]=\s*['\"]{$action}['\"]/", $content)
        );
        
        $status = $hasAction ? "<span class='ok'>✅ 存在</span>" : "<span class='err'>❌ 缺失</span>";
        echo "<tr><td>{$apiFile}</td><td>{$action}</td><td>{$status}</td></tr>";
    }
}

echo "</table></div>";

// 提供修复建议
echo "<div class='card'><h2>💡 修复建议</h2>";
echo "<ol>";
echo "<li>如果API文件存在但缺少CORS头，需要添加：<code>header(\"Access-Control-Allow-Origin: *\");</code></li>";
echo "<li>如果action缺失，需要在对应的API文件中添加处理逻辑</li>";
echo "<li>确保所有API返回统一的JSON格式：<code>{\"status\": \"success/error\", \"message\": \"...\", \"data\": ...}</code></li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='../index.html' style='color:#6366F1;'>返回首页</a> | <a href='check_all.php' style='color:#6366F1;'>系统检测</a></p>";
echo "</body></html>";

