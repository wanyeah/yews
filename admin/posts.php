<?php
/**
 * 文章管理列表
 */
$pageTitle = '文章管理';
$activeNav = 'posts';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_header.php';

$kw       = isset($_GET['kw']) ? trim($_GET['kw']) : '';
$cat      = isset($_GET['category']) ? trim($_GET['category']) : '';
$status   = isset($_GET['status']) ? trim($_GET['status']) : '';
$pageSize = 12;
$page     = current_page();

$where = array('1 = 1');
$args  = array();

if ($kw !== '') {
    $where[] = '(title LIKE ? OR author LIKE ?)';
    $like = '%' . $kw . '%';
    $args[] = $like;
    $args[] = $like;
}
if ($cat === 'news' || $cat === 'notice') {
    $where[] = 'category = ?';
    $args[] = $cat;
}
if ($status === '1' || $status === '0') {
    $where[] = 'status = ?';
    $args[] = (int)$status;
}

$whereSql = implode(' AND ', $where);
$total = (int)db_val('SELECT COUNT(*) FROM isw_posts WHERE ' . $whereSql, $args, 0);
$offset = ($page - 1) * $pageSize;

$list = db_all('SELECT id, title, author, category, summary, ip, status, views, comments, published_at, created_at, updated_at
                FROM isw_posts WHERE ' . $whereSql . '
                ORDER BY id DESC LIMIT ' . (int)$offset . ', ' . (int)$pageSize, $args);

$msgMap = array(
    'saved'   => array('success', '文章已保存'),
    'created' => array('success', '文章发布成功'),
    'deleted' => array('success', '文章已删除（含本地正文文件）'),
    'toggled' => array('success', '状态已更新'),
    'notfound'=> array('error',   '文章不存在'),
);
$msgKey = isset($_GET['msg']) ? $_GET['msg'] : '';

$tpl = url('admin/posts.php?') . http_build_query(array_filter(array(
    'kw' => $kw, 'category' => $cat, 'status' => $status, 'page' => '{page}'
), function ($v) { return $v !== '' && $v !== null; }));
?>
<?php if (isset($msgMap[$msgKey])) { ?>
    <?php admin_flash($msgMap[$msgKey][1], $msgMap[$msgKey][0]); ?>
<?php } ?>

<div class="card">
    <div class="card-head">
        <h3>全部文章（<?php echo $total; ?>）</h3>
        <a class="btn btn-sm btn-primary" href="<?php echo e(url('admin/post_edit.php')); ?>">+ 发布新文章</a>
    </div>
    <div class="card-body">
        <form class="toolbar" method="get" action="<?php echo e(url('admin/posts.php')); ?>">
            <input type="text" name="kw" value="<?php echo e($kw); ?>" placeholder="搜索标题 / 作者">
            <select name="category">
                <option value="">全部分类</option>
                <option value="news"   <?php echo ($cat === 'news') ? 'selected' : ''; ?>>企业动态</option>
                <option value="notice" <?php echo ($cat === 'notice') ? 'selected' : ''; ?>>通知公告</option>
            </select>
            <select name="status">
                <option value="">全部状态</option>
                <option value="1" <?php echo ($status === '1') ? 'selected' : ''; ?>>已发布</option>
                <option value="0" <?php echo ($status === '0') ? 'selected' : ''; ?>>草稿</option>
            </select>
            <button class="btn" type="submit">筛选</button>
            <a class="btn btn-ghost" href="<?php echo e(url('admin/posts.php')); ?>">重置</a>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="width:56px">编号</th>
                    <th>标题 / 摘要</th>
                    <th style="width:100px">作者</th>
                    <th style="width:86px">分类</th>
                    <th style="width:80px">状态</th>
                    <th style="width:70px">点击</th>
                    <th style="width:70px">评论</th>
                    <th style="width:120px">IP</th>
                    <th style="width:140px">发布 / 更新</th>
                    <th style="width:170px">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($list)) { ?>
                    <tr><td colspan="10"><div class="empty">没有符合条件的文章</div></td></tr>
                <?php } else { ?>
                    <?php foreach ($list as $p) { ?>
                        <tr>
                            <td class="id">#<?php echo (int)$p['id']; ?></td>
                            <td class="cell-title">
                                <a class="t" href="<?php echo e(url('article/?id=' . (int)$p['id'])); ?>" target="_blank" title="<?php echo e($p['title']); ?>">
                                    <?php echo e($p['title']); ?>
                                </a>
                                <span class="s"><?php echo e($p['summary'] ? $p['summary'] : make_summary(get_post_md($p['id']), 60)); ?></span>
                            </td>
                            <td><?php echo e($p['author']); ?></td>
                            <td><?php echo $p['category'] === 'notice' ? '<span class="tag orange">通知</span>' : '<span class="tag blue">动态</span>'; ?></td>
                            <td><?php echo (int)$p['status'] === 1 ? '<span class="tag green">已发布</span>' : '<span class="tag gray">草稿</span>'; ?></td>
                            <td><?php echo (int)$p['views']; ?></td>
                            <td><?php echo (int)$p['comments']; ?></td>
                            <td class="muted"><?php echo e($p['ip']); ?></td>
                            <td class="muted nowrap">
                                <?php echo e(fmt_date($p['published_at'] ? $p['published_at'] : $p['created_at'], 'Y-m-d H:i')); ?>
                            </td>
                            <td class="nowrap">
                                <div class="btn-row">
                                    <a class="btn btn-sm" href="<?php echo e(url('admin/post_edit.php?id=' . (int)$p['id'])); ?>">编辑</a>
                                    <form method="post" action="<?php echo e(url('admin/post_action.php')); ?>" style="display:inline"
                                          onsubmit="return confirm('确定删除《<?php echo e($p['title']); ?>》？该操作会同时删除本地正文文件，且不可恢复。');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="act" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                                    </form>
                                    <form method="post" action="<?php echo e(url('admin/post_action.php')); ?>" style="display:inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="act" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <button class="btn btn-sm" type="submit"><?php echo (int)$p['status'] === 1 ? '转草稿' : '发布'; ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <?php echo paginate_html($total, $page, $pageSize, $tpl); ?>
    </div>
</div>

<?php require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_footer.php'; ?>
