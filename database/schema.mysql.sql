-- Wruch Personal — esquema MySQL gerado a partir de app/migrations
-- Importe pelo phpMyAdmin ou rode: php cron/migrate.php

CREATE TABLE IF NOT EXISTS migrations (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL, applied_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NULL,
  name VARCHAR(120) NOT NULL,
  avatar_file_id INT UNSIGNED NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'student',
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  email_verified_at DATETIME NULL,
  google_sub VARCHAR(190) NULL,
  apple_sub VARCHAR(190) NULL,
  consent_at DATETIME NULL,
  consent_version VARCHAR(20) NULL,
  approved_at DATETIME NULL,
  decision_note TEXT NULL,
  last_login_at DATETIME NULL,
  last_activity_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE (email),
  UNIQUE (google_sub),
  UNIQUE (apple_sub)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_users_role_status ON users (role, status);

CREATE TABLE modalities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(40) NOT NULL,
  name VARCHAR(80) NOT NULL,
  fields TEXT NULL,
  active TINYINT NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_profiles (
  user_id INT UNSIGNED NOT NULL PRIMARY KEY,
  phone VARCHAR(30) NULL,
  birth_date DATE NULL,
  modality_id INT UNSIGNED NULL,
  cycle INT NOT NULL DEFAULT 1,
  cycle_started_on DATE NULL,
  onboarding_completed_at DATETIME NULL,
  self_eval_every_days INT NOT NULL DEFAULT 7,
  self_eval_categories TEXT NULL,
  self_eval_reminder_time VARCHAR(5) NULL,
  assessment_every_days INT NOT NULL DEFAULT 30,
  next_assessment_on DATE NULL,
  assessment_remind_days_before INT NOT NULL DEFAULT 3,
  assessment_reminders_count INT NOT NULL DEFAULT 2,
  assessment_overdue_alert TINYINT NOT NULL DEFAULT 1,
  access_override VARCHAR(20) NULL,
  trainer_notes TEXT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (modality_id) REFERENCES modalities(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_cycles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  number INT NOT NULL,
  started_on DATE NOT NULL,
  ended_on DATE NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE files (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  uploaded_by INT UNSIGNED NULL,
  student_id INT UNSIGNED NULL,
  context VARCHAR(30) NOT NULL,
  context_id INT UNSIGNED NULL,
  kind VARCHAR(20) NOT NULL,
  storage_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime VARCHAR(100) NOT NULL,
  size_bytes BIGINT NOT NULL,
  sensitive TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_files_context ON files (context, context_id);

CREATE INDEX idx_files_student ON files (student_id);

CREATE TABLE anamneses (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  cycle INT NOT NULL DEFAULT 1,
  data MEDIUMTEXT NULL,
  personal_notes TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  submitted_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_anamneses_student ON anamneses (student_id, cycle);

CREATE TABLE exercises (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  modality_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(80) NULL,
  description TEXT NULL,
  instructions TEXT NULL,
  video_url VARCHAR(500) NULL,
  video_file_id INT UNSIGNED NULL,
  notes TEXT NULL,
  fields TEXT NULL,
  archived_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (modality_id) REFERENCES modalities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_exercises_modality ON exercises (modality_id, category);

CREATE TABLE workouts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NULL,
  is_template TINYINT NOT NULL DEFAULT 0,
  label VARCHAR(40) NULL,
  name VARCHAR(150) NOT NULL,
  modality_id INT UNSIGNED NOT NULL,
  weekdays VARCHAR(20) NULL,
  starts_on DATE NULL,
  ends_on DATE NULL,
  sort_order INT NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (modality_id) REFERENCES modalities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_workouts_student ON workouts (student_id, is_template);

CREATE TABLE workout_versions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  workout_id INT UNSIGNED NOT NULL,
  version_no INT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  publish_at DATETIME NULL,
  published_at DATETIME NULL,
  general_notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_versions_workout ON workout_versions (workout_id, status);

CREATE INDEX idx_versions_publish ON workout_versions (status, publish_at);

CREATE TABLE workout_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  version_id INT UNSIGNED NOT NULL,
  position INT NOT NULL DEFAULT 0,
  exercise_id INT UNSIGNED NULL,
  name VARCHAR(150) NOT NULL,
  instructions TEXT NULL,
  notes TEXT NULL,
  video_url VARCHAR(500) NULL,
  video_file_id INT UNSIGNED NULL,
  params TEXT NULL,
  fields TEXT NULL,
  FOREIGN KEY (version_id) REFERENCES workout_versions(id) ON DELETE CASCADE,
  FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_items_version ON workout_items (version_id, position);

CREATE TABLE reschedule_requests (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  workout_id INT UNSIGNED NOT NULL,
  original_date DATE NOT NULL,
  requested_date DATE NOT NULL,
  note TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  decided_by INT UNSIGNED NULL,
  decided_at DATETIME NULL,
  decision_note TEXT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_reschedule_status ON reschedule_requests (status, student_id);

CREATE TABLE schedule_overrides (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  workout_id INT UNSIGNED NOT NULL,
  original_date DATE NOT NULL,
  new_date DATE NOT NULL,
  request_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_overrides_student ON schedule_overrides (student_id, original_date, new_date);

CREATE TABLE workout_sessions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  workout_id INT UNSIGNED NOT NULL,
  version_id INT UNSIGNED NOT NULL,
  scheduled_date DATE NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'in_progress',
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE (student_id, workout_id, scheduled_date),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE,
  FOREIGN KEY (version_id) REFERENCES workout_versions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_sessions_date ON workout_sessions (student_id, scheduled_date);

CREATE TABLE session_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  session_id INT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  data TEXT NULL,
  completed TINYINT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  UNIQUE (session_id, item_id),
  FOREIGN KEY (session_id) REFERENCES workout_sessions(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES workout_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE self_assessments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  ref_date DATE NOT NULL,
  scores TEXT NOT NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE (student_id, ref_date),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nutrition_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  ref_date DATE NOT NULL,
  meal VARCHAR(40) NULL,
  description TEXT NULL,
  water_ml INT NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_nutrition_student ON nutrition_logs (student_id, ref_date);

CREATE TABLE physical_assessments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  cycle INT NOT NULL DEFAULT 1,
  due_on DATE NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  measurements TEXT NULL,
  student_notes TEXT NULL,
  trainer_comments TEXT NULL,
  trainer_guidance TEXT NULL,
  submitted_at DATETIME NULL,
  reviewed_at DATETIME NULL,
  reviewed_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_assessments_status ON physical_assessments (status, student_id);

CREATE TABLE calendar_events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  type VARCHAR(20) NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  event_date DATE NOT NULL,
  start_time VARCHAR(5) NULL,
  end_time VARCHAR(5) NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_events_student_date ON calendar_events (student_id, event_date);

CREATE TABLE messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  sender_id INT UNSIGNED NULL,
  body TEXT NULL,
  file_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  read_at DATETIME NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_messages_thread ON messages (student_id, id);

CREATE TABLE email_queue (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  to_email VARCHAR(190) NOT NULL,
  template VARCHAR(60) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  html_body MEDIUMTEXT NOT NULL,
  text_body MEDIUMTEXT NOT NULL,
  dedupe_key VARCHAR(190) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'queued',
  attempts INT NOT NULL DEFAULT 0,
  last_error TEXT NULL,
  send_after DATETIME NOT NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  UNIQUE (dedupe_key),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_email_queue_status ON email_queue (status, send_after);

CREATE TABLE notification_prefs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  pref_key VARCHAR(60) NOT NULL,
  enabled TINYINT NOT NULL DEFAULT 1,
  UNIQUE (user_id, pref_key),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plans (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  price_cents INT NOT NULL,
  period_months INT NOT NULL DEFAULT 1,
  allow_recurring TINYINT NOT NULL DEFAULT 1,
  allow_one_time TINYINT NOT NULL DEFAULT 1,
  active TINYINT NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscriptions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NOT NULL,
  mode VARCHAR(20) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  provider_ref VARCHAR(100) NULL,
  external_reference VARCHAR(64) NOT NULL,
  checkout_url VARCHAR(500) NULL,
  amount_cents INT NOT NULL,
  current_period_end DATE NULL,
  next_charge_on DATE NULL,
  cancel_requested_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE (external_reference),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_subscriptions_student ON subscriptions (student_id, status);

CREATE TABLE payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  subscription_id INT UNSIGNED NULL,
  student_id INT UNSIGNED NOT NULL,
  provider_payment_id VARCHAR(100) NOT NULL,
  amount_cents INT NOT NULL,
  status VARCHAR(30) NOT NULL,
  status_detail VARCHAR(100) NULL,
  method VARCHAR(40) NULL,
  paid_at DATETIME NULL,
  raw MEDIUMTEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE (provider_payment_id),
  FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE webhook_events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(30) NOT NULL,
  event_key VARCHAR(190) NOT NULL,
  topic VARCHAR(60) NULL,
  resource_id VARCHAR(100) NULL,
  payload MEDIUMTEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'received',
  error TEXT NULL,
  attempts INT NOT NULL DEFAULT 0,
  received_at DATETIME NOT NULL,
  processed_at DATETIME NULL,
  UNIQUE (event_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  actor_id INT UNSIGNED NULL,
  acting_as VARCHAR(20) NULL,
  action VARCHAR(80) NOT NULL,
  entity VARCHAR(40) NULL,
  entity_id INT UNSIGNED NULL,
  meta TEXT NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_audit_created ON audit_logs (created_at);

CREATE INDEX idx_audit_actor ON audit_logs (actor_id);

CREATE TABLE settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value MEDIUMTEXT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tokens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  purpose VARCHAR(30) NOT NULL,
  token_hash VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  UNIQUE (token_hash),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
  bucket VARCHAR(190) NOT NULL PRIMARY KEY,
  hits INT NOT NULL DEFAULT 0,
  window_start INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE data_requests (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  request_type VARCHAR(20) NOT NULL,
  details TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  response TEXT NULL,
  created_at DATETIME NOT NULL,
  resolved_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (name, applied_at) VALUES ('001_initial', UTC_TIMESTAMP());

INSERT INTO modalities (slug, name, fields, active, sort_order) VALUES ('tenis', 'Tênis de quadra', '["sets","reps","duration","intensity"]', 1, 0);
INSERT INTO modalities (slug, name, fields, active, sort_order) VALUES ('beach-tennis', 'Beach tennis', '["sets","reps","duration","intensity"]', 1, 1);
INSERT INTO modalities (slug, name, fields, active, sort_order) VALUES ('volei-de-areia', 'Vôlei de areia', '["sets","reps","duration","intensity"]', 1, 2);
INSERT INTO modalities (slug, name, fields, active, sort_order) VALUES ('natacao', 'Natação', '["sets","distance","time","pace","intervals"]', 1, 3);
INSERT INTO modalities (slug, name, fields, active, sort_order) VALUES ('musculacao', 'Musculação', '["sets","reps","load","rest","intensity"]', 1, 4);
INSERT INTO modalities (slug, name, fields, active, sort_order) VALUES ('condicionamento', 'Condicionamento físico', '["sets","reps","duration","distance","intensity","rest"]', 1, 5);
