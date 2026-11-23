<?php
/**
 * 文章编辑 / 发布（Markdown 编辑器）
 */
$pageTitle = '文章编辑';
$activeNav = 'new';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_header.php';

$id       = valid_id(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$isEdit   = $id > 0;
$error    = '';
$notice   = '';

$post = array(
    'id'        => 0,
    'title'     => '',
    'author'    => SITE_NAME,
    'category'  => 'news',
    'summary'   => '',
    'cover'     => '',
    'status'    => 1,
    'published_at' => '',
);
$md = '';

if ($isEdit) {
    $row = db_one('SELECT * FROM isw_posts WHERE id = ?', array($id));
    if ($row === null) {
        redirect(url('admin/posts.php?msg=notfound'));
    }
    $post = array_merge($post, $row);
    $md = get_post_md($id);
}

// ---------------- 保存 ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title    = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $author   = trim(isset($_POST['author']) ? $_POST['author'] : '');
    $category = (isset($_POST['category']) && $_POST['category'] === 'notice') ? 'notice' : 'news';
    $summary  = trim(isset($_POST['summary']) ? $_POST['summary'] : '');
    $cover    = trim(isset($_POST['cover']) ? $_POST['cover'] : '');
    $status   = (isset($_POST['status']) && (int)$_POST['status'] === 0) ? 0 : 1;
    $mdText   = isset($_POST['content']) ? $_POST['content'] : '';

    if ($title === '') {
        $error = '标题不能为空';
    } elseif (mb_strlen($title, 'UTF-8') > 200) {
        $error = '标题不能超过 200 个字';
    } elseif ($mdText === '') {
        $error = '正文不能为空';
    } else {
        if ($author === '') $author = SITE_NAME;
        if ($summary === '') $summary = make_summary($mdText, 100);
        if ($cover !== '' && !preg_match('#^https?://#i', $cover) && strpos($cover, '/uploads/') !== 0) {
            $cover = ''; // 只接受站内 /uploads/ 或 http(s) 地址
        }

        if ($isEdit) {
            db_query('UPDATE isw_posts SET title = ?, author = ?, category = ?, summary = ?, cover = ?,
                      status = ?, published_at = IF(?, IFNULL(published_at, NOW()), published_at), updated_at = NOW()
                      WHERE id = ?',
                array($title, $author, $category, $summary, $cover, $status, $status, $id));
            save_post_content($id, $mdText);
            admin_log('编辑文章', '#' . $id . ' ' . $title);
            if (isset($_POST['back'])) {
                redirect(url('admin/posts.php?msg=saved'));
            }
            $notice = '已保存 · ' . date('H:i:s');
        } else {
            $ip = client_ip();
            db_query('INSERT INTO isw_posts (title, author, category, summary, cover, ip, status, views, comments, published_at, created_at)
                      VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, IF(?=1, NOW(), NULL), NOW())',
                array($title, $author, $category, $summary, $cover, $ip, $status, $status));
            $newId = (int)db()->lastInsertId();
            if ($newId > 0) {
                save_post_content($newId, $mdText);
                admin_log('发布文章', '#' . $newId . ' ' . $title);
                if (isset($_POST['back'])) {
                    redirect(url('admin/posts.php?msg=created'));
                }
                redirect(url('admin/post_edit.php?id=' . $newId . '&msg=created'));
            } else {
                $error = '创建失败，请重试';
            }
        }

        // 重新读取最新数据
        $row = db_one('SELECT * FROM isw_posts WHERE id = ?', array($id));
        if ($row) $post = array_merge($post, $row);
        $md = $mdText;
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'created' && $notice === '') {
    $notice = '文章发布成功';
}

$activeNav = $isEdit ? 'posts' : 'new';
?>
<?php if ($error !== '') { admin_flash($error, 'error'); } ?>
<?php if ($notice !== '') { admin_flash($notice, 'success'); } ?>

<form method="post" id="postForm" autocomplete="off">
<?php echo csrf_field(); ?>
<?php if ($isEdit) { ?><input type="hidden" name="id" value="<?php echo (int)$id; ?>"><?php } ?>

