<?php
/**
 * Oneカルテ — login.php
 * ログイン・新規登録を1ファイルで処理する
 */
require_once 'config.php';

session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// すでにログイン済みならトップへ
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error  = '';
$action = $_POST['action'] ?? '';

// ══════════════════════════════════════════
// 新規登録処理
// ══════════════════════════════════════════
if ($action === 'register') {
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  =      $_POST['password']  ?? '';
    $password2 =      $_POST['password2'] ?? '';

    if (!$username || !$email || !$password) {
        $error = 'すべての項目を入力してください';
    } elseif (mb_strlen($username) < 2 || mb_strlen($username) > 20) {
        $error = 'ユーザー名は2〜20文字にしてください';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'メールアドレスの形式が正しくありません';
    } elseif (strlen($password) < 8) {
        $error = 'パスワードは8文字以上にしてください';
    } elseif ($password !== $password2) {
        $error = 'パスワードが一致しません';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'そのユーザー名またはメールアドレスはすでに使われています';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$username, $email, $hash]);

            session_regenerate_id(true);
            $_SESSION['user_id']  = $db->lastInsertId();
            $_SESSION['username'] = $username;
            header('Location: index.php');
            exit;
        }
    }
}

// ══════════════════════════════════════════
// ログイン処理
// ══════════════════════════════════════════
if ($action === 'login') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'メールアドレスとパスワードを入力してください';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, username, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'メールアドレスまたはパスワードが違います';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit;
        }
    }
}

// フォームで最後に選んでいたタブを引き継ぐ
$activeTab = ($_POST['action'] === 'register') ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Oneカルテ — ログイン</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    .auth-bg {
      min-height: 100vh;
      background: linear-gradient(145deg, #4c1d95 0%, #7c3aed 50%, #a78bfa 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .auth-logo { text-align: center; margin-bottom: 32px; }
    .auth-logo .icon { font-size: 3.2rem; }
    .auth-logo h1 { color: #fff; font-size: 2rem; font-weight: 800; margin-top: 8px; letter-spacing: .05em; }
    .auth-logo p  { color: rgba(255,255,255,.75); font-size: .85rem; margin-top: 4px; }

    .auth-card {
      background: #fff;
      border-radius: 24px;
      padding: 32px 28px 28px;
      width: 100%; max-width: 400px;
      box-shadow: 0 20px 60px rgba(76,29,149,.35);
    }

    .auth-tabs { display: flex; border-bottom: 2px solid #ede9fe; margin-bottom: 28px; }
    .auth-tab {
      flex: 1; padding: 10px 0; background: transparent; border: none;
      font-size: .95rem; font-weight: 700; color: #9ca3af; cursor: pointer;
      position: relative; transition: color .2s; font-family: inherit;
    }
    .auth-tab.active { color: #7c3aed; }
    .auth-tab.active::after {
      content: ''; position: absolute; bottom: -2px; left: 10%; right: 10%;
      height: 3px; background: linear-gradient(90deg,#7c3aed,#a78bfa); border-radius: 2px;
    }

    /* タブで表示切り替え */
    .auth-form           { display: none; flex-direction: column; gap: 16px; }
    .auth-form.active    { display: flex; }

    .form-group label { display: block; font-size: .8rem; font-weight: 700; color: #6b7280; margin-bottom: 6px; letter-spacing: .05em; }
    .form-group input {
      width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb;
      border-radius: 12px; font-size: .95rem; font-family: inherit;
      outline: none; transition: border-color .2s; box-sizing: border-box;
      color: #1e1b4b; background: #faf9ff;
    }
    .form-group input:focus { border-color: #7c3aed; background: #fff; }

    .auth-btn {
      width: 100%; padding: 14px; border: none; border-radius: 12px;
      background: linear-gradient(135deg, #5b21b6, #a78bfa);
      color: #fff; font-size: 1rem; font-weight: 800; cursor: pointer;
      box-shadow: 0 4px 16px rgba(124,58,237,.35); transition: all .2s;
      font-family: inherit; letter-spacing: .03em;
    }
    .auth-btn:hover  { transform: translateY(-1px); }
    .auth-btn:active { transform: scale(.98); }

    .auth-error {
      background: #fef2f2; border: 1.5px solid #fca5a5; color: #dc2626;
      border-radius: 10px; padding: 10px 14px; font-size: .85rem;
      font-weight: 600; margin-bottom: 16px;
    }
    .auth-note { text-align: center; font-size: .78rem; color: #9ca3af; margin-top: 4px; }
  </style>
</head>
<body style="margin:0; padding-bottom:0;">

<div class="auth-bg">

  <div class="auth-logo">
    <div class="icon">🏥</div>
    <h1>Oneカルテ</h1>
    <p>難しい説明をわかりやすく整理します</p>
  </div>

  <div class="auth-card">

    <!-- タブ -->
    <div class="auth-tabs">
      <button class="auth-tab <?= $activeTab === 'login'    ? 'active' : '' ?>" onclick="switchTab('login')">ログイン</button>
      <button class="auth-tab <?= $activeTab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">新規登録</button>
    </div>

    <!-- PHPで生成したエラー表示 -->
    <?php if ($error): ?>
      <div class="auth-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ログインフォーム -->
    <form method="POST" action="login.php"
          class="auth-form <?= $activeTab === 'login' ? 'active' : '' ?>"
          id="loginForm">
      <input type="hidden" name="action" value="login" />
      <div class="form-group">
        <label>メールアドレス</label>
        <input type="email" name="email" placeholder="example@mail.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required autocomplete="email" />
      </div>
      <div class="form-group">
        <label>パスワード</label>
        <input type="password" name="password" placeholder="8文字以上" required autocomplete="current-password" />
      </div>
      <button type="submit" class="auth-btn">ログイン</button>
      <p class="auth-note">※ 情報は暗号化して保護されます</p>
    </form>

    <!-- 新規登録フォーム -->
    <form method="POST" action="login.php"
          class="auth-form <?= $activeTab === 'register' ? 'active' : '' ?>"
          id="registerForm">
      <input type="hidden" name="action" value="register" />
      <div class="form-group">
        <label>ユーザー名</label>
        <input type="text" name="username" placeholder="2〜20文字"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               required autocomplete="username" />
      </div>
      <div class="form-group">
        <label>メールアドレス</label>
        <input type="email" name="email" placeholder="example@mail.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required autocomplete="email" />
      </div>
      <div class="form-group">
        <label>パスワード</label>
        <input type="password" name="password" placeholder="8文字以上" required autocomplete="new-password" />
      </div>
      <div class="form-group">
        <label>パスワード（確認）</label>
        <input type="password" name="password2" placeholder="もう一度入力" required autocomplete="new-password" />
      </div>
      <button type="submit" class="auth-btn">アカウントを作成</button>
    </form>

  </div>
</div>

<script>
  function switchTab(tab) {
    document.querySelectorAll('.auth-tab').forEach((el, i) =>
      el.classList.toggle('active', (i===0 && tab==='login') || (i===1 && tab==='register'))
    );
    document.getElementById('loginForm').classList.toggle('active', tab === 'login');
    document.getElementById('registerForm').classList.toggle('active', tab === 'register');
  }
</script>

</body>
</html>