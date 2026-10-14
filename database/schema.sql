-- COVID-19 Information Portal - Database Schema
-- Import: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS covid_portal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE covid_portal;

CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(20)  NOT NULL,
  email         VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE registrations (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  full_name   VARCHAR(60)  NOT NULL,
  email       VARCHAR(254) NOT NULL,
  phone       VARCHAR(16)  NOT NULL,
  dob         DATE         NOT NULL,
  gender      ENUM('female','male','other','prefer_not_to_say') NOT NULL,
  pin_code    CHAR(6)      NOT NULL,
  state       VARCHAR(40)  NOT NULL,
  service     ENUM('vaccination_info','awareness_webinar','volunteer','helpline_callback') NOT NULL,
  consent     TINYINT(1)   NOT NULL,
  status      ENUM('active','cancelled') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_reg_consent CHECK (consent = 1),
  UNIQUE KEY uq_reg_user_service (user_id, service)
) ENGINE=InnoDB;

CREATE TABLE comments (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  body       VARCHAR(2000) NOT NULL,
  status     ENUM('visible','hidden','deleted') NOT NULL DEFAULT 'visible',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  KEY idx_comments_status_created (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(20) NOT NULL,
  ip           VARCHAR(45) NOT NULL,
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempts_lookup (username, ip, attempted_at)
) ENGINE=InnoDB;

CREATE TABLE covid_cache (
  cache_key  VARCHAR(80) NOT NULL PRIMARY KEY,
  payload    MEDIUMTEXT  NOT NULL,
  fetched_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