<div class="card">
    <div class="card-head">
        <h3><?php echo $isEdit ? '编辑文章 #' . (int)$id : '发布新文章'; ?></h3>
        <div class="btn-row">
            <?php if ($isEdit) { ?>
                <a class="btn btn-sm" href="<?php echo e(url('article/?id=' . (int)$id)); ?>" target="_blank">预览前台</a>
            <?php } ?>
            <button class="btn btn-sm" type="submit" name="back" value="1">保存并返回列表</button>
            <button class="btn btn-sm btn-primary" type="submit">保存</button>
        </div>
    </div>
    <div class="card-body">
        <div class="form-item">
            <label class="lbl" for="title">文章标题</label>
            <input type="text" id="title" name="title" maxlength="200" placeholder="请输入标题"
                   value="<?php echo e($post['title']); ?>" required>
        </div>

        <div class="form-row">
            <div class="form-item">
                <label class="lbl" for="author">作者</label>
                <input type="text" id="author" name="author" maxlength="64" value="<?php echo e($post['author']); ?>">
            </div>
            <div class="form-item">
                <label class="lbl" for="category">分类</label>
                <select id="category" name="category">
                    <option value="news"   <?php echo ($post['category'] === 'news') ? 'selected' : ''; ?>>企业动态</option>
                    <option value="notice" <?php echo ($post['category'] === 'notice') ? 'selected' : ''; ?>>通知公告</option>
                </select>
            </div>
            <div class="form-item">
                <label class="lbl" for="status">状态</label>
                <select id="status" name="status">
                    <option value="1" <?php echo ((int)$post['status'] === 1) ? 'selected' : ''; ?>>已发布</option>
                    <option value="0" <?php echo ((int)$post['status'] === 0) ? 'selected' : ''; ?>>草稿（前台不显示）</option>
                </select>
            </div>
        </div>

        <div class="form-item">
            <label class="lbl" for="summary">摘要（列表卡片展示，留空则自动截取正文前 100 字）</label>
            <textarea id="summary" name="summary" rows="2" maxlength="255" placeholder="一句话概括这篇文章"><?php echo e($post['summary']); ?></textarea>
        </div>

        <div class="form-item">
            <label class="lbl" for="cover">封面图地址（可选，填写 /uploads/xxx.jpg 或 http 地址）</label>
            <input type="text" id="cover" name="cover" value="<?php echo e($post['cover']); ?>" placeholder="可留空">
        </div>
    </div>
</div>

<div style="height:16px"></div>

<div class="card">
    <div class="card-head">
        <h3>正文（Markdown）</h3>
        <span class="muted" id="mdStat">字数：0</span>
    </div>
    <div class="card-body">
        <div class="md-editor">
            <div class="md-toolbar" id="mdToolbar">
                <button type="button" data-md="bold" title="加粗"><b>B</b></button>
                <button type="button" data-md="italic" title="斜体"><i>I</i></button>
                <button type="button" data-md="strike" title="删除线"><s>S</s></button>
                <span class="sep"></span>
                <button type="button" data-md="h2" title="二级标题">H2</button>
                <button type="button" data-md="h3" title="三级标题">H3</button>
                <span class="sep"></span>
                <button type="button" data-md="quote" title="引用">❝</button>
                <button type="button" data-md="ul" title="无序列表">•</button>
                <button type="button" data-md="ol" title="有序列表">1.</button>
                <button type="button" data-md="todo" title="任务列表">☑</button>
                <span class="sep"></span>
                <button type="button" data-md="link" title="链接">🔗</button>
                <button type="button" data-md="code" title="行内代码">&lt;/&gt;</button>
                <button type="button" data-md="pre" title="代码块">{ }</button>
                <button type="button" data-md="table" title="表格">▦</button>
                <button type="button" data-md="hr" title="分割线">—</button>
                <span class="sep"></span>
                <button type="button" id="uploadImgBtn" title="插入图片（也支持直接粘贴/拖拽）">🖼 图片</button>
                <div class="right">
                    <button type="button" id="togglePreview" title="切换预览">◧ 分栏预览</button>
                </div>
            </div>
            <div class="md-body" id="mdBody">
                <div class="md-input">
                    <textarea id="mdContent" name="content" spellcheck="false"
                              placeholder="在此书写正文，支持 Markdown 语法。发布时正文会渲染为 HTML 并保存到 data/posts/ 目录。"><?php echo e($md); ?></textarea>
                </div>
                <div class="md-preview markdown-body" id="mdPreview"></div>
            </div>
        </div>
        <div class="help">
            提示：支持 Markdown 语法；可直接 <b>拖拽 / 粘贴</b> 图片到编辑区自动上传；
            快捷键 Ctrl/⌘ + S 保存。正文发布后自动套用统一头部与尾部模板（includes/functions.php 中 article_tpl_head / article_tpl_foot）。
        </div>
    </div>
</div>

<div style="height:16px"></div>
<div class="btn-row" style="justify-content:flex-end">
    <a class="btn" href="<?php echo e(url('admin/posts.php')); ?>">返回列表</a>
    <button class="btn btn-primary" type="submit" name="back" value="1">保存并返回列表</button>
    <button class="btn btn-primary" type="submit">保存</button>
</div>
</form>

<input type="file" id="fileInput" accept="image/*" style="display:none">
<script src="<?php echo e(url('admin/assets/vendor/marked.min.js')); ?>"></script>
<script>
window.ISW_UPLOAD_URL = '<?php echo e(url('admin/upload.php')); ?>';
window.ISW_CSRF = '<?php echo e(csrf_token()); ?>';
</script>
<script src="<?php echo e(url('admin/assets/js/md-editor.js')); ?>"></script>

<?php require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_footer.php'; ?>
