CREATE DATABASE IF NOT EXISTS studyvault
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE studyvault;

CREATE TABLE users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name       VARCHAR(100) NOT NULL,
  email           VARCHAR(150) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  roll_no         VARCHAR(30)  NULL UNIQUE,
  role            ENUM('user','student','teacher','admin') NOT NULL DEFAULT 'user',
  requested_role  ENUM('student','teacher') NOT NULL DEFAULT 'student',
  account_status  ENUM('pending','approved','rejected','banned') NOT NULL DEFAULT 'pending',
  reviewed_by     INT UNSIGNED NULL,
  reviewed_at     DATETIME     NULL,
  reject_reason   VARCHAR(255) NULL,
  strike_count    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_queue (account_status, created_at),
  CONSTRAINT fk_users_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,

  CONSTRAINT chk_users_role_status
    CHECK (role = 'user' OR account_status IN ('approved','banned'))
) ENGINE=InnoDB;

CREATE TABLE courses (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(20)  NOT NULL UNIQUE,
  name        VARCHAR(150) NOT NULL,
  department  VARCHAR(100) NULL
) ENGINE=InnoDB;

CREATE TABLE resources (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL,
  course_id       INT UNSIGNED NOT NULL,
  title           VARCHAR(200) NOT NULL,
  description     TEXT NULL,
  semester        TINYINT UNSIGNED NOT NULL,
  instructor_name VARCHAR(100) NULL,

  file_type       ENUM('pdf','doc','excel','ppt','image') NOT NULL,
  file_ext        VARCHAR(10)  NOT NULL,
  file_size       INT UNSIGNED NOT NULL,
  file_hash       CHAR(64)     NOT NULL UNIQUE,
  file_path       VARCHAR(255) NOT NULL,

  status          ENUM('published','under_review','removed') NOT NULL DEFAULT 'published',

  avg_rating      DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  rating_count    INT UNSIGNED NOT NULL DEFAULT 0,
  download_count  INT UNSIGNED NOT NULL DEFAULT 0,
  comment_count   INT UNSIGNED NOT NULL DEFAULT 0,
  report_count    INT UNSIGNED NOT NULL DEFAULT 0,

  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_res_course    (course_id),
  INDEX idx_res_semester  (semester),
  INDEX idx_res_type      (file_type),
  INDEX idx_res_latest    (status, created_at),
  INDEX idx_res_trending  (status, download_count),
  INDEX idx_res_uploader  (user_id, created_at),
  FULLTEXT idx_res_search (title, description),

  CONSTRAINT fk_res_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  CONSTRAINT fk_res_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
  CONSTRAINT chk_res_sem   CHECK (semester BETWEEN 1 AND 8)
) ENGINE=InnoDB;

CREATE TABLE tags (
  id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE resource_tags (
  resource_id  INT UNSIGNED NOT NULL,
  tag_id       INT UNSIGNED NOT NULL,
  PRIMARY KEY (resource_id, tag_id),
  CONSTRAINT fk_rt_res FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
  CONSTRAINT fk_rt_tag FOREIGN KEY (tag_id)      REFERENCES tags(id)      ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ratings (
  resource_id  INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NOT NULL,
  score        TINYINT UNSIGNED NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (resource_id, user_id),
  CONSTRAINT fk_rate_res  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
  CONSTRAINT fk_rate_user FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT chk_score    CHECK (score BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE bookmarks (
  user_id      INT UNSIGNED NOT NULL,
  resource_id  INT UNSIGNED NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, resource_id),
  CONSTRAINT fk_bm_user FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT fk_bm_res  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comments (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resource_id  INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NOT NULL,
  body         VARCHAR(1000) NOT NULL,
  is_hidden    TINYINT(1) NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_comments_res (resource_id, created_at),
  CONSTRAINT fk_com_res  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
  CONSTRAINT fk_com_user FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE downloads (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NOT NULL,
  resource_id    INT UNSIGNED NOT NULL,
  downloaded_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_dl_user (user_id, downloaded_at),
  INDEX idx_dl_res  (resource_id),
  CONSTRAINT fk_dl_user FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT fk_dl_res  FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reports (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resource_id  INT UNSIGNED NULL,
  comment_id   INT UNSIGNED NULL,
  reporter_id  INT UNSIGNED NOT NULL,
  reason       ENUM('inappropriate','copyright','wrong_content','duplicate','spam','other') NOT NULL,
  details      VARCHAR(255) NULL,
  status       ENUM('open','upheld','dismissed') NOT NULL DEFAULT 'open',
  reviewed_by  INT UNSIGNED NULL,
  reviewed_at  DATETIME NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_report_resource (resource_id, reporter_id),
  UNIQUE KEY uq_report_comment  (comment_id,  reporter_id),
  INDEX idx_reports_queue (status, created_at),
  CONSTRAINT fk_rep_res      FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
  CONSTRAINT fk_rep_com      FOREIGN KEY (comment_id)  REFERENCES comments(id)  ON DELETE CASCADE,
  CONSTRAINT fk_rep_reporter FOREIGN KEY (reporter_id) REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT fk_rep_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)     ON DELETE SET NULL,
  CONSTRAINT chk_rep_target  CHECK ((resource_id IS NULL) <> (comment_id IS NULL))
) ENGINE=InnoDB;
