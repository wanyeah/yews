/**
 * 站内文章无刷新加载（AJAX 局部刷新）
 * ---------------------------------------------------------------
 * 作用范围：首页「最新动态」卡片与动态页列表点击文章标题 -> 只替换 <main> 内容，
 *          导航栏保持不动，仅切换高亮项（企业动态），地址栏同步为 /article/?id=N。
 * 设计原则：
 *   1) 渐进增强：浏览器不支持 fetch / history.pushState，或请求出错时，自动退回普通跳转；
 *   2) 链接本身保持真实 URL，支持直接访问、新窗口打开、右键复制、搜索引擎抓取；
 *   3) 服务端返回的 HTML 片段与直接访问整页共用同一套渲染，排版完全一致。
 * 无任何外部依赖
 */
(function () {
    'use strict';

    if (!window.fetch || !window.history || !window.history.pushState) return;

    var main = document.querySelector('main');
    var navMenu = document.getElementById('navMenu');
    if (!main) return;

    var cache = {};
    var loading = false;
    var currentUrl = location.pathname + location.search;

    // ---------------- 自带样式（避免改动站点原有 CSS 文件）----------------
    (function injectStyle() {
        if (document.getElementById('iswPjaxStyle')) return;
        var css = ''
            + 'main{transition:opacity .14s ease;}'
            + '.ajax-bar{position:fixed;top:0;left:0;height:2px;width:0;z-index:9999;'
            + 'opacity:0;pointer-events:none;'
            + 'background:linear-gradient(90deg,#4e6ef2 0%,#8a7bf7 100%);'
            + 'box-shadow:0 0 8px rgba(78,110,242,.45);'
            + 'transition:width .28s ease,opacity .2s ease;}'
            + '.ajax-bar.on{opacity:1;}';
        var st = document.createElement('style');
        st.id = 'iswPjaxStyle';
        st.type = 'text/css';
        st.appendChild(document.createTextNode(css));
        (document.getElementsByTagName('head')[0] || document.body).appendChild(st);
    })();

    // ---------------- 顶部加载进度条 ----------------
    var bar = document.createElement('div');
    bar.className = 'ajax-bar';
    document.body.appendChild(bar);

    function startBar() {
        bar.classList.add('on');
        bar.style.width = '65%';
    }
    function endBar() {
        bar.style.width = '100%';
        window.setTimeout(function () {
            bar.classList.remove('on');
            bar.style.width = '0%';
        }, 240);
    }

    // ---------------- 工具函数 ----------------
    // 由已加载的 main.js 路径推导站点根路径，避免写死域名或子目录
    function siteBase() {
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].getAttribute('src') || '';
            var idx = src.indexOf('/assets/js/main.js');
            if (idx >= 0) return src.substring(0, idx + 1);
        }
        return '/';
    }

    // 判断是否为文章链接，返回 {url, id}
    function parseArticle(href) {
        var a = document.createElement('a');
        a.href = href;
        if (a.host !== location.host) return null;
        var path = a.pathname.replace(/index\.php$/, '').replace(/\/$/, '');
        if (path !== '/article') return null;
        var m = a.search.match(/[?&]id=(\d+)/);
        if (!m) return null;
        return { url: '/article/?id=' + m[1], id: m[1] };
    }

    function markActiveNav(key) {
        if (!navMenu) return;
        var links = navMenu.getElementsByTagName('a');
        for (var i = 0; i < links.length; i++) {
            var href = links[i].getAttribute('href') || '';
            links[i].className = links[i].className.replace(/\s*\bactive\b\s*/g, ' ').replace(/\s+/g, ' ');
            if (!key) continue;
            if (key === 'news' && /\/news\/?(\?.*)?$/.test(href)) {
                links[i].className += ' active';
            } else if (key === 'home' && href.replace(/\/$/, '') === location.origin) {
                links[i].className += ' active';
            }
        }
    }

    // 动态按需加载代码高亮（整页模式下由 site_footer.php 负责，AJAX 模式才需要）
    function ensureHighlight(cb) {
        if (window.hljs) return cb();
        var s = document.createElement('script');
        s.src = siteBase() + 'assets/vendor/highlight.min.js';
        s.onload = function () { cb(); };
        s.onerror = function () { cb(); };
        document.getElementsByTagName('head')[0].appendChild(s);
    }

    // 新内容入场处理：揭示动画 + 代码高亮
    function enhance(scope, needHljs) {
        var reveals = scope.querySelectorAll('.reveal');
        for (var i = 0; i < reveals.length; i++) reveals[i].className += ' in';

        if (needHljs && scope.querySelector('.post-body pre code')) {
            ensureHighlight(function () {
                if (!window.hljs) return;
                var blocks = scope.querySelectorAll('.post-body pre code');
                for (var j = 0; j < blocks.length; j++) {
                    try { window.hljs.highlightElement(blocks[j]); } catch (e) {}
                }
            });
        }
    }

    // ---------------- 核心：加载并渲染 ----------------
    function apply(data, url, push) {
        main.style.opacity = '0';
        window.setTimeout(function () {
            main.innerHTML = data.html || '';
            main.style.opacity = '';
            document.title = data.fullTitle || data.title || document.title;
            var meta = document.querySelector('meta[name="description"]');
            if (meta) meta.setAttribute('content', data.desc || '');
            markActiveNav(data.activeNav);
            enhance(main, data.needHljs);
            window.scrollTo(0, 0);
            if (push) history.pushState({ url: url }, document.title, url);
            currentUrl = url;
        }, 120);
    }

    function load(url, push) {
        if (loading) return;
        if (cache[url]) {
            apply(cache[url], url, push);
            return;
        }
        loading = true;
        startBar();
        fetch(url + '&ajax=1', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        }).then(function (data) {
            if (!data || typeof data.html !== 'string') throw new Error('bad response');
            cache[url] = data;
            apply(data, url, push);
        }).catch(function () {
            // 出错就退回整页跳转，保证功能可用
            window.location.href = url;
        }).then(function () {
            loading = false;
            endBar();
        });
    }

    // ---------------- 事件绑定 ----------------
    document.addEventListener('click', function (ev) {
        if (ev.defaultPrevented || ev.button !== 0) return;
        if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;

        var el = ev.target;
        while (el && el.nodeName !== 'A') el = el.parentNode;
        if (!el) return;
        if (el.target === '_blank' || el.hasAttribute('download')) return;

        var info = parseArticle(el.href);
        if (!info) return;

        ev.preventDefault();
        if (navMenu) navMenu.className = navMenu.className.replace(/\s*\bopen\b\s*/g, '');

        if (info.url === currentUrl) {
            window.scrollTo(0, 0);
            return;
        }
        load(info.url, true);
    });

    window.addEventListener('popstate', function (ev) {
        var info = parseArticle(location.href);
        if (info) {
            load(info.url, false);
        } else if (ev.state && ev.state.url) {
            // 回到首页 / 动态页等非文章页：整页还原，避免 DOM 状态错乱
            window.location.reload();
        }
    });

    // 给当前页面补一个历史状态，保证「后退」能正确回到来源页
    history.replaceState({ url: currentUrl }, document.title, location.href);
})();
