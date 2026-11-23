<?php
/**
 * 独立评论页 /comment/?id=123
 * 文章详情页保持纯净，仅展示「已读人数」；评论入口放在本页
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
ensure_session();

$id = valid_id(isset($_GET['id']) ? $_GET['id'] : 0);
if (!$id) { redirect(url('news/')); }

$post = db_one('SELECT id, title, author, comments, published_at, created_at FROM isw_posts WHERE id = ? AND status = 1', array($id));
if ($post === null) { redirect(url('news/')); }

$error = '';
$ok    = '';

// ---------------- 提交评论 ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && comments_enabled()) {
    csrf_check();
    $author  = trim(isset($_POST['author']) ? $_POST['author'] : '');
    $content = trim(isset($_POST['content']) ? $_POST['content'] : '');
    $capAns  = trim(isset($_POST['cap']) ? $_POST['cap'] : '');
    $capRight = isset($_SESSION['isw_cap']) ? (string)$_SESSION['isw_cap'] : '';

    $last = isset($_SESSION['last_comment_time']) ? (int)$_SESSION['last_comment_time'] : 0;
    if ($capRight === '' || $capAns === '' || !hash_equals($capRight, $capAns)) {
        $error = '验证码不正确，请重新计算';
    } elseif (time() - $last < 30) {
        $error = '评论太频繁了，请 30 秒后再试';
    } elseif ($content === '') {
        $error = '评论内容不能为空';
    } elseif (mb_strlen($content, 'UTF-8') > 500) {
        $error = '评论内容不能超过 500 字';
    } else {
        if ($author === '') $author = '匿名访客';
        $author = str_limit($author, 30, '');
        db_query('INSERT INTO isw_comments (post_id, author, content, ip, status, created_at)
                  VALUES (?, ?, ?, ?, 1, NOW())',
            array($id, $author, $content, client_ip()));
        $n = (int)db_val('SELECT COUNT(*) FROM isw_comments WHERE post_id = ? AND status = 1', array($id), 0);
        db_query('UPDATE isw_posts SET comments = ? WHERE id = ?', array($n, $id));
        $_SESSION['last_comment_time'] = time();
        unset($_SESSION['isw_cap']);
        $ok = '评论发布成功，感谢你的反馈';
    }
}

// 生成算术验证码
$a = mt_rand(2, 19);
$b = mt_rand(2, 9);
$_SESSION['isw_cap'] = (string)($a + $b);
$capQuestion = $a . ' + ' . $b . ' = ?';

$size  = 20;
$page  = current_page();
$total = (int)db_val('SELECT COUNT(*) FROM isw_comments WHERE post_id = ? AND status = 1', array($id), 0);
$offset = ($page - 1) * $size;
$list = db_all('SELECT id, author, content, created_at FROM isw_comments
                WHERE post_id = ? AND status = 1 ORDER BY id DESC
                LIMIT ' . (int)$offset . ', ' . (int)$size, array($id));
$tpl = url('comment/?') . http_build_query(array('id' => $id, 'page' => '{page}'));

$pageTitle = '评论：' . $post['title'];
$activeNav = 'news';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_header.php';
?>
<section class="page-head">
    <div class="container">
        <div class="crumb">
            <a href="<?php echo e(url()); ?>">首页</a> /
            <a href="<?php echo e(url('news/')); ?>">企业动态</a> /
            <a href="<?php echo e(url('article/?id=' . (int)$id)); ?>"><?php echo e(str_limit($post['title'], 30)); ?></a> /
            评论
        </div>
        <h1 style="font-size:26px"><?php echo e($post['title']); ?></h1>
        <div class="sub">
            <a class="slide-ul" style="color:var(--brand)" href="<?php echo e(url('article/?id=' . (int)$id)); ?>">← 返回文章正文</a>
        </div>
    </div>
</section>

<section class="section" style="padding-top:44px">
    <div class="container">
        <div class="comment-wrap">
            <?php if (!comments_enabled()) { ?>
                <div class="empty-state"><div class="ico">🔒</div><p>评论功能已关闭</p></div>
            <?php } else { ?>
                <div class="comment-form">
                    <h3 style="font-size:16px;margin-bottom:14px">发表评论</h3>
                    <?php if ($error !== '') { ?>
                        <div class="alert alert-error" style="background:#fdeeee;border:1px solid #f6cfcf;color:#c0392b;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:14px"><?php echo e($error); ?></div>
                    <?php } ?>
                    <?php if ($ok !== '') { ?>
                        <div class="alert alert-success" style="background:#eefbf3;border:1px solid #c9ecd8;color:#16803c;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:14px"><?php echo e($ok); ?></div>
                    <?php } ?>
                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <div class="row">
                            <input type="text" name="author" maxlength="30" placeholder="你的昵称（可留空）">
                            <input type="text" name="cap" placeholder="验证码：<?php echo e($capQuestion); ?>" required>
                        </div>
                        <textarea name="content" maxlength="500" placeholder="写下你的想法（500 字以内）" required></textarea>
                        <div style="margin-top:14px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                            <div class="tip" style="font-size:12.5px;color:var(--muted)">评论将在提交后立即公开，请文明发言</div>
                            <button class="btn btn-primary" type="submit">发布评论</button>
                        </div>
                    </form>
                </div>
            <?php } ?>

            <h3 style="font-size:16px;margin-bottom:16px">全部评论（<?php echo $total; ?>）</h3>
            <?php if (empty($list)) { ?>
                <div class="empty-state"><div class="ico">💬</div><p>还没有评论，来说两句吧</p></div>
            <?php } else { ?>
                <?php foreach ($list as $c) { ?>
                    <div class="comment-item">
                        <div class="top">
                            <span class="avatar"><?php echo e(mb_substr($c['author'], 0, 1)); ?></span>
                            <span class="name"><?php echo e($c['author']); ?></span>
                            <span class="time"><?php echo e(fmt_date($c['created_at'], 'Y-m-d H:i')); ?></span>
                        </div>
                        <div class="body"><?php echo e($c['content']); ?></div>
                    </div>
                <?php } ?>
                <?php echo paginate_html($total, $page, $size, $tpl); ?>
            <?php } ?>
        </div>
    </div>
</section>
<?php require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_footer.php'; ?>
