<div class="content">
    <div class="thx_content_wrapper">
        <div class="thx_block">
            <div class="container">
                <div class="thx_block_title h2_title">
                    <?= __('dictionary.MSG_MSG_THX_PAGE_DYAKUYU_ZA_BRONYUVANNYA_BILETU') ?>
                </div>
                <div class="thx_block_subtitle par">
                    <?= __('dictionary.MSG_MSG_THX_PAGE_DANI_VASHOGO_BILETU') ?>
                </div>
                <? if (!\App\Service\User::isAuth()) { ?>
                    <a href="<?= route('auth') ?>" class="private_link h4_title blue_btn">
                        <span class="hidden-xs">
                            <?= __('dictionary.MSG_MSG_THX_PAGE_PEREJTI_U_PERSONALINIJ_KABINET') ?>
                        </span>
                        <span class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm col-xs-12">
                            <?= __('dictionary.MSG_MSG_THX_PAGE_PERSONALINIJ_KABINET') ?>
                        </span>
                    </a>
                <? } else { ?>
                    <a href="<?= route('future_races') ?>" class="private_link h4_title blue_btn">
                        <span class="hidden-xs">
                            <?= __('dictionary.MSG_MSG_THX_PAGE_PEREJTI_U_PERSONALINIJ_KABINET') ?>
                        </span>
                        <span class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm col-xs-12">
                            <?= __('dictionary.MSG_MSG_THX_PAGE_PERSONALINIJ_KABINET') ?>
                        </span>
                    </a>
                <? } ?>
            </div>
        </div>
        <div class="txh_image">
            <img src="<?= asset('images/legacy/common/thx_img.png'); ?>" alt="thanks" class="fit_img">
        </div>
    </div>
</div>
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/footer_scripts.php' ?>
<script>
    $(document).ready(function () {
        // AJAX-запрос при загрузке страницы
        $.ajax({
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: '<?= rtrim(url($Router->writelink(3)), '/') ?>',
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
