<?php
/**
 * 企业动态列表页（分页）
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';

$cat    = isset($_GET['category']) ? trim($_GET['category']) : '';
$kw     = isset($_GET['kw']) ? trim($_GET['kw']) : '';
$page   = current_page();
$size   = (int)setting('page_size', '9');
if ($size < 3) $size = 3;
if ($size > 30) $size = 30;

$where = array('status = 1');
$args  = array();
if ($cat === 'news' || $cat === 'notice') {
    $where[] = 'category = ?';
    $args[] = $cat;
}
if ($kw !== '') {
    $where[] = 'title LIKE ?';
    $args[] = '%' . $kw . '%';
}
$whereSql = implode(' AND ', $where);

$total  = (int)db_val('SELECT COUNT(*) FROM isw_posts WHERE ' . $whereSql, $args, 0);
$offset = ($page - 1) * $size;
$list = db_all('SELECT id, title, author, category, summary, views, comments, published_at, created_at
                FROM isw_posts WHERE ' . $whereSql . '
                ORDER BY IFNULL(published_at, created_at) DESC, id DESC
                LIMIT ' . (int)$offset . ', ' . (int)$size, $args);

$tpl = url('news/?') . http_build_query(array_filter(array(
    'category' => $cat, 'kw' => $kw, 'page' => '{page}'
), function ($v) { return $v !== '' && $v !== null; }));

$pageTitle = '企业动态';
$activeNav = 'news';
$pageDesc  = SITE_NAME . '的企业动态与通知公告';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_header.php';
?>
<section class="page-head">
    <div class="container">
        <div class="crumb"><a href="<?php echo e(url()); ?>">首页</a> / 企业动态</div>
        <h1>企业动态</h1>
        <div class="sub">共 <?php echo $total; ?> 篇内容 · <?php echo e($siteSlogan); ?></div>
    </div>
</section>

<section class="news-list">
    <div class="container">
        <div class="toolbar" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:26px;align-items:center">
            <a class="btn <?php echo ($cat === '') ? 'btn-primary' : 'btn-ghost'; ?>" style="padding:8px 18px;font-size:14px"
               href="<?php echo e(url('news/')); ?>">全部</a>
            <a class="btn <?php echo ($cat === 'news') ? 'btn-primary' : 'btn-ghost'; ?>" style="padding:8px 18px;font-size:14px"
               href="<?php echo e(url('news/?category=news')); ?>">企业动态</a>
            <a class="btn <?php echo ($cat === 'notice') ? 'btn-primary' : 'btn-ghost'; ?>" style="padding:8px 18px;font-size:14px"
               href="<?php echo e(url('news/?category=notice')); ?>">通知公告</a>
        </div>

        <?php if (empty($list)) { ?>
            <div class="empty-state">
                <div class="ico">📄</div>
                <p>暂时还没有相关内容</p>
                <p style="margin-top:14px"><a class="btn btn-ghost" href="<?php echo e(url('news/')); ?>">查看全部动态</a></p>
            </div>
        <?php } else { ?>
            <?php foreach ($list as $i => $p) {
                $time = $p['published_at'] ? $p['published_at'] : $p['created_at'];
                $summary = $p['summary'] !== '' ? $p['summary'] : make_summary(get_post_md($p['id']), 120);
            ?>
                <a class="news-item reveal" href="<?php echo e(url('article/?id=' . (int)$p['id'])); ?>">
                    <div class="date">
                        <div class="d"><?php echo e(fmt_date($time, 'd')); ?></div>
                        <div class="ym"><?php echo e(fmt_date($time, 'Y / m')); ?></div>
                    </div>
                    <div class="main">
                        <h3>
                            <span class="tag-cat <?php echo ($p['category'] === 'notice') ? 'notice' : ''; ?>">
                                <?php echo $p['category'] === 'notice' ? '通知' : '动态'; ?>
                            </span>
                            &nbsp;<?php echo e($p['title']); ?>
                        </h3>
                        <div class="excerpt"><?php echo e($summary); ?></div>
                        <div class="meta">
                            <span><?php echo e($p['author']); ?></span>
                            <span>阅读 <?php echo (int)$p['views']; ?></span>
                            <span>评论 <?php echo (int)$p['comments']; ?></span>
                            <span><?php echo e(fmt_date($time, 'Y-m-d H:i')); ?></span>
                        </div>
                    </div>
                </a>
            <?php } ?>

            <?php echo paginate_html($total, $page, $size, $tpl); ?>
        <?php } ?>
    </div>
</section>

<?php require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_footer.php'; ?>
