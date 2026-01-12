<!DOCTYPE html>
<html lang="<?php echo $Router->lang?>">
<head>
    <?php echo  view('layout.components.header.head', [
        'page_data' => $page_data,
    ])->render(); ?></head>
<body>
<div class="wrapper">
    <div class="header">
        <?php echo  view('layout.components.header.header', [
            'page_data' => $page_data,
        ])->render(); ?>
    </div>
    <div class="content">
        <div class="page_content_wrapper">
            <div class="contact_txt_wrapper">
                <div class="container">
                    <?php $contactInfo = $Db->getOne("SELECT image,title_".$Router->lang." AS title, text_".$Router->lang." AS text FROM `".DB_PREFIX."_contacts_txt` WHERE id = '1' ")?>
                    <div class="flex-row gap-30 contacts_info_blocks">
                        <div class="col-xl-6">
                            <div class="contact_txt_info">
                                <div class="contact_txt_title h2_title">
                                    <?php echo $contactInfo['title']?>
                                </div>
                                <div class="contact_txt par">
                                    <?php echo $contactInfo['text']?>
                                </div>
                                <a href="<?php echo $Router->writelink(76)?>" class="contacts_booking_link h4_title flex_ac blue_btn">
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_ZABRONYUVATI_BILET']?>
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="contact_img">
                                <img src="<?php echo  asset('images/legacy/upload/wellcome/' . $contactInfo['image']); ?>" alt="contact_image" class="fit_img">
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
                                    <?php $feedbackFormTxt = $Db->getOne("SELECT text_".$Router->lang." AS text FROM `".DB_PREFIX."_txt_blocks` WHERE id = '3'");?>
                                    <div class="contact_form_txt_title h2_title"><?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_FORMA_ZVOROTNIOGO_ZVYAZKU']?></div>
                                    <div class="contact_form_txt par"> <?php echo $feedbackFormTxt['text']?> </div>
                                </div>
                                <div class="contact_form">
                                    <div class="row">
                                        <input class="c_input req_input" type="text" placeholder="<?php echo $GLOBALS['dictionary']['MSG_CONTACTS_IMYA']?>" id="name">
                                    </div>
                                    <div class="row">
                                        <input class="c_input" type="text" placeholder="<?php echo $GLOBALS['dictionary']['MSG_CONTACTS_EMAIL']?>" id="email">
                                    </div>
                                    <div class="row">
                                        <div class="phone_input_wrapper flex_ac">
                                            <select class="phone_country_code flex_ac" onchange="changeInputMask(this)" id="phone_code">
                                                <?php $getPhoneCodes = $Db->getall("SELECT * FROM `".DB_PREFIX."_phone_codes` WHERE active = '1' ORDER BY sort DESC");
                                                foreach ($getPhoneCodes AS $k=>$phoneCode){
                                                    if ($k == 0){
                                                        $firstPhoneExample = $phoneCode['phone_example'];
                                                        $firstPhoneMask = $phoneCode['phone_mask'];
                                                    }?>
                                                    <option value="<?php echo $phoneCode['id']?>" data-mask="<?php echo $phoneCode['phone_mask']?>" data-placeholder="<?php echo $phoneCode['phone_example']?>" <?php if ($k == 0){echo 'selected';}?>><?php echo $phoneCode['phone_country']?></option>
                                                <?php }?>
                                            </select>
                                            <input type="text" class="customer_phone_input inter" placeholder="<?php echo $firstPhoneExample?>" id="phone">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <textarea class="c_input req_input" placeholder="<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_POVIDOMLENNYA']?>" id="message"></textarea>
                                    </div>
                                    <button class="send_contact_btn h4_title blue_btn" onclick="sendFeedback()">
                                        <?php echo $GLOBALS['dictionary']['MSG_CONTACTS_VIDPRAVITI']?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="contacts_map">
                                <div class="contacts_map_title h2_title"><?php echo $GLOBALS['dictionary']['MSG_CONTACTS_NASHI_KONTAKTI']?></div>
                                <div class="flex-row gap-30">
                                    <div class="col-md-7">
                                        <div class="contact_row h5_title">
                                            <?php echo $GLOBALS['dictionary']['MSG_CONTACTS_ADRESA']?> <?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_65000_M_ODESA_VUL_STAROSINNA_7']?>
                                        </div>
                                        <div class="contact_row h5_title">
                                            <?php echo $GLOBALS['dictionary']['MSG_CONTACTS_TELEFON']?><a href="tel:<?php echo $GLOBALS['site_settings']['CONTACT_PHONE']?>"><?php echo $GLOBALS['site_settings']['CONTACT_PHONE']?></a>
                                        </div>
                                        <div class="contact_row h5_title">
                                            <?php echo $GLOBALS['dictionary']['MSG_CONTACTS_EMAIL']?><a href="mailto:<?php echo $GLOBALS['site_settings']['CONTACT_EMAIL']?>"><?php echo $GLOBALS['site_settings']['CONTACT_EMAIL']?></a>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="contact_row h4_title">
                                            <?php echo $GLOBALS['dictionary']['MSG_CONTACTS_MI_U_SOCMEREZHAH']?>
                                        </div>
                                        <div class="contacts_messagers flex_ac">
                                            <a href="<?php echo $GLOBALS['site_settings']['VIBER']?>" class="m_link" target="_blank">
                                                <img src="<?php echo  asset('images/legacy/common/viber.svg'); ?>" alt="" class="fit_img">
                                            </a>
                                            <a href="<?php echo $GLOBALS['site_settings']['TELEGRAM']?>" class="m_link" target="_blank">
                                                <img src="<?php echo  asset('images/legacy/common/telegram.svg'); ?>" alt="" class="fit_img">
                                            </a>
                                            <a href="<?php echo $GLOBALS['site_settings']['FB']?>" class="m_link" target="_blank">
                                                <img src="<?php echo  asset('images/legacy/common/facebook.svg'); ?>" alt="" class="fit_img">
                                            </a>
                                            <a href="<?php echo $GLOBALS['site_settings']['INST']?>" class="m_link" target="_blank">
                                                <img src="<?php echo  asset('images/legacy/common/instagram.svg'); ?>" alt="" class="fit_img">
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="contact_map">
                                    <?php echo html_entity_decode(html_entity_decode($GLOBALS['site_settings']['CONTACT_MAP']))?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        <?php echo  view('layout.components.footer.footer', [
            'page_data' => $page_data,
        ])->render(); ?>
    </div>
