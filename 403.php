<?php
$script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/403.php';
$root   = rtrim(str_replace('\\', '/', dirname($script)), '/');
if ($root === '.') { $root = ''; }

$homeUrl = $root . '/';
$newsUrl = $root . '/news/';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>403 - 无权访问 · 晚夜工作室</title>
<style>
    :root { --brand:#4e6ef2; --brand-dark:#3b56c4; --ink:#14161a; --text:#2b3038;
            --sub:#6b7280; --muted:#9aa1ac; --line:#eceef2; }
    * { box-sizing: border-box; }
    body {
        margin: 0; min-height: 100vh; background: #f7f8fb; color: var(--text);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC",
                     "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
        display: flex; align-items: center; justify-content: center; padding: 40px 20px;
    }
    .wrap { width: 100%; max-width: 540px; text-align: center; }
    .logo { display: inline-flex; align-items: center; gap: 9px; font-size: 15px;
            color: var(--ink); font-weight: 600; margin-bottom: 30px; }
    .logo i { width: 20px; height: 20px; border-radius: 6px; display: inline-block;
              background: linear-gradient(135deg, var(--brand) 0%, #8a7bf7 100%); }
    .card {
        background: #fff; border: 1px solid var(--line); border-radius: 16px;
        padding: 42px 36px 34px; box-shadow: 0 8px 30px rgba(20, 30, 60, .06);
    }
    .code {
        font-size: 76px; line-height: 1; font-weight: 700; letter-spacing: 2px;
        background: linear-gradient(135deg, var(--brand) 0%, #8a7bf7 100%);
        -webkit-background-clip: text; background-clip: text; color: transparent;
    }
    .shade { font-size: 13px; color: var(--muted); letter-spacing: 3px; margin-top: 6px; }
    h1 { margin: 22px 0 10px; font-size: 21px; color: var(--ink); font-weight: 600; }
    p.desc { margin: 0; color: var(--sub); font-size: 14px; line-height: 1.9; }
    .btns { margin-top: 26px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .btn {
        display: inline-flex; align-items: center; justify-content: center; height: 40px;
        padding: 0 22px; border-radius: 10px; font-size: 14px; text-decoration: none;
        transition: transform .18s ease, background .18s ease, border-color .18s ease;
    }
    .btn-primary { background: var(--brand); color: #fff; box-shadow: 0 8px 22px rgba(78,110,242,.28); }
    .btn-primary:hover { background: var(--brand-dark); transform: translateY(-2px); }
    .btn-ghost { background: #fff; color: var(--text); border: 1px solid #dfe3ea; }
    .btn-ghost:hover { border-color: var(--brand); color: var(--brand); }
    .slogan { margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--line);
              color: var(--muted); font-size: 12.5px; }
    @media (max-width: 480px) {
        .card { padding: 32px 20px 26px; }
        .code { font-size: 62px; }
        .btns .btn { width: 100%; }
    }
</style>
</head>
<body>
<div class="wrap">
    <div class="logo"><i></i>晚夜工作室</div>
    <div class="card">
        <div class="code">403</div>
        <div class="shade">FORBIDDEN</div>
        <h1>无权访问</h1>
        <p class="desc">该目录或文件已受服务器保护，无法直接查看。<br>请回到站内浏览公开内容。</p>
        <div class="btns">
            <a class="btn btn-primary" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">返回首页</a>
            <a class="btn btn-ghost" href="<?php echo htmlspecialchars($newsUrl, ENT_QUOTES, 'UTF-8'); ?>">浏览企业动态</a>
        </div>
        <div class="slogan">做好量变的准备，促进事物的质变</div>
    </div>
</div>
</body>
</html>
