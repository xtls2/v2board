<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8"/>
    <meta
        name="viewport"
        content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no"
    />
    <title>{{$title}}</title>
    <!-- Icons -->
    <link rel="shortcut icon" href="/theme/{{$theme}}/assets/favicons/favicon-32x32.png"/>
    <link
        rel="icon"
        type="image/png"
        sizes="192x192"
        href="/theme/{{$theme}}/assets/favicons/android-icon-192x192.png"
    />
    <link
        rel="apple-touch-icon"
        sizes="180x180"
        href="/theme/{{$theme}}/assets/favicons/apple-touch-icon-180x180.png"
    />
    <link
        rel="stylesheet"
        href="https://fonts.lug.ustc.edu.cn/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap"
    />
    <link rel="stylesheet" href="/theme/{{$theme}}/assets/vendors.chunk.css?v={{$version}}"/>
    <link rel="stylesheet" href="/theme/{{$theme}}/assets/compoments.chunk.css?v={{$version}}"/>

    <script>window.routerBase = "/";</script>
    <script>
        window.settings = {
            title: '{{$title}}',
            theme: {
                sidebar: '{{$theme_sidebar}}',
                header: '{{$theme_header}}',
                color: '{{$theme_color}}',
            },
            version: '{{$version}}',
            background_url: '{{$backgroun_url}}',
            description: '{{$description}}',
            crisp_id: '{{$crisp_id}}'
        }
    </script>
    <script>
        if (
            window.settings !== undefined &&
            window.settings.crisp_id !== undefined
        ) {
            window.$crisp = [];
            window.CRISP_WEBSITE_ID = window.settings.crisp_id;
            (function () {
                d = document;
                s = d.createElement("script");
                s.src = "https://client.crisp.chat/l.js";
                s.async = 1;
                d.getElementsByTagName("head")[0].appendChild(s);
            })();
        }
    </script>
</head>

<body>
<div id="root"></div>
<script src="/theme/{{$theme}}/assets/vendors.js?v={{$version}}"></script>
<script src="/theme/{{$theme}}/assets//compoments.js?v={{$version}}"></script>
<script src="/theme/{{$theme}}/assets//umi.js?v={{$version}}"></script>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-6ZV4J6SYM3"></script>
<script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }

    gtag('js', new Date());

    gtag('config', 'G-6ZV4J6SYM3');
</script>
</body>

</html>
