<?php
/**
 * 企业简约官网 —— 一键安装程序
 * ---------------------------------------------------------------
 * 使用方式：把整个程序上传到服务器后，浏览器访问 /install/ 按提示填写即可。
 * 安装完成后会自动生成 install/install.lock，再次访问本程序将被拒绝。
 * 兼容 PHP 7.0 ~ PHP 8.x（已在 PHP 7.3 环境验证）
 *
 * 安装过程会依次写入：
 *   1) 数据库：建表 + 站点配置（isw_settings）+ 可选示例文章
 *   2) includes/config.php：站点信息、数据库账号、管理员账号
 *   3) install/install.lock：安装锁
 */

define('ISW_INSTALL', true);

$selfDir   = dirname(__FILE__) . DIRECTORY_SEPARATOR;
$rootPath  = $selfDir . '..' . DIRECTORY_SEPARATOR;
$cfgFile   = $rootPath . 'includes' . DIRECTORY_SEPARATOR . 'config.php';
$sqlFile   = $selfDir . 'database.sql';
$lockFile  = $selfDir . 'install.lock';
$dataDir   = $rootPath . 'data' . DIRECTORY_SEPARATOR . 'posts' . DIRECTORY_SEPARATOR;
$uploadDir = $rootPath . 'uploads' . DIRECTORY_SEPARATOR;

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');

// PHP 未配置时区时兜底，避免安装时间记录成 UTC
if (!ini_get('date.timezone')) {
    @date_default_timezone_set('Asia/Shanghai');
}

// ---------------------------------------------------------------- 工具函数

/** HTML 转义 */
function h($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/** 生成 PHP 字符串字面量（单引号，自动转义 \ 与 '） */
function php_lit($str)
{
    return "'" . str_replace(array('\\', "'"), array('\\\\', "\\'"), (string)$str) . "'";
}

/** 目录是否可写（不存在时尝试创建） */
function dir_writable($dir)
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return is_dir($dir) && is_writable($dir);
}

/** 文件是否可写（不存在时看所在目录） */
function file_writable($file)
{
    if (is_file($file)) return is_writable($file);
    return is_writable(dirname($file));
}

/**
 * 把 SQL 脚本切分为单条语句
 * 已处理：-- 行注释、# 行注释、字符串/反引号内的分号
 */
function split_sql($sql)
{
    $out = array();
    $buf = '';
    $len = strlen($sql);
    $inStr = false;
    $quote = '';
    $inComment = false;

    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        $n = ($i + 1 < $len) ? $sql[$i + 1] : '';

        if ($inComment) {
            if ($c === "\n") {
                $inComment = false;
                $buf .= "\n";
            }
            continue;
        }

        if (!$inStr) {
            if (($c === '-' && $n === '-') || $c === '#') {
                $inComment = true;
                $i++;
                continue;
            }
            if ($c === '"' || $c === "'" || $c === '`') {
                $inStr = true;
                $quote = $c;
                $buf .= $c;
                continue;
            }
            if ($c === ';') {
                if (trim($buf) !== '') $out[] = trim($buf);
                $buf = '';
                continue;
            }
            $buf .= $c;
            continue;
        }

        $buf .= $c;
        if ($c === '\\') {
            $buf .= $n;
            $i++;
            continue;
        }
        if ($c === $quote) {
            $inStr = false;
        }
    }
    if (trim($buf) !== '') $out[] = trim($buf);
    return $out;
}

/** 用 Parsedown 渲染一份 Markdown（安装期专用，不依赖站点配置） */
function install_render_md($md, $id)
{
    static $pd = null;
    if ($pd === null) {
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                   . 'includes' . DIRECTORY_SEPARATOR . 'Parsedown.php';
        $pd = new Parsedown();
        $pd->setSafeMode(true);
        $pd->setBreaksEnabled(true);
    }
    // 站点版本 >= PHP 7.2 时 suppress 可能的弃用告警
    return $pd->text((string)$md);
}

/**
 * 探测站点是否应该启用 HTTPS
 * ---------------------------------------------------------------
 * 依次用三种方式判断，任一命中即建议开启：
 *   1) 当前安装页面本身就是 https 打开的
 *   2) 服务器能从本机连上 443 端口（说明很可能已部署 SSL）
 *   3) 已有配置里明确打开过 SITE_FORCE_HTTPS
 * 这一步只影响表单默认勾选，用户仍可手动更改。
 *
 * @return array{https:bool,reason:string}
 */
