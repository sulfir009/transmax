<div class="container">
    <div class="d_none">
        <?php out($_SESSION)?>
    </div>
    <div class="callback_pop-up-btn" onclick="popUpForm()">
        <div class="blue_btn callback_btn">
            <div class="callback_img">
                <svg>
                    <use xlink:href="<?php echo  asset('images/legacy/upload/logos/callback.svg#callback'); ?>"></use>
                </svg>
            </div>
            <div class="callback_title"><?php echo $GLOBALS['dictionary']['MSG_ALL_DIZNATIS_VARTIST']?></div>
        </div>
    </div>
    <div class="flex-row gap-24">
        <div class="col-lg-6 col-xs-12">
            <div class="footer_block footer_left">
                <div class="footer_logo">
                    <img src="<?php echo  asset('images/legacy/upload/logos/' . $logo['white_logo']); ?>
" alt="logo" class="fit_img">
                </div>
                <div class="footer_txt par">
                    <?php $footerTxt = $Db->getOne("SELECT text_".$Router->lang." AS text FROM `".DB_PREFIX."_txt_blocks` WHERE id = '4' ")?>
                    <?php echo $footerTxt['text']?>
                </div>
                <div class="paymethod_logos">
                    <img src="<?php echo  asset('images/legacy/common/maestro.svg'); ?>" alt="maestro">
                    <img src="<?php echo  asset('images/legacy/common/mastercard.svg'); ?>" alt="mastercard">
                    <img src="<?php echo  asset('images/legacy/common/visa.svg'); ?>" alt="visa">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-12">
            <div class="footer_block footer_center">
                <div class="footer_block_header h3_title">
                    <?php echo $GLOBALS['dictionary']['MSG_ALL_INFORMACIYA']?>
                </div>
                <div class="footer_links">
                    <ul class="h5_title footer_links_list">
                        <li><a href="<?php echo route('main')?>"><?php echo $Router->writetitle(1)?></a></li>
                        <li><a href="<?php echo route('about.us')?>"><?php echo $Router->writetitle(71)?></a></li>
                        <?/*<li><a href="#">Повернути квитки</a></li>*/?>
                        <li><a href="<?php echo route('schedule')?>"><?php echo $Router->writetitle(73)?></a></li>
                        <li><a href="<?php echo route('kontakti')?>"><?php echo $Router->writetitle(75)?></a></li>
                        <li><a href="<?php echo route('faq')?>"><?php echo $Router->writetitle(74)?></a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-12">
            <div class="footer_right">
                <div class="footer_block_header h3_title">
                    <?php echo $GLOBALS['dictionary']['MSG_ALL_ZVYAZHISI_Z_NAMI']?>
                </div>
                <div class="footer_contacts h5_title">
                    <div>
                        <?php echo $GLOBALS['dictionary']['MSG_CONTACTS_ADRESA']?> <?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_65000_M_ODESA_VUL_STAROSINNA_7']?>
                    </div>
                    <div>
                        <?php echo $GLOBALS['dictionary']['MSG_CONTACTS_TELEFON']?><a href="tel:<?php echo $GLOBALS['site_settings']['CONTACT_PHONE']?>"><?php echo $GLOBALS['site_settings']['CONTACT_PHONE']?></a>
                    </div>
                    <div>
                        <?php echo $GLOBALS['dictionary']['MSG_CONTACTS_EMAIL']?><a href="mailto:<?php echo $GLOBALS['site_settings']['CONTACT_EMAIL']?>"><?php echo $GLOBALS['site_settings']['CONTACT_EMAIL']?></a>
                    </div>
                </div>
                <div class="footer_contacts_bottom">
                    <div class="footer_contacts_bottom_title h5_title">
                        <?php echo $GLOBALS['dictionary']['MSG_ALL_MI_U_SOCMEREZHAH']?>
                    </div>
                    <div class="footer_social">
                        <a href="<?php echo $GLOBALS['site_settings']['VIBER']?>" target="_blank">
                            <img src="<?php echo  asset('images/legacy/common/viber.svg'); ?>" alt="viber">
                        </a>
                        <a href="<?php echo $GLOBALS['site_settings']['TELEGRAM']?>" target="_blank">
                            <img src="<?php echo  asset('images/legacy/common/telegram.svg'); ?>" alt="telegram">
                        </a>
                        <a href="<?php echo $GLOBALS['site_settings']['FB']?>" target="_blank">
                            <img src="<?php echo  asset('images/legacy/common/facebook.svg'); ?>" alt="facebook">
                        </a>
                        <a href="<?php echo $GLOBALS['site_settings']['INST']?>" target="_blank">
                            <img src="<?php echo  asset('images/legacy/common/instagram.svg'); ?>" alt="instagram">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer_bottom">
        <div class="footer_bottom_txt par">
            <?php $footerCookie = $Db->getOne("SELECT text_".$Router->lang." AS text FROM `".DB_PREFIX."_txt_blocks` WHERE id = '5' ")?>
            <?php echo $footerCookie['text']?>
        </div>
        <div class="footer_bottom_list flex_ac par">
            <div class="footer_bottom_links">
                <a href="<?php echo $Router->writelink(83)?>" class="footer_bottom_link"><?php echo $Router->writetitle(83)?></a>
                <a href="<?php echo $Router->writelink(84)?>" class="footer_bottom_link fbl_offer"><?php echo $Router->writetitle(84)?></a>
            </div>
            <div class="copyrights">
                © <?php echo $GLOBALS['dictionary']['MSG_ALL_VSI_PRAVA_ZAHISCHENI']?> |  MaxTrans 2024
            </div>
        </div>
    </div>
