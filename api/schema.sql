CREATE DATABASE IF NOT EXISTS data_tagihan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE data_tagihan;

CREATE TABLE IF NOT EXISTS tagihan (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jenis           VARCHAR(100)    NOT NULL,
    id_pelanggan    VARCHAR(100)    NOT NULL,
    nama_pelanggan  VARCHAR(255)    NOT NULL,
    nominal         DECIMAL(15,2)   NOT NULL,
    pembeli_dapat   DECIMAL(15,2)   NOT NULL,
    status          ENUM('Ready','Sold','Off','Pending') NOT NULL DEFAULT 'Pending',
    user_login      VARCHAR(100)    NOT NULL,
    bukti_transaksi VARCHAR(255)    DEFAULT NULL,
    bukti_bayar     VARCHAR(255)    DEFAULT NULL,
    instruksi       TEXT            DEFAULT NULL,
    verified        TINYINT(1)      NOT NULL DEFAULT 0,
    verified_by     VARCHAR(100)    DEFAULT NULL,
    verified_at     DATETIME        DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_user_login (user_login),
    INDEX idx_verified (verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(100)    NOT NULL UNIQUE,
    pass_hash   VARCHAR(255)    NOT NULL,
    is_active   TINYINT(1)      NOT NULL DEFAULT 1,
    created_by  VARCHAR(100)    NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timestamp   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actor       VARCHAR(100)    NOT NULL,
    role        ENUM('admin','user') NOT NULL DEFAULT 'user',
    action      VARCHAR(100)    NOT NULL,
    tagihan_id  INT UNSIGNED    DEFAULT NULL,
    before_data JSON            DEFAULT NULL,
    after_data  JSON            DEFAULT NULL,
    ip          VARCHAR(45)     DEFAULT '',
    user_agent  TEXT            DEFAULT NULL,
    INDEX idx_actor (actor),
    INDEX idx_action (action),
    INDEX idx_tagihan_id (tagihan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