function detect_https_support()
{
    $req = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    if ($req) {
        $viaCdn = empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) === 'off';
        if ($viaCdn && (!isset($_SERVER['SERVER_PORT']) || (int)$_SERVER['SERVER_PORT'] !== 443)) {
            return array('https' => true, 'reason' => '检测到反向代理 / CDN 声明了 HTTPS 来源');
        }
        return array('https' => true, 'reason' => '当前安装页面本身就是通过 HTTPS 打开的');
    }

    $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
    $host = preg_replace('/[^a-zA-Z0-9._:\-]/', '', $host);
    $host = preg_replace('/:\d+$/', '', $host);

    if ($host === '' || preg_match('/^(localhost|127\.|\[::1\]|192\.168\.|10\.)/i', $host)) {
        return array('https' => false, 'reason' => '本地 / 内网环境，按 HTTP 处理');
    }

    if (function_exists('fsockopen')) {
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, 443, $errno, $errstr, 2);
        if ($fp) {
            fclose($fp);
            return array('https' => true, 'reason' => '检测到服务器 443 端口已开放，疑似已部署 SSL');
        }
    }

    return array('https' => false, 'reason' => '未检测到 HTTPS 环境，疑似纯 HTTP 站点');
}

/** 载入已存在的配置文件，用于表单默认值 */
function load_existing_config($cfgFile)
{
    if (is_file($cfgFile)) {
        require_once $cfgFile;
    }
}

// ---------------------------------------------------------------- 锁检测

if (is_file($lockFile)) {
    $lockText = trim((string)@file_get_contents($lockFile));
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>已安装 - 晚夜工作室官网</title>
    <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <div class="wrap">
        <div class="card lock-card">
            <div class="state-icon state-done">✓</div>
            <h1>本站已完成安装</h1>
            <p class="lead">检测到安装锁文件 <code>install/install.lock</code>，为防止覆盖已有数据，安装程序已被禁用。</p>
            <?php if ($lockText !== '') { ?>
            <div class="lock-info"><?php echo h($lockText); ?></div>
            <?php } ?>
            <div class="btn-row">
                <a class="btn btn-primary" href="../">访问网站首页</a>
                <a class="btn btn-ghost" href="../admin/">进入后台管理</a>
            </div>
            <div class="tips">
                <strong>提示</strong>
                <ul>
                    <li>如需重新安装，请先删除 <code>install/install.lock</code>（务必确认已备份数据库与 <code>data/posts/</code> 下的文章文件）。</li>
                    <li>出于安全考虑，建议安装成功后直接删除整个 <code>install</code> 目录。</li>
                    <li>忘记后台密码时，重新运行安装会覆盖数据，请谨慎操作；也可以直接编辑 <code>includes/config.php</code> 修改账号。</li>
                </ul>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// ---------------------------------------------------------------- 默认值

$prefill = array();
load_existing_config($cfgFile);
if (defined('DB_HOST'))   $prefill['db_host'] = DB_HOST;
if (defined('DB_PORT'))   $prefill['db_port'] = DB_PORT;
if (defined('DB_NAME'))   $prefill['db_name'] = DB_NAME;
if (defined('DB_USER'))   $prefill['db_user'] = DB_USER;
if (defined('SITE_NAME')) $prefill['site_name'] = SITE_NAME;
if (defined('SITE_SLOGAN'))    $prefill['site_slogan'] = SITE_SLOGAN;
if (defined('SITE_ICP'))       $prefill['icp'] = SITE_ICP;
if (defined('SITE_DOMAIN'))    $prefill['domain'] = SITE_DOMAIN;
if (defined('SITE_COPYRIGHT_YEAR')) $prefill['year'] = SITE_COPYRIGHT_YEAR;
// 已装过且明确配置过 HTTPS 开关时，沿用原配置
if (defined('SITE_FORCE_HTTPS'))   $prefill['force_https'] = SITE_FORCE_HTTPS ? '1' : '0';

// 自动探测 SSL，仅用于表单默认值，用户仍可手动勾选 / 取消
$httpsProbe  = detect_https_support();
$httpsReason = $httpsProbe['reason'];

$defaults = array_merge(array(
    'db_host'        => '127.0.0.1',
    'db_port'        => '3306',
    'db_name'        => 'iswanye',
    'db_user'        => '',
    'db_pass'        => '',
    'admin_user'     => 'admin',
    'admin_pass'     => '',
    'admin_pass2'    => '',
    'hash_pass'      => '1',
    'site_name'      => '晚夜计算机科技工作室',
    'site_slogan'    => '做好量变的准备 促进事物的质变',
    'icp'            => '苏ICP备2026005148号-1',
    'domain'         => 'www.iswanye.cn',
    'year'           => '2026',
    'page_size'      => '9',
    'enable_comment' => '1',
    'force_https'    => ($httpsProbe['https'] ? '1' : '0'),
    'demo'           => '1',
    'agree'          => '',
), $prefill);

$errors = array();
$step   = '1';
if (isset($_GET['step'])) {
    $step = trim((string)$_GET['step']);
}
if ($step !== '1' && $step !== '2' && $step !== '3') {
    $step = '1';
}

$vals  = $defaults;
$writeConfigFallback = '';   // 配置文件不可写时，交给用户手动上传
$installedInfo = array();

// ---------------------------------------------------------------- 表单取值

function field($key)
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : null;
}

