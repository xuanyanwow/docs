<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>Swoole 文档 (<?=$title?>)</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta name="description" content="由Swoole官方提供的Swoole全量新文档">
    <meta name="keywords" content="php,swoole,swoole4,swoole文档,swoole手册,swoole4文档,swoole扩展,协程">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/_images/icons/touch-icon-192x192.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="stylesheet" href="/resource/css/vue.css">
    <link rel="stylesheet" href="/resource/css/style.css?20210921">
</head>
<body>
<div id="app"></div>
<script src="/resource/js/docsify.min.js"></script>
<script src="/resource/js/prism-php.min.js"></script>
<script src="/resource/js/pangu.min.js"></script>
<script>
    window.$docsify = {
        name: 'Swoole',
        logo: 'https://wiki.swoole.com/_images/swoole-logo.svg',
        loadSidebar: true,
        loadNavbar: true,
        cornerExternalLinkTarget: '_self',
        subMaxLevel: 3,
        themeColor: '#5C9DFF',
        mergeNavbar: true,
        auto2top: true,
        search: {
            depth: 6,
            noData: '没有找到结果!',
            paths: 'auto',
            placeholder: '输入关键词搜索',
            hideOtherSidebarContent: true
        },
        alias: {
            '/process': '/process/process.md',
            '/process_pool': '/process/process_pool.md',
        },
        ad: {
            'image': 'https://wenda-1252906962.file.myqcloud.com/images/ad/crmeb-3.jpg',
            'link': 'https://github.crmeb.net/u/Swoole'
        },
        formatUpdated: '{YYYY}-{MM}-{DD} {HH}:{mm}',
        plugins: [
            function (hook, vm) {
                hook.beforeEach(function (html) {
                    var page
                    if (window.location.hash.indexOf('?') > 0) {
                        page = window.location.hash.substr(2, window.location.hash.indexOf('?') - 2);
                    } else {
                        page = window.location.hash.substr(1, window.location.hash.length)
                    }
                    if (page === '/') {
                        page = 'README'
                    }
                    const issue =
                        '📝 更新时间：{docsify-updated} ' + '，[修改](https://github.com/swoole/docs/edit/main/public/<?=$lang?>/' + page + '.md)\n';
                    return issue + html
                })

                hook.afterEach(function (html) {
                    const top = [
                        '<div class="wiki-resource" style="padding: 0px 0px 20px;">' +
                        '<a href="' + window.$docsify.ad.link + '" target="_blank">' +
                        '<img src="' + window.$docsify.ad.image + '"><span style="position: absolute;right: 15px;"><img src="/_images/ico.png"></span></a>' +
                        '</div>'
                    ].join('');

                    const bottom = [
                        '<br/>',
                        '<div class="wiki-resource">' +
                        '<a href="' + window.$docsify.ad.link + '" target="_blank">' +
                        '<img src="' + window.$docsify.ad.image + '"><span style="position: absolute;right: 15px;"><img src="/_images/ico.png"></span></a>' +
                        '</div>'
                    ].join('');

                    return top + html + bottom
                })

                hook.doneEach(function () {
                    pangu.spacingElementByClassName('content')
                    if (document.location.hash !== '#/') document.title += ' | Swoole4 文档'
                })
            }
        ]
    }
</script>
<script src="/resource/js/docsify-copy-code.min.js"></script>
<script src="/resource/js/search.min.js"></script>
<script src="/resource/js/docsify-pagination.min.js"></script>
<script>
    var _hmt = _hmt || [];
    (function () {
        var hm = document.createElement("script");
        hm.src = "https://hm.baidu.com/hm.js?4967f2faa888a2e52742bebe7fcb5f7d";
        var s = document.getElementsByTagName("script")[0];
        s.parentNode.insertBefore(hm, s);
    })();
    if (typeof navigator.serviceWorker !== 'undefined') {
        navigator.serviceWorker.register('sw.js')
    }
</script>
</body>
</html>
