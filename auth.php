<?php
/**
 * Oneカルテ — auth.php
 * login.phpに統合したため、このファイルはAPIログアウトのみ担当。
 * app.jsから fetch('./auth.php?action=logout') で呼ばれる。
 */

require_once 'config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// セッション開始（セキュリティ設定つき）
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),  // HTTPSなら自動でtrue
    'httponly' => true,                       // JavaScriptからアクセス不可
    'samesite' => 'Lax',
]);
session_start();

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getBody(): array {
    $body = json_decode(file_get_contents('php://input'), true);
    return is_array($body) ? $body : [];
}

$action = $_GET['action'] ?? '';

switch ($action) {

    // ══════════════════════════════════════════
    // 新規登録
    // ══════════════════════════════════════════
    case 'register':
        $body = getBody();
        $username = trim($body['username'] ?? '');
        $email    = trim($body['email']    ?? '');
        $password =      $body['password'] ?? '';

        // バリデーション
        if (!$username || !$email || !$password) {
            respond(['success' => false, 'error' => 'すべての項目を入力してください'], 400);
        }
        if (mb_strlen($username) < 2 || mb_strlen($username) > 20) {
            respond(['success' => false, 'error' => 'ユーザー名は2〜20文字にしてください'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(['success' => false, 'error' => 'メールアドレスの形式が正しくありません'], 400);
        }
        if (strlen($password) < 8) {
            respond(['success' => false, 'error' => 'パスワードは8文字以上にしてください'], 400);
        }

        $db = getDB();

        // 重複チェック
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            respond(['success' => false, 'error' => 'そのユーザー名またはメールアドレスはすでに使われています'], 409);
        }

        // パスワードをハッシュ化して保存
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $hash]);
        $userId = $db->lastInsertId();

        // セッションにユーザー情報を保存
        session_regenerate_id(true);
        $_SESSION['user_id']  = $userId;
        $_SESSION['username'] = $username;

        respond(['success' => true, 'username' => $username]);

    // ══════════════════════════════════════════
    // ログイン
    // ══════════════════════════════════════════
    case 'login':
        $body     = getBody();
        $email    = trim($body['email']    ?? '');
        $password =      $body['password'] ?? '';

        if (!$email || !$password) {
            respond(['success' => false, 'error' => 'メールアドレスとパスワードを入力してください'], 400);
        }

        $db   = getDB();
        $stmt = $db->prepare('SELECT id, username, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // password_verify でハッシュと照合
        if (!$user || !password_verify($password, $user['password'])) {
            respond(['success' => false, 'error' => 'メールアドレスまたはパスワードが違います'], 401);
        }

        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];

        respond(['success' => true, 'username' => $user['username']]);

    // ══════════════════════════════════════════
    // ログアウト
    // ══════════════════════════════════════════
    case 'logout':
        $_SESSION = [];
        session_destroy();
        respond(['success' => true]);

    // ══════════════════════════════════════════
    // セッション確認（ページ読み込み時に使用）
    // ══════════════════════════════════════════
    case 'check':
        if (!empty($_SESSION['user_id'])) {
            respond([
                'success'  => true,
                'loggedIn' => true,
                'username' => $_SESSION['username'],
                'userId'   => $_SESSION['user_id'],
            ]);
        } else {
            respond(['success' => true, 'loggedIn' => false]);
        }

    default:
        respond(['success' => false, 'error' => 'Unknown action'], 404);
}