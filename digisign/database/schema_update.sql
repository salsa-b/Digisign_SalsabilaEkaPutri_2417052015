USE `digisign`;

ALTER TABLE `users`
    ADD COLUMN `username`   VARCHAR(80)                         NOT NULL DEFAULT '' AFTER `id`,
    ADD COLUMN `password`   VARCHAR(255)                        NOT NULL DEFAULT '' AFTER `username`,
    ADD COLUMN `role`       ENUM('user','admin')                NOT NULL DEFAULT 'user' AFTER `password`,
    ADD UNIQUE KEY `uq_users_username` (`username`);

ALTER TABLE `digital_id_requests`
    ADD COLUMN `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `is_sent`,
    ADD COLUMN `approved_by`  INT UNSIGNED                          NULL     DEFAULT NULL AFTER `status`,
    ADD COLUMN `approved_at`  DATETIME                              NULL     DEFAULT NULL AFTER `approved_by`,
    ADD KEY `idx_digreq_status_enum` (`status`);

INSERT INTO `users`
    (`username`, `email`, `password`, `role`, `otp_verified`, `created_at`)
VALUES
    ('admin', 'admin@digisign.local', '$2y$12$6ahsdhkxi4AVJzofzGS3redNWpPgD3piAmuJI0yVunsrjYld.lhRe', 'admin', 1, NOW());
