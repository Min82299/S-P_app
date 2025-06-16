<?php
session_start();
require_once "db.php";
$noti_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $noti_count = $stmt->fetchColumn();}
// Lấy tất cả bài viết kèm tên và avatar
$stmt = $pdo->query("
    SELECT posts.*, users.username, users.avatar 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Social Pixel - Timeline</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .comment-box {
            margin-top: 10px;
            padding-left: 20px;
            border-left: 2px solid #ccc;
        }
        .comment-box p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Social <span>Pixel</span></div>
        <div class="icons">
            <a href="notifications.php">🔔 Thông báo (<?= $noti_count ?>)</a> |
            <a href="post.php">✏️ 投稿する</a> |
            <a href="profile.php">👤 プロフィール</a> |
            <a href="logout.php">🚪 ログアウト</a>
        </div>
    </div>

    <div class="container">
        <h2>📰 タイムライン</h2>

        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="author">
                    <img src="avatar/<?= htmlspecialchars($post['avatar'] ?? 'default.png') ?>" width="40" height="40" style="border-radius:50%">
                    <a href="profile.php?id=<?= $post['user_id'] ?>">
                        <strong><?= htmlspecialchars($post['username']) ?></strong>
                    </a>
                </div>

                <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>

                <?php if (!empty($post['image_path'])): ?>
                    <div class="image">
                        <img src="post_images/<?= htmlspecialchars($post['image_path']) ?>" alt="post image">
                    </div>
                <?php endif; ?>

                <?php
                // ❤️ Đếm lượt thích
                $like_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
                $like_stmt->execute([$post['id']]);
                $like_count = $like_stmt->fetchColumn();

                // Kiểm tra người dùng hiện tại đã thích chưa
                $liked = false;
                if (isset($_SESSION['user_id'])) {
                    $check_like = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
                    $check_like->execute([$_SESSION['user_id'], $post['id']]);
                    $liked = $check_like->rowCount() > 0;
                }
                ?>

                <div class="actions">
                    <form action="like.php" method="post" style="display:inline;">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <button type="submit"><?= $liked ? '💔 Bỏ thích' : '❤️ Thích' ?> (<?= $like_count ?>)</button>
                    </form>
                </div>

                <!-- 🔈 Hiển thị bình luận -->
                <div class="comment-box">
                    <?php
                    $comment_stmt = $pdo->prepare("
                        SELECT comments.*, users.username FROM comments 
                        JOIN users ON comments.user_id = users.id 
                        WHERE post_id = ? ORDER BY created_at ASC
                    ");
                    $comment_stmt->execute([$post['id']]);
                    $comments = $comment_stmt->fetchAll();
                    ?>

                    <?php foreach ($comments as $c): ?>
                        <p><strong><?= htmlspecialchars($c['username']) ?>:</strong> <?= nl2br(htmlspecialchars($c['content'])) ?></p>
                    <?php endforeach; ?>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="comment.php" method="post">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <textarea name="content" rows="2" cols="40" placeholder="Viết bình luận..." required></textarea><br>
                        <button type="submit">💬 Bình luận</button>
                    </form>
                <?php endif; ?>

                <div class="actions">
                    🕒 <?= $post['created_at'] ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>


