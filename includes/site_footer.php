<?php
/**
 * 前台公共底部
 */
if (!isset($siteName))   $siteName   = setting('site_name', SITE_NAME);
if (!isset($siteSlogan)) $siteSlogan = setting('site_slogan', SITE_SLOGAN);
if (!isset($icp))        $icp        = setting('icp', SITE_ICP);
?>
</main>

<footer class="site-footer" id="contact">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="name"><?php echo e($siteName); ?></div>
                <div class="slogan">
                    <?php echo e($siteSlogan); ?><br>
                    我们相信持续的量变积累，终会带来真正的质变。
                </div>
            </div>
            <div>
                <h4>导航</h4>
                <ul>
                    <li><a class="slide-ul" href="<?php echo e(url()); ?>">网站首页</a></li>
                    <li><a class="slide-ul" href="<?php echo e(url('news/')); ?>">企业动态</a></li>
                    <li><a class="slide-ul" href="<?php echo e(url('#about')); ?>">关于我们</a></li>
                    <li><a class="slide-ul" href="<?php echo e(url('#service')); ?>">服务与能力</a></li>
                </ul>
            </div>
            <div>
                <h4>动态分类</h4>
                <ul>
                    <li><a class="slide-ul" href="<?php echo e(url('news/?category=news')); ?>">企业动态</a></li>
                    <li><a class="slide-ul" href="<?php echo e(url('news/?category=notice')); ?>">通知公告</a></li>
                </ul>
            </div>
            <div>
                <h4>联系</h4>
                <ul>
                    <li>邮箱：me@<?php echo e(SITE_DOMAIN); ?></li>
                    <li>网址：<?php echo e(SITE_DOMAIN); ?></li>
                    <li>工作日 9:00 - 16:00</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                © <?php echo e(SITE_COPYRIGHT_YEAR); ?> <?php echo e($siteName); ?> 版权所有 ·
                <a class="slide-ul" href="https://beian.miit.gov.cn/" target="_blank" rel="noopener nofollow"><?php echo e($icp); ?></a>
            </div>
            <div><?php echo e($siteSlogan); ?></div>
        </div>
    </div>
</footer>

<script src="<?php echo e(url('assets/js/main.js')); ?>"></script>
<script src="<?php echo e(url('assets/js/pjax.js')); ?>"></script>
<?php if (!empty($hljs)) { ?>
<script src="<?php echo e(url('assets/vendor/highlight.min.js')); ?>"></script>
<script>
(function () {
    if (typeof hljs !== 'undefined') {
        var blocks = document.querySelectorAll('.post-body pre code');
        for (var i = 0; i < blocks.length; i++) { try { hljs.highlightElement(blocks[i]); } catch (e) {} }
    }
})();
</script>
<?php } ?>
</body>
</html>
