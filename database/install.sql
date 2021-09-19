/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 80016
 Source Host           : localhost
 Source Database       : v2board

 Target Server Type    : MySQL
 Target Server Version : 80016
 File Encoding         : utf-8

 Date: 09/18/2021 07:01:50 AM
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `failed_jobs`
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
                               `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                               `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                               `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                               `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                               `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                               `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
--  Table structure for `v2_coupon`
-- ----------------------------
DROP TABLE IF EXISTS `v2_coupon`;
CREATE TABLE `v2_coupon` (
                             `id` int(11) NOT NULL AUTO_INCREMENT,
                             `code` varchar(255) NOT NULL,
                             `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
                             `type` tinyint(1) NOT NULL,
                             `value` int(11) NOT NULL,
                             `limit_use` int(11) DEFAULT NULL,
                             `limit_use_with_user` int(11) DEFAULT NULL,
                             `limit_plan_ids` varchar(255) DEFAULT NULL,
                             `started_at` int(11) NOT NULL,
                             `ended_at` int(11) NOT NULL,
                             `created_at` int(11) NOT NULL,
                             `updated_at` int(11) NOT NULL,
                             PRIMARY KEY (`id`),
                             KEY `code` (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=162 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_invite_code`
-- ----------------------------
DROP TABLE IF EXISTS `v2_invite_code`;
CREATE TABLE `v2_invite_code` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `user_id` int(11) NOT NULL,
                                  `code` char(32) NOT NULL,
                                  `status` tinyint(1) NOT NULL DEFAULT '0',
                                  `pv` int(11) NOT NULL DEFAULT '0',
                                  `created_at` int(11) NOT NULL,
                                  `updated_at` int(11) NOT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `user_id_status` (`user_id`,`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_knowledge`
-- ----------------------------
DROP TABLE IF EXISTS `v2_knowledge`;
CREATE TABLE `v2_knowledge` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `language` char(5) NOT NULL COMMENT '語言',
                                `category` varchar(255) NOT NULL COMMENT '分類名',
                                `title` varchar(255) NOT NULL COMMENT '標題',
                                `body` text NOT NULL COMMENT '內容',
                                `sort` int(11) DEFAULT NULL COMMENT '排序',
                                `show` tinyint(1) NOT NULL DEFAULT '0' COMMENT '顯示',
                                `created_at` int(11) NOT NULL COMMENT '創建時間',
                                `updated_at` int(11) NOT NULL COMMENT '更新時間',
                                PRIMARY KEY (`id`),
                                KEY `language_show` (`language`,`show`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='知識庫';

-- ----------------------------
--  Table structure for `v2_mail_log`
-- ----------------------------
DROP TABLE IF EXISTS `v2_mail_log`;
CREATE TABLE `v2_mail_log` (
                               `id` int(11) NOT NULL AUTO_INCREMENT,
                               `email` varchar(64) NOT NULL,
                               `subject` varchar(255) NOT NULL,
                               `template_name` varchar(255) NOT NULL,
                               `error` text,
                               `created_at` int(11) NOT NULL,
                               `updated_at` int(11) NOT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_notice`
-- ----------------------------
DROP TABLE IF EXISTS `v2_notice`;
CREATE TABLE `v2_notice` (
                             `id` int(11) NOT NULL AUTO_INCREMENT,
                             `title` varchar(255) NOT NULL,
                             `content` text NOT NULL,
                             `img_url` varchar(255) DEFAULT NULL,
                             `created_at` int(11) NOT NULL,
                             `updated_at` int(11) NOT NULL,
                             PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_order`
-- ----------------------------
DROP TABLE IF EXISTS `v2_order`;
CREATE TABLE `v2_order` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `invite_user_id` int(11) DEFAULT '0',
                            `user_id` int(11) NOT NULL,
                            `plan_id` int(11) NOT NULL,
                            `coupon_id` int(11) DEFAULT NULL COMMENT '0',
                            `payment_id` int(11) DEFAULT '0',
                            `type` int(11) NOT NULL COMMENT '1新购2续费3升级',
                            `cycle` varchar(255) NOT NULL,
                            `trade_no` varchar(36) NOT NULL,
                            `callback_no` varchar(255) DEFAULT NULL,
                            `total_amount` int(11) NOT NULL,
                            `discount_amount` int(11) DEFAULT NULL,
                            `surplus_amount` int(11) DEFAULT NULL COMMENT '剩余价值',
                            `refund_amount` int(11) DEFAULT NULL COMMENT '退款金额',
                            `balance_amount` int(11) DEFAULT NULL COMMENT '使用余额',
                            `surplus_order_ids` text COMMENT '折抵订单',
                            `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待支付1开通中2已取消3已完成4已折抵',
                            `commission_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待确认1发放中2有效3无效',
                            `commission_balance` int(11) NOT NULL DEFAULT '0',
                            `paid_at` int(11) DEFAULT NULL,
                            `created_at` int(11) NOT NULL,
                            `updated_at` int(11) NOT NULL,
                            PRIMARY KEY (`id`),
                            KEY `status` (`status`) USING BTREE,
                            KEY `invite_user_id` (`invite_user_id`) USING BTREE,
                            KEY `user_id` (`user_id`) USING BTREE,
                            KEY `created_at` (`created_at`) USING BTREE,
                            KEY `status_user_id` (`user_id`,`status`) USING BTREE,
                            KEY `created_at_status` (`status`,`created_at`) USING BTREE,
                            KEY `trade_no` (`trade_no`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_order_stat`
-- ----------------------------
DROP TABLE IF EXISTS `v2_order_stat`;
CREATE TABLE `v2_order_stat` (
                                 `id` int(11) NOT NULL AUTO_INCREMENT,
                                 `order_count` int(11) NOT NULL COMMENT '订单数量',
                                 `order_amount` int(11) NOT NULL COMMENT '订单合计',
                                 `commission_count` int(11) NOT NULL,
                                 `commission_amount` int(11) NOT NULL COMMENT '佣金合计',
                                 `record_type` char(1) NOT NULL,
                                 `record_at` int(11) NOT NULL,
                                 `created_at` int(11) NOT NULL,
                                 `updated_at` int(11) NOT NULL,
                                 PRIMARY KEY (`id`),
                                 UNIQUE KEY `record_at` (`record_at`) USING BTREE,
                                 KEY `record_at_record_type` (`record_type`,`record_at`) USING BTREE,
                                 KEY `record_type` (`record_type`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='订单统计';

-- ----------------------------
--  Table structure for `v2_payment`
-- ----------------------------
DROP TABLE IF EXISTS `v2_payment`;
CREATE TABLE `v2_payment` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `uuid` char(32) NOT NULL,
                              `payment` varchar(16) NOT NULL,
                              `name` varchar(255) NOT NULL,
                              `config` text NOT NULL,
                              `enable` tinyint(1) NOT NULL DEFAULT '0',
                              `sort` int(11) DEFAULT NULL,
                              `created_at` int(11) NOT NULL,
                              `updated_at` int(11) NOT NULL,
                              PRIMARY KEY (`id`),
                              KEY `uuid` (`uuid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
--  Table structure for `v2_plan`
-- ----------------------------
DROP TABLE IF EXISTS `v2_plan`;
CREATE TABLE `v2_plan` (
                           `id` int(11) NOT NULL AUTO_INCREMENT,
                           `group_id` int(11) NOT NULL,
                           `transfer_enable` int(11) NOT NULL,
                           `name` varchar(255) NOT NULL,
                           `show` tinyint(1) NOT NULL DEFAULT '0',
                           `sort` int(11) DEFAULT NULL,
                           `renew` tinyint(1) NOT NULL DEFAULT '1',
                           `content` text,
                           `month_price` int(11) DEFAULT (NULL),
                           `quarter_price` int(11) DEFAULT (NULL),
                           `half_year_price` int(11) DEFAULT (NULL),
                           `year_price` int(11) DEFAULT (NULL),
                           `two_year_price` int(11) DEFAULT NULL,
                           `three_year_price` int(11) DEFAULT NULL,
                           `onetime_price` int(11) DEFAULT NULL,
                           `reset_price` int(11) DEFAULT NULL,
                           `created_at` int(11) NOT NULL,
                           `updated_at` int(11) NOT NULL,
                           PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_plan_empty`
-- ----------------------------
DROP TABLE IF EXISTS `v2_plan_empty`;
CREATE TABLE `v2_plan_empty` (
                                 `id` int(11) NOT NULL AUTO_INCREMENT,
                                 `group_id` int(11) NOT NULL,
                                 `transfer_enable` int(11) NOT NULL,
                                 `name` varchar(255) NOT NULL,
                                 `show` tinyint(1) NOT NULL DEFAULT '0',
                                 `sort` int(11) DEFAULT NULL,
                                 `renew` tinyint(1) NOT NULL DEFAULT '1',
                                 `content` text,
                                 `month_price` int(11) DEFAULT '0',
                                 `quarter_price` int(11) DEFAULT '0',
                                 `half_year_price` int(11) DEFAULT '0',
                                 `year_price` int(11) DEFAULT '0',
                                 `two_year_price` int(11) DEFAULT NULL,
                                 `three_year_price` int(11) DEFAULT NULL,
                                 `onetime_price` int(11) DEFAULT NULL,
                                 `reset_price` int(11) DEFAULT NULL,
                                 `created_at` int(11) NOT NULL,
                                 `updated_at` int(11) NOT NULL,
                                 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_server`
-- ----------------------------
DROP TABLE IF EXISTS `v2_server`;
CREATE TABLE `v2_server` (
                             `id` int(11) NOT NULL AUTO_INCREMENT,
                             `group_id` varchar(255) NOT NULL,
                             `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                             `parent_id` int(11) DEFAULT '0',
                             `host` varchar(255) NOT NULL,
                             `port` int(11) NOT NULL,
                             `server_port` int(11) NOT NULL,
                             `tags` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
                             `rate` varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                             `network` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                             `tls` tinyint(4) NOT NULL DEFAULT '0',
                             `alter_id` int(11) NOT NULL DEFAULT '1',
                             `network_settings` text,
                             `tls_settings` text,
                             `rule_settings` text,
                             `dns_settings` text,
                             `show` tinyint(1) NOT NULL DEFAULT '0',
                             `sort` int(11) DEFAULT '0',
                             `created_at` int(11) NOT NULL,
                             `updated_at` int(11) NOT NULL,
                             PRIMARY KEY (`id`),
                             KEY `show` (`show`) USING BTREE,
                             KEY `parent_id` (`parent_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_server_group`
-- ----------------------------
DROP TABLE IF EXISTS `v2_server_group`;
CREATE TABLE `v2_server_group` (
                                   `id` int(11) NOT NULL AUTO_INCREMENT,
                                   `name` varchar(255) NOT NULL,
                                   `created_at` int(11) NOT NULL,
                                   `updated_at` int(11) NOT NULL,
                                   PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_server_log`
-- ----------------------------
DROP TABLE IF EXISTS `v2_server_log`;
CREATE TABLE `v2_server_log` (
                                 `id` bigint(20) NOT NULL AUTO_INCREMENT,
                                 `user_id` int(11) NOT NULL,
                                 `server_id` int(11) NOT NULL,
                                 `u` varchar(255) NOT NULL,
                                 `d` varchar(255) NOT NULL,
                                 `rate` decimal(10,2) NOT NULL,
                                 `method` varchar(255) NOT NULL,
                                 `log_at` int(11) NOT NULL,
                                 `created_at` int(11) NOT NULL,
                                 `updated_at` int(11) NOT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `log_at` (`log_at`),
                                 KEY `union` (`log_at`,`user_id`,`server_id`,`rate`,`method`) USING BTREE,
                                 KEY `user_id_creatd_at` (`user_id`,`created_at`) USING BTREE,
                                 KEY `user_id` (`user_id`),
                                 KEY `server_id` (`server_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1008 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_server_shadowsocks`
-- ----------------------------
DROP TABLE IF EXISTS `v2_server_shadowsocks`;
CREATE TABLE `v2_server_shadowsocks` (
                                         `id` int(11) NOT NULL AUTO_INCREMENT,
                                         `group_id` varchar(255) NOT NULL,
                                         `parent_id` int(11) DEFAULT '0',
                                         `tags` varchar(255) DEFAULT NULL,
                                         `name` varchar(255) NOT NULL,
                                         `rate` varchar(11) NOT NULL,
                                         `host` varchar(255) NOT NULL,
                                         `port` int(11) NOT NULL,
                                         `server_port` int(11) NOT NULL,
                                         `cipher` varchar(255) NOT NULL,
                                         `show` tinyint(4) NOT NULL DEFAULT '0',
                                         `sort` int(11) DEFAULT NULL,
                                         `created_at` int(11) NOT NULL,
                                         `updated_at` int(11) NOT NULL,
                                         PRIMARY KEY (`id`),
                                         KEY `show` (`show`) USING BTREE,
                                         KEY `parent_id` (`parent_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
--  Table structure for `v2_server_stat`
-- ----------------------------
DROP TABLE IF EXISTS `v2_server_stat`;
CREATE TABLE `v2_server_stat` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `server_id` int(11) NOT NULL COMMENT '节点id',
                                  `server_type` char(11) NOT NULL COMMENT '节点类型',
                                  `u` varchar(255) NOT NULL,
                                  `d` varchar(255) NOT NULL,
                                  `record_type` char(1) NOT NULL COMMENT 'd day m month',
                                  `record_at` int(11) NOT NULL COMMENT '记录时间',
                                  `created_at` int(11) NOT NULL,
                                  `updated_at` int(11) NOT NULL,
                                  PRIMARY KEY (`id`),
                                  UNIQUE KEY `server_id_server_type_record_at` (`server_id`,`server_type`,`record_at`),
                                  KEY `record_at` (`record_at`),
                                  KEY `server_id` (`server_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='节点数据统计';

-- ----------------------------
--  Table structure for `v2_server_trojan`
-- ----------------------------
DROP TABLE IF EXISTS `v2_server_trojan`;
CREATE TABLE `v2_server_trojan` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '节点ID',
                                    `group_id` varchar(255) NOT NULL COMMENT '节点组',
                                    `parent_id` int(11) DEFAULT '0' COMMENT '父节点',
                                    `tags` varchar(255) DEFAULT NULL COMMENT '节点标签',
                                    `name` varchar(255) NOT NULL COMMENT '节点名称',
                                    `rate` varchar(11) NOT NULL COMMENT '倍率',
                                    `host` varchar(255) NOT NULL COMMENT '主机名',
                                    `port` int(11) NOT NULL COMMENT '连接端口',
                                    `server_port` int(11) NOT NULL COMMENT '服务端口',
                                    `allow_insecure` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否允许不安全',
                                    `server_name` varchar(255) DEFAULT NULL,
                                    `show` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示',
                                    `sort` int(11) DEFAULT NULL,
                                    `created_at` int(11) NOT NULL,
                                    `updated_at` int(11) NOT NULL,
                                    PRIMARY KEY (`id`),
                                    KEY `show` (`show`) USING BTREE,
                                    KEY `parent_id` (`parent_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='trojan伺服器表';

-- ----------------------------
--  Table structure for `v2_ticket`
-- ----------------------------
DROP TABLE IF EXISTS `v2_ticket`;
CREATE TABLE `v2_ticket` (
                             `id` int(11) NOT NULL AUTO_INCREMENT,
                             `user_id` int(11) NOT NULL,
                             `last_reply_user_id` int(11) NOT NULL,
                             `subject` varchar(255) NOT NULL,
                             `level` tinyint(1) NOT NULL,
                             `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:已开启 1:已关闭',
                             `created_at` int(11) NOT NULL,
                             `updated_at` int(11) NOT NULL,
                             PRIMARY KEY (`id`),
                             KEY `status` (`status`) USING BTREE,
                             KEY `user_id_creatd_at` (`user_id`,`created_at`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_ticket_message`
-- ----------------------------
DROP TABLE IF EXISTS `v2_ticket_message`;
CREATE TABLE `v2_ticket_message` (
                                     `id` int(11) NOT NULL AUTO_INCREMENT,
                                     `user_id` int(11) NOT NULL,
                                     `ticket_id` int(11) NOT NULL,
                                     `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                                     `created_at` int(11) NOT NULL,
                                     `updated_at` int(11) NOT NULL,
                                     PRIMARY KEY (`id`),
                                     KEY `user_id` (`user_id`) USING BTREE,
                                     KEY `ticket_id` (`ticket_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `v2_tutorial`
-- ----------------------------
DROP TABLE IF EXISTS `v2_tutorial`;
CREATE TABLE `v2_tutorial` (
                               `id` int(11) NOT NULL AUTO_INCREMENT,
                               `category_id` int(11) NOT NULL,
                               `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                               `steps` text,
                               `show` tinyint(1) NOT NULL DEFAULT '0',
                               `sort` int(11) DEFAULT NULL,
                               `created_at` int(11) NOT NULL,
                               `updated_at` int(11) NOT NULL,
                               PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
--  Table structure for `v2_user`
-- ----------------------------
DROP TABLE IF EXISTS `v2_user`;
CREATE TABLE `v2_user` (
                           `id` int(11) NOT NULL AUTO_INCREMENT,
                           `invite_user_id` int(11) DEFAULT '0',
                           `telegram_id` bigint(20) DEFAULT '0',
                           `email` varchar(64) NOT NULL,
                           `password` varchar(64) NOT NULL,
                           `password_algo` char(10) DEFAULT NULL,
                           `password_salt` char(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
                           `balance` int(11) DEFAULT '0',
                           `commission_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0: system 1: cycle 2: onetime',
                           `discount` int(11) DEFAULT NULL,
                           `commission_rate` int(11) DEFAULT NULL,
                           `commission_balance` int(11) DEFAULT '0',
                           `t` int(11) DEFAULT '0',
                           `u` bigint(20) DEFAULT '0',
                           `d` bigint(20) DEFAULT '0',
                           `transfer_enable` bigint(20) NOT NULL DEFAULT '0',
                           `banned` tinyint(1) NOT NULL DEFAULT '0',
                           `is_admin` tinyint(1) DEFAULT '0',
                           `last_login_at` int(11) DEFAULT NULL,
                           `is_staff` tinyint(1) DEFAULT '0',
                           `last_login_ip` int(11) DEFAULT NULL,
                           `uuid` varchar(36) NOT NULL,
                           `group_id` int(11) DEFAULT '0',
                           `plan_id` int(11) DEFAULT '0',
                           `remind_expire` tinyint(4) DEFAULT '1',
                           `remind_traffic` tinyint(4) DEFAULT '1',
                           `token` char(32) NOT NULL,
                           `expired_at` bigint(20) DEFAULT NULL,
                           `remarks` text,
                           `created_at` int(11) NOT NULL,
                           `updated_at` int(11) NOT NULL,
                           PRIMARY KEY (`id`),
                           UNIQUE KEY `email` (`email`),
                           KEY `expired_at` (`expired_at`) USING BTREE,
                           KEY `plan_id` (`plan_id`) USING BTREE,
                           KEY `group_id` (`group_id`) USING BTREE,
                           KEY `token` (`token`) USING BTREE,
                           KEY `password_email` (`password`,`email`) USING BTREE,
                           KEY `telegram_id` (`telegram_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
