<?php
/**
 * 最简单的测试脚本 - 测试PHP是否能正常运行
 */

error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'success',
    'message' => 'PHP运行正常',
    'php_version' => PHP_VERSION,
    'time' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);
?>

