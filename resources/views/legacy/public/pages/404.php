<?
$Lang = $Main->GetDefaultLanguage();
$lang = $Lang['code'];
if(session()->has('last_lang') && in_array(session()->get('last_lang'), $Router->langList)){
    $lang = session()->get('last_lang');
}

$Router->lang = $Main->lang = $lang;

$GLOBALS['ar_define_langterms'] = $Main->GetDefineLangTerms( $lang );
$defaultLinks['index'] = $Router->writelinkOne(1);
$page_404 = 0 ;
?>
<!DOCTYPE html>
<html lang="<?php echo $Router->lang?>">
<head>
    <?php echo  view('layout.components.header.head', [
        'page_data' => $page_data,
    ])->render(); ?></head>
<body>
<div id="content" >
    <div id="page">

        <?php echo  view('layout.components.header.header', [
            'page_data' => $page_data,
        ])->render(); ?>

        <div class="container">

            <div style="text-align: center">
                <img src="/images/404.png" alt="">
            </div>
            <div style="text-align: center">
                <h3>
                    Page not found
                </h3>
            </div>

        </div>

    </div>
</div>


<?php echo  view('layout.components.footer.footer', [
    'page_data' => $page_data,
])->render(); ?>

</body>
</html>
