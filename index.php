<?php
// git commit: "feat: update index.php navbar — add profile link"

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Feed</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; background: #f5f5f5; min-height: 100vh; }

    nav {
      background: #534AB7; padding: 12px 24px;
      display: flex; justify-content: space-between; align-items: center; color: white;
      position: sticky; top: 0; z-index: 100;
    }
    nav .nav-logo { font-weight: 600; font-size: 1rem; }
    .nav-links { display: flex; gap: 10px; align-items: center; }
    .nav-links span { font-size: 0.85rem; color: rgba(255,255,255,0.8); }
    .nav-links a {
      color: white; text-decoration: none; font-size: 0.9rem;
      background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 8px;
    }
    .nav-links a:hover { background: rgba(255,255,255,0.3); }

    .container { max-width: 600px; margin: 30px auto; padding: 0 16px; }

    .upload-form {
      background: white; border-radius: 16px; padding: 20px;
      margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .upload-form h3 { margin-bottom: 12px; color: #1a1a1a; font-size: 1rem; }
    .upload-form input[type="file"],
    .upload-form input[type="text"] {
      width: 100%; padding: 10px; margin-bottom: 10px;
      border: 1.5px solid #e0dfd8; border-radius: 8px; font-size: 0.9rem; background: #fafaf8;
    }
    .upload-form button {
      width: 100%; padding: 10px; background: #534AB7; color: white;
      border: none; border-radius: 8px; font-weight: 600; cursor: pointer;
    }
    .upload-form button:hover { background: #3C3489; }

    .post {
      background: white; border-radius: 16px; margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;
    }
    .post-header {
      padding: 12px 16px; font-weight: 600; font-size: 0.95rem;
      color: #534AB7; display: flex; align-items: center; gap: 10px;
    }
    .post-header-avatar {
      width: 36px; height: 36px; border-radius: 50%; object-fit: cover;
      background: linear-gradient(135deg, #534AB7, #1D9E75);
      display: flex; align-items: center; justify-content: center;
      color: white; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;
      overflow: hidden;
    }
    .post-header-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .post-header a { color: #534AB7; text-decoration: none; }
    .post-header a:hover { text-decoration: underline; }
    .post img { width: 100%; display: block; max-height: 500px; object-fit: cover; }
    .post-footer { padding: 12px 16px; display: flex; align-items: center; gap: 12px; }
    .post-caption { padding: 0 16px 12px; font-size: 0.9rem; color: #333; }

    .like-btn {
      background: none; border: 1.5px solid #534AB7; color: #534AB7;
      padding: 6px 14px; border-radius: 8px; cursor: pointer;
      font-weight: 600; font-size: 0.85rem; transition: background 0.15s;
    }
    .like-btn:hover { background: #534AB7; color: white; }
    .like-count { font-size: 0.9rem; color: #666; }
    .post-time { font-size: 0.8rem; color: #999; margin-left: auto; }

    #message {
      text-align: center; padding: 10px; margin-bottom: 16px;
      border-radius: 8px; display: none;
    }
  </style>
</head>
<body>

<nav>
  <div class="nav-logo">📸 Feed</div>
  <div class="nav-links">
    <span>Sveiks, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
    <a href="profile.php">👤 Profils</a>
    <a href="logout.php">Iziet</a>
  </div>
</nav>

<div class="container">
  <div class="upload-form">
    <h3>Pievienot postu</h3>
    <div id="message"></div>
    <input type="file" id="imageInput" accept="image/*">
    <input type="text" id="captionInput" placeholder="Apraksts (neobligāts)">
    <button onclick="uploadPost()">Publicēt</button>
  </div>
  <div id="feed"></div>
</div>

<script>
async function loadPosts() {
  const res = await fetch('get_posts.php');
  if (!res.ok) return;
  const posts = await res.json();
  const feed = document.getElementById('feed');
  feed.innerHTML = '';

  if (posts.length === 0) {
    feed.innerHTML = '<p style="text-align:center;color:#999;">Vēl nav postu. Pievieno pirmo!</p>';
    return;
  }

  posts.forEach(post => {
    const avatarHtml = post.avatar_url
      ? `<div class="post-header-avatar"><img src="${post.avatar_url}" alt=""></div>`
      : `<div class="post-header-avatar">${post.username.charAt(0).toUpperCase()}</div>`;

    feed.innerHTML += `
      <div class="post">
        <div class="post-header">
          ${avatarHtml}
          <a href="profile.php?user_id=${post.user_id}">@${post.username}</a>
        </div>
        <img src="${post.image_path}" alt="post">
        ${post.caption ? `<div class="post-caption">${post.caption}</div>` : ''}
        <div class="post-footer">
          <button class="like-btn" id="btn-${post.post_id}" onclick="likePost(${post.post_id}, this)">❤️ Patīk</button>
          <span class="like-count" id="likes-${post.post_id}">${post.likes} likes</span>
          <span class="post-time">${new Date(post.created_at).toLocaleDateString('lv-LV')}</span>
        </div>
      </div>
    `;
  });
}

async function likePost(postId, btn) {
  btn.disabled = true;
  const res = await fetch(`like_post.php?post_id=${postId}`);
  const data = await res.json();
  document.getElementById(`likes-${postId}`).textContent = data.likes + ' likes';
  if (data.action === 'liked') {
    btn.textContent = '💔 Noņemt';
    btn.style.background = '#534AB7';
    btn.style.color = 'white';
  } else {
    btn.textContent = '❤️ Patīk';
    btn.style.background = 'none';
    btn.style.color = '#534AB7';
  }
  btn.disabled = false;
}

async function uploadPost() {
  const file = document.getElementById('imageInput').files[0];
  const caption = document.getElementById('captionInput').value;
  const msg = document.getElementById('message');

  if (!file) {
    msg.style.display = 'block'; msg.style.background = '#fee';
    msg.style.color = 'red'; msg.textContent = 'Izvēlies attēlu!';
    return;
  }

  const formData = new FormData();
  formData.append('image', file);
  formData.append('caption', caption);

  const res = await fetch('add_post.php', { method: 'POST', body: formData });
  const text = await res.text();

  if (text === 'OK') {
    msg.style.display = 'block'; msg.style.background = '#efe';
    msg.style.color = 'green'; msg.textContent = 'Posts publicēts!';
    document.getElementById('imageInput').value = '';
    document.getElementById('captionInput').value = '';
    loadPosts();
  } else {
    msg.style.display = 'block'; msg.style.background = '#fee';
    msg.style.color = 'red'; msg.textContent = 'Kļūda: ' + text;
  }
}

loadPosts();
</script>
</body>
</html>