// ---------------------------------------------------------------- 执行安装

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '2') {
    foreach ($defaults as $k => $v) {
        $posted = field($k);
        if ($posted !== null) $vals[$k] = $posted;
    }
    $vals['hash_pass']      = isset($_POST['hash_pass']) ? '1' : '0';
    $vals['enable_comment'] = isset($_POST['enable_comment']) ? '1' : '0';
    $vals['force_https']    = isset($_POST['force_https']) ? '1' : '0';
    $vals['demo']           = isset($_POST['demo']) ? '1' : '0';
    $vals['agree']          = isset($_POST['agree']) ? '1' : '0';

    // --- 校验 ---
    if ($vals['db_host'] === '')              $errors[] = '请填写数据库地址。';
    if (!preg_match('/^\d{2,5}$/', $vals['db_port'])) $errors[] = '数据库端口必须是数字（常见为 3306）。';
    if ($vals['db_name'] === '')              $errors[] = '请填写数据库名。';
    if (!preg_match('/^[\p{L}\p{N}_\-\.]+$/u', $vals['db_name'])) $errors[] = '数据库名只能包含字母、数字、下划线、短横线和点。';
    if ($vals['db_user'] === '')              $errors[] = '请填写数据库用户名。';
    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $vals['admin_user'])) $errors[] = '管理员用户名需为 3~20 位字母、数字或下划线。';
    if (strlen($vals['admin_pass']) < 8)      $errors[] = '管理员密码至少需要 8 位。';
    if ($vals['admin_pass'] !== $vals['admin_pass2']) $errors[] = '两次输入的密码不一致。';
    if ($vals['site_name'] === '')            $errors[] = '请填写站点名称。';
    if ($vals['site_slogan'] === '')          $errors[] = '请填写企业口号。';
    if (!preg_match('/^[A-Za-z0-9.\-:]+$/', $vals['domain'])) $errors[] = '网站域名格式不正确（示例：www.iswanye.cn）。';
    if (!preg_match('/^\d{4}$/', $vals['year']))  $errors[] = '版权年份需为 4 位数字。';
    $pageSize = (int)$vals['page_size'];
    if ($pageSize < 3 || $pageSize > 30)      $errors[] = '每页显示条数需在 3 ~ 30 之间。';
    if ($vals['agree'] !== '1')               $errors[] = '请勾选「已了解安装会覆盖同名数据表」后再继续。';
    if ($vals['hash_pass'] === '1' && !function_exists('password_hash')) $errors[] = '当前 PHP 不支持 password_hash()，请取消密码加密选项。';

    // --- 开始安装 ---
    if (empty($errors)) {
        $pdo = null;
        try {
            // 1) 连接数据库（先不带库名，便于自动建库）
            $opts = array(
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            );
            $portPart = ';port=' . (int)$vals['db_port'];
            try {
                $pdo = new PDO('mysql:host=' . $vals['db_host'] . $portPart . ';charset=utf8mb4',
                    $vals['db_user'], $vals['db_pass'], $opts);
                $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $vals['db_name'] . '` '
                         . 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
                $pdo->exec('USE `' . $vals['db_name'] . '`');
            } catch (Exception $e1) {
                // 无建库权限时，直接连接已存在的库
                $pdo = new PDO('mysql:host=' . $vals['db_host'] . $portPart . ';dbname=' . $vals['db_name'] . ';charset=utf8mb4',
                    $vals['db_user'], $vals['db_pass'], $opts);
            }
            $pdo->exec('SET NAMES utf8mb4');

            // 2) 建表
            if (!is_file($sqlFile)) {
                throw new Exception('未找到数据库结构文件 install/database.sql，请确认文件已上传完整。');
            }
            $statements = split_sql((string)file_get_contents($sqlFile));
            $demoSkipped = 0;
            foreach ($statements as $st) {
                $head = strtoupper(substr(ltrim($st), 0, 120));
                // 示例文章数据可选
                if (strpos($head, 'INSERT INTO `ISW_POSTS`') === 0 && $vals['demo'] !== '1') {
                    $demoSkipped++;
                    continue;
                }
                if (strpos($head, 'INSERT INTO `ISW_SETTINGS`') === 0) {
                    continue; // 设置表由下方按用户填写的内容写入
                }
                $pdo->exec($st);
            }

            // 3) 写入站点配置
            $settings = array(
                'site_name'      => $vals['site_name'],
                'site_slogan'    => $vals['site_slogan'],
                'icp'            => $vals['icp'],
                'enable_comment' => $vals['enable_comment'],
                'page_size'      => (string)$pageSize,
                'installed_at'   => date('Y-m-d H:i:s'),
            );
            $stmt = $pdo->prepare('INSERT INTO `isw_settings` (`skey`, `svalue`) VALUES (?, ?)
                                   ON DUPLICATE KEY UPDATE `svalue` = VALUES(`svalue`)');
            foreach ($settings as $k => $v) {
                $stmt->execute(array($k, $v));
            }

            // 4) 示例文章正文文件（缺失时按标题与摘要补一份）
            $demoPosts = array();
            if ($vals['demo'] === '1') {
                if (!dir_writable($dataDir)) {
                    $errors[] = '文章正文目录 data/posts 不可写，示例文章正文未生成，请检查目录权限后到后台重新保存文章。';
                } else {
                    $rows = $pdo->query('SELECT id, title, summary FROM `isw_posts` ORDER BY id ASC')->fetchAll();
                    foreach ($rows as $row) {
                        $id    = (int)$row['id'];
                        $mdF   = $dataDir . $id . '.md';
                        $htmlF = $dataDir . $id . '.html';
                        $demoPosts[] = array('id' => $id, 'title' => $row['title']);
                        if (is_file($mdF) && is_file($htmlF)) continue;
                        if (!is_file($mdF)) {
                            $md = '# ' . $row['title'] . "\n\n"
                                . ($row['summary'] !== '' ? '> ' . $row['summary'] . "\n\n" : '')
                                . "这里是《" . $row['title'] . "》的正文内容。\n\n"
                                . "示例文章仅用于展示排版效果，登录后台即可编辑或删除本文，正文会重新保存为本地文件。\n\n"
                                . "## 小标题示例\n\n"
                                . "- 列表项一\n- 列表项二\n- 列表项三\n\n"
                                . "正文采用 Markdown 书写，支持标题、列表、引用、代码块等常用语法。\n";
                            @file_put_contents($mdF, $md);
                        }
                        $md = (string)@file_get_contents($mdF);
                        @file_put_contents($htmlF,
                            '<div class="post-body markdown-body">' . "\n"
                            . install_render_md($md, $id)
                            . "\n" . '</div><!-- /.post-body -->' . "\n");
                    }
                }
            }

            // 5) 生成配置文件
            $now = date('Y-m-d H:i:s');
            $adminLine = ($vals['hash_pass'] === '1')
                ? "define('ADMIN_PASS', '');          // 密码已加密存储，留空\n"
                  . "// 加密存储：下方由安装程序生成，修改密码请替换下方整行\n"
                  . "define('ADMIN_PASS_HASH', " . php_lit(password_hash($vals['admin_pass'], PASSWORD_BCRYPT)) . ");"
                : "define('ADMIN_PASS', " . php_lit($vals['admin_pass']) . ");\n"
                  . "// 可选：若想使用加密存储，用下面这行替换上面的 ADMIN_PASS 定义\n"
                  . "// 生成方式：php -r \"echo password_hash('你的密码', PASSWORD_BCRYPT);\"\n"
                  . "// define('ADMIN_PASS_HASH', '\$2y\$10\$..............................................');";

            $cfg = "<?php\n"
                . "/**\n"
                . " * 晚夜计算机科技工作室 官网 —— 站点配置文件\n"
                . " * ---------------------------------------------------------------\n"
                . " * 本文件由 install/index.php 于 " . $now . " 自动生成\n"
                . " * 后续如需调整，直接修改本文件对应常量即可（数据库配置与管理员账号在下方）\n"
                . " * 兼容 PHP 7.0 ~ PHP 8.x（已在 PHP 7.3 环境验证）\n"
                . " */\n\n"
                . "// ============ 1. 站点基本信息 ============\n"
                . "define('SITE_NAME',            " . php_lit($vals['site_name']) . ");\n"
                . "define('SITE_SLOGAN',          " . php_lit($vals['site_slogan']) . ");\n"
                . "define('SITE_ICP',             " . php_lit($vals['icp']) . ");\n"
                . "define('SITE_DOMAIN',          " . php_lit($vals['domain']) . ");\n"
                . "define('SITE_COPYRIGHT_YEAR',  " . php_lit($vals['year']) . ");\n\n"
                . "// ============ 2. 数据库配置 ============\n"
                . "define('DB_HOST',    " . php_lit($vals['db_host']) . ");\n"
                . "define('DB_PORT',    " . php_lit($vals['db_port']) . ");\n"
                . "define('DB_NAME',    " . php_lit($vals['db_name']) . ");   // 数据库名\n"
                . "define('DB_USER',    " . php_lit($vals['db_user']) . ");   // 数据库用户名\n"
                . "define('DB_PASS',    " . php_lit($vals['db_pass']) . ");   // 数据库密码\n"
                . "define('DB_CHARSET', 'utf8mb4');\n\n"
                . "// ============ 3. 后台管理员账号 ============\n"
                . "define('ADMIN_USER', " . php_lit($vals['admin_user']) . ");\n"
                . $adminLine . "\n\n"
                . "// ============ 4. 物理路径 ============\n"
                . "define('ROOT_PATH',   dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);\n"
                . "define('DATA_PATH',   ROOT_PATH . 'data' . DIRECTORY_SEPARATOR . 'posts' . DIRECTORY_SEPARATOR);\n"
                . "define('UPLOAD_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);\n"
                . "define('UPLOAD_URL',  '/uploads/');\n\n"
                . "// ============ 5. 站点 URL（HTTPS 开关按安装时的选择生成）============\n"
                . "define('SITE_FORCE_HTTPS', " . (($vals['force_https'] === '1') ? 'true' : 'false') . ");\n\n"
                . "\$__host  = isset(\$_SERVER['HTTP_HOST']) ? \$_SERVER['HTTP_HOST'] : SITE_DOMAIN;\n"
                . "\$__host  = preg_replace('/[^a-zA-Z0-9._:\\-]/', '', \$__host);\n\n"
                . "// 本地/内网主机一律走 http，其余主机按开关强制 https\n"
                . "\$__local = (bool)preg_match('/^(localhost|127\\.|\\[::1\\]|192\\.168\\.|10\\.)/i', \$__host);\n"
                . "\$__auto  = (!empty(\$_SERVER['HTTPS']) && strtolower(\$_SERVER['HTTPS']) !== 'off')\n"
                . "        || (isset(\$_SERVER['SERVER_PORT']) && (int)\$_SERVER['SERVER_PORT'] === 443)\n"
                . "        || (isset(\$_SERVER['HTTP_X_FORWARDED_PROTO']) && \$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');\n"
                . "\$__https = (SITE_FORCE_HTTPS && !\$__local) ? true : \$__auto;\n\n"
                . "define('BASE_URL', (\$__https ? 'https://' : 'http://') . \$__host . '/');\n"
                . "unset(\$__https, \$__auto, \$__local, \$__host);\n";

            if (@file_put_contents($cfgFile, $cfg) === false) {
                $writeConfigFallback = $cfg;
                throw new Exception('无法写入 includes/config.php，请检查该文件或 includes 目录的权限（建议 644 / 目录 755，必要时临时 777）。');
            }
            @chmod($cfgFile, 0644);

            // 6) 生成安装锁
            $lockBody = "晚夜工作室官网 安装锁\n"
                . "安装时间：" . $now . "\n"
                . "站点名称：" . $vals['site_name'] . "\n"
                . "站点域名：" . $vals['domain'] . "\n"
                . "如需重新安装，请先删除本文件（install/install.lock）。";
            if (@file_put_contents($lockFile, $lockBody) === false) {
                $errors[] = '安装已完成，但写入 install/install.lock 失败，请手动在该目录创建此文件以免被重复安装。';
            }

            $installedInfo = array(
                'site_name'   => $vals['site_name'],
                'site_slogan' => $vals['site_slogan'],
                'icp'         => $vals['icp'],
                'domain'      => $vals['domain'],
                'year'        => $vals['year'],
                'admin_user'  => $vals['admin_user'],
                'demo'        => $vals['demo'],
                'demo_posts'  => $demoPosts,
                'tables'      => array('isw_posts', 'isw_post_views', 'isw_comments', 'isw_settings', 'isw_logs'),
            );
            $step = '3';

        } catch (Exception $ex) {
            $errors[] = '安装失败：' . $ex->getMessage();
        }
    }
}

