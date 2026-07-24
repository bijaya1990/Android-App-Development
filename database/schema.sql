-- =====================================================================
-- Talpadar TV News - Database Schema (Phase 1)
-- Scope: Bargarh District only (V1)
-- Engine: InnoDB, utf8mb4 (MySQL 5.7+ / MySQL 8, compatible with MilesWeb
-- shared hosting via phpMyAdmin/cPanel)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Table: blocks
-- Fixed list of the 12 blocks under Bargarh district. No other district
-- or block is added in V1.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `blocks`;
CREATE TABLE `blocks` (
    `id`   TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_blocks_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: users
-- Two roles only: super_admin, content_writer.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(50) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name`     VARCHAR(100) NOT NULL,
    `role`          ENUM('super_admin', 'content_writer') NOT NULL,
    `status`        ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: articles
-- Status flow: draft -> pending_review -> published
--                                       -> returned -> (writer edits) -> pending_review
-- Minimum 2 / maximum 4 images per article is enforced at the
-- application layer (not expressible as a plain SQL constraint).
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`          VARCHAR(255) NOT NULL,
    `block_id`       TINYINT UNSIGNED NOT NULL,
    `content`        LONGTEXT NOT NULL,
    `writer_id`      INT UNSIGNED NOT NULL,
    `status`         ENUM('draft', 'pending_review', 'published', 'returned') NOT NULL DEFAULT 'draft',
    `breaking_news`  TINYINT(1) NOT NULL DEFAULT 0,
    `return_reason`  TEXT NULL,
    `reviewed_by`    INT UNSIGNED NULL,
    `published_at`   DATETIME NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_articles_status` (`status`),
    KEY `idx_articles_block_id` (`block_id`),
    KEY `idx_articles_writer_id` (`writer_id`),
    KEY `idx_articles_published_at` (`published_at`),
    CONSTRAINT `fk_articles_block` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_articles_writer` FOREIGN KEY (`writer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_articles_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: article_images
-- Each row is one uploaded image belonging to an article, ordered by
-- sort_order (first image is used as the thumbnail).
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `article_images`;
CREATE TABLE `article_images` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `article_id` INT UNSIGNED NOT NULL,
    `file_path`  VARCHAR(500) NOT NULL,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_article_images_article_id` (`article_id`),
    CONSTRAINT `fk_article_images_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
