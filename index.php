<?php
session_start();
require_once "db.php";

// Lấy thông báo chưa đọc
$noti_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $noti_count = $stmt->fetchColumn();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['story_image'])) {
    $user_id = $_SESSION['user_id'];
    $image_name = time() . '_' . basename($_FILES['story_image']['name']);
    $target_dir = 'uploads/stories/';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target = $target_dir . $image_name;

    if (move_uploaded_file($_FILES['story_image']['tmp_name'], $target)) {
        $stmt = $pdo->prepare("INSERT INTO stories (user_id, image_url, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $target]);
        echo "<script>alert('Đăng story thành công!');window.location='index.php';</script>";
        exit;
    } else {
        echo "<script>alert('Lỗi upload ảnh!');</script>";
    }
}
if (isset($_FILES['story_image']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $image_name = time() . '_' . $_FILES['story_image']['name'];
    $target = 'uploads/stories/' . $image_name;
    
    if (!is_dir('uploads/stories/')) {
        mkdir('uploads/stories/', 0777, true);
    }
    
    if (move_uploaded_file($_FILES['story_image']['tmp_name'], $target)) {
        $stmt = $pdo->prepare("INSERT INTO stories (user_id, image_url, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $target]);
    }
}
// Trending topics
$trending_topics = ['#TechLife', '#Photography', '#Travel', '#Food', '#Nature', '#Coding'];

// Lấy danh sách bài viết
$stmt = $pdo->query("SELECT posts.*, users.username, users.avatar FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
$posts = $stmt->fetchAll();
// ...existing code...
$story_stmt = $pdo->prepare("
    SELECT u.id, u.username, u.avatar, s.created_at as latest_story, s.image_url
    FROM users u
    JOIN stories s ON s.user_id = u.id
    JOIN (
        SELECT user_id, MAX(created_at) as max_created
        FROM stories
        WHERE created_at >= NOW() - INTERVAL 1 DAY
        GROUP BY user_id
    ) m ON m.user_id = s.user_id AND m.max_created = s.created_at
    ORDER BY latest_story DESC
");
$story_stmt->execute();
$story_users = $story_stmt->fetchAll();
// ...existing code...

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Social Pixel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="logo">Social <span>Pixel</span></div>
        <div class="icons">
            <a href="notifications.php" class="bell">🔔<span class="count"><?= $noti_count ?></span></a>
        </div>
    </div>

    <div class="sidebar">
        <div class="logo">Social Pixel</div>
        <ul class="nav-links">
            <li class="active"><a href="#">🏠 Home</a></li>
            <li><a href="profile.php">👤 Profile</a></li>
            <li><a href="#">⚡ Explore</a></li>
            <li><a href="#">👥 Friends</a></li>
        </ul>
        <div class="trending">
            <h4>Trending Topics</h4>
            <ul>
                <?php foreach ($trending_topics as $topic): ?>
                    <li><a href="#"><?= $topic ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="container">
        <div class="stories">
    <div class="story" onclick="createStory()">
        <div class="story-avatar">+</div>
        <div class="story-name">Your Story</div>
    </div>
    <?php foreach ($story_users as $user): ?>
        <div class="story" onclick="viewStory('<?= $user['username'] ?>')">
            <div class="story-avatar">
                <img src="avatar/<?= htmlspecialchars($user['avatar']) ?>" width="100%" style="border-radius: 50%">
            </div>
            <div class="story-name"><?= htmlspecialchars($user['username']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal hiển thị story động -->
<div id="storyModal" style="display:none; position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.8);align-items:center;justify-content:center;z-index:9999;">
    <div style="position:relative;width:320px;height:480px;background:#000;border-radius:16px;overflow:hidden;">
        <span onclick="closeStory()" style="position:absolute;top:10px;right:20px;color:#fff;font-size:30px;cursor:pointer;z-index:2;">&times;</span>
        <img id="storyImg" src="" style="width:100%;height:100%;object-fit:cover;">
        <div class="progress-bar" style="position:absolute;top:8px;left:8px;right:8px;height:4px;background:rgba(255,255,255,0.3);border-radius:2px;overflow:hidden;">
            <div id="progress" style="height:100%;background:#fff;width:0%;transition:width 0.1s;"></div>
        </div>
    </div>
</div>

<script>
function viewStory(username) {
    // Ví dụ: Lấy danh sách story của user (ở đây demo 3 ảnh)
    const stories = [
        'https://picsum.photos/id/1015/320/480',
        'https://picsum.photos/id/1016/320/480',
        'https://picsum.photos/id/1018/320/480'
    ];
    let current = 0;
    const duration = 3000;
    const storyImg = document.getElementById('storyImg');
    const progress = document.getElementById('progress');
    const modal = document.getElementById('storyModal');
    modal.style.display = 'flex';

    function showSlide(idx) {
        storyImg.src = stories[idx];
        progress.style.transition = 'none';
        progress.style.width = '0%';
        setTimeout(() => {
            progress.style.transition = `width ${duration}ms linear`;
            progress.style.width = '100%';
        }, 50);
    }

    showSlide(current);
    let interval = setInterval(() => {
        current++;
        if (current >= stories.length) {
            closeStory();
            clearInterval(interval);
        } else {
            showSlide(current);
        }
    }, duration);

    // Đóng story khi click ngoài modal hoặc nút đóng
    window.closeStory = function() {
        modal.style.display = 'none';
        clearInterval(interval);
    }
}
function createStory() {
    alert('Tạo story mới!');
}
</script>
        <div class="feed">
            <div class="post-box">
                <div class="user-avatar">Y</div>
                <form action="post.php" method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; width:100%">
                    <input type="text" name="content" placeholder="Hôm nay bạn muốn chia sẻ điều gì?" required>
                    <input type="file" name="image">
                    <button type="submit">➕</button>
                </form>
            </div>

            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <div class="author">
                        <img src="avatar/<?= htmlspecialchars($post['avatar']) ?>">
                        <strong><?= htmlspecialchars($post['username']) ?></strong>
                    </div>
                    <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    <?php if (!empty($post['image_path'])): ?>
                        <div class="image">
                            <img src="post_images/<?= htmlspecialchars($post['image_path']) ?>" alt="post image">
                        </div>
                    <?php endif; ?>

                    <?php
                    $like_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
                    $like_stmt->execute([$post['id']]);
                    $like_count = $like_stmt->fetchColumn();

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
                            <button type="submit" style="background:none;border:none;cursor:pointer;">
                                <?= $liked ? '💔 Bỏ thích' : '❤️ Thích' ?> (<?= $like_count ?>)
                            </button>
                        </form>
                    </div>

                    <div class="comment-box">
                        <?php
                        $comment_stmt = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY created_at ASC");
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
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>





