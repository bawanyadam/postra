-- Postra initial schema (from plan/postra_schema.sql)
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS postra
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

USE postra;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS submission_fields;
DROP TABLE IF EXISTS submissions;
DROP TABLE IF EXISTS forms;
DROP TABLE IF EXISTS api_credentials;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE projects (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(26) NOT NULL,
  name VARCHAR(128) NOT NULL,
  description TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_projects_public_id (public_id),
  UNIQUE KEY uq_projects_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE api_credentials (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(128) NOT NULL,
  provider ENUM('sendgrid') NOT NULL,
  scope ENUM('global','project','form') NOT NULL,
  scope_ref_id BIGINT UNSIGNED NULL,
  secret_encrypted VARBINARY(4096) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_api_credentials_name (name),
  UNIQUE KEY uq_api_credentials_scope (provider, scope, scope_ref_id),
  CHECK ((scope = 'global' AND scope_ref_id IS NULL) OR (scope IN ('project','form') AND scope_ref_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE forms (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(26) NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(128) NOT NULL,
  recipient_email VARCHAR(254) NOT NULL,
  redirect_url VARCHAR(1024) NOT NULL,
  allowed_domain VARCHAR(255) NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  api_credential_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_forms_public_id (public_id),
  UNIQUE KEY uq_forms_project_name (project_id, name),
  KEY idx_forms_project (project_id),
  CONSTRAINT fk_forms_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT fk_forms_api_credential FOREIGN KEY (api_credential_id) REFERENCES api_credentials(id) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE submissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  form_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  client_ip VARBINARY(16) NULL,
  user_agent VARCHAR(512) NULL,
  payload_json JSON NOT NULL,
  dedupe_hash CHAR(64) NULL,
  PRIMARY KEY (id),
  KEY idx_submissions_form_created (form_id, created_at),
  KEY idx_submissions_dedupe (dedupe_hash),
  CONSTRAINT fk_submissions_form FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE submission_fields (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  submission_id BIGINT UNSIGNED NOT NULL,
  field VARCHAR(128) NOT NULL,
  value_text TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_submission_fields_submission (submission_id),
  KEY idx_submission_fields_field (field),
  CONSTRAINT fk_submission_fields_submission FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;

