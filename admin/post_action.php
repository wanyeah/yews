<?php
/**
 * 文章操作：删除 / 状态切换（POST + CSRF + 鉴权）
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/posts.php'));
}
csrf_check();

$act = isset($_POST['act']) ? $_POST['act'] : '';
$id  = valid_id(isset($_POST['id']) ? $_POST['id'] : 0);

if (!$id) {
    redirect(url('admin/posts.php?msg=notfound'));
}

$post = db_one('SELECT id, title FROM isw_posts WHERE id = ?', array($id));
if ($post === null) {
    redirect(url('admin/posts.php?msg=notfound'));
}

if ($act === 'delete') {
    // 删除评论 + 阅读记录 + 正文文件 + 主记录
    db_query('DELETE FROM isw_comments   WHERE post_id = ?', array($id));
    db_query('DELETE FROM isw_post_views WHERE post_id = ?', array($id));
    delete_post_content($id);
    db_query('DELETE FROM isw_posts WHERE id = ?', array($id));
    admin_log('删除文章', '#' . $id . ' ' . $post['title']);
    redirect(url('admin/posts.php?msg=deleted'));
}

if ($act === 'toggle') {
    $cur = (int)db_val('SELECT status FROM isw_posts WHERE id = ?', array($id), 0);
    if ($cur === 1) {
        db_query('UPDATE isw_posts SET status = 0 WHERE id = ?', array($id));
    } else {
        db_query('UPDATE isw_posts SET status = 1, published_at = IFNULL(published_at, NOW()) WHERE id = ?', array($id));
    }
    admin_log('切换文章状态', '#' . $id . ' ' . $post['title'] . ' => ' . ($cur === 1 ? '草稿' : '已发布'));
    redirect(url('admin/posts.php?msg=toggled'));
}

redirect(url('admin/posts.php'));
