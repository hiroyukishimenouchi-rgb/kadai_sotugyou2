<?php
/**
 * Oneカルテ — メモAPI (api.php)
 *
 * ※ すべてのエンドポイントはログイン済みセッションが必要です。
 *
 * GET  api.php?action=load              → ログインユーザーの全メモ取得
 * POST api.php?action=save              → メモ保存（新規 or 更新）
 * POST api.php?action=delete            → メモ削除 { date: "2025-06-01" }
 */

require_once 'config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// セッション開始
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 未ログインは全エンドポイントを拒否 ──
if (empty($_SESSION['user_id'])) {
    respond(['success' => false, 'error' => 'ログインが必要です', 'redirect' => 'login.html'], 401);
}

$userId = (int) $_SESSION['user_id'];
$db     = getDB();
$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ══════════════════════════════════════════
    // 全メモ取得
    // ══════════════════════════════════════════
    case 'load':
        $stmt = $db->prepare(
            'SELECT note_date, title, raw_text, summary, terms, created_at, updated_at
             FROM notes WHERE user_id = ? ORDER BY note_date DESC'
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        // フロントエンドが期待する { "2025-06-01": { title, rawText, summary, terms } } 形式に変換
        $notes = [];
        foreach ($rows as $row) {
            $notes[$row['note_date']] = [
                'date'      => $row['note_date'],
                'title'     => $row['title'],
                'rawText'   => $row['raw_text'],
                'summary'   => json_decode($row['summary'], true) ?? [],
                'terms'     => json_decode($row['terms'],   true) ?? [],
                'createdAt' => $row['created_at'],
                'updatedAt' => $row['updated_at'],
            ];
        }
        respond(['success' => true, 'notes' => $notes]);

    // ══════════════════════════════════════════
    // メモ保存（新規 or 更新）
    // ══════════════════════════════════════════
    case 'save':
        $key  = $body['key']  ?? '';   // "2025-06-01"
        $note = $body['note'] ?? null;

        if (!$key || !$note) {
            respond(['success' => false, 'error' => 'key と note が必要です'], 400);
        }

        // INSERT or UPDATE（同じ user_id + note_date があれば上書き）
        $stmt = $db->prepare('
            INSERT INTO notes (user_id, note_date, title, raw_text, summary, terms)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                title    = VALUES(title),
                raw_text = VALUES(raw_text),
                summary  = VALUES(summary),
                terms    = VALUES(terms),
                updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            $userId,
            $key,
            $note['title']   ?? '',
            $note['rawText'] ?? '',
            json_encode($note['summary'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($note['terms']   ?? [], JSON_UNESCAPED_UNICODE),
        ]);
        respond(['success' => true]);

    // ══════════════════════════════════════════
    // メモ削除
    // ══════════════════════════════════════════
    case 'delete':
        $date = $body['date'] ?? ($_GET['date'] ?? '');
        if (!$date) respond(['success' => false, 'error' => 'date が必要です'], 400);

        $stmt = $db->prepare('DELETE FROM notes WHERE user_id = ? AND note_date = ?');
        $stmt->execute([$userId, $date]);
        respond(['success' => true]);

    default:
        respond(['success' => false, 'error' => 'Unknown action'], 404);
}