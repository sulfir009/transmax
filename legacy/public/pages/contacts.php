<!DOCTYPE html>
<html lang="<?=$Router->lang?>">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/blocks/head.php'?>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/header.php' ?>
    </div>
    <div class="content">
        <div class="page_content_wrapper">
            <div class="contact_txt_wrapper">
                <div class="container">
                    <?$contactInfo = $Db->getOne("SELECT image,title_".$Router->lang." AS title, text_".$Router->lang." AS text FROM `".DB_PREFIX."_contacts_txt` WHERE id = '1' ")?>
                    <div class="flex-row gap-30 contacts_info_blocks">
                        <div class="col-xl-6">
                            <div class="contact_txt_info">
                                <div class="contact_txt_title h2_title">
                                    <?=$contactInfo['title']?>
                                </div>
                                <div class="contact_txt par">
                                    <?=$contactInfo['text']?>
                                </div>
                                <a href="<?=$Router->writelink(76)?>" class="contacts_booking_link h4_title flex_ac blue_btn">
                                    <?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_ZABRONYUVATI_BILET']?>
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="contact_img">
                                <img src="<?= asset('images/legacy/upload/wellcome/' . $contactInfo['image']); ?>" alt="contact_image" class="fit_img">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="contact_form_wrapper">
                <div class="container">
                    <div class="flex-row gap-30">
                        <div class="col-xl-6">
                            <div class="contact_form_block">
                                <div class="contact_form_txt_block">
                                    <?$feedbackFormTxt = $Db->getOne("SELECT text_".$Router->lang." AS text FROM `".DB_PREFIX."_txt_blocks` WHERE id = '3'");?>
                                    <div class="contact_form_txt_title h2_title"><?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_FORMA_ZVOROTNIOGO_ZVYAZKU']?></div>
                                    <div class="contact_form_txt par"> <?=$feedbackFormTxt['text']?> </div>
                                </div>
                                <div class="contact_form">
                                    <div class="row">
                                        <input class="c_input req_input" type="text" placeholder="<?=$GLOBALS['dictionary']['MSG_CONTACTS_IMYA']?>" id="name">
                                    </div>
                                    <div class="row">
                                        <input class="c_input" type="text" placeholder="<?=$GLOBALS['dictionary']['MSG_CONTACTS_EMAIL']?>" id="email">
                                    </div>
                                    <div class="row">
                                        <div class="phone_input_wrapper flex_ac">
                                            <select class="phone_country_code flex_ac" onchange="changeInputMask(this)" id="phone_code">
                                                <?$getPhoneCodes = $Db->getall("SELECT * FROM `".DB_PREFIX."_phone_codes` WHERE active = '1' ORDER BY sort DESC");
                                                foreach ($getPhoneCodes AS $k=>$phoneCode){
                                                    if ($k == 0){
                                                        $firstPhoneExample = $phoneCode['phone_example'];
                                                        $firstPhoneMask = $phoneCode['phone_mask'];
                                                    }?>
                                                    <option value="<?=$phoneCode['id']?>" data-mask="<?=$phoneCode['phone_mask']?>" data-placeholder="<?=$phoneCode['phone_example']?>" <?if ($k == 0){echo 'selected';}?>><?=$phoneCode['phone_country']?></option>
                                                <?}?>
                                            </select>
                                            <input type="text" class="customer_phone_input inter" placeholder="<?=$firstPhoneExample?>" id="phone">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <textarea class="c_input req_input" placeholder="<?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_POVIDOMLENNYA']?>" id="message"></textarea>
                                    </div>
                                    <button class="send_contact_btn h4_title blue_btn" onclick="sendFeedback()">
                                        <?=$GLOBALS['dictionary']['MSG_CONTACTS_VIDPRAVITI']?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="contacts_map">
                                <div class="contacts_map_title h2_title"><?=$GLOBALS['dictionary']['MSG_CONTACTS_NASHI_KONTAKTI']?></div>
                                <div class="flex-row gap-30">
                                    <div class="col-md-7">
                                        <div class="contact_row h5_title">
                                            <?=$GLOBALS['dictionary']['MSG_CONTACTS_ADRESA']?> <?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_65000_M_ODESA_VUL_STAROSINNA_7']?>
                                        </div>
                                        <div class="contact_row h5_title">
                                            <?=$GLOBALS['dictionary']['MSG_CONTACTS_TELEFON']?><a href="tel:<?=$GLOBALS['site_settings']['CONTACT_PHONE']?>"><?=$GLOBALS['site_settings']['CONTACT_PHONE']?></a>
                                        </div>
                                        <div class="contact_row h5_title">
                                            <?=$GLOBALS['dictionary']['MSG_CONTACTS_EMAIL']?><a href="mailto:<?=$GLOBALS['site_settings']['CONTACT_EMAIL']?>"><?=$GLOBALS['site_settings']['CONTACT_EMAIL']?></a>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="contact_row h4_title">
                                            <?=$GLOBALS['dictionary']['MSG_CONTACTS_MI_U_SOCMEREZHAH']?>
                                        </div>
                                        <div class="contacts_messagers flex_ac">
                                            <a href="<?=$GLOBALS['site_settings']['VIBER']?>" class="m_link" target="_blank">
                                                <img src="<?= asset('images/legacy/common/viber.svg'); ?>" alt="" class="fit_img">
                                            </a>
                                            <a href="<?=$GLOBALS['site_settings']['TELEGRAM']?>" class="m_link" target="_blank">
                                                <img src="<?= asset('images/legacy/common/telegram.svg'); ?>" alt="" class="fit_img">
                                            </a>
                                            <a href="<?=$GLOBALS['site_settings']['FB']?>" class="m_link" target="_blank">
                                                <img src="<?= asset('images/legacy/common/facebook.svg'); ?>" alt="" class="fit_img">
                                            </a>
                                            <a href="<?=$GLOBALS['site_settings']['INST']?>" class="m_link" target="_blank">
                                                <img src="<?= asset('images/legacy/common/instagram.svg'); ?>" alt="" class="fit_img">
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="contact_map">
                                    <?=html_entity_decode(html_entity_decode($GLOBALS['site_settings']['CONTACT_MAP']))?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/blocks/footer.php'?>
    </div>
