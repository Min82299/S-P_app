<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Lấy thông tin user trước để dùng cho avatar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$avatar = !empty($user['avatar']) ? $user['avatar'] : 'default.png';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);

    // Đổi mật khẩu nếu có
    if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        if (!password_verify($_POST['current_password'], $user['password_hash'])) {
            $errors[] = "❌ Mật khẩu hiện tại không đúng.";
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $errors[] = "❌ Mật khẩu mới không khớp.";
        } else {
            $newHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user_id]);
            $success .= "✅ Đã cập nhật mật khẩu.<br>";
        }
    }

    // Xử lý ảnh đại diện
    if (!empty($_FILES['avatar']['name'])) {
        $avatarPath = basename($_FILES['avatar']['name']);
        move_uploaded_file($_FILES['avatar']['tmp_name'], "avatar/" . $avatarPath);
        $avatar = $avatarPath;

        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$username, $email, $bio, $avatarPath, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?");
        $stmt->execute([$username, $email, $bio, $user_id]);
    }

    $_SESSION['username'] = $username;
    if (empty($errors)) $success .= "✅ Hồ sơ đã được cập nhật.";
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>✏️ Chỉnh sửa hồ sơ</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .avatar-preview {
            border-radius: 50%;
            object-fit: cover;
            width: 100px;
            height: 100px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>✏️ Chỉnh sửa hồ sơ</h2>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e) echo $e . "<br>"; ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form action="edit_profile.php" method="post" enctype="multipart/form-data" id="edit-form">
        <label>Tên người dùng:<br>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
        </label><br><br>

        <label>Email:<br>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </label><br><br>

        <label>Mô tả bản thân:<br>
            <textarea name="bio" rows="4" cols="40"><?= htmlspecialchars($user['bio']) ?></textarea>
        </label><br><br>

        <label>Ảnh đại diện mới:<br>
            <input type="file" name="avatar" accept="image/*" onchange="previewAvatar(event)">
        </label><br><br>

        <img id="avatarPreview" src="avatar/<?= htmlspecialchars($avatar) ?>" class="avatar-preview"><br><br>

        <hr>
        <h3>🔐 Đổi mật khẩu (không bắt buộc)</h3>
        <label>Mật khẩu hiện tại:<br>
            <input type="password" name="current_password">
        </label><br><br>

        <label>Mật khẩu mới:<br>
            <input type="password" name="new_password">
        </label><br><br>

        <label>Xác nhận mật khẩu mới:<br>
            <input type="password" name="confirm_password">
        </label><br><br>

        <hr>
        <button type="submit">💾 Lưu thay đổi</button>
        <a href="profile.php">⬅️ Quay lại hồ sơ</a>
    </form>
</div>

<script>
function previewAvatar(event) {
    const img = document.getElementById('avatarPreview');
    img.src = URL.createObjectURL(event.target.files[0]);
}
</script>
</body>
</html>


