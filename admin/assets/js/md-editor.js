/**
 * 后台 Markdown 编辑器（依赖本地 marked.min.js，无任何网络请求）
 */
(function () {
    var ta = document.getElementById('mdContent');
    if (!ta) return;

    var preview = document.getElementById('mdPreview');
    var toolbar = document.getElementById('mdToolbar');
    var mdBody = document.getElementById('mdBody');
    var stat = document.getElementById('mdStat');
    var form = document.getElementById('postForm');
    var fileInput = document.getElementById('fileInput');

    // ---------- 预览渲染 ----------
    function sanitize(html) {
        return html
            .replace(/<\s*(script|iframe|object|embed|link|style)[^>]*>[\s\S]*?<\s*\/\s*\1\s*>/gi, '')
            .replace(/<\s*(script|iframe|object|embed|link|style)[^>]*\/?>/gi, '')
            .replace(/\son[a-z]+\s*=\s*(".*?"|'.*?'|[^\s>]+)/gi, '')
            .replace(/(href|src)\s*=\s*(["'])\s*javascript:[^"']*\2/gi, '$1="#"');
    }

    function render() {
        var text = ta.value;
        if (typeof window.marked === 'undefined') {
            preview.innerHTML = '<p style="color:#999">预览组件未加载，请检查 admin/assets/vendor/marked.min.js</p>';
            return;
        }
        if (typeof marked.setOptions === 'function') {
            marked.setOptions({ breaks: true, gfm: true });
        }
        var html = (typeof marked.parse === 'function') ? marked.parse(text) : marked(text);
        preview.innerHTML = sanitize(html);
    }

    var timer = null;
    function scheduleRender() {
        if (timer) clearTimeout(timer);
        timer = setTimeout(render, 150);
    }

    ta.addEventListener('input', function () {
        scheduleRender();
        updateStat();
    });

    function updateStat() {
        if (stat) stat.textContent = '字数：' + ta.value.length + ' · 行：' + ta.value.split('\n').length;
    }

    // ---------- 工具栏插入 ----------
    function wrap(before, after, placeholder) {
        var start = ta.selectionStart, end = ta.selectionEnd;
        var sel = ta.value.substring(start, end);
        if (!sel) sel = placeholder || '';
        var text = before + sel + (after || before);
        ta.value = ta.value.substring(0, start) + text + ta.value.substring(end);
        ta.focus();
        var pos = start + before.length;
        ta.setSelectionRange(pos, pos + sel.length);
        render();
        updateStat();
    }

    function linePrefix(prefix) {
        var start = ta.selectionStart, end = ta.selectionEnd;
        var val = ta.value;
        var lineStart = val.lastIndexOf('\n', start - 1) + 1;
        var block = val.substring(lineStart, end);
        var lines = block.split('\n');
        var allPrefixed = lines.every(function (l) { return l.indexOf(prefix) === 0; });
        var out = lines.map(function (l) {
            return allPrefixed ? l.substring(prefix.length) : (prefix + l);
        }).join('\n');
        ta.value = val.substring(0, lineStart) + out + val.substring(end);
        ta.focus();
        render();
        updateStat();
    }

    var actions = {
        bold: function () { wrap('**', '**', '加粗文字'); },
        italic: function () { wrap('*', '*', '斜体文字'); },
        strike: function () { wrap('~~', '~~', '删除线'); },
        h2: function () { linePrefix('## '); },
        h3: function () { linePrefix('### '); },
        quote: function () { linePrefix('> '); },
        ul: function () { linePrefix('- '); },
        ol: function () { linePrefix('1. '); },
        todo: function () { linePrefix('- [ ] '); },
        code: function () { wrap('`', '`', 'code'); },
        hr: function () { wrap('\n\n---\n\n', '', ''); },
        link: function () {
            var url = window.prompt('请输入链接地址', 'https://');
            if (!url) return;
            var start = ta.selectionStart, end = ta.selectionEnd;
            var sel = ta.value.substring(start, end) || '链接文字';
            var text = '[' + sel + '](' + url + ')';
            ta.value = ta.value.substring(0, start) + text + ta.value.substring(end);
            render();
        },
        pre: function () {
            var start = ta.selectionStart, end = ta.selectionEnd;
            var sel = ta.value.substring(start, end) || '在此粘贴代码';
            var text = '\n```php\n' + sel + '\n```\n';
            ta.value = ta.value.substring(0, start) + text + ta.value.substring(end);
            render();
        },
        table: function () {
            var text = '\n| 列1 | 列2 | 列3 |\n| --- | --- | --- |\n| 内容 | 内容 | 内容 |\n';
            var start = ta.selectionStart;
            ta.value = ta.value.substring(0, start) + text + ta.value.substring(start);
            render();
        }
    };

    if (toolbar) {
        toolbar.addEventListener('click', function (ev) {
            var btn = ev.target.closest ? ev.target.closest('button[data-md]') : null;
            if (!btn) return;
            var key = btn.getAttribute('data-md');
            if (actions[key]) actions[key]();
        });
    }

    // ---------- 图片上传 ----------
    function insertText(text) {
        var start = ta.selectionStart;
        ta.value = ta.value.substring(0, start) + text + ta.value.substring(start);
        render();
        updateStat();
    }

    function uploadFiles(files) {
        if (!files || !files.length) return;
        for (var i = 0; i < files.length; i++) {
            (function (file) {
                if (!/^image\//.test(file.type)) return;
                insertText('\n![上传中…]()\n');
                var fd = new FormData();
                fd.append('file', file);
                fd.append('csrf_token', window.ISW_CSRF || '');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', window.ISW_UPLOAD_URL || 'upload.php', true);
                xhr.onload = function () {
                    var res = {};
                    try { res = JSON.parse(xhr.responseText); } catch (e) { res = { ok: false, msg: '返回格式错误' }; }
                    // 移除占位
                    ta.value = ta.value.replace('\n![上传中…]()\n', '');
                    if (res.ok && res.url) {
                        insertText('\n![' + file.name + '](' + res.url + ')\n');
                    } else {
                        alert('图片上传失败：' + (res.msg || '未知错误'));
                    }
                };
                xhr.onerror = function () {
                    ta.value = ta.value.replace('\n![上传中…]()\n', '');
                    alert('图片上传失败：网络错误');
                };
                xhr.send(fd);
            })(files[i]);
        }
    }

    var uploadBtn = document.getElementById('uploadImgBtn');
    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener('click', function () { fileInput.click(); });
        fileInput.addEventListener('change', function () {
            uploadFiles(fileInput.files);
            fileInput.value = '';
        });
    }

    // 拖拽上传
    ta.addEventListener('dragover', function (e) { e.preventDefault(); });
    ta.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
            e.preventDefault();
            uploadFiles(e.dataTransfer.files);
        }
    });
    // 粘贴上传
    ta.addEventListener('paste', function (e) {
        if (!e.clipboardData || !e.clipboardData.items) return;
        var items = e.clipboardData.items, imgs = [];
        for (var i = 0; i < items.length; i++) {
            if (items[i].type && items[i].type.indexOf('image') === 0) {
                imgs.push(items[i].getAsFile());
            }
        }
        if (imgs.length) {
            e.preventDefault();
            uploadFiles(imgs);
        }
    });

    // ---------- 分栏切换 ----------
    var toggleBtn = document.getElementById('togglePreview');
    if (toggleBtn && mdBody) {
        toggleBtn.addEventListener('click', function () {
            mdBody.classList.toggle('only-edit');
            toggleBtn.textContent = mdBody.classList.contains('only-edit') ? '◨ 显示预览' : '◧ 分栏预览';
        });
    }

    // ---------- Ctrl + S 保存 ----------
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            if (form) form.submit();
        }
    });

    // Tab 缩进支持
    ta.addEventListener('keydown', function (e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            var s = ta.selectionStart;
            ta.value = ta.value.substring(0, s) + '  ' + ta.value.substring(ta.selectionEnd);
            ta.setSelectionRange(s + 2, s + 2);
        }
    });

    render();
    updateStat();
})();
