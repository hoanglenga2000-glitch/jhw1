<?php
// 直接执行版本 - 不使用 include，完全复制代码
error_reporting(E_ALL);
ini_set('display_errors', 1); // 临时启用错误显示

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 直接数据库连接（不通过 config/db.php）
$servername = "localhost";
$username = "jhw";
$password = "jhw20041108";
$dbname = "jhw";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => '数据库连接失败: ' . $conn->connect_error], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->set_charset("utf8mb4");

// 获取参数
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(50, max(1, intval($_GET['limit'] ?? 12)));
$offset = ($page - 1) * $limit;

// 构建WHERE条件
$where = "status='已通过'";

// 检查is_banned字段（可选）
$checkBanned = $conn->query("SHOW COLUMNS FROM tutors LIKE 'is_banned'");
if ($checkBanned && $checkBanned->num_rows > 0) {
    $where .= " AND (is_banned=0 OR is_banned IS NULL)";
}

// 搜索条件
if (!empty($search)) {
    $searchEscaped = $conn->real_escape_string($search);
    $where .= " AND (name LIKE '%$searchEscaped%' OR subject LIKE '%$searchEscaped%' OR school LIKE '%$searchEscaped%' OR major LIKE '%$searchEscaped%')";
}

// 检查VIP字段
$checkVip = $conn->query("SHOW COLUMNS FROM tutors LIKE 'is_vip'");
$hasVip = ($checkVip && $checkVip->num_rows > 0);

// 排序逻辑
$orderBy = "id DESC";
if ($hasVip) {
    $vipOrder = "(is_vip = 1 AND vip_expire_time > NOW()) DESC";
    switch ($sort) {
        case 'price_asc':
            $orderBy = "$vipOrder, price ASC";
            break;
        case 'price_desc':
            $orderBy = "$vipOrder, price DESC";
            break;
        case 'rating':
            $orderBy = "$vipOrder, rating DESC";
            break;
        default:
            $orderBy = "$vipOrder, id DESC";
    }
} else {
    switch ($sort) {
        case 'price_asc':
            $orderBy = "price ASC";
            break;
        case 'price_desc':
            $orderBy = "price DESC";
            break;
        case 'rating':
            $orderBy = "rating DESC";
            break;
        default:
            $orderBy = "id DESC";
    }
}

// 获取总数
$countSql = "SELECT COUNT(*) as total FROM tutors WHERE $where";
$countResult = $conn->query($countSql);
if (!$countResult) {
    echo json_encode(['status' => 'error', 'message' => '查询失败: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$totalRow = $countResult->fetch_assoc();
$totalCount = intval($totalRow['total'] ?? 0);
$totalPages = $totalCount > 0 ? ceil($totalCount / $limit) : 0;

// 查询数据
$sql = "SELECT * FROM tutors WHERE $where ORDER BY $orderBy LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => '查询数据失败: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

// 处理数据
$list = [];
while ($row = $result->fetch_assoc()) {
    // 处理头像路径
    $avatar = $row['avatar'] ?? '';
    if (empty($avatar) || $avatar === 'null' || $avatar === 'NULL') {
        $avatar = 'assets/default_boy.png';
    } elseif (!preg_match('/^(http|https|assets\/|uploads\/)/', $avatar)) {
        $avatar = 'uploads/' . $avatar;
    }
    
    // VIP状态
    $isVip = 0;
    if ($hasVip && isset($row['is_vip']) && intval($row['is_vip']) == 1) {
        if (!empty($row['vip_expire_time']) && strtotime($row['vip_expire_time']) > time()) {
            $isVip = 1;
        }
    }
    
    // 构建标签数组
    $tags = [];
    if (!empty($row['school'])) $tags[] = $row['school'];
    if (!empty($row['major'])) $tags[] = $row['major'];
    if (!empty($row['subject'])) {
        $subjects = preg_split('/[,，、\s]+/', $row['subject']);
        foreach ($subjects as $sub) {
            $sub = trim($sub);
            if (!empty($sub)) {
                $tags[] = $sub;
            }
        }
    }
    
    $list[] = [
        'id' => intval($row['id'] ?? 0),
        'name' => $row['name'] ?? '未命名',
        'school' => $row['school'] ?? '',
        'major' => $row['major'] ?? '',
        'subject' => $row['subject'] ?? '',
        'price' => floatval($row['price'] ?? 0),
        'rating' => floatval($row['rating'] ?? 5.0),
        'avatar' => $avatar,
        'is_vip' => $isVip,
        'tags' => array_values(array_unique(array_slice($tags, 0, 5))),
        'intro_short' => mb_substr($row['intro'] ?? '', 0, 50, 'UTF-8')
    ];
}

// 输出JSON
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

