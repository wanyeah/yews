<?php
/**
 * 评论管理
 */
$pageTitle = '评论管理';
$activeNav = 'comments';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_header.php';

// ---------- 动作处理 ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = isset($_POST['act']) ? $_POST['act'] : '';
    $cid = valid_id(isset($_POST['cid']) ? $_POST['cid'] : 0);
    if ($cid) {
        $c = db_one('SELECT id, post_id FROM isw_comments WHERE id = ?', array($cid));
        if ($c) {
            if ($act === 'delete') {
                db_query('DELETE FROM isw_comments WHERE id = ?', array($cid));
                sync_comment_count($c['post_id']);
                admin_log('删除评论', '#' . $cid);
                redirect(url('admin/comments.php?msg=deleted'));
            } elseif ($act === 'hide') {
                db_query('UPDATE isw_comments SET status = 0 WHERE id = ?', array($cid));
                sync_comment_count($c['post_id']);
                admin_log('隐藏评论', '#' . $cid);
                redirect(url('admin/comments.php?msg=hidden'));
            } elseif ($act === 'show') {
                db_query('UPDATE isw_comments SET status = 1 WHERE id = ?', array($cid));
                sync_comment_count($c['post_id']);
                admin_log('显示评论', '#' . $cid);
                redirect(url('admin/comments.php?msg=shown'));
            }
        }
    }
}

/** 同步文章评论数 */
function sync_comment_count($postId)
{
    $n = (int)db_val('SELECT COUNT(*) FROM isw_comments WHERE post_id = ? AND status = 1', array($postId), 0);
    db_query('UPDATE isw_posts SET comments = ? WHERE id = ?', array($n, $postId));
}

$msgMap = array(
    'deleted' => array('success', '评论已删除'),
    'hidden'  => array('success', '评论已隐藏'),
    'shown'   => array('success', '评论已显示'),
);
$msgKey = isset($_GET['msg']) ? $_GET['msg'] : '';

$filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$pid    = valid_id(isset($_GET['post_id']) ? $_GET['post_id'] : 0);
$page   = current_page();
$size   = 15;

$where = array('1 = 1');
$args  = array();
if ($filter === '1' || $filter === '0') {
    $where[] = 'c.status = ?';
    $args[] = (int)$filter;
}
if ($pid) {
    $where[] = 'c.post_id = ?';
    $args[] = $pid;
}
$whereSql = implode(' AND ', $where);
$total = (int)db_val('SELECT COUNT(*) FROM isw_comments c WHERE ' . $whereSql, $args, 0);
$offset = ($page - 1) * $size;

$list = db_all('SELECT c.*, p.title AS post_title FROM isw_comments c
                LEFT JOIN isw_posts p ON p.id = c.post_id
                WHERE ' . $whereSql . ' ORDER BY c.id DESC
                LIMIT ' . (int)$offset . ', ' . (int)$size, $args);

$tpl = url('admin/comments.php?') . http_build_query(array_filter(array(
    'status' => $filter, 'post_id' => $pid ? $pid : '', 'page' => '{page}'
), function ($v) { return $v !== '' && $v !== null; }));
?>
<?php if (isset($msgMap[$msgKey])) { admin_flash($msgMap[$msgKey][1], $msgMap[$msgKey][0]); } ?>

<div class="card">
    <div class="card-head">
        <h3>评论列表（<?php echo $total; ?>）</h3>
        <span class="muted">评论入口位于前台文章底部「查看评论」</span>
    </div>
    <div class="card-body">
        <form class="toolbar" method="get" action="<?php echo e(url('admin/comments.php')); ?>">
            <select name="status">
                <option value="">全部状态</option>
                <option value="1" <?php echo ($filter === '1') ? 'selected' : ''; ?>>已显示</option>
                <option value="0" <?php echo ($filter === '0') ? 'selected' : ''; ?>>待审核</option>
            </select>
            <input type="text" name="post_id" value="<?php echo $pid ? (int)$pid : ''; ?>" placeholder="按文章编号筛选">
            <button class="btn" type="submit">筛选</button>
            <a class="btn btn-ghost" href="<?php echo e(url('admin/comments.php')); ?>">重置</a>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th style="width:56px">ID</th><th style="width:120px">评论人</th><th>内容</th>
                    <th style="width:200px">所属文章</th><th style="width:110px">IP</th>
                    <th style="width:90px">状态</th><th style="width:130px">时间</th><th style="width:130px">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($list)) { ?>
                    <tr><td colspan="8"><div class="empty">暂无评论</div></td></tr>
                <?php } else { ?>
                    <?php foreach ($list as $c) { ?>
                        <tr>
                            <td class="id">#<?php echo (int)$c['id']; ?></td>
                            <td><?php echo e($c['author']); ?></td>
                            <td style="max-width:420px"><?php echo nl2br(e($c['content'])); ?></td>
                            <td class="cell-title">
                                <?php if ($c['post_title']) { ?>
                                    <a class="t" href="<?php echo e(url('article/?id=' . (int)$c['post_id'])); ?>" target="_blank">
                                        <?php echo e(str_limit($c['post_title'], 26)); ?>
                                    </a>
                                    <span class="s">#<?php echo (int)$c['post_id']; ?></span>
                                <?php } else { ?>
                                    <span class="muted">文章已删除（#<?php echo (int)$c['post_id']; ?>）</span>
                                <?php } ?>
                            </td>
                            <td class="muted"><?php echo e($c['ip']); ?></td>
                            <td><?php echo (int)$c['status'] === 1 ? '<span class="tag green">已显示</span>' : '<span class="tag gray">待审核</span>'; ?></td>
                            <td class="muted nowrap"><?php echo e(fmt_date($c['created_at'], 'Y-m-d H:i')); ?></td>
                            <td>
                                <div class="btn-row">
                                    <?php if ((int)$c['status'] === 1) { ?>
                                        <form method="post" style="display:inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="act" value="hide">
                                            <input type="hidden" name="cid" value="<?php echo (int)$c['id']; ?>">
                                            <button class="btn btn-sm" type="submit">隐藏</button>
                                        </form>
                                    <?php } else { ?>
                                        <form method="post" style="display:inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="act" value="show">
                                            <input type="hidden" name="cid" value="<?php echo (int)$c['id']; ?>">
                                            <button class="btn btn-sm btn-primary" type="submit">通过</button>
                                        </form>
                                    <?php } ?>
                                    <form method="post" style="display:inline" onsubmit="return confirm('确定删除该评论？');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="act" value="delete">
                                        <input type="hidden" name="cid" value="<?php echo (int)$c['id']; ?>">
                                        <button class="btn btn-sm btn-danger" type="submit">删除</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <?php echo paginate_html($total, $page, $size, $tpl); ?>
    </div>
</div>
<?php require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_footer.php'; ?>
