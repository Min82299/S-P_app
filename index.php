<?php include('header.php'); ?>
<?php include('nav.php'); ?>

<div class="container">
  <main>
    <div class="stories">
      <div class="story add">+</div>
      <div class="story">タン</div>
      <div class="story">神山桜子</div>
      <div class="story">森川</div>
    </div>

    <div class="post-box">
      <form action="post.php" method="POST">
        <input type="text" name="content" placeholder="What's on your mind?">
        <button type="submit">+</button>
      </form>
    </div>

    <div class="post">
      <div class="post-header">
        <div class="avatar">S</div>
        <div>
          <strong>森川</strong><br>
          <span>@morikawa_1100 • 1d ago</span>
        </div>
      </div>
      <p> 宇宙は常に神秘的に動いています。 </p>
      <img src="images/galaxy.jpg" alt="Galaxy" />
      <div class="post-actions">
        <span>♥ 142</span>
        <span>💬 23</span>
      </div>
    </div>
  </main>
</div>

</body>
</html>
