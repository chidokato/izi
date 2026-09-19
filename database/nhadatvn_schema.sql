SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `customer_inquiries`;
DROP TABLE IF EXISTS `apartment_images`;
DROP TABLE IF EXISTS `apartments`;
DROP TABLE IF EXISTS `post_floor_plans`;
DROP TABLE IF EXISTS `post_images`;
DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `menus`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `wards`;
DROP TABLE IF EXISTS `provinces`;
DROP TABLE IF EXISTS `seo_configs`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `secondary_phone` varchar(255) DEFAULT NULL,
  `whatsapp_phone` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `provinces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `source_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provinces_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `province_id` bigint unsigned NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `source_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wards_code_unique` (`code`),
  KEY `wards_province_id_foreign` (`province_id`),
  CONSTRAINT `wards_province_id_foreign`
    FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL DEFAULT 'product',
  `parent_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `categories_parent_id_foreign`
    FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `target` varchar(20) NOT NULL DEFAULT '_self',
  `sort_order` int unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menus_slug_unique` (`slug`),
  KEY `menus_parent_id_foreign` (`parent_id`),
  CONSTRAINT `menus_parent_id_foreign`
    FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) DEFAULT NULL,
  `address` varchar(1000) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `hotline` varchar(50) DEFAULT NULL,
  `social` json DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `footer_logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `footer_column_1_title` varchar(255) DEFAULT NULL,
  `footer_column_1_content` longtext DEFAULT NULL,
  `footer_column_2_title` varchar(255) DEFAULT NULL,
  `footer_column_2_content` longtext DEFAULT NULL,
  `footer_column_3_title` varchar(255) DEFAULT NULL,
  `footer_column_3_content` longtext DEFAULT NULL,
  `footer_column_4_title` varchar(255) DEFAULT NULL,
  `footer_column_4_content` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `seo_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_key` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seo_configs_page_key_unique` (`page_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL DEFAULT 'product',
  `category_id` bigint unsigned DEFAULT NULL,
  `seller_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `sales_policy` longtext DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `address` varchar(1000) DEFAULT NULL,
  `province_id` bigint unsigned DEFAULT NULL,
  `ward_id` bigint unsigned DEFAULT NULL,
  `map_embed` longtext DEFAULT NULL,
  `location_image` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `area` decimal(10,2) DEFAULT NULL,
  `area_from` decimal(10,2) DEFAULT NULL,
  `area_to` decimal(10,2) DEFAULT NULL,
  `floor_count` int unsigned DEFAULT NULL,
  `floor_count_from` int unsigned DEFAULT NULL,
  `floor_count_to` int unsigned DEFAULT NULL,
  `unit_count` int unsigned DEFAULT NULL,
  `unit_count_from` int unsigned DEFAULT NULL,
  `unit_count_to` int unsigned DEFAULT NULL,
  `bedroom_count` int unsigned DEFAULT NULL,
  `bedroom_count_from` int unsigned DEFAULT NULL,
  `bedroom_count_to` int unsigned DEFAULT NULL,
  `bathroom_count` int unsigned DEFAULT NULL,
  `bathroom_count_from` int unsigned DEFAULT NULL,
  `bathroom_count_to` int unsigned DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `pdf_file` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_slug_unique` (`slug`),
  KEY `posts_category_id_foreign` (`category_id`),
  KEY `posts_seller_id_foreign` (`seller_id`),
  KEY `posts_province_id_foreign` (`province_id`),
  KEY `posts_ward_id_foreign` (`ward_id`),
  CONSTRAINT `posts_category_id_foreign`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `posts_seller_id_foreign`
    FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `posts_province_id_foreign`
    FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `posts_ward_id_foreign`
    FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL,
  `image` varchar(255) NOT NULL,
  `image_type` varchar(30) NOT NULL DEFAULT 'perspective',
  `sort_order` int unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_images_post_id_foreign` (`post_id`),
  CONSTRAINT `post_images_post_id_foreign`
    FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_floor_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_floor_plans_post_id_foreign` (`post_id`),
  CONSTRAINT `post_floor_plans_post_id_foreign`
    FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `apartments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `area` decimal(10,2) DEFAULT NULL,
  `bedroom_count` int unsigned DEFAULT NULL,
  `bathroom_count` int unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `apartments_project_id_foreign` (`project_id`),
  CONSTRAINT `apartments_project_id_foreign`
    FOREIGN KEY (`project_id`) REFERENCES `posts` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `apartment_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `apartment_id` bigint unsigned NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `apartment_images_apartment_id_foreign` (`apartment_id`),
  CONSTRAINT `apartment_images_apartment_id_foreign`
    FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customer_inquiries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned DEFAULT NULL,
  `project_title` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `source_url` text DEFAULT NULL,
  `download_url` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_inquiries_post_id_foreign` (`post_id`),
  CONSTRAINT `customer_inquiries_post_id_foreign`
    FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`name`, `email`, `password`, `created_at`, `updated_at`)
VALUES (
  'Administrator',
  'tuan.pn92@gmail.com',
  '$2y$10$erEgCMPmRr3GW22SC8NFwOegmKXfGw9h.9Ncq8oGqrbo2dgwcpXp.',
  NOW(),
  NOW()
);

INSERT INTO `settings` (`company_name`, `email`, `hotline`, `created_at`, `updated_at`)
VALUES ('NhaDatVN', 'contact@nhadatvn.org', '0900000000', NOW(), NOW());

INSERT INTO `seo_configs` (`page_key`, `title`, `description`, `keywords`, `created_at`, `updated_at`)
VALUES
  ('home', 'Trang chủ', 'Trang chủ bất động sản NhaDatVN.', 'trang chu, bat dong san, nha dat, du an, NhaDatVN', NOW(), NOW()),
  ('about', 'Giới thiệu', 'Giới thiệu NhaDatVN.', 'gioi thieu, NhaDatVN', NOW(), NOW()),
  ('contact', 'Liên hệ', 'Liên hệ NhaDatVN.', 'lien he, NhaDatVN', NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;
