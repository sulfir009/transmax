<?php
/**
 * @var string $lang
 * @var string $head
 * @var string $header
 * @var string $content
 * @var string $footer
 * @var string $footerScripts
 */
?>
<!DOCTYPE html>
<html lang="<?php echo $lang?>">
<head>
    {!! $head !!}
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KPZPXJNJ"
            height="0" width="0" style="display:none;visibility:hidden"></iframe>
</noscript>
<!-- End Google Tag Manager (noscript) -->
<div class="wrapper">
    {!! $header !!}
    {!! $content !!}
    <div class="footer">
        {!! $footer !!}
    </div>
</div>
{!! $pageScripts !!}
</body>
</html>
