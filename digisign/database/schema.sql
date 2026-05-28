-- ============================================================
-- Digital Signature System - Database Schema
-- Engine: InnoDB | Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS `digisign`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `digisign`;

-- ------------------------------------------------------------
-- 1. USERS
--    Menyimpan akun pengguna beserta OTP & session info
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                    BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `email`                 VARCHAR(255)        NOT NULL,
    `otp_secret`            VARCHAR(64)         NULL        DEFAULT NULL COMMENT 'TOTP/HOTP secret key (Base32)',
    `otp_verified`          TINYINT(1)          NOT NULL    DEFAULT 0   COMMENT '0 = belum verifikasi, 1 = sudah',
    `session_last_activity` INT UNSIGNED        NULL        DEFAULT NULL COMMENT 'Unix timestamp aktivitas session terakhir',
    `created_at`            DATETIME            NOT NULL    DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tabel akun pengguna sistem tanda tangan digital';

-- ------------------------------------------------------------
-- 2. DIGITAL ID REQUESTS
--    Permintaan pembuatan Digital ID (sertifikat)
--    Status pipeline: is_approved → is_ready → is_sent
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `digital_id_requests` (
    `id`                BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`           BIGINT UNSIGNED     NOT NULL,
    `role`              ENUM(
                            'dosen',
                            'tendik',
                            'mahasiswa'
                        )                   NOT NULL                    COMMENT 'Peran / jabatan pemohon',
    `passphrase_hash`   VARCHAR(255)        NOT NULL                    COMMENT 'Passphrase yang di-hash (bcrypt)',
    `is_approved`       TINYINT(1)          NOT NULL    DEFAULT 0       COMMENT 'Admin menyetujui request',
    `is_ready`          TINYINT(1)          NOT NULL    DEFAULT 0       COMMENT 'Sertifikat sudah digenerate',
    `is_sent`           TINYINT(1)          NOT NULL    DEFAULT 0       COMMENT 'Sertifikat sudah dikirim ke pemohon',
    `certificate_path`  VARCHAR(500)        NULL        DEFAULT NULL    COMMENT 'Path file sertifikat (.p12 / .pfx)',
    `created_at`        DATETIME            NOT NULL    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME            NOT NULL    DEFAULT CURRENT_TIMESTAMP
                                                        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_digreq_user_id`    (`user_id`),
    KEY `idx_digreq_role`       (`role`),
    KEY `idx_digreq_status`     (`is_approved`, `is_ready`, `is_sent`),

    CONSTRAINT `fk_digreq_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Permintaan Digital ID – role, passphrase, status pipeline';

-- ------------------------------------------------------------
-- 3. SIGNATURES  (Signature Specimen)
--    Menyimpan gambar spesimen tanda tangan, rasio 1:2.23
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `signatures` (
    `id`            BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`       BIGINT UNSIGNED     NOT NULL,
    `image_path`    VARCHAR(500)        NOT NULL                COMMENT 'Path file gambar spesimen TTD',
    `aspect_ratio`  DECIMAL(6,4)        NOT NULL    DEFAULT 2.2300
                                                    COMMENT 'Rasio lebar:tinggi, default 1:2.23',
    `is_active`     TINYINT(1)          NOT NULL    DEFAULT 1   COMMENT '1 = aktif digunakan',
    `created_at`    DATETIME            NOT NULL    DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_sig_user_active` (`user_id`, `is_active`),

    CONSTRAINT `fk_sig_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Spesimen tanda tangan pengguna (rasio 1:2.23)';

-- ------------------------------------------------------------
-- 4. SIGNING REQUESTS  (Sign via Web)
--    Proses penandatanganan dokumen PDF
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `signing_requests` (
    `id`                    BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`               BIGINT UNSIGNED     NOT NULL,
    `pdf_path`              VARCHAR(500)        NOT NULL                COMMENT 'Path file PDF yang akan ditandatangani',
    `signature_coordinates` JSON                NULL        DEFAULT NULL
                                                            COMMENT 'Koordinat & halaman penempatan TTD, e.g. {"page":1,"x":100,"y":200,"width":150,"height":67}',
    `status`                ENUM(
                                'pending',
                                'signed',
                                'failed'
                            )                   NOT NULL    DEFAULT 'pending'
                                                            COMMENT 'Status proses penandatanganan',
    `signed_at`             DATETIME            NULL        DEFAULT NULL COMMENT 'Timestamp dokumen berhasil ditandatangani',
    `created_at`            DATETIME            NOT NULL    DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_signreq_user_id`   (`user_id`),
    KEY `idx_signreq_status`    (`status`),

    CONSTRAINT `fk_signreq_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Permintaan penandatanganan dokumen PDF via Web';

-- ============================================================
-- END OF SCHEMA
-- ============================================================
