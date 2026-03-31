<?php
// git commit: "feat: add profile.php — view and edit user profile with avatar"

session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$profile_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $current_user_id;
$is_own_profile = ($profile_id === $current_user_id);

$stmt = $conn->prepare("SELECT user_id, username, email, bio, avatar_url, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->bind_result($uid, $username, $email, $bio, $avatar_url, $created_at);
if (!$stmt->fetch()) { echo "Lietotājs nav atrasts."; exit; }
$stmt->close();

$pstmt = $conn->prepare("
    SELECT p.post_id, p.image_path, p.caption, p.created_at, COUNT(l.like_id) AS likes
    FROM posts p LEFT JOIN likes l ON p.post_id = l.post_id
    WHERE p.user_id = ? GROUP BY p.post_id ORDER BY p.created_at DESC
");
$pstmt->bind_param("i", $profile_id);
$pstmt->execute();
$result = $pstmt->get_result();
$posts = [];
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}
$pstmt->close();
$conn->close();

$post_count = count($posts);
$member_since = date("Y. gada F", strtotime($created_at));
?>
<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@<?= htmlspecialchars($username) ?> — Profils</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,500;9..40,700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --brand: #534AB7; --brand-dark: #3C3489; --accent: #1D9E75;
      --bg: #f2f1f7; --card: #ffffff; --text: #1a1a1a;
      --muted: #7a7a8c; --border: #e4e2f0; --avatar-size: 110px;
    }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

    nav {
      background: var(--brand); padding: 12px 28px;
      display: flex; justify-content: space-between; align-items: center;
      color: white; position: sticky; top: 0; z-index: 100;
      box-shadow: 0 2px 12px rgba(83,74,183,0.25);
    }
    .nav-logo { font-family: 'Playfair Display', serif; font-size: 1.2rem; }
    .nav-links { display: flex; gap: 10px; align-items: center; }
    .nav-links a {
      color: white; text-decoration: none; font-size: 0.85rem; font-weight: 500;
      background: rgba(255,255,255,0.15); padding: 6px 14px; border-radius: 20px; transition: background 0.15s;
    }
    .nav-links a:hover, .nav-links a.active { background: rgba(255,255,255,0.3); }

    .profile-wrap { max-width: 900px; margin: 36px auto; padding: 0 16px; }

    .profile-header {
      background: var(--card); border-radius: 20px; padding: 32px 36px;
      display: flex; gap: 32px; align-items: flex-start;
      box-shadow: 0 4px 20px rgba(83,74,183,0.08); margin-bottom: 28px;
      position: relative; overflow: hidden;
    }
    .profile-header::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 80px;
      background: linear-gradient(135deg, var(--brand) 0%, var(--accent) 100%);
    }

    .avatar-wrap { position: relative; flex-shrink: 0; margin-top: 20px; z-index: 2; }
    .avatar-wrap img, .avatar-placeholder {
      width: var(--avatar-size); height: var(--avatar-size);
      border-radius: 50%; object-fit: cover;
      border: 4px solid white; box-shadow: 0 4px 16px rgba(0,0,0,0.15); display: block;
    }
    .avatar-placeholder {
      background: linear-gradient(135deg, var(--brand), var(--accent));
      display: flex; align-items: center; justify-content: center;
      font-size: 2.4rem; color: white; font-family: 'Playfair Display', serif;
    }
    .avatar-edit-btn {
      position: absolute; bottom: 4px; right: 4px;
      width: 30px; height: 30px; background: var(--brand); border: 2px solid white;
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
      cursor: pointer; font-size: 0.75rem; color: white; transition: background 0.15s;
    }
    .avatar-edit-btn:hover { background: var(--brand-dark); }

    .profile-info { flex: 1; padding-top: 60px; z-index: 2; position: relative; }
    .profile-username { font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 4px; }
    .profile-email { font-size: 0.85rem; color: var(--muted); margin-bottom: 12px; }
    .profile-bio { font-size: 0.95rem; color: #444; line-height: 1.55; margin-bottom: 16px; font-style: italic; }
    .profile-bio.empty { color: var(--muted); font-style: normal; }
    .profile-stats { display: flex; gap: 24px; margin-bottom: 12px; }
    .stat-num { font-size: 1.3rem; font-weight: 700; color: var(--brand); }
    .stat-lbl { font-size: 0.75rem; color: var(--muted); }
    .profile-meta { font-size: 0.8rem; color: var(--muted); margin-bottom: 16px; }
    .profile-actions { display: flex; gap: 10px; }

    .btn-primary {
      background: var(--brand); color: white; border: none; border-radius: 20px;
      padding: 8px 20px; font-size: 0.88rem; font-weight: 600;
      cursor: pointer; font-family: 'DM Sans', sans-serif; transition: background 0.15s;
    }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline {
      background: none; color: var(--brand); border: 1.5px solid var(--brand); border-radius: 20px;
      padding: 8px 20px; font-size: 0.88rem; font-weight: 600;
      cursor: pointer; font-family: 'DM Sans', sans-serif; transition: all 0.15s;
    }
    .btn-outline:hover { background: var(--brand); color: white; }

    .posts-section h2 { font-family: 'Playfair Display', serif; font-size: 1.2rem; margin-bottom: 16px; }
    .posts-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3px; border-radius: 16px; overflow: hidden; }
    @media (max-width: 600px) {
      .posts-grid { grid-template-columns: repeat(2, 1fr); }
      .profile-header { flex-direction: column; padding: 20px; }
      .profile-info { padding-top: 10px; }
    }
    .post-thumb { position: relative; aspect-ratio: 1; overflow: hidden; background: var(--border); cursor: pointer; }
    .post-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
    .post-thumb:hover img { transform: scale(1.06); }
    .post-thumb-overlay {
      position: absolute; inset: 0; background: rgba(83,74,183,0.6);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; transition: opacity 0.2s; color: white; font-size: 1rem; font-weight: 700;
    }
    .post-thumb:hover .post-thumb-overlay { opacity: 1; }
    .no-posts { grid-column: 1/-1; text-align: center; padding: 60px 20px; color: var(--muted); }
    .no-posts span { font-size: 2.5rem; display: block; margin-bottom: 12px; }

    /* MODAL */
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
      z-index: 200; align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal {
      background: var(--card); border-radius: 20px; padding: 32px;
      width: 100%; max-width: 420px; margin: 16px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    .modal h3 { font-family: 'Playfair Display', serif; font-size: 1.3rem; margin-bottom: 20px; }
    .modal label { display: block; font-size: 0.82rem; font-weight: 600; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
    .modal textarea {
      width: 100%; padding: 10px 14px; border: 1.5px solid var(--border);
      border-radius: 10px; font-size: 0.9rem; font-family: 'DM Sans', sans-serif;
      background: #fafaf9; margin-bottom: 16px; resize: vertical; min-height: 80px;
    }
    .modal textarea:focus { outline: none; border-color: var(--brand); }
    .modal-actions { display: flex; gap: 10px; }
    .modal-actions .btn-primary, .modal-actions .btn-outline { flex: 1; text-align: center; }
    #editMsg { font-size: 0.85rem; text-align: center; padding: 8px; border-radius: 8px; margin-bottom: 12px; display: none; }

    /* LIGHTBOX */
    .lightbox-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.8); backdrop-filter: blur(6px);
      z-index: 300; align-items: center; justify-content: center;
    }
    .lightbox-overlay.open { display: flex; }
    .lightbox-content {
      max-width: 800px; width: 100%; margin: 16px;
      background: var(--card); border-radius: 20px; overflow: hidden;
      display: flex; box-shadow: 0 30px 80px rgba(0,0,0,0.3);
    }
    @media (max-width: 600px) { .lightbox-content { flex-direction: column; } }
    .lightbox-img { width: 60%; object-fit: cover; display: block; }
    @media (max-width: 600px) { .lightbox-img { width: 100%; max-height: 280px; } }
    .lightbox-info { flex: 1; padding: 28px; }
    .lightbox-user { font-weight: 700; color: var(--brand); margin-bottom: 10px; font-size: 1rem; }
    .lightbox-caption { font-size: 0.92rem; color: #444; margin-bottom: 12px; line-height: 1.6; }
    .lightbox-likes { font-size: 0.85rem; color: var(--muted); }
    .lightbox-date { font-size: 0.8rem; color: var(--muted); margin-top: 8px; }
    .lightbox-close {
      position: fixed; top: 20px; right: 24px;
      font-size: 1.8rem; color: white; cursor: pointer;
      background: none; border: none; line-height: 1; z-index: 301;
    }
    #avatarInput { display: none; }

    .upload-spinner { display: none; font-size: 0.85rem; color: var(--muted); margin-top: 8px; }
  </style>
</head>
<body>

<nav>
  <div class="nav-logo">📸 Feed</div>
  <div class="nav-links">
    <span style="color:rgba(255,255,255,0.8);font-size:0.85rem;">Sveiks, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
    <a href="index.php">Sākums</a>
    <a href="profile.php" class="active">Profils</a>
    <a href="logout.php">Iziet</a>
  </div>
</nav>

<div class="profile-wrap">

  <div class="profile-header">
    <div class="avatar-wrap">
      <?php if ($avatar_url): ?>
        <img src="<?= htmlspecialchars($avatar_url) ?>" alt="Avatar" id="avatarDisplay">
      <?php else: ?>
        <div class="avatar-placeholder" id="avatarDisplay">
          <?= strtoupper(mb_substr($username, 0, 1)) ?>
        </div>
      <?php endif; ?>
      <?php if ($is_own_profile): ?>
        <div class="avatar-edit-btn" onclick="document.getElementById('avatarInput').click()" title="Mainīt bildi">✏️</div>
        <input type="file" id="avatarInput" accept="image/*" onchange="uploadAvatar(this)">
      <?php endif; ?>
    </div>

    <div class="profile-info">
      <div class="profile-username">@<?= htmlspecialchars($username) ?></div>
      <div class="profile-email"><?= htmlspecialchars($email) ?></div>
      <div class="profile-bio <?= !$bio ? 'empty' : '' ?>" id="bioDisplay">
        <?= $bio ? htmlspecialchars($bio) : ($is_own_profile ? 'Pievieno aprakstu par sevi...' : '') ?>
      </div>
      <div class="profile-stats">
        <div class="stat">
          <div class="stat-num"><?= $post_count ?></div>
          <div class="stat-lbl">Postu</div>
        </div>
      </div>
      <div class="profile-meta">📅 Biedrs kopš <?= $member_since ?></div>
      <?php if ($is_own_profile): ?>
        <div class="profile-actions">
          <button class="btn-primary" onclick="openEditModal()">✏️ Rediģēt profilu</button>
        </div>
        <div class="upload-spinner" id="avatarSpinner">⏳ Augšupielādē...</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="posts-section">
    <h2>Postu galerija</h2>
    <div class="posts-grid">
      <?php if (empty($posts)): ?>
        <div class="no-posts">
          <span>🖼️</span>
          <?= $is_own_profile ? 'Tev vēl nav postu. <a href="index.php" style="color:var(--brand)">Publicē pirmo!</a>' : 'Šim lietotājam vēl nav postu.' ?>
        </div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <div class="post-thumb" onclick='openLightbox(<?= json_encode($post) ?>)'>
            <img src="<?= htmlspecialchars($post['image_path']) ?>" alt="">
            <div class="post-thumb-overlay">❤️ <?= (int)$post['likes'] ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php if ($is_own_profile): ?>
<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal" onclick="if(event.target===this)closeEditModal()">
  <div class="modal">
    <h3>Rediģēt profilu</h3>
    <div id="editMsg"></div>
    <label>Bio / Apraksts</label>
    <textarea id="bioInput" placeholder="Pastāsti par sevi..."><?= htmlspecialchars($bio ?? '') ?></textarea>
    <div class="modal-actions">
      <button class="btn-outline" onclick="closeEditModal()">Atcelt</button>
      <button class="btn-primary" onclick="saveProfile()">💾 Saglabāt</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- LIGHTBOX -->
<div class="lightbox-overlay" id="lightbox" onclick="if(event.target===this)closeLightbox()">
  <div class="lightbox-content">
    <img src="" alt="" class="lightbox-img" id="lightboxImg">
    <div class="lightbox-info">
      <div class="lightbox-user" id="lightboxUser"></div>
      <div class="lightbox-caption" id="lightboxCaption"></div>
      <div class="lightbox-likes" id="lightboxLikes"></div>
      <div class="lightbox-date" id="lightboxDate"></div>
    </div>
  </div>
  <button class="lightbox-close" onclick="closeLightbox()">✕</button>
</div>

<script>
function openEditModal() { document.getElementById('editModal').classList.add('open'); }
function closeEditModal() { document.getElementById('editModal').classList.remove('open'); }

async function saveProfile() {
  const bio = document.getElementById('bioInput').value;
  const msg = document.getElementById('editMsg');

  const formData = new FormData();
  formData.append('bio', bio);

  const res = await fetch('update_profile.php', { method: 'POST', body: formData });
  const data = await res.json();

  if (data.success) {
    msg.style.display = 'block';
    msg.style.background = '#efe';
    msg.style.color = 'green';
    msg.textContent = 'Profils saglabāts!';
    const bioEl = document.getElementById('bioDisplay');
    bioEl.textContent = data.bio || 'Pievieno aprakstu par sevi...';
    bioEl.className = 'profile-bio' + (data.bio ? '' : ' empty');
    setTimeout(closeEditModal, 800);
  } else {
    msg.style.display = 'block';
    msg.style.background = '#fee';
    msg.style.color = 'red';
    msg.textContent = data.error || 'Kļūda!';
  }
}

async function uploadAvatar(input) {
  if (!input.files[0]) return;
  const spinner = document.getElementById('avatarSpinner');
  spinner.style.display = 'block';

  const formData = new FormData();
  formData.append('avatar', input.files[0]);

  const res = await fetch('update_profile.php', { method: 'POST', body: formData });
  const data = await res.json();
  spinner.style.display = 'none';

  if (data.success && data.avatar_url) {
    const wrap = document.getElementById('avatarDisplay');
    const newImg = document.createElement('img');
    newImg.src = data.avatar_url + '?t=' + Date.now();
    newImg.alt = 'Avatar';
    newImg.id = 'avatarDisplay';
    newImg.style.cssText = 'width:110px;height:110px;border-radius:50%;object-fit:cover;border:4px solid white;box-shadow:0 4px 16px rgba(0,0,0,0.15);display:block;';
    wrap.parentNode.replaceChild(newImg, wrap);
  } else {
    alert(data.error || 'Augšupielāde neizdevās');
  }
}

function openLightbox(post) {
  document.getElementById('lightboxImg').src = post.image_path;
  document.getElementById('lightboxUser').textContent = '@<?= htmlspecialchars($username) ?>';
  document.getElementById('lightboxCaption').textContent = post.caption || '';
  document.getElementById('lightboxLikes').textContent = '❤️ ' + post.likes + ' likes';
  document.getElementById('lightboxDate').textContent = '📅 ' + new Date(post.created_at).toLocaleDateString('lv-LV');
  document.getElementById('lightbox').classList.add('open');
}
function closeLightbox() { document.getElementById('lightbox').classList.remove('open'); }

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeLightbox(); closeEditModal(); } });
</script>

</body>
</html>