</div>
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/blocks/footer_scripts.php'?>
<script src="<?= mix('js/legacy/libs/jquery.maskedinput.min.js') ?>"></script>
<script>
    $('.phone_country_code').niceSelect();
    $('.customer_phone_input').mask("<?=$firstPhoneMask?>");
    function changeInputMask(item){
        let selectedOption = $(item).find(':selected');
        $('.customer_phone_input').mask($(selectedOption).data('mask'));
        $('.customer_phone_input').attr('placeholder',$(selectedOption).data('placeholder'));
    };

    function sendFeedback(){
        let name = $.trim($('#name').val());
        let email = $.trim($('#email').val());
        let phone = $.trim($('#phone').val());
        let message = $.trim($('#message').val());
        $('.req_input').each(function(){
            if ($.trim($(this).val()) == ''){
                out('<?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_ZAPOLNITE_OBYAZATELINYE_POLYA']?>','<?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_POLYA_OTMECHENNYE__YAVLYAYUTSYA_OBYAZATELINYMI_DLYA_ZAPOLNENIYA']?>');
                return false;
            }
        });
        if (!isEmail(email)){
            out('<?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_EMAIL_UKAZAN_NEVERNO']?>','<?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_UKAZHITE_PRAVILINYJ_EMAIL']?>');
            return false;
        }
        initLoader();
        $.ajax({
           type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?=$Router->writelink(3)?>',
            data:{
                'request':'feedback',
                'name':name,
                'email':email,
                'phone':phone,
                'message':message
            },
            success:function(request){
                removeLoader();
                $('.contact_form').find('input,textarea').val('');

                if ($.trim(request) == 'ok'){
                    out('<?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_VASHE_SOOBSCHENIE_OTPRAVLENO']?>', '<?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_MY_SVYAZHEMSYA_S_VAMI_V_BLIZHAJSHEE_VREMYA']?>');
                }else{
                    out('<?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_NE_UDALOSI_OTPRAVITI_SOOBSCHENIE']?>', '<?=$GLOBALS['dictionary']['MSG_MSG_CONTACTS_POPROBUJTE_POZZHE']?>');
                }
            }
        })
    }
</script>
</body>
</html>
