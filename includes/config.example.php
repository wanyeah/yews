<?php
/**
 * 晚夜计算机科技工作室 官网 —— 站点配置示例文件
 * ---------------------------------------------------------------
 * 使用方法：复制本文件为 config.php，然后按实际情况修改
 * 「数据库配置」与「管理员账号」两部分。
 *
 * 注意：config.php 含敏感信息，已加入 .gitignore，不会被提交到代码仓库。
 * 兼容 PHP 7.0 ~ PHP 8.x（已在 PHP 7.3 / 7.4 环境验证）
 */

// ============ 1. 站点基本信息 ============
define('SITE_NAME',    '晚夜计算机科技工作室');
define('SITE_SLOGAN',  '做好量变的准备 促进事物的质变');
define('SITE_ICP',     '苏ICP备2026005148号-1');
define('SITE_DOMAIN',  'www.iswanye.cn');
define('SITE_COPYRIGHT_YEAR', '2026');

// ============ 2. 数据库配置（按实际情况填写）============
define('DB_HOST',    '127.0.0.1');
define('DB_PORT',    '3306');
define('DB_NAME',    'iswanye');       // 数据库名
define('DB_USER',    'iswanye');       // 数据库用户名
define('DB_PASS',    '请填写数据库密码'); // 数据库密码
define('DB_CHARSET', 'utf8mb4');

// ============ 3. 后台管理员账号（直接在此定义）============
// 如需修改密码，把 ADMIN_PASS 改成自己的密码即可（明文，方便你自己改）
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', '请填写后台密码');
// 可选：若想使用加密存储，用下面这行替换上面的 ADMIN_PASS 定义
// 生成方式：php -r "echo password_hash('你的密码', PASSWORD_BCRYPT);"
// define('ADMIN_PASS_HASH', '$2y$10$..............................................');

// ============ 4. 物理路径 ============
define('ROOT_PATH',   dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
define('DATA_PATH',   ROOT_PATH . 'data' . DIRECTORY_SEPARATOR . 'posts' . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL',  '/uploads/');

// ============ 5. 站点 URL（自动推导，一般无需修改）============

/**
 * HTTPS 开关
 * ---------------------------------------------------------------
 * 服务器已部署 SSL、但 PHP 取不到 HTTPS 标记时（常见于宝塔 Nginx 未向 PHP 传递
 * 该参数、或前面挂了 CDN 回源为 http），站内资源的绝对地址会被拼成 http://，
 * 浏览器按「混合内容」策略直接拦截，表现就是：CSS/JS 单独打开正常，页面上全失效。
 *
 *   true  = 强制站内链接使用 https（正式 https 站点请保持 true）
 *   false = 交给下面的服务器环境自动推断（本地 http 预览时可改为 false）
 *
 * 说明：localhost / 127.0.0.1 / 内网 IP 的访问会自动使用 http，
 *       无需为此在本地预览时来回切换本开关。
 */
define('SITE_FORCE_HTTPS', true);

$__host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : SITE_DOMAIN;
$__host = preg_replace('/[^a-zA-Z0-9._:\-]/', '', $__host);

// 本地/内网主机一律走 http，其余主机按开关强制 https
$__local = (bool)preg_match('/^(localhost|127\.|\[::1\]|192\.168\.|10\.)/i', $__host);
$__auto  = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$__https = (SITE_FORCE_HTTPS && !$__local) ? true : $__auto;

define('BASE_URL', ($__https ? 'https://' : 'http://') . $__host . '/');
unset($__https, $__auto, $__local, $__host);
