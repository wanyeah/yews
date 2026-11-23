/**
 * 前台交互：导航滚动隐藏、移动端菜单、滚动入场动画、数字滚动
 * 无任何外部依赖
 */
(function () {
    'use strict';

    // ---------------- 导航栏：下滑隐藏 / 上滑显示 ----------------
    var nav = document.getElementById('siteNav');
    var lastY = window.pageYOffset || 0;
    var ticking = false;

    function onScroll() {
        var y = window.pageYOffset || document.documentElement.scrollTop || 0;
        if (nav) {
            if (y > 10) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
            if (y > 140 && y > lastY + 4) {
                nav.classList.add('hide');
            } else if (y < lastY - 4) {
                nav.classList.remove('hide');
            }
        }
        lastY = y;
        ticking = false;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(onScroll);
        }
    }, { passive: true });
    onScroll();

    // ---------------- 移动端菜单 ----------------
    var toggle = document.getElementById('navToggle');
    var menu = document.getElementById('navMenu');
    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            menu.classList.toggle('open');
        });
        menu.addEventListener('click', function (ev) {
            if (ev.target.tagName === 'A') menu.classList.remove('open');
        });
    }

    // ---------------- 滚动入场动画 ----------------
    var revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    en.target.classList.add('in');
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        for (var i = 0; i < revealEls.length; i++) io.observe(revealEls[i]);
    } else {
        for (var j = 0; j < revealEls.length; j++) revealEls[j].classList.add('in');
    }

    // ---------------- 数字滚动 ----------------
    var nums = document.querySelectorAll('[data-count]');
    function runCount(el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        var suffix = el.getAttribute('data-suffix') || '';
        var dur = 1100;
        var start = null;
        function step(ts) {
            if (start === null) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased) + suffix;
            if (p < 1) window.requestAnimationFrame(step);
        }
        window.requestAnimationFrame(step);
    }
    if ('IntersectionObserver' in window && nums.length) {
        var io2 = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    runCount(en.target);
                    io2.unobserve(en.target);
                }
            });
        }, { threshold: 0.4 });
        for (var k = 0; k < nums.length; k++) io2.observe(nums[k]);
    } else {
        for (var m = 0; m < nums.length; m++) {
            nums[m].textContent = nums[m].getAttribute('data-count') + (nums[m].getAttribute('data-suffix') || '');
        }
    }
})();
