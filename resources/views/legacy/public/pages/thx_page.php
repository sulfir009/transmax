<!DOCTYPE html>
<html lang="<?php echo $Router->lang?>">
<head>
    <?php echo  view('layout.components.header.head', [
        'page_data' => $page_data,
    ])->render(); ?></head>
<body>
<div class="wrapper">
    <div class="header" style="padding: 0px;">
        <?php echo  view('layout.components.header.header', [
            'page_data' => $page_data,
            'header_class' => 'header_blue',
        ])->render(); ?>
    </div>
    <div class="content" style="padding-top:60px;">
        <div class="thx_content_wrapper">
            <div class="thx_block">
                <div class="container">
                    <div class="thx_block_title h2_title">
                        <?php echo $GLOBALS['dictionary']['MSG_MSG_THX_PAGE_DYAKUYU_ZA_BRONYUVANNYA_BILETU']?>
                    </div>
                    <div class="thx_block_subtitle par">
                        <?php echo $GLOBALS['dictionary']['MSG_MSG_THX_PAGE_DANI_VASHOGO_BILETU']?>
                    </div>
                    <?php if (!$_SESSION['user']['auth']) { ?>
                        <a href="<?php echo $Router->writelink(77)?>" class="private_link h4_title blue_btn">
                        <span class="hidden-xs">
                            <?php echo  __('dictionary.MSG_MSG_THX_PAGE_PEREJTI_U_PERSONALINIJ_KABINET') ?>
                        </span>
                        <span class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm col-xs-12">
                            <?php echo  __('dictionary.MSG_MSG_THX_PAGE_PERSONALINIJ_KABINET') ?>
                        </span>
                    </a>
                <? } else { ?>
                    <a href="<?php echo  route('future_races') ?>" class="private_link h4_title blue_btn">
                        <span class="hidden-xs">
                            <?php echo  __('dictionary.MSG_MSG_THX_PAGE_PEREJTI_U_PERSONALINIJ_KABINET') ?>
                        </span>
                        <span class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm col-xs-12">
                            <?php echo  __('dictionary.MSG_MSG_THX_PAGE_PERSONALINIJ_KABINET') ?>
                        </span>
                    </a>
                <? } ?>
            </div>
        </div>
        <div class="txh_image">
            <img src="<?php echo  asset('images/legacy/common/thx_img.png'); ?>" alt="thanks" class="fit_img">
        </div>
    </div>
    <div class="footer">
        <<?php echo  view('layout.components.footer.footer', [
            'page_data' => $page_data,
        ])->render(); ?>
    </div>
</div>
    <?php echo  view('layout.components.footer.footer_scripts', [
        'page_data' => $page_data,
    ])->render(); ?>
    <script>
    $(document).ready(function () {
        // AJAX-запрос при загрузке страницы
        $.ajax({
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: '/ajax/ru',
            data: {'request': 'clear_session_data'},
            success: function (response) {
                // Обработка ответа
                if ($.trim(response.data) == 'ok') {
                    // Данные из сессии успешно удалены
                    console.log('Данные из сессии успешно удалены');
                } else {
                    // Произошла ошибка при удалении данных из сессии
                    console.log('Произошла ошибка при удалении данных из сессии');
                }
            }
        });
    });
</script>
</body>
</html>
