<?php
/**
 * 后台登录页（白色简约）
 * 账号密码在 includes/config.php 中定义
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
ensure_session();

if (is_admin()) {
    redirect(url('admin/dashboard.php'));
}

$error = '';
$lockSeconds = 0;

// 简单爆破防护：连续失败 5 次后锁定 60 秒
if (!empty($_SESSION['login_fail']) && $_SESSION['login_fail'] >= 5) {
    $passed = time() - (int)(isset($_SESSION['login_fail_time']) ? $_SESSION['login_fail_time'] : 0);
    if ($passed < 60) {
        $lockSeconds = 60 - $passed;
    } else {
        $_SESSION['login_fail'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockSeconds === 0) {
    csrf_check();
    $user    = isset($_POST['username']) ? trim($_POST['username']) : '';
    $pass    = isset($_POST['password']) ? $_POST['password'] : '';
    $captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
    $sessCap = isset($_SESSION['isw_captcha']) ? strtolower($_SESSION['isw_captcha']) : '';

    if ($captcha === '' || $sessCap === '' || !hash_equals($sessCap, strtolower($captcha))) {
        $error = '验证码不正确或已过期';
    } elseif (admin_login($user, $pass)) {
        $_SESSION['login_fail'] = 0;
        unset($_SESSION['isw_captcha']);
        admin_log('登录后台', '管理员 ' . ADMIN_USER . ' 登录成功');
        redirect(url('admin/dashboard.php'));
    } else {
        $error = '用户名或密码错误';
        admin_log('登录失败', '尝试账号：' . $user);
    }

    if ($error !== '') {
        $_SESSION['login_fail'] = (int)(isset($_SESSION['login_fail']) ? $_SESSION['login_fail'] : 0) + 1;
        $_SESSION['login_fail_time'] = time();
        unset($_SESSION['isw_captcha']);
        if ($_SESSION['login_fail'] >= 5) $lockSeconds = 60;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>后台登录 - <?php echo e(SITE_NAME); ?></title>
<link rel="stylesheet" href="<?php echo e(url('admin/assets/css/admin.css')); ?>">
</head>
<body class="login-page">
<div class="login-box">
    <div class="login-left">
        <div class="dot"></div>
        <h1><?php echo e(SITE_NAME); ?></h1>
        <div class="slogan">
            <?php echo e(SITE_SLOGAN); ?><br>
            内容管理后台
        </div>
        <div class="tip">
            · 发布 / 编辑 / 删除文章与通知<br>
            · 实时查看点击量、评论量<br>
            · 文章正文保存为本地文件，轻量高效
        </div>
    </div>
    <div class="login-right">
        <h2>管理员登录</h2>
        <div class="sub">请使用在 config.php 中配置的账号登录</div>

        <?php if ($error !== '') { ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php } ?>
        <?php if ($lockSeconds > 0) { ?>
            <div class="alert alert-error">错误次数过多，请 <?php echo (int)$lockSeconds; ?> 秒后再试</div>
        <?php } ?>

        <form method="post" class="login-form" autocomplete="off">
            <?php echo csrf_field(); ?>
            <div class="form-item">
                <label class="lbl" for="username">用户名</label>
                <input type="text" id="username" name="username" placeholder="请输入用户名" autocomplete="off" required autofocus>
            </div>
            <div class="form-item">
                <label class="lbl" for="password">密码</label>
                <input type="password" id="password" name="password" placeholder="请输入密码" autocomplete="new-password" required>
            </div>
            <div class="form-item">
                <label class="lbl" for="captcha">验证码</label>
                <div class="captcha-row">
                    <input type="text" id="captcha" name="captcha" maxlength="4" placeholder="点击图片可刷新" autocomplete="off" required>
                    <img class="captcha-img" id="captchaImg" src="<?php echo e(url('admin/captcha.php?t=' . time())); ?>"
                         alt="验证码" title="点击刷新验证码" onclick="this.src='<?php echo e(url('admin/captcha.php')); ?>?t='+Date.now();">
                </div>
            </div>
            <button type="submit" class="login-btn" <?php echo ($lockSeconds > 0) ? 'disabled' : ''; ?>>登 录</button>
        </form>

        <div class="login-foot">
            <?php echo e(SITE_NAME); ?> · <?php echo e(SITE_COPYRIGHT_YEAR); ?><br>
            当前 IP：<?php echo e(client_ip()); ?>
        </div>
    </div>
</div>
</body>
</html>
