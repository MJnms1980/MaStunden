-- MaStunden Schema 1 – Referenz für Entwickler.
-- Reguläre Installation über install.php; nicht zusätzlich manuell importieren.

CREATE TABLE `ma_settings` (
  k VARCHAR(100) PRIMARY KEY, v LONGTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_users` (
  id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(190) NOT NULL UNIQUE, name VARCHAR(190) NOT NULL, personnel VARCHAR(80) NOT NULL DEFAULT '', password VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL DEFAULT 'employee', active TINYINT NOT NULL DEFAULT 1, must_change TINYINT NOT NULL DEFAULT 0, employment_start DATE NOT NULL, employment_end DATE NULL, vacation_days DECIMAL(7,2) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_models` (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, valid_from DATE NOT NULL, minutes TEXT NOT NULL, UNIQUE KEY model_date(user_id,valid_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_entries` (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NULL, breaks_json LONGTEXT NOT NULL, note TEXT NOT NULL, updated_at DATETIME NOT NULL, INDEX user_start(user_id,start_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_absences` (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, type VARCHAR(40) NOT NULL, fraction DECIMAL(5,2) NOT NULL DEFAULT 1, amount_minutes INT NOT NULL DEFAULT 0, status VARCHAR(30) NOT NULL, note TEXT NOT NULL, response TEXT NOT NULL, created_at DATETIME NOT NULL, INDEX absence_user(user_id,start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_holidays` (
  id INT AUTO_INCREMENT PRIMARY KEY, day DATE NOT NULL UNIQUE, name VARCHAR(190) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_closures` (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, month VARCHAR(7) NOT NULL, status VARCHAR(30) NOT NULL, comment TEXT NOT NULL, updated_at DATETIME NOT NULL, snapshot LONGTEXT NULL, UNIQUE KEY user_month(user_id,month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_documents` (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, title VARCHAR(190) NOT NULL, category VARCHAR(80) NOT NULL, absence_id INT NULL, original_name VARCHAR(190) NOT NULL, storage_name VARCHAR(90) NOT NULL, mime VARCHAR(90) NOT NULL, size INT NOT NULL, status VARCHAR(30) NOT NULL, note TEXT NOT NULL, response TEXT NOT NULL, created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_adjustments` (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, day DATE NOT NULL, minutes INT NOT NULL DEFAULT 0, vacation DECIMAL(7,2) NOT NULL DEFAULT 0, reason TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_notifications` (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, message TEXT NOT NULL, seen TINYINT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_audit` (
  id INT AUTO_INCREMENT PRIMARY KEY, actor_id INT NULL, action VARCHAR(80) NOT NULL, entity VARCHAR(80) NOT NULL, entity_id INT NULL, before_json LONGTEXT NULL, after_json LONGTEXT NULL, reason TEXT NOT NULL, created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ma_attempts` (
  id INT AUTO_INCREMENT PRIMARY KEY, bucket VARCHAR(64) NOT NULL, attempted_at DATETIME NOT NULL, INDEX bucket_time(bucket,attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
