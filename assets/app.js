/* mxyA — 悬浮气泡导航、公告弹窗、复制按钮、编号预览 */
(function () {
    'use strict';

    var ICONS = {
        home: '<path d="M4 10.6 12 4l8 6.6"/><path d="M6.6 9.6V20h10.8V9.6"/>',
        apply: '<path d="M12 5.5v13M5.5 12h13"/>',
        query: '<circle cx="11" cy="11" r="6.2"/><path d="m15.6 15.6 4.4 4.4"/>',
        admin: '<path d="M12 3.6 19 6.2v5.9c0 4.1-2.9 7.1-7 8.4-4.1-1.3-7-4.3-7-8.4V6.2z"/>',
        theme: '<circle cx="12" cy="12" r="4.2"/><path d="M12 3.2v2.2M12 18.6v2.2M3.2 12h2.2M18.6 12h2.2M5.9 5.9l1.6 1.6M16.5 16.5l1.6 1.6M18.1 5.9l-1.6 1.6M7.5 16.5l-1.6 1.6"/>',
        menu: '<path d="M4.5 7.5h15M4.5 12h15M4.5 16.5h15"/>'
    };

    function svg(name) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" ' +
            'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + ICONS[name] + '</svg>';
    }

    function buildNav() {
        var bar = document.querySelector('.topbar');
        if (!bar || document.querySelector('.fab')) return;

        var links = Array.prototype.slice.call(bar.querySelectorAll('a'));
        var themeBtn = bar.querySelector('.theme-toggle');
        var iconOf = { '首页': 'home', '申请': 'apply', '查询': 'query', '管理': 'admin' };

        var fab = document.createElement('button');
        fab.type = 'button';
        fab.className = 'fab';
        fab.setAttribute('aria-label', '打开导航');
        fab.innerHTML = svg('menu');

        var mask = document.createElement('div');
        mask.className = 'drawer-mask';

        var drawer = document.createElement('nav');
        drawer.className = 'drawer';
        drawer.setAttribute('aria-label', '站点导航');

        var head = document.createElement('div');
        head.className = 'drawer-title';
        head.innerHTML = '<span>导航</span>';
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'drawer-close';
        close.setAttribute('aria-label', '关闭');
        close.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" ' +
            'stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>';
        head.appendChild(close);
        drawer.appendChild(head);

        links.forEach(function (a) {
            var item = document.createElement('a');
            item.href = a.getAttribute('href');
            item.innerHTML = svg(iconOf[a.textContent.trim()] || 'home') + '<span>' + a.textContent.trim() + '</span>';
            drawer.appendChild(item);
        });

        var foot = document.createElement('div');
        foot.className = 'drawer-foot';
        if (themeBtn) {
            themeBtn.classList.add('drawer-theme');
            foot.appendChild(themeBtn);
        }
        drawer.appendChild(foot);

        document.body.appendChild(fab);
        document.body.appendChild(mask);
        document.body.appendChild(drawer);

        function open() {
            drawer.classList.add('show');
            mask.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function shut() {
            drawer.classList.remove('show');
            mask.classList.remove('show');
            document.body.style.overflow = '';
        }

        fab.addEventListener('click', open);
        mask.addEventListener('click', shut);
        close.addEventListener('click', shut);
        drawer.addEventListener('click', function (e) {
            if (e.target.closest('a')) shut();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') shut();
        });
    }

    function bindModal() {
        var mask = document.querySelector('.modal-mask.notice');
        if (!mask) return;

        function shut() {
            mask.classList.remove('show');
            document.body.style.overflow = '';
        }

        Array.prototype.forEach.call(mask.querySelectorAll('[data-close]'), function (el) {
            el.addEventListener('click', shut);
        });
        mask.addEventListener('click', function (e) {
            if (e.target === mask) shut();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') shut();
        });

        setTimeout(function () {
            mask.classList.add('show');
            document.body.style.overflow = 'hidden';
        }, 280);
    }

    function bindCopy() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-copy]'), function (btn) {
            btn.addEventListener('click', function () {
                var box = document.querySelector(btn.getAttribute('data-copy'));
                if (!box) return;
                var text = box.value !== undefined ? box.value : box.textContent;
                var old = btn.textContent;

                function done() {
                    btn.textContent = '已复制';
                    setTimeout(function () { btn.textContent = old; }, 1400);
                }

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done, function () {
                        box.select();
                    });
                } else {
                    box.select();
                    document.execCommand('copy');
                    done();
                }
            });
        });
    }

    function bindCodePreview() {
        var input = document.querySelector('[data-code-suffix]');
        var out = document.querySelector('[data-code-preview]');
        if (!input || !out) return;

        var prefix = out.getAttribute('data-prefix') || '';
        var year = out.getAttribute('data-year') || '';

        function render() {
            var v = (input.value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            if (v !== input.value) input.value = v;
            out.textContent = v ? prefix + '-' + year + '-' + v : prefix + '-' + year + '-XXXX';
            out.classList.toggle('ready', v.length >= 2);
        }

        input.addEventListener('input', render);
        render();
    }

    function boot() {
        buildNav();
        bindModal();
        bindCopy();
        bindCodePreview();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
