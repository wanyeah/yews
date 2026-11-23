<?php
/**
 * 前台公共头部（导航栏 + 样式）
 * 使用前可定义：$pageTitle、$activeNav、$pageDesc
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'functions.php';

$pageTitle = isset($pageTitle) ? $pageTitle : '';
$activeNav = isset($activeNav) ? $activeNav : '';
$pageDesc  = isset($pageDesc) ? $pageDesc : SITE_SLOGAN;
$siteName  = setting('site_name', SITE_NAME);
$siteSlogan = setting('site_slogan', SITE_SLOGAN);
$icp       = setting('icp', SITE_ICP);

$fullTitle = $pageTitle === '' ? ($siteName . ' - ' . $siteSlogan) : ($pageTitle . ' - ' . $siteName);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($fullTitle); ?></title>
<meta name="description" content="<?php echo e(str_limit($pageDesc, 150, '')); ?>">
<meta name="keywords" content="<?php echo e($siteName); ?>,企业官网,<?php echo e($siteSlogan); ?>">
<link rel="stylesheet" href="<?php echo e(url('assets/css/style.css')); ?>">
<link rel="stylesheet" href="<?php echo e(url('assets/vendor/highlight.min.css')); ?>">
</head>
<body>
<header class="site-nav" id="siteNav">
    <div class="nav-inner">
        <a class="nav-logo" href="<?php echo e(url()); ?>">
            <span class="mark"></span><span><?php echo e($siteName); ?></span>
        </a>
        <button class="nav-toggle" id="navToggle" type="button" aria-label="菜单">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav-menu" id="navMenu">
            <a href="<?php echo e(url()); ?>" class="<?php echo ($activeNav === 'home') ? 'active' : ''; ?>">首页</a>
            <a href="<?php echo e(url('news/')); ?>" class="<?php echo ($activeNav === 'news') ? 'active' : ''; ?>">企业动态</a>
            <a href="<?php echo e(url('#about')); ?>" class="<?php echo ($activeNav === 'about') ? 'active' : ''; ?>">关于我们</a>
            <a href="<?php echo e(url('#service')); ?>" class="<?php echo ($activeNav === 'service') ? 'active' : ''; ?>">服务与能力</a>
            <a href="<?php echo e(url('#contact')); ?>" class="<?php echo ($activeNav === 'contact') ? 'active' : ''; ?>">联系我们</a>
        </nav>
    </div>
</header>
<main>
