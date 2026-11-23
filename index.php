<?php
$pageTitle = '';
$activeNav = 'home';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_header.php';

// 最新 4 篇已发布文章
$latest = db_all('SELECT id, title, author, category, summary, cover, views, comments, published_at, created_at
                  FROM isw_posts WHERE status = 1
                  ORDER BY IFNULL(published_at, created_at) DESC, id DESC LIMIT 4');

$totalPosts = (int)db_val('SELECT COUNT(*) FROM isw_posts WHERE status = 1', array(), 0);
$totalViews = (int)db_val('SELECT IFNULL(SUM(views),0) FROM isw_posts', array(), 0);
$totalCmts  = (int)db_val('SELECT COUNT(*) FROM isw_comments WHERE status = 1', array(), 0);
?>
<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <div class="hero-badge"><i></i>持续更新 · 稳步前行</div>
            <h1><?php echo e($siteName); ?></h1>
            <div class="slogan"><?php echo e($siteSlogan); ?></div>
            <p class="desc">
                我们是一支专注产品与技术的团队，坚持用扎实的基础能力去承接每一个想法。
                不追逐短期的喧嚣，只把每一次交付都当作一次积累 —— 做好量变的准备，促进事物的质变。
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?php echo e(url('news/')); ?>">查看企业动态 <span class="arrow">→</span></a>
                <a class="btn btn-ghost" href="#about">了解我们</a>
            </div>
        </div>
    </div>
</section>

<!-- ============ 企业最新动态 ============ -->
<section class="section" id="news">
    <div class="container">
        <div class="section-head reveal">
            <div class="left">
                <div class="kicker">NEWS &amp; NOTICE</div>
                <h2>企业最新动态</h2>
                <div class="sub">记录团队的每一次更新、思考与公告</div>
            </div>
            <a class="more-link slide-ul" href="<?php echo e(url('news/')); ?>">查看更多 <span class="arrow">→</span></a>
        </div>

        <?php if (empty($latest)) { ?>
            <div class="empty-state reveal">
                <div class="ico">📄</div>
                <p>还没有发布任何动态，前往后台发布第一篇文章吧</p>
            </div>
        <?php } else { ?>
            <div class="news-grid">
                <?php foreach ($latest as $i => $p) {
                    $time = $p['published_at'] ? $p['published_at'] : $p['created_at'];
                    $summary = $p['summary'] !== '' ? $p['summary'] : make_summary(get_post_md($p['id']), 100);
                ?>
                    <a class="news-card reveal d<?php echo ($i % 4) + 1; ?>" href="<?php echo e(url('article/?id=' . (int)$p['id'])); ?>">
                        <div class="meta">
                            <span class="cat <?php echo ($p['category'] === 'notice') ? 'notice' : ''; ?>">
                                <?php echo $p['category'] === 'notice' ? '通知' : '动态'; ?>
                            </span>
                            <span><?php echo e(fmt_date($time, 'Y-m-d')); ?></span>
                        </div>
                        <h3><?php echo e($p['title']); ?></h3>
                        <div class="excerpt"><?php echo e($summary); ?></div>
                        <div class="foot">
                            <span><?php echo e($p['author']); ?></span>
                            <span>阅读 <?php echo (int)$p['views']; ?></span>
                        </div>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</section>

<!-- ============ 服务与能力 ============ -->
<section class="section soft" id="service">
    <div class="container">
        <div class="section-head reveal">
            <div class="left">
                <div class="kicker">WHAT WE DO</div>
                <h2>服务与能力</h2>
                <div class="sub">以工程化的方式，把想法落成可用的产品</div>
            </div>
        </div>

        <div class="feature-grid">
            <div class="feature-card reveal d1">
                <div class="ico">🖥</div>
                <h3>网站建设与定制开发</h3>
                <p>企业官网、门户站点、后台管理系统，从信息架构到视觉落地，提供一站式设计与开发服务，交付即用、易于维护。</p>
            </div>
            <div class="feature-card reveal d2">
                <div class="ico">⚙</div>
                <h3>业务系统与技术中台</h3>
                <p>围绕真实业务场景搭建数据模型与流程系统，注重稳定性与可扩展性，让系统随着业务增长而平滑演进。</p>
            </div>
            <div class="feature-card reveal d3">
                <div class="ico">☁</div>
                <h3>部署运维与长期陪伴</h3>
                <p>服务器环境搭建、域名备案指引、数据备份与安全加固，交付之后依然保持响应，陪你把产品跑稳跑久。</p>
            </div>
        </div>
    </div>
</section>

<!-- ============ 数据条 ============ -->
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="stat-strip reveal">
            <div class="item">
                <div class="num"><span data-count="<?php echo $totalPosts; ?>">0</span></div>
                <div class="label">累计发布内容</div>
            </div>
            <div class="item">
                <div class="num"><span data-count="<?php echo $totalViews; ?>">0</span></div>
                <div class="label">文章累计阅读</div>
            </div>
            <div class="item">
                <div class="num"><span data-count="<?php echo $totalCmts; ?>">0</span></div>
                <div class="label">收到读者评论</div>
            </div>
            <div class="item">
                <div class="num"><span data-count="100" data-suffix="%">0</span></div>
                <div class="label">投入与专注度</div>
            </div>
        </div>
    </div>
</section>

<!-- ============ 关于我们 ============ -->
<section class="section soft" id="about">
    <div class="container">
        <div class="about-wrap">
            <div class="about-text reveal">
                <div class="kicker" style="font-size:13px;color:var(--brand);letter-spacing:2px;font-weight:600">ABOUT US</div>
                <h2 style="margin-top:10px">把每一次积累，都变成下一次的起点</h2>
                <p>
                    晚夜工作室成立于一群热爱技术与产品的人之间。我们习惯把大问题拆成小问题，
                    把长周期的目标拆成每周可以完成的具体事项。
                </p>
                <p>
                    我们不太相信"一蹴而就"，更愿意相信日复一日的正确积累：
                    写好每一行代码、打磨每一处细节、认真回应每一次反馈。
                    当量变足够充分，质变自然会到来。
                </p>
                <p>
                    如果你也认同这样的节奏，欢迎与我们聊聊你的想法。
                </p>
            </div>
            <div class="quote-box reveal d2">
                <div class="q">做好量变的准备<br>促进事物的质变</div>
                <div class="by">—— <?php echo e($siteName); ?> · 企业口号</div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'site_footer.php'; ?>
