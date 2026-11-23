<?php
/**
 * 公共函数库
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'db.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Parsedown.php';

if (!function_exists('__isw_bootstrap')) {
function __isw_bootstrap() {}

// ---------------- 基础工具 ----------------

/** HTML 转义输出 */
function e($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/** 生成站内绝对 URL，如 url('news/') => http://host/news/ */
function url($path = '')
{
    return BASE_URL . ltrim((string)$path, '/');
}

/** 开启 session（幂等） */
function ensure_session()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(0, '/', '', false, true);
        session_name('ISWSESSID');
        @session_start();
    }
}

/** 获取客户端 IP */
function client_ip()
{
    $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

/** 截取字符串（超出加省略号） */
function str_limit($str, $len = 100, $suffix = '…')
{
    $str = trim((string)$str);
    if (function_exists('mb_substr')) {
        if (mb_strlen($str, 'UTF-8') <= $len) return $str;
        return mb_substr($str, 0, $len, 'UTF-8') . $suffix;
    }
    if (strlen($str) <= $len) return $str;
    return substr($str, 0, $len) . $suffix;
}

/** 日期格式化 */
function fmt_date($time, $format = 'Y-m-d')
{
    if (empty($time) || $time === '0000-00-00 00:00:00') return '';
    $ts = is_numeric($time) ? (int)$time : strtotime($time);
    return $ts ? date($format, $ts) : '';
}

/** 校验文章编号（必须为正整数，杜绝路径穿越与注入） */
function valid_id($id)
{
    $id = (string)$id;
    return (preg_match('/^[1-9][0-9]{0,9}$/', $id) === 1) ? (int)$id : 0;
}

/** 当前页码 */
function current_page($size = 10)
{
    $p = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($p < 1) $p = 1;
    if ($p > 9999) $p = 9999;
    return $p;
}

// ---------------- 站点配置 ----------------

function setting($key, $default = '')
{
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $v = db_val('SELECT svalue FROM isw_settings WHERE skey = ?', array($key), null);
    if ($v === null) $v = $default;
    $cache[$key] = $v;
    return $v;
}

function set_setting($key, $value)
{
    db_query('INSERT INTO isw_settings (skey, svalue) VALUES (?, ?)
              ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', array($key, $value));
}

/** 评论功能是否开启 */
function comments_enabled()
{
    return setting('enable_comment', '1') === '1';
}

// ---------------- 文章本地文件（头尾模板自动套用）----------------

/**
 * 文章正文模板 —— 头部
 * 每次发布文章时自动套用，你只需要书写中间的正文部分
 */
function article_tpl_head()
{
    return '<div class="post-body markdown-body">' . "\n";
}

/** 文章正文模板 —— 尾部 */
function article_tpl_foot()
{
    return "\n" . '</div><!-- /.post-body -->' . "\n";
}

/** md 源文件路径 */
function post_md_file($id)
{
    return DATA_PATH . $id . '.md';
}

/** 渲染后的 html 正文文件路径（含头尾模板） */
function post_html_file($id)
{
    return DATA_PATH . $id . '.html';
}

/**
 * Markdown 转 HTML（安全模式，自动转义原始 HTML，防 XSS）
 */
function render_markdown($md)
{
    $pd = new Parsedown();
    $pd->setSafeMode(true);      // 禁用内联 HTML
    $pd->setBreaksEnabled(true); // 换行即 <br>，符合中文书写习惯
    $html = $pd->text((string)$md);
    return $html;
}

/**
 * 保存文章正文：md 源文件 + 渲染后的 html 文件（头尾自动套用）
 * @return bool
 */
function save_post_content($id, $md)
{
    $id = valid_id($id);
    if (!$id) return false;
    if (!is_dir(DATA_PATH)) {
        @mkdir(DATA_PATH, 0755, true);
    }
    $ok1 = @file_put_contents(post_md_file($id), $md);
    $html = article_tpl_head() . render_markdown($md) . article_tpl_foot();
    $ok2 = @file_put_contents(post_html_file($id), $html);
    return ($ok1 !== false && $ok2 !== false);
}

/**
 * 读取文章正文 HTML（含头尾）；html 缺失时由 md 现场渲染并缓存
 * @return string
 */
function get_post_content($id)
{
    $id = valid_id($id);
    if (!$id) return '';
    $file = post_html_file($id);
    if (is_file($file)) {
        $html = @file_get_contents($file);
        if ($html !== false && $html !== '') return $html;
    }
    $mdFile = post_md_file($id);
    if (is_file($mdFile)) {
        $md = @file_get_contents($mdFile);
        if ($md !== false) {
            $html = article_tpl_head() . render_markdown($md) . article_tpl_foot();
            @file_put_contents($file, $html);
            return $html;
        }
    }
    return '';
}

/** 读取 md 源（后台编辑用） */
function get_post_md($id)
{
    $id = valid_id($id);
    if (!$id) return '';
    $file = post_md_file($id);
    return is_file($file) ? (string)@file_get_contents($file) : '';
}

/** 删除文章正文文件 */
function delete_post_content($id)
{
    $id = valid_id($id);
    if (!$id) return false;
    @unlink(post_md_file($id));
    @unlink(post_html_file($id));
    return true;
}

/** 提取纯文本摘要（用于列表卡片） */
function make_summary($html_or_text, $len = 90)
{
    $text = strip_tags($html_or_text);
    $text = str_replace(array("\r", "\n", "\t", '&nbsp;', '　'), ' ', $text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return str_limit(trim($text), $len);
}

// ---------------- 阅读量（防刷：同设备只计一次）----------------

/** 设备唯一标识（存于 cookie，有效期 1 年） */
function visitor_token()
{
    ensure_session();
    $ck = 'isw_visitor';
    if (!empty($_COOKIE[$ck]) && preg_match('/^[a-f0-9]{32}$/', $_COOKIE[$ck])) {
        return $_COOKIE[$ck];
    }
    $token = function_exists('random_bytes') ? bin2hex(random_bytes(16)) : md5(uniqid('', true));
    $expire = time() + 86400 * 365;
    @setcookie($ck, $token, $expire, '/', '', false, true);
    $_COOKIE[$ck] = $token;
    return $token;
}

/**
 * 记录一次阅读：同一设备（cookie）+ 同一 IP 对同一篇文章只计一次
 */
function record_view($id)
{
    $id = valid_id($id);
    if (!$id) return false;
    $token = visitor_token();
    $ip = client_ip();
    try {
        $stmt = db_query('INSERT IGNORE INTO isw_post_views (post_id, visitor, ip, created_at)
                          VALUES (?, ?, ?, NOW())', array($id, $token, $ip));
        if ($stmt->rowCount() > 0) {
            db_query('UPDATE isw_posts SET views = views + 1 WHERE id = ?', array($id));
            return true;
        }
    } catch (Exception $ex) {
        // 忽略异常，不影响页面展示
    }
    return false;
}

// ---------------- 评论 ----------------

/** 文章评论数（后台统计用） */
function count_comments($postId = 0)
{
    if ($postId) {
        return (int)db_val('SELECT COUNT(*) FROM isw_comments WHERE post_id = ? AND status = 1', array($postId), 0);
    }
    return (int)db_val('SELECT COUNT(*) FROM isw_comments WHERE status = 1', array(), 0);
}

// ---------------- 后台鉴权 ----------------

function is_admin()
{
    ensure_session();
    return !empty($_SESSION['isw_admin']) && $_SESSION['isw_admin'] === true;
}

function require_admin()
{
    if (!is_admin()) {
        header('Location: ' . url('admin/index.php'));
        exit;
    }
}

function admin_login($user, $pass)
{
    $okUser = hash_equals(ADMIN_USER, (string)$user);
    $okPass = false;
    if (defined('ADMIN_PASS_HASH') && ADMIN_PASS_HASH !== '') {
        $okPass = password_verify((string)$pass, ADMIN_PASS_HASH);
    } else {
        $okPass = hash_equals(ADMIN_PASS, (string)$pass);
    }
    if ($okUser && $okPass) {
        ensure_session();
        $_SESSION['isw_admin'] = true;
        $_SESSION['isw_admin_user'] = ADMIN_USER;
        $_SESSION['isw_admin_time'] = time();
        session_regenerate_id(true);
        return true;
    }
    return false;
}

function admin_logout()
{
    ensure_session();
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        @setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    @session_destroy();
}

// ---------------- CSRF ----------------

function csrf_token()
{
    ensure_session();
    if (empty($_SESSION['isw_csrf'])) {
        $_SESSION['isw_csrf'] = function_exists('random_bytes') ? bin2hex(random_bytes(24)) : md5(uniqid('', true));
    }
    return $_SESSION['isw_csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check($strict = true)
{
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!empty($token) && hash_equals(csrf_token(), $token)) return true;
    if ($strict) {
        http_response_code(403);
        exit('表单已过期，请刷新页面后重试（CSRF 校验失败）。');
    }
    return false;
}

// ---------------- 日志 ----------------

function admin_log($action, $detail = '')
{
    try {
        db_query('INSERT INTO isw_logs (action, detail, ip, created_at) VALUES (?, ?, ?, NOW())',
            array($action, $detail, client_ip()));
    } catch (Exception $ex) {
        // 忽略
    }
}

// ---------------- 分页 ----------------

/**
 * 生成分页 HTML
 * @param int    $total  总条数
 * @param int    $page   当前页
 * @param int    $size   每页条数
 * @param string $tpl    链接模板，用 {page} 占位，如 '/news/?page={page}'
 */
function paginate_html($total, $page, $size, $tpl)
{
    $pages = (int)ceil($total / $size);
    if ($pages <= 1) return '';
    if ($page > $pages) $page = $pages;

    $link = function ($p) use ($tpl) {
        return e(str_replace('{page}', (string)$p, $tpl));
    };

    $html = '<nav class="pagination" aria-label="分页导航">';
    if ($page > 1) {
        $html .= '<a class="page-btn" href="' . $link($page - 1) . '">上一页</a>';
    } else {
        $html .= '<span class="page-btn disabled">上一页</span>';
    }

    // 页码窗口
    $start = max(1, $page - 2);
    $end = min($pages, $page + 2);
    if ($end - $start < 4) {
        $start = max(1, $end - 4);
        $end = min($pages, $start + 4);
    }
    if ($start > 1) {
        $html .= '<a class="page-num" href="' . $link(1) . '">1</a>';
        if ($start > 2) $html .= '<span class="page-dots">…</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $page) {
            $html .= '<span class="page-num active" aria-current="page">' . $i . '</span>';
        } else {
            $html .= '<a class="page-num" href="' . $link($i) . '">' . $i . '</a>';
        }
    }
    if ($end < $pages) {
        if ($end < $pages - 1) $html .= '<span class="page-dots">…</span>';
        $html .= '<a class="page-num" href="' . $link($pages) . '">' . $pages . '</a>';
    }

    if ($page < $pages) {
        $html .= '<a class="page-btn" href="' . $link($page + 1) . '">下一页</a>';
    } else {
        $html .= '<span class="page-btn disabled">下一页</span>';
    }
    $html .= '<span class="page-total">共 ' . $pages . ' 页 / ' . $total . ' 条</span>';
    $html .= '</nav>';
    return $html;
}

// ---------------- 图片上传 ----------------

/**
 * 处理后台编辑器的图片上传
 * @return array{ok:bool,url:string,msg:string}
 */
function handle_image_upload($field = 'file')
{
    $ret = array('ok' => false, 'url' => '', 'msg' => '');
    if (empty($_FILES[$field]) || empty($_FILES[$field]['name'])) {
        $ret['msg'] = '没有选择文件';
        return $ret;
    }
    $f = $_FILES[$field];
    if (!empty($f['error'])) {
        $ret['msg'] = '上传失败，错误码：' . (int)$f['error'];
        return $ret;
    }
    if ($f['size'] > 3 * 1024 * 1024) {
        $ret['msg'] = '图片不能超过 3MB';
        return $ret;
    }
    $info = @getimagesize($f['tmp_name']);
    if ($info === false) {
        $ret['msg'] = '文件不是有效图片';
        return $ret;
    }
    $map = array(
        IMAGETYPE_GIF => 'gif', IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp',
    );
    if (!isset($map[$info[2]])) {
        $ret['msg'] = '仅支持 JPG / PNG / GIF / WEBP';
        return $ret;
    }
    if (!is_dir(UPLOAD_PATH)) @mkdir(UPLOAD_PATH, 0755, true);
    $name = date('Ymd') . '_' . bin2hex(function_exists('random_bytes') ? random_bytes(6) : md5(uniqid('', true))) . '.' . $map[$info[2]];
    if (!@move_uploaded_file($f['tmp_name'], UPLOAD_PATH . $name)) {
        $ret['msg'] = '写入目录失败，请检查 uploads 权限';
        return $ret;
    }
    $ret['ok'] = true;
    $ret['url'] = UPLOAD_URL . $name;
    return $ret;
}

// ---------------- 其它 ----------------

/** 安全的跳转 */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}
}
