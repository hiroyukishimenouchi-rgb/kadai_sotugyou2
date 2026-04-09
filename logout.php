<?php
/**
 * Oneカルテ — logout.php
 * ログアウト処理してlogin.phpへリダイレクト
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

// セッションを完全に破棄
$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;