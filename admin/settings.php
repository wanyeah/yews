<?php
/**
 * 站点设置
 */
$pageTitle = '站点设置';
$activeNav = 'settings';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_header.php';

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $siteName   = trim(isset($_POST['site_name']) ? $_POST['site_name'] : '');
    $siteSlogan = trim(isset($_POST['site_slogan']) ? $_POST['site_slogan'] : '');
    $icp        = trim(isset($_POST['icp']) ? $_POST['icp'] : '');
    $enableCmt  = isset($_POST['enable_comment']) ? '1' : '0';
    $pageSize   = (int)(isset($_POST['page_size']) ? $_POST['page_size'] : 9);
    if ($pageSize < 3) $pageSize = 3;
    if ($pageSize > 30) $pageSize = 30;

    if ($siteName !== '')   set_setting('site_name', $siteName);
    if ($siteSlogan !== '') set_setting('site_slogan', $siteSlogan);
    set_setting('icp', $icp);
    set_setting('enable_comment', $enableCmt);
    set_setting('page_size', (string)$pageSize);
    admin_log('修改站点设置', '');
    $saved = true;
}

$siteName   = setting('site_name', SITE_NAME);
$siteSlogan = setting('site_slogan', SITE_SLOGAN);
$icp        = setting('icp', SITE_ICP);
$enableCmt  = setting('enable_comment', '1');
$pageSize   = (int)setting('page_size', '9');

// 环境自检
$checks = array(
    'PHP 版本' => PHP_VERSION,
    '正文目录 data/posts' => (is_dir(DATA_PATH) ? '存在' : '缺失') . (is_writable(DATA_PATH) ? ' · 可写' : ' · 不可写（请设置 755/777）'),
    '上传目录 uploads'    => (is_dir(UPLOAD_PATH) ? '存在' : '缺失') . (is_writable(UPLOAD_PATH) ? ' · 可写' : ' · 不可写'),
    'GD 库（验证码）'      => function_exists('imagecreatetruecolor') ? '已启用' : '未启用（验证码将异常）',
    '上传限制'            => ini_get('upload_max_filesize') . ' / post ' . ini_get('post_max_size'),
    '正文文件数'          => count(glob(DATA_PATH . '*.html')) . ' 个',
);
?>
<?php if ($saved) { admin_flash('设置已保存', 'success'); } ?>

<div class="grid-2">
    <div class="card">
        <div class="card-head"><h3>基本设置</h3></div>
        <div class="card-body">
            <form method="post">
                <?php echo csrf_field(); ?>
                <div class="form-item">
                    <label class="lbl" for="site_name">站点名称</label>
                    <input type="text" id="site_name" name="site_name" value="<?php echo e($siteName); ?>">
                </div>
                <div class="form-item">
                    <label class="lbl" for="site_slogan">企业口号</label>
                    <input type="text" id="site_slogan" name="site_slogan" value="<?php echo e($siteSlogan); ?>">
                </div>
                <div class="form-item">
                    <label class="lbl" for="icp">ICP 备案号</label>
                    <input type="text" id="icp" name="icp" value="<?php echo e($icp); ?>">
                </div>
                <div class="form-item">
                    <label class="lbl" for="page_size">前台动态页每页显示条数</label>
                    <input type="number" id="page_size" name="page_size" min="3" max="30" value="<?php echo e($pageSize); ?>">
                </div>
                <div class="form-item">
                    <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#30363f">
                        <input type="checkbox" name="enable_comment" value="1" style="width:auto"
                            <?php echo ($enableCmt === '1') ? 'checked' : ''; ?>>
                        开启前台评论功能（关闭后文章底部不再显示评论入口）
                    </label>
                </div>
                <button class="btn btn-primary" type="submit">保存设置</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>运行环境自检</h3></div>
        <div class="card-body">
            <table class="table">
                <tbody>
                <?php foreach ($checks as $k => $v) { ?>
                    <tr><th style="width:180px"><?php echo e($k); ?></th><td><?php echo e($v); ?></td></tr>
                <?php } ?>
                </tbody>
            </table>
            <div class="help">
                管理员账号在 <code>includes/config.php</code> 中修改（ADMIN_USER / ADMIN_PASS）。<br>
                文章正文模板的头尾在 <code>includes/functions.php</code> 的
                <code>article_tpl_head()</code> / <code>article_tpl_foot()</code> 中修改。
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_footer.php'; ?>
