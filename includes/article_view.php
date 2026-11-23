<?php
/**
 * 文章正文渲染（公共）
 * ---------------------------------------------------------------
 * 抽出为独立函数，供两处共用，保证「直接访问」与「AJAX 无刷新加载」看到的排版完全一致：
 *   1) article/index.php        —— 整页渲染
 *   2) article/index.php?ajax=1 —— 只取本段 HTML 返回给前端
 */

if (!function_exists('isw_render_article')) {
/**
 * @param array   $post    文章行（含 id/title/author/category/summary/comments）
 * @param int     $views   阅读数
 * @param string  $content 正文 HTML（含头尾模板）
 * @param array|null $prev 上一篇
 * @param array|null $next 下一篇
 * @return string
 */
function isw_render_article($post, $views, $content, $prev, $next)
{
    $id  = (int)$post['id'];
    $time = $post['published_at'] ? $post['published_at'] : $post['created_at'];

    $html = '<section class="article-page">' . "\n"
          . '    <div class="container">' . "\n"
          . '        <article class="article-wrap">' . "\n"
          . '            <div class="article-head">' . "\n"
          . '                <div class="crumb">' . "\n"
          . '                    <a href="' . e(url()) . '">首页</a> /' . "\n"
          . '                    <a href="' . e(url('news/')) . '">企业动态</a> /' . "\n"
          . '                    ' . ($post['category'] === 'notice' ? '通知公告' : '企业动态') . "\n"
          . '                </div>' . "\n"
          . '                <h1>' . e($post['title']) . '</h1>' . "\n"
          . '                <div class="meta">' . "\n"
          . '                    <span>' . e($post['author']) . '</span>' . "\n"
          . '                    <span class="dot"></span>' . "\n"
          . '                    <span>' . e(fmt_date($time, 'Y-m-d H:i')) . '</span>' . "\n"
          . '                    <span class="dot"></span>' . "\n"
          . '                    <span class="read-badge">👁 已读 ' . (int)$views . ' 次</span>' . "\n";

    if ((int)$post['comments'] > 0 && comments_enabled()) {
        $html .= '                    <span class="dot"></span>' . "\n"
               . '                    <span>💬 评论 ' . (int)$post['comments'] . '</span>' . "\n";
    }

    $html .= '                </div>' . "\n"
           . '            </div>' . "\n\n"
           . '            <div class="article-content">' . "\n";

    if ($content === '') {
        $html .= '                <p class="empty-state">正文内容缺失，请到后台重新编辑保存。</p>' . "\n";
    } else {
        // 内容已由 Parsedown 安全模式渲染并保存在本地文件
        $html .= '                ' . $content . "\n";
    }

    $html .= '            </div>' . "\n\n"
           . '            <div class="article-foot">' . "\n"
           . '                <div>' . "\n"
           . '                    <span class="read-badge">👁 已读 ' . (int)$views . ' 次</span>' . "\n";

    if (comments_enabled()) {
        $html .= '                    &nbsp;·&nbsp;' . "\n"
               . '                    <a class="slide-ul" style="color:var(--brand)"'
               . ' href="' . e(url('comment/?id=' . $id)) . '">'
               . '查看 / 发表评论（' . (int)$post['comments'] . '）</a>' . "\n";
    }

    $html .= '                </div>' . "\n"
           . '                <div>' . "\n"
           . '                    <a class="slide-ul" href="' . e(url('news/')) . '">← 返回企业动态</a>' . "\n"
           . '                </div>' . "\n"
           . '            </div>' . "\n";

    if ($prev || $next) {
        $html .= '            <div class="article-foot" style="margin-top:18px;border-top:none;padding-top:0;display:block">' . "\n"
               . '                <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap">' . "\n"
               . '                    <div style="max-width:48%">' . "\n";
        if ($prev) {
            $html .= '                        <div style="color:var(--muted);font-size:12.5px">上一篇</div>' . "\n"
                   . '                        <a class="slide-ul" href="' . e(url('article/?id=' . (int)$prev['id'])) . '">'
                   . e(str_limit($prev['title'], 40)) . '</a>' . "\n";
        }
        $html .= '                    </div>' . "\n"
               . '                    <div style="max-width:48%;text-align:right">' . "\n";
        if ($next) {
            $html .= '                        <div style="color:var(--muted);font-size:12.5px">下一篇</div>' . "\n"
                   . '                        <a class="slide-ul" href="' . e(url('article/?id=' . (int)$next['id'])) . '">'
                   . e(str_limit($next['title'], 40)) . '</a>' . "\n";
        }
        $html .= '                    </div>' . "\n"
               . '                </div>' . "\n"
               . '            </div>' . "\n";
    }

    $html .= '        </article>' . "\n"
           . '    </div>' . "\n"
           . '</section>' . "\n";

    return $html;
}
}
