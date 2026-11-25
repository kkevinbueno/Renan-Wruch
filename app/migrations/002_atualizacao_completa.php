<?php
/**
 * Atualização completa (chat, biblioteca de exercícios, alimentação do treinador).
 * Só ACRESCENTA colunas, tabelas e índices: nenhum dado existente é alterado ou apagado.
 * Compatível com MySQL 5.7+/MariaDB 10.3+ e SQLite 3.
 */
return [

// ───────── Chat: exclusão "para todos" e metadados do áudio (duração e forma de onda) ─────────
"ALTER TABLE messages ADD COLUMN deleted_at DATETIME NULL",
"ALTER TABLE messages ADD COLUMN deleted_by INT UNSIGNED NULL",
"ALTER TABLE messages ADD COLUMN audio_meta TEXT NULL",
"CREATE INDEX ix_messages_student_deleted ON messages (student_id, deleted_at)",

// Exclusão "apagar para mim": cada usuário oculta a mensagem só para si.
"CREATE TABLE message_hides (
  id {PK},
  message_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE (message_id, user_id),
  FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) {ENGINE}",

// ───────── Biblioteca de exercícios ─────────
"CREATE TABLE muscle_groups (
  id {PK},
  slug VARCHAR(40) NOT NULL,
  name VARCHAR(80) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE (slug)
) {ENGINE}",

"CREATE TABLE equipment (
  id {PK},
  slug VARCHAR(40) NOT NULL,
  name VARCHAR(80) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE (slug)
) {ENGINE}",

"ALTER TABLE exercises ADD COLUMN slug VARCHAR(120) NULL",
"ALTER TABLE exercises ADD COLUMN name_en VARCHAR(150) NULL",
"ALTER TABLE exercises ADD COLUMN primary_muscle VARCHAR(40) NULL",
"ALTER TABLE exercises ADD COLUMN secondary_muscles VARCHAR(255) NULL",
"ALTER TABLE exercises ADD COLUMN equipment VARCHAR(40) NULL",
"ALTER TABLE exercises ADD COLUMN difficulty VARCHAR(20) NULL",
"ALTER TABLE exercises ADD COLUMN mistakes TEXT NULL",
"ALTER TABLE exercises ADD COLUMN image_file_id INT UNSIGNED NULL",
"ALTER TABLE exercises ADD COLUMN video_status VARCHAR(20) NOT NULL DEFAULT 'none'",
"ALTER TABLE exercises ADD COLUMN source VARCHAR(255) NULL",
"ALTER TABLE exercises ADD COLUMN license VARCHAR(255) NULL",
"ALTER TABLE exercises ADD COLUMN is_custom TINYINT NOT NULL DEFAULT 1",
"ALTER TABLE exercises ADD COLUMN created_by INT UNSIGNED NULL",
"CREATE UNIQUE INDEX ux_exercises_slug ON exercises (slug)",
"CREATE INDEX ix_exercises_name ON exercises (name)",
"CREATE INDEX ix_exercises_filters ON exercises (modality_id, primary_muscle, equipment)",

"CREATE TABLE exercise_favorites (
  id {PK},
  user_id INT UNSIGNED NOT NULL,
  exercise_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE (user_id, exercise_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
) {ENGINE}",

// ───────── Alimentação: observações do Renan sobre refeições e hidratação ─────────
"CREATE TABLE nutrition_notes (
  id {PK},
  student_id INT UNSIGNED NOT NULL,
  author_id INT UNSIGNED NULL,
  topic VARCHAR(20) NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) {ENGINE}",
"CREATE INDEX ix_nutrition_logs_student_date ON nutrition_logs (student_id, ref_date)",

];