</div>
<?php echo  view('layout.components.footer.footer_scripts', [
    'page_data' => $page_data,
])->render(); ?>
<script src="<?php echo  mix('js/legacy/libs/jquery.maskedinput.min.js') ?>"></script>
<script>
    $('.phone_country_code').niceSelect();
    $('.customer_phone_input').mask("<?php echo $firstPhoneMask?>");
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
                out('<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_ZAPOLNITE_OBYAZATELINYE_POLYA']?>','<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_POLYA_OTMECHENNYE__YAVLYAYUTSYA_OBYAZATELINYMI_DLYA_ZAPOLNENIYA']?>');
                return false;
            }
        });
        if (!isEmail(email)){
            out('<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_EMAIL_UKAZAN_NEVERNO']?>','<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_UKAZHITE_PRAVILINYJ_EMAIL']?>');
            return false;
        }
        initLoader();
        $.ajax({
           type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'/ajax/ru',
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
                    out('<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_VASHE_SOOBSCHENIE_OTPRAVLENO']?>', '<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_MY_SVYAZHEMSYA_S_VAMI_V_BLIZHAJSHEE_VREMYA']?>');
                }else{
                    out('<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_NE_UDALOSI_OTPRAVITI_SOOBSCHENIE']?>', '<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_POPROBUJTE_POZZHE']?>');
                }
            }
        })
    }
</script>
</body>
</html>
