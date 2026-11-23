<?php
/**
 * 后台概览
 */
$pageTitle = '概览';
$activeNav = 'dashboard';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_header.php';

$totalPosts    = (int)db_val('SELECT COUNT(*) FROM isw_posts', array(), 0);
$pubPosts      = (int)db_val('SELECT COUNT(*) FROM isw_posts WHERE status = 1', array(), 0);
$draftPosts    = (int)db_val('SELECT COUNT(*) FROM isw_posts WHERE status = 0', array(), 0);
$noticeCount   = (int)db_val("SELECT COUNT(*) FROM isw_posts WHERE category = 'notice'", array(), 0);
$totalViews    = (int)db_val('SELECT IFNULL(SUM(views),0) FROM isw_posts', array(), 0);
$totalComments = (int)db_val('SELECT COUNT(*) FROM isw_comments WHERE status = 1', array(), 0);
$pendingCmt    = (int)db_val('SELECT COUNT(*) FROM isw_comments WHERE status = 0', array(), 0);
$todayViews    = (int)db_val('SELECT COUNT(*) FROM isw_post_views WHERE created_at >= CURDATE()', array(), 0);
$todayPosts    = (int)db_val('SELECT COUNT(*) FROM isw_posts WHERE created_at >= CURDATE()', array(), 0);

