<?php
/**
 * 后台公共头部（含鉴权）
 * 调用前需定义：$pageTitle（页面标题）、$activeNav（当前导航标识）
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_auth.php';

$pageTitle = isset($pageTitle) ? $pageTitle : '后台管理';
$activeNav = isset($activeNav) ? $activeNav : '';

$navs = array(
    'dashboard' => array('url' => 'dashboard.php',  'icon' => '▤', 'name' => '概览'),
    'posts'     => array('url' => 'posts.php',      'icon' => '☰', 'name' => '文章管理'),
    'new'       => array('url' => 'post_edit.php',  'icon' => '✎', 'name' => '发布文章'),
    'comments'  => array('url' => 'comments.php',   'icon' => '💬', 'name' => '评论管理'),
    'settings'  => array('url' => 'settings.php',   'icon' => '⚙', 'name' => '站点设置'),
);
$pendingComments = 0;
try {
    $pendingComments = (int)db_val('SELECT COUNT(*) FROM isw_comments WHERE status = 0', array(), 0);
} catch (Exception $ex) { $pendingComments = 0; }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo e($pageTitle); ?> - <?php echo e(SITE_NAME); ?> 后台</title>
<link rel="stylesheet" href="<?php echo e(url('admin/assets/css/admin.css')); ?>">
</head>
<body>
<div class="admin-wrap">
    <aside class="admin-side" id="adminSide">
        <div class="admin-brand">
            <div class="logo"><i></i><?php echo e(SITE_NAME); ?></div>
            <div class="sub">内容管理后台</div>
        </div>
        <nav class="admin-nav">
            <?php foreach ($navs as $key => $n) { ?>
                <a href="<?php echo e(url('admin/' . $n['url'])); ?>"
                   class="<?php echo ($activeNav === $key) ? 'active' : ''; ?>">
                    <span class="ic"><?php echo $n['icon']; ?></span>
                    <span><?php echo e($n['name']); ?></span>
                    <?php if ($key === 'comments' && $pendingComments > 0) { ?>
                        <span class="tag orange" style="margin-left:auto"><?php echo $pendingComments; ?></span>
                    <?php } ?>
                </a>
            <?php } ?>
            <a href="<?php echo e(url()); ?>" target="_blank">
                <span class="ic">↗</span><span>查看前台首页</span>
            </a>
        </nav>
        <div class="admin-side-foot">
            登录：<?php echo e(isset($_SESSION['isw_admin_user']) ? $_SESSION['isw_admin_user'] : '-'); ?><br>
            <?php echo e(SITE_NAME); ?> · 内容管理系统
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-top">
            <div class="title">
                <button class="nav-toggle" id="navToggle" type="button">☰</button>
                <?php echo e($pageTitle); ?>
            </div>
            <div class="right">
                <span>IP：<?php echo e(client_ip()); ?></span>
                <span class="avatar"><?php echo e(mb_substr((string)(isset($_SESSION['isw_admin_user']) ? $_SESSION['isw_admin_user'] : 'A'), 0, 1)); ?></span>
                <a href="<?php echo e(url('admin/logout.php')); ?>">退出登录</a>
            </div>
        </header>
        <div class="admin-content">
<?php
/** 输出提示信息 */
function admin_flash($msg, $type = 'success')
{
    echo '<div class="alert alert-' . e($type) . '">' . e($msg) . '</div>';
}
?>
