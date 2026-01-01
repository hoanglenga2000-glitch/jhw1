<?php
$servername = "localhost";
$username = "jhw";
$password = "jhw20041108";
$dbname = "jhw";

// 不使用 @ 抑制错误，让错误可以被捕获
$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接
if ($conn->connect_error) {
    // 连接失败，$conn 仍然是对象，但 connect_error 有值
    // 调用者需要检查 $conn->connect_error
}

// 如果连接成功，设置字符集
if (!$conn->connect_error) {
    $conn->set_charset("utf8mb4");
}