// ---------------------------------------------------------------- 环境检测

$phpOk      = version_compare(PHP_VERSION, '7.0.0', '>=');
$pdoOk      = class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers(), true);
$gdOk       = function_exists('imagecreatetruecolor');
$mbOk       = function_exists('mb_substr');
$cfgOk      = file_writable($cfgFile);
$dataOk     = dir_writable($dataDir);
$uploadOk   = dir_writable($uploadDir);
$lockOk     = dir_writable($selfDir);
$sqlOk      = is_file($sqlFile);

$checks = array(
    array('PHP 版本', PHP_VERSION, $phpOk ? 'ok' : 'bad', '要求 7.0 及以上，推荐 7.3 / 7.4'),
    array('PDO MySQL 扩展', $pdoOk ? '已启用' : '未启用', $pdoOk ? 'ok' : 'bad', '必需：程序通过 PDO 操作数据库'),
    array('GD 扩展', $gdOk ? '已启用' : '未启用', $gdOk ? 'ok' : 'warn', '推荐：后台验证码依赖此扩展'),
    array('mbstring 扩展', $mbOk ? '已启用' : '未启用', $mbOk ? 'ok' : 'warn', '推荐：中文摘要截断更精确，缺失时有降级方案'),
    array('数据库结构文件', $sqlOk ? 'install/database.sql 已找到' : '缺失', $sqlOk ? 'ok' : 'bad', '缺失时无法自动建表'),
    array('配置文件可写', $cfgOk ? 'includes/config.php 可写' : 'includes/config.php 不可写', $cfgOk ? 'ok' : 'bad', '安装时会重写此文件'),
    array('正文目录可写', $dataOk ? 'data/posts 可写' : 'data/posts 不可写', $dataOk ? 'ok' : 'warn', '文章正文保存于此，必须可写'),
    array('上传目录可写', $uploadOk ? 'uploads 可写' : 'uploads 不可写', $uploadOk ? 'ok' : 'warn', '编辑器图片上传使用'),
    array('安装目录可写', $lockOk ? 'install 可写' : 'install 不可写', $lockOk ? 'ok' : 'warn', '用于写入 install.lock'),
);

