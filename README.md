#ALTER TABLE `bot_configurations` CHANGE `connection_status` `connection_status` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disconnected';


ALTER TABLE `bot_configurations` ADD `image` VARCHAR(200) NULL DEFAULT NULL AFTER `login`;