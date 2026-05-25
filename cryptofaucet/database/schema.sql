-- =====================================================================
-- Crypto Faucet Platform - MySQL Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.3+ on cPanel shared hosting.
-- All amounts are stored as DECIMAL(20,8) to support 8-decimal crypto
-- precision. Foreign keys use ON DELETE CASCADE / SET NULL where safe.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- USERS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`           VARCHAR(32)  NOT NULL,
    `email`              VARCHAR(190) NOT NULL,
    `password_hash`      VARCHAR(255) NOT NULL,
    `wallet_address`     VARCHAR(190) NULL,
    `role`               ENUM('user','admin') NOT NULL DEFAULT 'user',
    `status`             ENUM('active','banned','pending') NOT NULL DEFAULT 'active',
    `balance`            DECIMAL(20,8) NOT NULL DEFAULT 0,
    `total_earned`       DECIMAL(20,8) NOT NULL DEFAULT 0,
    `total_withdrawn`    DECIMAL(20,8) NOT NULL DEFAULT 0,
    `referral_code`      VARCHAR(16)  NOT NULL,
    `referred_by`        INT UNSIGNED NULL,
    `referral_earnings`  DECIMAL(20,8) NOT NULL DEFAULT 0,
    `last_login_ip`      VARCHAR(45)  NULL,
    `last_login_at`      DATETIME     NULL,
    `register_ip`        VARCHAR(45)  NULL,
    `failed_logins`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until`       DATETIME     NULL,
    `two_factor_secret`  VARCHAR(64)  NULL,
    `created_at`         DATETIME     NOT NULL,
    `updated_at`         DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_username` (`username`),
    UNIQUE KEY `uk_users_email` (`email`),
    UNIQUE KEY `uk_users_refcode` (`referral_code`),
    KEY `idx_users_referred_by` (`referred_by`),
    KEY `idx_users_status` (`status`),
    CONSTRAINT `fk_users_referred_by` FOREIGN KEY (`referred_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- COINS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coins` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`               VARCHAR(16)  NOT NULL,
    `name`               VARCHAR(64)  NOT NULL,
    `faucetpay_token`    VARCHAR(32)  NOT NULL,
    `min_reward`         DECIMAL(20,8) NOT NULL DEFAULT 0,
    `max_reward`         DECIMAL(20,8) NOT NULL DEFAULT 0,
    `min_withdraw`       DECIMAL(20,8) NOT NULL DEFAULT 0,
    `withdraw_fee`       DECIMAL(20,8) NOT NULL DEFAULT 0,
    `withdraw_fee_percent` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `is_active`          TINYINT(1)   NOT NULL DEFAULT 1,
    `display_order`      SMALLINT     NOT NULL DEFAULT 0,
    `created_at`         DATETIME     NOT NULL,
    `updated_at`         DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_coins_code` (`code`),
    KEY `idx_coins_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- CLAIMS  (faucet claim history)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `claims` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED NOT NULL,
    `coin_id`       INT UNSIGNED NOT NULL,
    `amount`        DECIMAL(20,8) NOT NULL,
    `ip_address`    VARCHAR(45)  NOT NULL,
    `user_agent`    VARCHAR(255) NULL,
    `device_fp`     VARCHAR(64)  NULL,
    `claimed_at`    DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_claims_user` (`user_id`),
    KEY `idx_claims_coin` (`coin_id`),
    KEY `idx_claims_user_time` (`user_id`,`claimed_at`),
    KEY `idx_claims_ip_time` (`ip_address`,`claimed_at`),
    CONSTRAINT `fk_claims_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_claims_coin` FOREIGN KEY (`coin_id`) REFERENCES `coins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- TRANSACTIONS  (audit trail of every balance change)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transactions` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `coin_id`      INT UNSIGNED NULL,
    `type`         ENUM('faucet','referral','ptc','shortlink','hilo_win','hilo_bet','withdrawal','admin_credit','admin_debit','bonus','task') NOT NULL,
    `amount`       DECIMAL(20,8) NOT NULL,
    `balance_after` DECIMAL(20,8) NOT NULL,
    `meta`         TEXT NULL,
    `ip_address`   VARCHAR(45) NULL,
    `created_at`   DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_tx_user` (`user_id`),
    KEY `idx_tx_user_time` (`user_id`,`created_at`),
    KEY `idx_tx_type` (`type`),
    CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tx_coin` FOREIGN KEY (`coin_id`) REFERENCES `coins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- WITHDRAWALS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `withdrawals` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED NOT NULL,
    `coin_id`        INT UNSIGNED NOT NULL,
    `amount`         DECIMAL(20,8) NOT NULL,
    `fee`            DECIMAL(20,8) NOT NULL DEFAULT 0,
    `net_amount`     DECIMAL(20,8) NOT NULL,
    `wallet_address` VARCHAR(190) NOT NULL,
    `status`         ENUM('pending','processing','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
    `faucetpay_tx`   VARCHAR(190) NULL,
    `payout_id`      VARCHAR(64)  NULL,
    `error_message`  VARCHAR(255) NULL,
    `ip_address`     VARCHAR(45)  NOT NULL,
    `processed_at`   DATETIME     NULL,
    `created_at`     DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_wd_user` (`user_id`),
    KEY `idx_wd_status` (`status`),
    KEY `idx_wd_ip_time` (`ip_address`,`created_at`),
    CONSTRAINT `fk_wd_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_wd_coin` FOREIGN KEY (`coin_id`) REFERENCES `coins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PTC ADS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ptc_ads` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`         VARCHAR(120) NOT NULL,
    `description`   VARCHAR(255) NULL,
    `target_url`    VARCHAR(255) NOT NULL,
    `image_url`     VARCHAR(255) NULL,
    `coin_id`       INT UNSIGNED NOT NULL,
    `reward`        DECIMAL(20,8) NOT NULL,
    `duration`      SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    `daily_limit`   INT UNSIGNED NOT NULL DEFAULT 0,
    `max_views`     INT UNSIGNED NOT NULL DEFAULT 0,
    `views_count`   INT UNSIGNED NOT NULL DEFAULT 0,
    `status`        ENUM('active','paused','ended') NOT NULL DEFAULT 'active',
    `created_at`    DATETIME NOT NULL,
    `updated_at`    DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ptc_status` (`status`),
    CONSTRAINT `fk_ptc_coin` FOREIGN KEY (`coin_id`) REFERENCES `coins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ptc_views` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `ad_id`      INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `device_fp`  VARCHAR(64) NULL,
    `viewed_at`  DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ptc_view_daily` (`user_id`,`ad_id`,`viewed_at`),
    KEY `idx_ptc_view_user` (`user_id`,`viewed_at`),
    CONSTRAINT `fk_ptcv_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ptcv_ad`   FOREIGN KEY (`ad_id`)   REFERENCES `ptc_ads`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- SHORTLINKS  (admin-managed)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shortlinks` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(120) NOT NULL,
    `target_url`   VARCHAR(500) NOT NULL,
    `coin_id`      INT UNSIGNED NOT NULL,
    `reward`       DECIMAL(20,8) NOT NULL,
    `daily_limit`  INT UNSIGNED NOT NULL DEFAULT 5,
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_sl_coin` FOREIGN KEY (`coin_id`) REFERENCES `coins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shortlink_completions` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `shortlink_id` INT UNSIGNED NOT NULL,
    `token`        CHAR(64) NOT NULL,
    `status`       ENUM('issued','redeemed','expired') NOT NULL DEFAULT 'issued',
    `ip_address`   VARCHAR(45) NULL,
    `created_at`   DATETIME NOT NULL,
    `redeemed_at`  DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sl_token` (`token`),
    KEY `idx_sl_user_time` (`user_id`,`created_at`),
    CONSTRAINT `fk_slc_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_slc_sl`   FOREIGN KEY (`shortlink_id`) REFERENCES `shortlinks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- HI-LO GAMES (provably fair)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hilo_games` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED NOT NULL,
    `coin_id`       INT UNSIGNED NOT NULL,
    `bet_amount`    DECIMAL(20,8) NOT NULL,
    `prediction`    ENUM('high','low','equal') NOT NULL,
    `roll_value`    SMALLINT UNSIGNED NOT NULL,
    `target_value`  SMALLINT UNSIGNED NOT NULL,
    `result`        ENUM('win','loss','push') NOT NULL,
    `payout`        DECIMAL(20,8) NOT NULL DEFAULT 0,
    `multiplier`    DECIMAL(8,4) NOT NULL DEFAULT 0,
    `server_seed`   CHAR(64) NOT NULL,
    `server_seed_hash` CHAR(64) NOT NULL,
    `client_seed`   VARCHAR(64) NOT NULL,
    `nonce`         BIGINT UNSIGNED NOT NULL,
    `created_at`    DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_hilo_user_time` (`user_id`,`created_at`),
    CONSTRAINT `fk_hilo_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hilo_coin` FOREIGN KEY (`coin_id`) REFERENCES `coins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- SETTINGS  (key/value store managed in admin panel)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `key_name`   VARCHAR(64) NOT NULL,
    `value`      TEXT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- SECURITY LOGS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `security_logs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NULL,
    `event`      VARCHAR(64) NOT NULL,
    `severity`   ENUM('info','warning','danger') NOT NULL DEFAULT 'info',
    `message`    VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sl_user` (`user_id`),
    KEY `idx_sl_event` (`event`),
    KEY `idx_sl_time` (`created_at`),
    CONSTRAINT `fk_sl_user_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- IP BLACKLIST
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ip_blacklist` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `reason`     VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_blk_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- RATE LIMITS  (sliding window counters)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `bucket`     VARCHAR(128) NOT NULL,
    `hits`       INT UNSIGNED NOT NULL DEFAULT 0,
    `expires_at` DATETIME NOT NULL,
    PRIMARY KEY (`bucket`),
    KEY `idx_rl_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PASSWORD RESETS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `token_hash` CHAR(64) NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at`    DATETIME NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`token_hash`),
    KEY `idx_pr_user` (`user_id`),
    CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ANNOUNCEMENTS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `announcements` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(120) NOT NULL,
    `body`       TEXT NOT NULL,
    `level`      ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ann_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- DAILY BONUS  (one-per-day claim history)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `daily_bonuses` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `streak`     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `amount`     DECIMAL(20,8) NOT NULL,
    `coin_id`    INT UNSIGNED NOT NULL,
    `claimed_on` DATE NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_db_user_date` (`user_id`,`claimed_on`),
    CONSTRAINT `fk_db_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_db_coin` FOREIGN KEY (`coin_id`) REFERENCES `coins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- =====================================================================

INSERT INTO `coins` (`code`,`name`,`faucetpay_token`,`min_reward`,`max_reward`,`min_withdraw`,`withdraw_fee`,`withdraw_fee_percent`,`is_active`,`display_order`,`created_at`,`updated_at`) VALUES
('LTC','Litecoin','LTC',0.00000010,0.00000050,0.00010000,0.00000100,0,1,1,NOW(),NOW()),
('DOGE','Dogecoin','DOGE',0.00010000,0.00100000,0.10000000,0.00010000,0,1,2,NOW(),NOW()),
('TRX','Tron','TRX',0.00010000,0.00100000,0.50000000,0.00010000,0,1,3,NOW(),NOW())
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `settings` (`key_name`,`value`,`updated_at`) VALUES
('site_name','Crypto Faucet',NOW()),
('site_url','https://example.com',NOW()),
('site_description','Earn free crypto every 5 minutes',NOW()),
('faucet_cooldown_seconds','300',NOW()),
('faucet_daily_limit','100',NOW()),
('referral_percent','10',NOW()),
('hilo_house_edge_percent','3',NOW()),
('withdraw_max_per_ip_per_day','2',NOW()),
('withdraw_min_account_age_minutes','30',NOW()),
('recaptcha_site_key','',NOW()),
('recaptcha_secret','',NOW()),
('faucetpay_api_key','',NOW()),
('maintenance_mode','0',NOW()),
('maintenance_message','We are performing scheduled maintenance.',NOW()),
('proxy_check_enabled','1',NOW()),
('vpn_block_enabled','1',NOW()),
('proxycheck_api_key','',NOW()),
('default_coin_code','LTC',NOW()),
('smtp_host','',NOW()),
('smtp_port','587',NOW()),
('smtp_user','',NOW()),
('smtp_pass','',NOW()),
('smtp_from','noreply@example.com',NOW()),
('telegram_bot_token','',NOW()),
('telegram_chat_id','',NOW()),
('online_window_minutes','5',NOW())
ON DUPLICATE KEY UPDATE `updated_at`=VALUES(`updated_at`);
