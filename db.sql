-- ══════════════════════════════════════════════
-- Oneカルテ — データベース完全セットアップSQL
-- phpMyAdminのSQLタブに貼り付けて実行
-- ※ onekarte_db を選択した状態で実行してください
-- ══════════════════════════════════════════════

-- 1. ユーザーテーブル
CREATE TABLE IF NOT EXISTS users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(50)  NOT NULL UNIQUE COMMENT 'ユーザー名',
  email       VARCHAR(100) NOT NULL UNIQUE COMMENT 'メールアドレス',
  password    VARCHAR(255) NOT NULL        COMMENT 'bcryptハッシュ',
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ユーザー管理';

-- 2. メモテーブル
CREATE TABLE IF NOT EXISTS notes (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT          NOT NULL              COMMENT 'users.idの外部キー',
  note_date   DATE         NOT NULL              COMMENT '記録日（YYYY-MM-DD）',
  title       VARCHAR(200)                       COMMENT 'AIが生成したタイトル',
  raw_text    TEXT                               COMMENT '入力した元のテキスト',
  summary     JSON                               COMMENT '箇条書き配列 ["...","..."]',
  terms       JSON                               COMMENT '専門用語配列 [{"term":"...","simple":"..."}]',
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- 同じユーザーが同じ日に複数メモを作れないようにする制約
  UNIQUE KEY  uq_user_date (user_id, note_date),

  -- usersが削除されたらメモも自動削除
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='メモ管理';