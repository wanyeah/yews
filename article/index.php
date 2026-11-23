<?php
/**
 * 文章详情页
 * ---------------------------------------------------------------
 * 两种访问方式：
 *   1) /article/?id=123        —— 整页渲染（直接访问、搜索引擎、新窗口打开）
 *   2) /article/?id=123&ajax=1 —— 只返回 JSON 片段，供首页 / 动态页无刷新加载
 *      返回字段：ok、id、title、fullTitle、desc、activeNav、needHljs、html
 * 正文从本地文件 data/posts/{id}.html 读取（含统一头尾模板）
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'article_view.php';

// 是否为 AJAX 片段请求（支持 ?ajax=1 或 X-Requested-With 头）
$isAjax = (isset($_GET['ajax']) && $_GET['ajax'] === '1')
       || (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
           && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

/**
 * 统一的 AJAX 输出
 */
if (!function_exists('isw_article_json')) {
function isw_article_json($arr)
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
}

$siteName = setting('site_name', SITE_NAME);

$id = valid_id(isset($_GET['id']) ? $_GET['id'] : 0);

// ---------- 参数不合法 ----------
if (!$id) {
    $errHtml = '<section class="section"><div class="container"><div class="empty-state">'
             . '<div class="ico">🔍</div><p>参数不合法，请检查链接</p>'
             . '<p style="margin-top:16px"><a class="btn btn-ghost" href="' . e(url('news/')) . '">返回企业动态</a></p>'
             . '</div></div></section>';
    if ($isAjax) {
        isw_article_json(array(
            'ok' => 0, 'id' => 0, 'title' => '内容不存在',
            'fullTitle' => '内容不存在 - ' . $siteName, 'desc' => '',
            'activeNav' => 'news', 'needHljs' => false, 'html' => $errHtml,
        ));
    }
    http_response_code(404);
    $pageTitle = '内容不存在';
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_header.php';
    echo $errHtml;
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_footer.php';
    exit;
}

$post = db_one('SELECT * FROM isw_posts WHERE id = ? AND status = 1', array($id));

// ---------- 文章不存在 ----------
if ($post === null) {
    $errHtml = '<section class="section"><div class="container"><div class="empty-state">'
             . '<div class="ico">📭</div><p>该文章不存在或已下线</p>'
             . '<p style="margin-top:16px"><a class="btn btn-ghost" href="' . e(url('news/')) . '">返回企业动态</a></p>'
             . '</div></div></section>';
    if ($isAjax) {
        isw_article_json(array(
            'ok' => 0, 'id' => (int)$id, 'title' => '内容不存在',
            'fullTitle' => '内容不存在 - ' . $siteName, 'desc' => '',
            'activeNav' => 'news', 'needHljs' => false, 'html' => $errHtml,
        ));
    }
    http_response_code(404);
    $pageTitle = '内容不存在';
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_header.php';
    echo $errHtml;
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_footer.php';
    exit;
}

// 记录阅读（同一设备只计一次，需在输出前调用以便写入 cookie）
$viewed = record_view($id);
$views  = (int)db_val('SELECT views FROM isw_posts WHERE id = ?', array($id), 0);

$content = get_post_content($id);

// 上一篇 / 下一篇
$prev = db_one('SELECT id, title FROM isw_posts WHERE status = 1 AND id < ? ORDER BY id DESC LIMIT 1', array($id));
$next = db_one('SELECT id, title FROM isw_posts WHERE status = 1 AND id > ? ORDER BY id ASC LIMIT 1', array($id));

$pageTitle = $post['title'];
$activeNav = 'news';
$pageDesc  = $post['summary'] !== '' ? $post['summary'] : make_summary(get_post_md($id), 120);
$hljs      = true;

// ---------- AJAX 片段输出（与整页共用同一份渲染函数）----------
if ($isAjax) {
    $bodyHtml = isw_render_article($post, $views, $content, $prev, $next);
    isw_article_json(array(
        'ok'        => 1,
        'id'        => (int)$id,
        'title'     => $post['title'],
        'fullTitle' => ($pageTitle === '' ? $siteName : $pageTitle . ' - ' . $siteName),
        'desc'      => str_limit($pageDesc, 150, ''),
        'activeNav' => $activeNav,
        'needHljs'  => (strpos($content, '<pre>') !== false),
        'html'      => $bodyHtml,
    ));
}

// ---------- 正常整页输出 ----------
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_header.php';
echo isw_render_article($post, $views, $content, $prev, $next);
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_footer.php';
