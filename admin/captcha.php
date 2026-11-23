<?php
/**
 * 后台登录验证码（GD 库绘制，纯本地，无任何外部依赖）
 * 兼容：优先使用本地字体 assets/fonts/inter.ttf，
 *       若服务器 GD 不支持 TTF，则自动回退到内置点阵字体，保证可用。
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
ensure_session();

// ---------- 生成验证码字符（去掉 0/O/1/l/I 等易混淆字符）----------
$pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$len  = strlen($pool);
$code = '';
for ($i = 0; $i < 4; $i++) {
    $code .= $pool[mt_rand(0, $len - 1)];
}
$_SESSION['isw_captcha'] = strtolower($code);
$_SESSION['isw_captcha_time'] = time();

// ---------- 画布 ----------
$w = 118;
$h = 40;
if (!function_exists('imagecreatetruecolor')) {
    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
    exit;
}
$im = imagecreatetruecolor($w, $h);
$white  = imagecolorallocate($im, 255, 255, 255);
$light  = imagecolorallocate($im, 214, 220, 230);
$dark   = imagecolorallocate($im, 44, 52, 66);
$brand  = imagecolorallocate($im, 43, 92, 255);
imagefilledrectangle($im, 0, 0, $w, $h, $white);

// 干扰线 + 干扰点
for ($i = 0; $i < 5; $i++) {
    imageline($im, mt_rand(0, $w), mt_rand(0, $h), mt_rand(0, $w), mt_rand(0, $h), $light);
}
for ($i = 0; $i < 60; $i++) {
    imagesetpixel($im, mt_rand(0, $w), mt_rand(0, $h), $light);
}

// ---------- 绘制文字 ----------
$font = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'inter.ttf';
$useTtf = function_exists('imagettftext') && is_file($font);

if ($useTtf) {
    $x = 14;
    for ($i = 0; $i < strlen($code); $i++) {
        $size = 19;
        $angle = mt_rand(-12, 12);
        $y = 29 + mt_rand(-2, 2);
        $color = ($i % 2 === 0) ? $dark : $brand;
        imagettftext($im, $size, $angle, $x, $y, $color, $font, $code[$i]);
        $x += 24;
    }
} else {
    // 回退方案：内置点阵字体（一定可用）
    $x = 22;
    for ($i = 0; $i < strlen($code); $i++) {
        $y = mt_rand(8, 14);
        $color = ($i % 2 === 0) ? $dark : $brand;
        imagestring($im, 5, $x, $y, $code[$i], $color);
        $x += 22;
    }
}

// 边框
imagerectangle($im, 0, 0, $w - 1, $h - 1, $light);

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
imagepng($im);
imagedestroy($im);
exit;
