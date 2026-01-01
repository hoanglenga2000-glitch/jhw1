<?php
// 基于最简化版本，逐步添加功能 - 确保100%稳定
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

require_once dirname(__DIR__) . '/config/db.php';

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => '数据库连接失败: ' . $conn->connect_error], JSON_UNESCAPED_UNICODE);
    exit;
}

// 获取参数 - 最基础的处理
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12;
if ($limit < 1 || $limit > 50) $limit = 12;
$offset = ($page - 1) * $limit;

// 搜索和排序参数
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';

// 构建WHERE条件
$where = "status='已通过'";
if (!empty($search)) {
    $searchEscaped = $conn->real_escape_string($search);
    $where .= " AND (name LIKE '%$searchEscaped%' OR school LIKE '%$searchEscaped%')";
}

// 排序
$orderBy = "id DESC";
if ($sort == 'price_asc') {
    $orderBy = "price ASC";
} elseif ($sort == 'price_desc') {
    $orderBy = "price DESC";
} elseif ($sort == 'rating') {
    $orderBy = "rating DESC";
}

// 获取总数
$countSql = "SELECT COUNT(*) as total FROM tutors WHERE $where";
$countResult = $conn->query($countSql);
$totalCount = 0;
if ($countResult) {
    $totalRow = $countResult->fetch_assoc();
    $totalCount = intval($totalRow['total']);
}
$totalPages = $totalCount > 0 ? ceil($totalCount / $limit) : 0;

// 查询数据 - 使用最基础版本的结构
$sql = "SELECT id, name, school, price, rating, avatar FROM tutors WHERE $where ORDER BY $orderBy LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$list = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $avatar = $row['avatar'] ?? '';
        if (empty($avatar) || $avatar === 'null' || $avatar === '') {
            $avatar = 'assets/default_boy.png';
        } elseif (strpos($avatar, 'http') === 0) {
            // 完整URL保持不变
        } elseif (strpos($avatar, 'assets/') === 0 || strpos($avatar, 'uploads/') === 0) {
            // 已有正确前缀，保持不变
        } else {
            // 优先使用assets目录
            $avatar = 'assets/' . basename($avatar);
        }
        
        $list[] = [
            'id' => intval($row['id']),
            'name' => $row['name'] ?? '未命名',
            'school' => $row['school'] ?? '',
            'price' => floatval($row['price'] ?? 0),
            'rating' => floatval($row['rating'] ?? 5.0),
            'avatar' => $avatar
        ];
    }
}

// 输出JSON - 添加分页信息
echo json_encode([
    'status' => 'success',
    'message' => '获取成功',
    'data' => $list,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $totalCount,
        'total_pages' => $totalPages,
        'has_more' => ($page < $totalPages)
    ]
], JSON_UNESCAPED_UNICODE);
