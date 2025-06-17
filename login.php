<?php
require_once "db.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($user_id, $hash);
        $stmt->fetch();

        if (password_verify($password, $hash)) {
            $_SESSION["user_id"] = $user_id;
            $_SESSION["username"] = $username;
            header("Location: index.php");
            exit();
        } else {
            echo "Sai mật khẩu!";
        }
    } else {
        echo "Không tìm thấy tài khoản.";
    }
}
?>

<form method="POST" action="">
    <h2>Đăng nhập</h2>
    <input name="username" placeholder="Tên người dùng" required><br>
    <input name="password" type="password" placeholder="Mật khẩu" required><br>
    <button type="submit">Đăng nhập</button>
</form>
