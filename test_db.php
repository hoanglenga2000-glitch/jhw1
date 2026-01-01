<?php
/**
 * 数据库连接测试脚本
 * 用于诊断宝塔服务器上的数据库问题
 */

error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

$result = [
    'status' => 'testing',
    'php_version' => PHP_VERSION,
    'tests' => []
];

// 测试1: PHP版本
$result['tests']['php'] = [
    'name' => 'PHP版本检查',
    'status' => version_compare(PHP_VERSION, '7.0', '>=') ? 'ok' : 'error',
    'value' => PHP_VERSION
];

// 测试2: mysqli扩展
$result['tests']['mysqli'] = [
    'name' => 'MySQLi扩展',
    'status' => extension_loaded('mysqli') ? 'ok' : 'error',
    'value' => extension_loaded('mysqli') ? '已加载' : '未加载'
];

// 测试3: 数据库配置文件
$dbConfigPath = '../config/db.php';
$result['tests']['config_file'] = [
    'name' => '配置文件',
    'status' => file_exists($dbConfigPath) ? 'ok' : 'error',
    'value' => file_exists($dbConfigPath) ? '存在' : '不存在'
];

// 测试4: 数据库连接
if (extension_loaded('mysqli') && file_exists($dbConfigPath)) {
    try {
        // 直接读取配置
        $servername = "localhost";
        $username = "jhw";
        $password = "jhw20041108";
        $dbname = "jhw";
        
        $testConn = @new mysqli($servername, $username, $password, $dbname);
        
        if ($testConn->connect_error) {
            $result['tests']['db_connect'] = [
                'name' => '数据库连接',
                'status' => 'error',
                'value' => '连接失败: ' . $testConn->connect_error
            ];
        } else {
            $result['tests']['db_connect'] = [
                'name' => '数据库连接',
                'status' => 'ok',
                'value' => '连接成功'
            ];
            
            // 测试5: 检查核心表
            $tables = ['users', 'tutors', 'bookings'];
            foreach ($tables as $table) {
                $check = $testConn->query("SHOW TABLES LIKE '$table'");
                $result['tests']['table_' . $table] = [
                    'name' => "表 $table",
                    'status' => ($check && $check->num_rows > 0) ? 'ok' : 'warning',
                    'value' => ($check && $check->num_rows > 0) ? '存在' : '不存在'
                ];
            }
            
            // 测试6: 尝试查询tutors表
            $tutorCheck = $testConn->query("SELECT COUNT(*) as c FROM tutors");
            if ($tutorCheck) {
                $count = $tutorCheck->fetch_assoc()['c'];
                $result['tests']['tutors_count'] = [
                    'name' => '教员数量',
                    'status' => 'ok',
                    'value' => $count . ' 位教员'
                ];
            }
            
            $testConn->close();
        }
    } catch (Exception $e) {
        $result['tests']['db_connect'] = [
            'name' => '数据库连接',
            'status' => 'error',
            'value' => '异常: ' . $e->getMessage()
        ];
    }
}

// 总结
$allOk = true;
foreach ($result['tests'] as $test) {
    if ($test['status'] === 'error') {
        $allOk = false;
        break;
    }
}

$result['status'] = $allOk ? 'success' : 'error';
$result['message'] = $allOk ? '所有测试通过！' : '部分测试失败，请检查上方详情';

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>