</div>
<div class="callback_popUp" id="callback_popUp">
    <div class="callback_form">
        <div class="callback_form-race">
            <div class="filter_block_wrapper">
                <div class="filter_city_select_wrapper flex-row">
                    <div class="filter_block_title city_select_title par"><?php echo  $GLOBALS['dictionary']['MSG_ALL_ZVIDKI'] ?></div>
                    <select class="filter_city_select" id="callback_departure" name="departure_callback">
                        <? $getCities = $Db->getall("SELECT id,title_".$Router->lang." AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = 1 AND section_id > 0 AND station = 0 ORDER BY sort DESC,title_".$Router->lang." ASC");
                        foreach ($getCities as $k => $city) { ?>
                            <option value="<?php echo  $city['id'] ?>" <? if ($filterDeparture == $city['id'] || ($filterDeparture == "" && mb_strtoupper(mb_substr($city['title'], 0, 1)) === 'А')) {
                                echo  'selected';
                            } ?>>
                                <?php if ($city['station'] == 0){
                                    echo  $city['title'];
                                }else{
                                    echo  $city['city_title'].' '.$city['title'];
                                }?>
                            </option>

                        <? } ?>
                    </select>
                </div>
                <div class="filter_city_select_wrapper flex-row">
                    <div class="filter_block_title city_select_title par"><?php echo  $GLOBALS['dictionary']['MSG_ALL_KUDA'] ?></div>
                    <select class="filter_city_select" id="callback_arrival" name="arrival_callback">
                        <? foreach ($getCities as $k => $city) { ?>
                            <option value="<?php echo  $city['id'] ?>" <? if ($filterArrival == $city['id'] || ($filterDeparture == "" && mb_strtoupper(mb_substr($city['title'], 0, 1)) === 'А')) {
                                echo  'selected';
                            } ?>>
                                <?php if ($city['station'] == 0){
                                    echo  $city['title'];
                                }else{
                                    echo  $city['city_title'].' '.$city['title'];
                                }?>
                            </option>
                        <? } ?>
                    </select>
                </div>
            </div>
            <div class="callback_phone">
                <div class="phone_input_wrapper flex_ac">
                    <select class="cb_phone_country_code flex_ac" onchange="changeInputMask(this)" id="phone_code">
                        <?php $getPhoneCodes = $Db->getall("SELECT * FROM `".DB_PREFIX."_phone_codes` WHERE active = '1' ORDER BY sort DESC");
                        foreach ($getPhoneCodes AS $k=>$phoneCode){
                            if ($k == 0){
                                $firstPhoneExample = $phoneCode['phone_example'];
                                $firstPhoneMask = $phoneCode['phone_mask'];
                            }?>
                            <option value="<?php echo $phoneCode['id']?>" data-mask="<?php echo $phoneCode['phone_mask']?>" data-placeholder="<?php echo $phoneCode['phone_example']?>" <?php if ($k == 0){echo 'selected';}?>><?php echo $phoneCode['phone_country']?></option>
                        <?php }?>
                    </select>
                    <input type="text" class="customer_phone_input inter cb_phone_input cb_req_input" placeholder="<?php echo $firstPhoneExample?>" id="callback_phone">
                </div>
            </div>
            <div class="callback_message">
                <textarea class="c_input cb_req_input" placeholder="<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_POVIDOMLENNYA']?>" id="callback_message"></textarea>
            </div>
            <button class="send_contact_btn h4_title blue_btn" onclick="sendCallback()">
                <?php echo $GLOBALS['dictionary']['MSG_CONTACTS_VIDPRAVITI']?>
            </button>
        </div>
    </div>
</div>
<div class="callback_popup_overlay overlay" onclick="popUpForm()">

</div>
