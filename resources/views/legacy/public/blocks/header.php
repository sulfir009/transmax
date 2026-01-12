    <div class="container">
        <?php $logo = $Db->getOne("SELECT white_logo_".$Router->lang." AS white_logo,black_logo_".$Router->lang." AS black_logo FROM `" .  DB_PREFIX . "_logos`WHERE id = 1 "); ?>
        <?php $lang = $Router->lang;
        $image_logo = '';
        ?>
        <div class="header_content flex_ac">
            <div class="logo">
                <a href="<?php echo route('main')?>">
                    <?php $image_logo = $logo['black_logo'];
                    $burger = 'burger_dark.svg';
                    $langs_class = 'dark';
                    if($pageData['page_id'] == '1'){
                        $image_logo = $logo['white_logo'];
                        $burger = 'burger.svg';
                        $langs_class = '';
                    }?>
                    <img src="<?php echo  asset('images/legacy/upload/logos/' .  $image_logo); ?>" alt="logo" class="fit_img">
                </a>
            </div>
            <?php $regularRaces = mysqli_query($db, "
                                SELECT rra.id as id,
                                       rra.title_{$lang} as title
                                FROM `mt_regular_race_alias` as rra");?>
            <div class="menu flex_ac">
                <div class="menu_links hidden-md hidden-sm hidden-xs">
                    <div class="regular_tours_wrapper">
                        <button class="link dropdown_link" onclick="toggleSupport2(this)">
                            <?php echo $GLOBALS['dictionary']['MSG_REGULAR_TOURS']?>
                            <?php echo  $arrowDown?>
                        </button>
                        <div class="regular_tours">
                            <?php foreach ($regularRaces as $race) {?>
                                <a href="/regular_races" class="regular_tour">
                                    <?php echo  $race['title']; ?>
                                </a>
                            <?php }?>
                        </div>
                    </div>
                    <div class="support_wrapper">
                        <button class="link dropdown_link" onclick="toggleSupport(this)">
                            <?php echo $GLOBALS['dictionary']['MSG_ALL_SLUZHBA_PIDTRIMKI']?>
                            <?php echo  $arrowDown?>
                        </button>
                        <div class="support_phones">
                            <a href="tel:<?php echo  str_replace(" ","",$GLOBALS['site_settings']['SUPPORT_PHONE_1'])?>" class="support_phone">
                                <img src="<?php echo  asset('images/legacy/common/lifecell.svg'); ?>" alt="lifecell">
                                <?php echo $GLOBALS['site_settings']['SUPPORT_PHONE_1']?>
                            </a>
                            <a href="tel:<?php echo  str_replace(" ","",$GLOBALS['site_settings']['SUPPORT_PHONE_2'])?>" class="support_phone">
                                <img src="<?php echo  asset('images/legacy/common/kyivstar.svg'); ?>" alt="kyivstar">
                                <?php echo $GLOBALS['site_settings']['SUPPORT_PHONE_2']?>

                            </a>
                        </div>
                    </div>
                    <?php if (\App\Service\User::isAuth()){
                        $privateLink = $Router->writelink(79);
                    }else{
                        $privateLink = $Router->writelink(77);
                    }?>
                    <a href="<?php echo $privateLink?>" class="link">
                        <?php echo $GLOBALS['dictionary']['MSG_ALL_OSOBISTIJ_KABINET']?>
                    </a>
                    <?php if (\App\Service\User::isAuth()){
                        ?>
                        <button class="link" onclick="exitAccount()">
                    Выход
                </button>
                   <? }?>
                </div>
                <?php $siteLangs = get_list_lang_public();?>
                <div class="langs_block">
                    <select class="langs_select <?php echo  $langs_class?>" onchange="location.href = $(this).val();">
                        <?php
                        foreach ($siteLangs['lang'] AS $langCode=>$langInfo) { ?>

                            <?php $selected = ($langInfo['code'] == $Router->lang) ? 'selected' : '';  ?>
                            <option value="<?php echo $langInfo['href']?>"
                                <?php if ($langInfo['code'] == $Router->lang) {
                                    echo  'selected';
                                }
                                ?>
                            >
                                <?php
                                    echo  strtoupper($langInfo['code'])
                                ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <button class="burger" onclick="toggleMobileMenu()">
                    <img src="<?php echo  asset('images/legacy/common/' . $burger); ?>" alt="burger">
                </button>
            </div>
        </div>
    </div>
    <div class="mobile_menu blue_popup">
        <div class="mobile_menu_content">
            <button class="close_menu" onclick="toggleMobileMenu()">
                <img src="<?php echo  asset('images/legacy/common/arrow_left.svg'); ?>" alt="arrow left">
            </button>
            <div class="mobile_menu_links">
                <ul>
                    <li><a href="<?php echo route('main')?>" class="mobile_menu_link manrope <?php if ($pageData['page_id'] == '1'){echo 'active';}?>"><?php echo $Router->writetitle(1)?></a></li>
                    <li><a href="<?php echo route('about.us')?>" class="mobile_menu_link manrope <?php if ($pageData['page_id'] == '71'){echo 'active';}?>"><?php echo $Router->writetitle(71)?></a></li>
                    <li><a href="<?php echo route('avtopark')?>" class="mobile_menu_link manrope <?php if ($pageData['page_id'] == '72'){echo 'active';}?>"><?php echo $Router->writetitle(72)?></a></li>
                    <li><a href="<?php echo route('schedule')?>" class="mobile_menu_link manrope <?php if ($pageData['page_id'] == '73'){echo 'active';}?>"><?php echo $Router->writetitle(73)?></a></li>
                    <li><a href="<?php echo route('faq')?>" class="mobile_menu_link manrope <?php if ($pageData['page_id'] == '74'){echo 'active';}?>"><?php echo $Router->writetitle(74)?></a></li>
                    <li><a href="<?php echo route('kontakti')?>" class="mobile_menu_link manrope <?php if ($pageData['page_id'] == '75'){echo 'active';}?>"><?php echo $Router->writetitle(75)?></a></li>
                </ul>
            </div>
            <div class="mobile_menu_social">
                <div class="mobile_menu_social_header btn_txt">
                    <?php echo $GLOBALS['dictionary']['MSG_ALL_MI_U_SOCMEREZHAH']?>
                </div>
                <div class="mobile_menu_social_links flex_ac">
                    <a href="<?php echo $GLOBALS['site_settings']['VIBER']?>">
                        <img src="<?php echo  asset('images/legacy/common/viber.svg'); ?>" alt="viber">
                    </a>
                    <a href="<?php echo $GLOBALS['site_settings']['TELEGRAM']?>">
                        <img src="<?php echo  asset('images/legacy/common/telegram.svg'); ?>" alt="telegram">
                    </a>
                    <a href="<?php echo $GLOBALS['site_settings']['FB']?>">
                        <img src="<?php echo  asset('images/legacy/common/facebook.svg'); ?>" alt="facebook">
                    </a>
                    <a href="<?php echo $GLOBALS['site_settings']['INST']?>">
                        <img src="<?php echo  asset('images/legacy/common/instagram.svg'); ?>" alt="instagram">
                    </a>
                </div>
            </div>
            <div class="menu_links mobile hidden-xxl hidden-xl hidden-lg">
                <div class="regular_tours_wrapper">
                    <button class="link dropdown_link" onclick="toggleSupport2(this)">
                        <?php echo $GLOBALS['dictionary']['MSG_REGULAR_TOURS']?>
                        <?php echo  $arrowDown?>
                    </button>
                    <div class="regular_tours">
                        <?php foreach ($regularRaces as $race) {?>
                            <a href="#" class="regular_tour">
                                <?php echo  $race['title']; ?>
                            </a>
                        <?php }?>
                    </div>
                </div>
                <div class="support_wrapper">
                    <button class="link dropdown_link" onclick="toggleSupport(this)">
                        <?php echo $GLOBALS['dictionary']['MSG_ALL_SLUZHBA_PIDTRIMKI']?>
                        <?php echo  $arrowDown?>
                    </button>
                    <div class="support_phones">
                        <a href="tel:<?php echo  str_replace(" ","",$GLOBALS['site_settings']['SUPPORT_PHONE_1'])?>">
                            <img src="<?php echo  asset('images/legacy/common/lifecell.svg'); ?>" alt="lifecell">
                            <?php echo $GLOBALS['site_settings']['SUPPORT_PHONE_1']?>
                        </a>
                        <a href="tel:<?php echo  str_replace(" ","",$GLOBALS['site_settings']['SUPPORT_PHONE_2'])?>">
                            <img src="<?php echo  asset('images/legacy/common/kyivstar.svg'); ?>" alt="kyivstar">
                            <?php echo $GLOBALS['site_settings']['SUPPORT_PHONE_2']?>
                        </a>
                    </div>
                </div>
                <a href="<?php echo $privateLink?>" class="link">
                    <?php echo $GLOBALS['dictionary']['MSG_ALL_OSOBISTIJ_KABINET']?>
                </a>
            </div>
        </div>
    </div>
    <div class="mobile_menu_overlay overlay" onclick="toggleMobileMenu()"></div>
    <meta name="csrf-token" content="<?php echo  csrf_token() ?>">
