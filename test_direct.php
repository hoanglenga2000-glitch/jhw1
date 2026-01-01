<?php
/**
 * 直接测试 - 不使用任何复杂的逻辑
 */
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

// 测试1: 直接输出
echo json_encode(['status' => 'success', 'message' => 'PHP工作正常', 'test' => 'direct_output'], JSON_UNESCAPED_UNICODE);
exit;