$blocked = !($phpOk && $pdoOk && $cfgOk && $sqlOk);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>安装向导 - 晚夜工作室官网</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
    <div class="head">
        <div class="brand"><span class="mark"></span>晚夜工作室</div>
        <div class="head-sub">官方网站 · 安装向导</div>
    </div>

    <ol class="steps">
        <li class="<?php echo ($step === '1') ? 'on' : (($step === '2' || $step === '3') ? 'done' : ''); ?>"><i>1</i>环境检测</li>
        <li class="<?php echo ($step === '2') ? 'on' : (($step === '3') ? 'done' : ''); ?>"><i>2</i>填写配置</li>
        <li class="<?php echo ($step === '3') ? 'on' : ''; ?>"><i>3</i>安装完成</li>
    </ol>

    <div class="card">

    <?php if ($step === '1') { ?>

        <h1>第一步 · 运行环境检测</h1>
        <p class="lead">程序会检测运行所需的扩展与目录权限，全部就绪后即可进入下一步。</p>

        <table class="check">
            <tbody>
            <?php foreach ($checks as $c) { ?>
                <tr>
                    <th><?php echo h($c[0]); ?></th>
                    <td class="v"><?php echo h($c[1]); ?></td>
                    <td class="s s-<?php echo h($c[2]); ?>"><?php echo ($c[2] === 'ok') ? '正常' : (($c[2] === 'warn') ? '建议处理' : '不可用'); ?></td>
                    <td class="tip"><?php echo h($c[3]); ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>

        <?php if ($blocked) { ?>
            <div class="alert alert-bad">存在必需项未通过，请先按上表修复后再继续安装。</div>
            <div class="btn-row"><a class="btn btn-ghost" href="?step=1">重新检测</a></div>
        <?php } else { ?>
            <div class="alert alert-ok">环境检测通过，可以开始安装。</div>
            <div class="btn-row"><a class="btn btn-primary" href="?step=2">下一步：填写配置</a></div>
        <?php } ?>

    <?php } elseif ($step === '2') { ?>

        <h1>第二步 · 填写站点信息</h1>
        <p class="lead">以下内容将写入 <code>includes/config.php</code> 与数据库 <code>isw_settings</code> 表，安装后仍可在后台「站点设置」中修改。</p>

        <?php if (!empty($errors)) { ?>
            <div class="alert alert-bad">
                <ul><?php foreach ($errors as $err) { echo '<li>' . h($err) . '</li>'; } ?></ul>
            </div>
        <?php } ?>

        <?php if ($writeConfigFallback !== '') { ?>
            <div class="alert alert-warn">
                <strong>配置文件无法自动写入，请手动处理：</strong>
                <div>在 <code>includes/</code> 目录下新建 <code>config.php</code>，把下面内容完整复制进去后再回到本页重试。</div>
                <textarea class="code" rows="10" readonly onclick="this.select()"><?php echo h($writeConfigFallback); ?></textarea>
            </div>
        <?php } ?>

        <form method="post" action="?step=2">
            <h2 class="sec">数据库信息</h2>
            <div class="grid">
                <label class="fi"><span>数据库地址 <em>*</em></span>
                    <input type="text" name="db_host" value="<?php echo h($vals['db_host']); ?>" placeholder="127.0.0.1" autocomplete="off">
                    <i class="hint">一般填 127.0.0.1 或 localhost</i></label>
                <label class="fi"><span>端口 <em>*</em></span>
                    <input type="text" name="db_port" value="<?php echo h($vals['db_port']); ?>" placeholder="3306" autocomplete="off"></label>
                <label class="fi"><span>数据库名 <em>*</em></span>
                    <input type="text" name="db_name" value="<?php echo h($vals['db_name']); ?>" placeholder="iswanye" autocomplete="off">
                    <i class="hint">不存在时程序会尝试自动创建</i></label>
                <label class="fi"><span>数据库用户名 <em>*</em></span>
                    <input type="text" name="db_user" value="<?php echo h($vals['db_user']); ?>" autocomplete="off"></label>
                <label class="fi"><span>数据库密码</span>
                    <input type="password" name="db_pass" value="<?php echo h($vals['db_pass']); ?>" autocomplete="new-password"></label>
                <div class="fi"><span>&nbsp;</span><i class="hint">宝塔面板可在「数据库」页面查看以上信息</i></div>
            </div>

            <h2 class="sec">后台管理员</h2>
            <div class="grid">
                <label class="fi"><span>管理员账号 <em>*</em></span>
                    <input type="text" name="admin_user" value="<?php echo h($vals['admin_user']); ?>" autocomplete="off">
                    <i class="hint">3~20 位字母、数字或下划线</i></label>
                <label class="fi"><span>登录密码 <em>*</em></span>
                    <input type="password" name="admin_pass" value="<?php echo h($vals['admin_pass']); ?>" autocomplete="new-password">
                    <i class="hint">至少 8 位，请务必牢记</i></label>
                <label class="fi"><span>确认密码 <em>*</em></span>
                    <input type="password" name="admin_pass2" value="<?php echo h($vals['admin_pass2']); ?>" autocomplete="new-password"></label>
                <div class="fi"><span>&nbsp;</span>
                    <label class="ck"><input type="checkbox" name="hash_pass" value="1" <?php echo ($vals['hash_pass'] === '1') ? 'checked' : ''; ?>> 密码加密存储（推荐）</label>
                    <i class="hint">加密后配置文件中不保存明文密码</i></div>
            </div>

            <h2 class="sec">站点信息</h2>
            <div class="grid">
                <label class="fi"><span>网站名称 <em>*</em></span>
                    <input type="text" name="site_name" value="<?php echo h($vals['site_name']); ?>" autocomplete="off"></label>
                <label class="fi"><span>企业口号 <em>*</em></span>
                    <input type="text" name="site_slogan" value="<?php echo h($vals['site_slogan']); ?>" autocomplete="off"></label>
                <label class="fi"><span>网站域名 <em>*</em></span>
                    <input type="text" name="domain" value="<?php echo h($vals['domain']); ?>" autocomplete="off">
                    <i class="hint">不含 http://，如 www.iswanye.cn</i></label>
                <label class="fi"><span>ICP 备案号</span>
                    <input type="text" name="icp" value="<?php echo h($vals['icp']); ?>" autocomplete="off">
                    <i class="hint">显示在页脚，留空则不显示</i></label>
                <label class="fi"><span>版权年份</span>
                    <input type="text" name="year" value="<?php echo h($vals['year']); ?>" autocomplete="off"></label>
                <label class="fi"><span>动态页每页条数</span>
                    <input type="number" name="page_size" min="3" max="30" value="<?php echo h($vals['page_size']); ?>"></label>
                <div class="fi"><span>站点 HTTPS</span>
                    <label class="ck"><input type="checkbox" name="force_https" value="1" <?php echo ($vals['force_https'] === '1') ? 'checked' : ''; ?>> 启用 HTTPS（站内所有链接强制走 https）</label>
                    <i class="hint">检测结果：<?php echo h($httpsReason); ?>。已部署 SSL 请勾选，否则 CSS / JS 会被浏览器按混合内容拦截而失效</i></div>
            </div>

            <h2 class="sec">初始数据</h2>
            <div class="opts">
                <label class="ck"><input type="checkbox" name="demo" value="1" <?php echo ($vals['demo'] === '1') ? 'checked' : ''; ?>> 安装 3 篇示例文章（建议勾选，方便查看排版效果）</label>
                <label class="ck"><input type="checkbox" name="enable_comment" value="1" <?php echo ($vals['enable_comment'] === '1') ? 'checked' : ''; ?>> 开启前台评论功能</label>
                <label class="ck ck-warn"><input type="checkbox" name="agree" value="1" <?php echo ($vals['agree'] === '1') ? 'checked' : ''; ?>> 我已了解：安装会覆盖同名数据表（isw_posts / isw_comments 等）</label>
            </div>

            <div class="btn-row">
                <a class="btn btn-ghost" href="?step=1">上一步</a>
                <button class="btn btn-primary" type="submit">开始安装</button>
            </div>
        </form>

    <?php } else { ?>

        <?php if (!empty($errors)) { ?>
            <div class="state-icon state-bad">!</div>
            <h1>安装未完成</h1>
            <div class="alert alert-bad">
                <ul><?php foreach ($errors as $err) { echo '<li>' . h($err) . '</li>'; } ?></ul>
            </div>
            <div class="btn-row"><a class="btn btn-ghost" href="?step=2">返回修改配置</a></div>
        <?php } else { ?>
            <div class="state-icon state-done">✓</div>
            <h1>安装成功</h1>
            <p class="lead">数据库与配置文件已写入，安装锁已生成，本安装程序已被禁用。</p>

            <table class="check result">
                <tbody>
                <tr><th>网站名称</th><td colspan="3"><?php echo h($installedInfo['site_name']); ?></td></tr>
                <tr><th>企业口号</th><td colspan="3"><?php echo h($installedInfo['site_slogan']); ?></td></tr>
                <tr><th>域名 / 备案号</th><td colspan="3"><?php echo h($installedInfo['domain']); ?> · <?php echo h($installedInfo['icp']); ?></td></tr>
                <tr><th>管理员账号</th><td colspan="3"><?php echo h($installedInfo['admin_user']); ?>（密码为你刚才设置的密码）</td></tr>
                <tr><th>已建数据表</th><td colspan="3"><?php echo h(implode('、', $installedInfo['tables'])); ?></td></tr>
                <?php if ($installedInfo['demo'] === '1') { ?>
                <tr><th>示例文章</th><td colspan="3">已安装 <?php echo count($installedInfo['demo_posts']); ?> 篇，可在后台「文章管理」中编辑或删除</td></tr>
                <?php } else { ?>
                <tr><th>示例文章</th><td colspan="3">未安装，站点为空白状态，请到后台发布第一篇文章</td></tr>
                <?php } ?>
                </tbody>
            </table>

            <div class="btn-row">
                <a class="btn btn-primary" href="../admin/">立即进入后台</a>
                <a class="btn btn-ghost" href="../">查看网站首页</a>
            </div>

            <div class="tips">
                <strong>接下来建议做的事</strong>
                <ul>
                    <li>出于安全考虑，建议在服务器上删除或改名整个 <code>install</code> 目录；保留时 <code>install.lock</code> 也会阻止他人再次安装。</li>
                    <li>确认 <code>data/posts</code> 与 <code>uploads</code> 两个目录具有写入权限，否则文章正文与图片无法保存。</li>
                    <li>站点信息可在后台「站点设置」中随时修改，无需重跑安装程序。</li>
                    <li>若使用 Nginx，请在站点配置中加上 <code>location ^~ /data/ { deny all; }</code>，避免文章源文件被直接下载。</li>
                </ul>
            </div>
        <?php } ?>

    <?php } ?>

    </div>

    <div class="foot">© <?php echo date('Y'); ?> 晚夜工作室 · 做好量变的准备，促进事物的质变</div>
</div>
</body>
</html>

