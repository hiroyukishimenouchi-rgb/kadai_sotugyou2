<?php
require_once 'config.php';

$db = getDB();

// 既存のテストユーザーを削除
$db->exec("DELETE FROM users WHERE email = 'test@test.com'");

// PHPで正しくハッシュ化して登録
$hash = password_hash('test1234', PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
$stmt->execute(['テストユーザー', 'test@test.com', $hash]);

echo 'OK! ユーザーを作成しました。<br>';
echo 'メール: test@test.com<br>';
echo 'パスワード: test1234';
?>