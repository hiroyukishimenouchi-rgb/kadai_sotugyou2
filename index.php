<?php
/**
 * Oneカルテ — index.php
 * セッション確認をPHP側でやるのでJSの確認が不要になる
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

// 未ログインならlogin.phpへ飛ばす
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Oneカルテ — 難しい説明をわかりやすく</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    .header-inner {
      display: flex; align-items: center; justify-content: space-between;
    }
    .header-username {
      color: rgba(255,255,255,.9); font-size: .82rem; font-weight: 700;
    }
    .logout-btn {
      background: rgba(255,255,255,.18); border: 1.5px solid rgba(255,255,255,.4);
      color: #fff; border-radius: 20px; padding: 5px 12px;
      font-size: .75rem; font-weight: 700; cursor: pointer;
      font-family: inherit; transition: background .2s;
    }
    .logout-btn:hover { background: rgba(255,255,255,.3); }
  </style>
</head>
<body>

  <!-- ヘッダー（PHPでユーザー名を埋め込む） -->
  <header class="app-header">
    <div class="header-inner">
      <div>
        <h1>🏥 Oneカルテ</h1>
        <p>難しい説明をわかりやすく整理します</p>
      </div>
      <div style="display:flex; align-items:center; gap:10px;">
        <span class="header-username"><?= $username ?> さん</span>
        <a href="logout.php"><button class="logout-btn">ログアウト</button></a>
      </div>
    </div>
  </header>

  <!-- メインコンテンツ（app.jsで差し替え） -->
  <main id="mainContent"></main>

  <!-- ボトムナビ -->
  <nav class="nav-bar">
    <button class="nav-btn active" data-view="home">
      <span class="icon">🏠</span><span>ホーム</span>
    </button>
    <button class="nav-btn" data-view="detail">
      <span class="icon">📋</span><span>メモ</span>
    </button>
    <button class="nav-btn" data-view="calendar">
      <span class="icon">📅</span><span>カレンダー</span>
    </button>
  </nav>

  <!-- 専門用語解説モーダル -->
  <div id="termModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <div id="termModalTitle" class="modal-term-title"></div>
        <button id="termModalClose" class="modal-close">✕</button>
      </div>
      <div id="termModalBody" class="modal-body"></div>
    </div>
  </div>

  <!-- 通知 -->
  <div id="notif" class="notif" style="display:none;"></div>

  <script src="app.js"></script>

</body>
</html>