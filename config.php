<?php
/**
 * Oneカルテ — データベース設定
 *
 * XAMPPローカル環境とさくらサーバー、どちらかをコメントアウトして使ってください。
 */

// ─────────────────────────────────────────
// ★ XAMPPローカル環境用（開発時）
// ─────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'onekarte_db');
define('DB_USER', 'root');       // XAMPPデフォルト
define('DB_PASS', '');           // XAMPPデフォルトはパスワードなし

// ─────────────────────────────────────────
// ★ さくらサーバー用（本番時はこちらに書き換え）
// ─────────────────────────────────────────
// define('DB_HOST', 'mysql**.sakura.ne.jp');  // さくらのDBホスト名
// define('DB_NAME', 'アカウント名_onekarte');  // さくらのDB名
// define('DB_USER', 'アカウント名');           // さくらのDBユーザー名
// define('DB_PASS', 'パスワード');             // さくらのDBパスワード

// セッション設定
define('SESSION_LIFETIME', 60 * 60 * 24 * 30); // 30日間

// ─────────────────────────────────────────
// DB接続（PDO）
// ─────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB接続エラー']);
        exit;
    }
    return $pdo;
}