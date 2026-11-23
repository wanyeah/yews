-- =============================================================
--  晚夜工作室 官网内容管理系统  数据库初始化文件
--  适用：MySQL 5.6+ / 5.7 / 8.0   MariaDB 5.5+
--  导入方式：宝塔面板 → 数据库 → 导入 / phpMyAdmin 导入
--  注意：文章正文不入库，只保存为服务器本地文件（data/posts/）
--        数据库中仅保存文章编号、标题、作者、IP 等元信息
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1. 文章（动态 / 通知）主表
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `isw_posts`;
CREATE TABLE `isw_posts` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '文章编号',
  `title`         VARCHAR(200) NOT NULL DEFAULT '' COMMENT '标题',
  `author`        VARCHAR(64)  NOT NULL DEFAULT '晚夜工作室' COMMENT '作者',
  `category`      VARCHAR(20)  NOT NULL DEFAULT 'news' COMMENT '分类：news动态 / notice通知',
  `summary`       VARCHAR(255) NOT NULL DEFAULT '' COMMENT '摘要（列表卡片展示）',
  `cover`         VARCHAR(255) NOT NULL DEFAULT '' COMMENT '封面图（可选）',
  `ip`            VARCHAR(45)  NOT NULL DEFAULT '' COMMENT '发布者IP',
  `status`        TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '状态：1已发布 0草稿',
  `views`         INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点击量',
  `comments`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '评论量',
  `published_at`  DATETIME     DEFAULT NULL COMMENT '发布时间',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_time` (`status`, `published_at`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章主表';

-- -------------------------------------------------------------
-- 2. 阅读明细表（防刷阅读量：同一设备 / 同一IP 对同一篇文章只计一次）
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `isw_post_views`;
CREATE TABLE `isw_post_views` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`       INT UNSIGNED NOT NULL COMMENT '文章编号',
  `visitor`       CHAR(32)     NOT NULL DEFAULT '' COMMENT '设备唯一标识（cookie生成，32位）',
  `ip`            VARCHAR(45)  NOT NULL DEFAULT '' COMMENT '访问者IP',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次阅读时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_post_visitor` (`post_id`, `visitor`),
  KEY `idx_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='阅读明细（防刷）';

-- -------------------------------------------------------------
-- 3. 评论表
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `isw_comments`;
CREATE TABLE `isw_comments` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id`       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '文章编号',
  `author`        VARCHAR(64)  NOT NULL DEFAULT '' COMMENT '评论人昵称',
  `content`       VARCHAR(1000) NOT NULL DEFAULT '' COMMENT '评论内容',
  `ip`            VARCHAR(45)  NOT NULL DEFAULT '' COMMENT '评论者IP',
  `status`        TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '状态：1显示 0待审核/隐藏',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '评论时间',
  PRIMARY KEY (`id`),
  KEY `idx_post_status` (`post_id`, `status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评论表';

-- -------------------------------------------------------------
-- 4. 站点配置表（key / value）
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `isw_settings`;
CREATE TABLE `isw_settings` (
  `skey`   VARCHAR(50)  NOT NULL COMMENT '配置键',
  `svalue` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '配置值',
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='站点配置';

INSERT INTO `isw_settings` (`skey`, `svalue`) VALUES
  ('site_name',   '晚夜工作室'),
  ('site_slogan', '做好量变的准备 促进事物的质变'),
  ('icp',         '苏ICP备2026005148号-1'),
  ('enable_comment', '1'),
  ('page_size',   '9');

-- -------------------------------------------------------------
-- 5. 后台操作日志（登录 / 发布 / 删除 等）
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `isw_logs`;
CREATE TABLE `isw_logs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `action`     VARCHAR(100) NOT NULL DEFAULT '' COMMENT '操作类型',
  `detail`     VARCHAR(255) NOT NULL DEFAULT '' COMMENT '操作详情',
  `ip`         VARCHAR(45)  NOT NULL DEFAULT '' COMMENT '操作者IP',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台操作日志';

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
--  示例数据（如不需要，可删除下面整段）
--  注意：示例文章对应的正文文件位于 data/posts/1.html / 1.md
-- =============================================================
INSERT INTO `isw_posts` (`id`, `title`, `author`, `category`, `summary`, `ip`, `status`, `views`, `comments`, `published_at`, `created_at`) VALUES
(1, '晚夜工作室官网正式上线', '晚夜工作室', 'news', '历经数月打磨，晚夜工作室官网正式上线，我们坚持以量变促质变，持续输出稳定可靠的作品。', '127.0.0.1', 1, 0, 0, NOW(), NOW()),
(2, '关于「量变与质变」的一点思考', '晚夜工作室', 'news', '所有的质变，都来自漫长而枯燥的量变积累。这是我们写在墙上的一句话，也是我们做事的方法论。', '127.0.0.1', 1, 0, 0, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, '站点维护通知', '晚夜工作室', 'notice', '本站将于本周日凌晨进行例行维护，期间可能出现短暂无法访问的情况，敬请谅解。', '127.0.0.1', 1, 0, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));