// 近 7 天阅读量
$days = array();
$trend = array();
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime('-' . $i . ' day'));
    $days[] = $d;
    $trend[$d] = 0;
}
$rows = db_all('SELECT DATE(created_at) d, COUNT(*) c FROM isw_post_views
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(created_at)');
foreach ($rows as $r) {
    if (isset($trend[$r['d']])) $trend[$r['d']] = (int)$r['c'];
}
$maxTrend = max(1, max($trend));

// 热门文章 TOP5
$hot = db_all('SELECT id, title, views, comments, published_at FROM isw_posts
               WHERE status = 1 ORDER BY views DESC, id DESC LIMIT 5');

// 最新文章
$latest = db_all('SELECT id, title, category, status, views, comments, published_at, created_at
                  FROM isw_posts ORDER BY id DESC LIMIT 6');

// 最新评论
$cmts = db_all('SELECT c.id, c.post_id, c.author, c.content, c.status, c.created_at, p.title
                FROM isw_comments c LEFT JOIN isw_posts p ON p.id = c.post_id
                ORDER BY c.id DESC LIMIT 5');
?>
<div class="stat-grid">
    <div class="stat blue">
        <div class="label">文章总数</div>
        <div class="value"><?php echo $totalPosts; ?></div>
        <div class="foot">已发布 <?php echo $pubPosts; ?> · 草稿 <?php echo $draftPosts; ?> · 通知 <?php echo $noticeCount; ?></div>
    </div>
    <div class="stat green">
        <div class="label">累计点击量</div>
        <div class="value"><?php echo number_format($totalViews); ?></div>
        <div class="foot">今日新增 <?php echo $todayViews; ?> 次阅读（已去重）</div>
    </div>
    <div class="stat orange">
        <div class="label">评论总数</div>
        <div class="value"><?php echo $totalComments; ?></div>
        <div class="foot">待审核 <?php echo $pendingCmt; ?> 条</div>
    </div>
    <div class="stat purple">
        <div class="label">今日动态</div>
        <div class="value"><?php echo $todayPosts; ?></div>
        <div class="foot">今日新增文章数</div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-head"><h3>近 7 天阅读趋势</h3><span class="muted">按设备去重统计</span></div>
        <div class="card-body">
            <div style="display:flex;align-items:flex-end;gap:14px;height:170px;padding-top:10px">
                <?php foreach ($trend as $d => $v) {
                    $h = (int)round($v / $maxTrend * 130);
                    if ($v > 0 && $h < 6) $h = 6;
                ?>
                    <div style="flex:1;text-align:center">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:4px"><?php echo $v; ?></div>
                        <div style="height:<?php echo $h; ?>px;background:linear-gradient(180deg,#5b81ff,#2b5cff);border-radius:6px 6px 0 0;transition:height .4s"></div>
                        <div style="font-size:12px;color:#9099a3;margin-top:8px"><?php echo e(date('m-d', strtotime($d))); ?></div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>热门文章 TOP 5</h3><a class="btn btn-sm" href="<?php echo e(url('admin/posts.php')); ?>">全部文章</a></div>
        <div class="card-body" style="padding:8px 20px 16px">
            <?php if (empty($hot)) { ?>
                <div class="empty">暂无文章，去发布第一篇吧</div>
            <?php } else { ?>
                <table class="table">
                    <tbody>
                    <?php foreach ($hot as $i => $p) { ?>
                        <tr>
                            <td style="width:28px;color:#b6bcc6;font-weight:700"><?php echo $i + 1; ?></td>
                            <td class="cell-title">
                                <a class="t" href="<?php echo e(url('article/?id=' . (int)$p['id'])); ?>" target="_blank"><?php echo e($p['title']); ?></a>
                            </td>
                            <td class="nowrap muted">👁 <?php echo (int)$p['views']; ?></td>
                            <td class="nowrap muted">💬 <?php echo (int)$p['comments']; ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>
</div>

<div style="height:18px"></div>

<div class="card">
    <div class="card-head">
        <h3>最新文章</h3>
        <a class="btn btn-sm btn-primary" href="<?php echo e(url('admin/post_edit.php')); ?>">+ 发布新文章</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th style="width:60px">编号</th><th>标题</th><th style="width:90px">分类</th>
                <th style="width:80px">状态</th><th style="width:80px">点击</th><th style="width:80px">评论</th>
                <th style="width:150px">发布时间</th><th style="width:130px">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($latest)) { ?>
                <tr><td colspan="8"><div class="empty">暂无文章</div></td></tr>
            <?php } else { ?>
                <?php foreach ($latest as $p) { ?>
                    <tr>
                        <td class="id">#<?php echo (int)$p['id']; ?></td>
                        <td class="cell-title">
                            <a class="t" href="<?php echo e(url('article/?id=' . (int)$p['id'])); ?>" target="_blank"><?php echo e($p['title']); ?></a>
                        </td>
                        <td><?php echo $p['category'] === 'notice' ? '<span class="tag orange">通知</span>' : '<span class="tag blue">动态</span>'; ?></td>
                        <td><?php echo (int)$p['status'] === 1 ? '<span class="tag green">已发布</span>' : '<span class="tag gray">草稿</span>'; ?></td>
                        <td><?php echo (int)$p['views']; ?></td>
                        <td><?php echo (int)$p['comments']; ?></td>
                        <td class="muted nowrap"><?php echo e(fmt_date($p['published_at'] ? $p['published_at'] : $p['created_at'], 'Y-m-d H:i')); ?></td>
                        <td class="nowrap">
                            <a class="btn btn-sm" href="<?php echo e(url('admin/post_edit.php?id=' . (int)$p['id'])); ?>">编辑</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div style="height:18px"></div>

<div class="card">
    <div class="card-head"><h3>最新评论</h3><a class="btn btn-sm" href="<?php echo e(url('admin/comments.php')); ?>">评论管理</a></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th style="width:60px">ID</th><th style="width:120px">评论人</th><th>内容</th><th style="width:100px">状态</th><th style="width:140px">时间</th></tr></thead>
            <tbody>
            <?php if (empty($cmts)) { ?>
                <tr><td colspan="5"><div class="empty">暂无评论</div></td></tr>
            <?php } else { ?>
                <?php foreach ($cmts as $c) { ?>
                    <tr>
                        <td class="id">#<?php echo (int)$c['id']; ?></td>
                        <td><?php echo e($c['author']); ?></td>
                        <td>
                            <?php echo e(str_limit($c['content'], 60)); ?>
                            <span class="muted">（<?php echo e(str_limit($c['title'] ? $c['title'] : '文章已删除', 24)); ?>）</span>
                        </td>
                        <td><?php echo (int)$c['status'] === 1 ? '<span class="tag green">已显示</span>' : '<span class="tag gray">待审核</span>'; ?></td>
                        <td class="muted nowrap"><?php echo e(fmt_date($c['created_at'], 'Y-m-d H:i')); ?></td>
                    </tr>
                <?php } ?>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_footer.php'; ?>
