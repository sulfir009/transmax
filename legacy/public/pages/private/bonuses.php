<?if (!\App\Service\User::isAuth()){
    header('Location:'.route('main'));
}?>
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
        <div class="private_links_wrapper">
            <div class="tabs_links_container hidden-xs">
                <div class="private_links">
                    <div class="private_link_wrapper">
                        <a href="<?=$Router->writelink(80)?>" class="private_tab h4_title">
                            <?=$Router->writetitle(80)?>
                        </a>
                    </div>
                    <div class="private_link_wrapper">
                        <a href="<?=$Router->writelink(78)?>" class="private_tab h4_title">
                            <?=$Router->writetitle(78)?>
                        </a>
                    </div>
                    <div class="private_link_wrapper">
                        <a href="<?=$Router->writelink(79)?>" class="private_tab h4_title">
                            <?=$Router->writetitle(79)?>
                        </a>
                    </div>
                    <div class="private_link_wrapper">
                        <a href="<?=$Router->writelink(82)?>" class="private_tab h4_title">
                            <?=$Router->writetitle(82)?>
                        </a>
                    </div>
                    <div class="private_link_wrapper">
                        <a href="<?=$Router->writelink(81)?>" class="private_tab h4_title active">
                            <?=$Router->writetitle(81)?>
                        </a>
                    </div>
                </div>
            </div>
            <div class="container hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm">
                <div class="mobile_private_links flex_ac">
                    <div class="private_link_wrapper">
                        <a href="<?=$Router->writelink(80)?>" class="private_tab h4_title">
                            <div class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm private_link_icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="36" viewBox="0 0 35 36" fill="none">
                                    <path d="M25.0229 10.453C24.4542 9.88416 24.1212 9.12157 24.0905 8.31778C24.0599 7.51399 24.3338 6.72824 24.8574 6.11769C24.9007 6.06691 24.9233 6.00163 24.9205 5.93495C24.9177 5.86827 24.8898 5.8051 24.8424 5.75811L21.8257 2.738C21.7758 2.68816 21.7082 2.66016 21.6377 2.66016C21.5672 2.66016 21.4996 2.68816 21.4497 2.738L16.6413 7.5464C16.4639 7.72376 16.3303 7.94003 16.251 8.17804C16.172 8.4166 16.0385 8.63347 15.8611 8.81146C15.6837 8.98945 15.4673 9.12365 15.229 9.20343C14.9908 9.28279 14.7744 9.41641 14.5967 9.59376L2.26534 21.9224C2.2155 21.9722 2.1875 22.0399 2.1875 22.1104C2.1875 22.1809 2.2155 22.2485 2.26534 22.2983L5.28204 25.315C5.32903 25.3625 5.39219 25.3903 5.45887 25.3931C5.52556 25.3959 5.59083 25.3734 5.64161 25.3301C6.25204 24.8058 7.03796 24.5315 7.84203 24.562C8.6461 24.5925 9.40898 24.9256 9.97795 25.4945C10.5469 26.0635 10.88 26.8264 10.9105 27.6305C10.941 28.4345 10.6666 29.2204 10.1424 29.8309C10.0991 29.8816 10.0766 29.9469 10.0794 30.0136C10.0821 30.0803 10.11 30.1435 10.1574 30.1904L13.1741 33.2071C13.224 33.257 13.2916 33.285 13.3621 33.285C13.4326 33.285 13.5002 33.257 13.5501 33.2071L25.8821 20.8758C26.0595 20.6981 26.1931 20.4816 26.2725 20.2435C26.3514 20.0049 26.4849 19.788 26.6623 19.61C26.8397 19.4321 27.0561 19.2979 27.2944 19.2181C27.5325 19.1388 27.7487 19.0052 27.9261 18.8277L32.7345 14.0193C32.7843 13.9695 32.8123 13.9019 32.8123 13.8314C32.8123 13.7609 32.7843 13.6932 32.7345 13.6434L29.7178 10.6267C29.6708 10.5793 29.6076 10.5514 29.5409 10.5486C29.4743 10.5458 29.409 10.5683 29.3582 10.6116C28.7485 11.1362 27.9632 11.4113 27.1595 11.3819C26.3557 11.3525 25.5926 11.0208 25.0229 10.453Z" stroke="white" stroke-width="2" stroke-miterlimit="10"/>
                                    <path d="M17.1247 10.0729L15.9961 8.94434M20.1339 13.0821L19.3813 12.3302M23.1431 16.092L22.3911 15.3394M26.5289 19.4771L25.4003 18.3485" stroke="white" stroke-width="2" stroke-miterlimit="10" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </a>
                    </div>
                    <div class="private_link_wrapper">
                        <a href="<?=$Router->writelink(78)?>" class="private_tab h4_title">
                            <div class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm private_link_icon">
                                <svg class="fill_stroke" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 26 26" fill="none">
                                    <g clip-path="url(#clip0_421_17501)">
                                        <path d="M13.5547 2.06484C13.2869 2.04507 13.0185 2.03517 12.75 2.03516V0.472656C13.0571 0.472806 13.3641 0.484272 13.6703 0.507031L13.5547 2.06484ZM16.6859 2.76797C16.1851 2.57469 15.6707 2.41854 15.1469 2.30078L15.4891 0.775781C16.0875 0.910156 16.6766 1.08828 17.2484 1.31016L16.6859 2.76797ZM18.8266 3.87734C18.6032 3.72836 18.3744 3.58764 18.1406 3.45547L18.9109 2.09609C19.4452 2.39881 19.9564 2.74035 20.4406 3.11797L19.4797 4.35078C19.2679 4.18552 19.05 4.02812 18.8266 3.87891V3.87734ZM21.6922 6.67422C21.3831 6.23512 21.0421 5.81941 20.6719 5.43047L21.8031 4.35234C22.225 4.79766 22.6156 5.27422 22.9703 5.77422L21.6922 6.67422ZM22.8547 8.78672C22.7521 8.539 22.6406 8.29509 22.5203 8.05547L23.9156 7.35234C24.1916 7.90091 24.4266 8.46913 24.6188 9.05234L23.1344 9.54141C23.0504 9.28651 22.9571 9.03478 22.8547 8.78672ZM23.6828 12.7039C23.6702 12.1669 23.618 11.6316 23.5266 11.1023L25.0656 10.8367C25.1703 11.4398 25.2313 12.0523 25.2469 12.6648L23.6844 12.7039H23.6828ZM23.4781 15.107C23.5297 14.8414 23.5719 14.5773 23.6047 14.3102L25.1562 14.5023C25.0812 15.112 24.9611 15.7152 24.7969 16.307L23.2906 15.8898C23.3625 15.632 23.425 15.3711 23.4781 15.107ZM21.9906 18.8242C22.2781 18.3711 22.5312 17.8961 22.75 17.4055L24.1781 18.0383C23.9281 18.6008 23.6391 19.1414 23.3109 19.6602L21.9906 18.8242ZM20.4844 20.707C20.675 20.5164 20.8578 20.3195 21.0313 20.1164L22.2156 21.1367C22.0151 21.3693 21.8061 21.5944 21.5891 21.8117L20.4844 20.707Z" fill="#40A6FF"/>
                                        <path d="M12.751 2.03516C10.9524 2.03529 9.18151 2.47898 7.59532 3.32694C6.00912 4.17489 4.65652 5.40094 3.65731 6.89648C2.65811 8.39203 2.04315 10.1109 1.8669 11.9009C1.69065 13.6908 1.95855 15.4967 2.64688 17.1584C3.3352 18.8201 4.4227 20.2864 5.81305 21.4274C7.2034 22.5685 8.85369 23.349 10.6177 23.7C12.3818 24.0509 14.2052 23.9614 15.9264 23.4393C17.6476 22.9173 19.2135 21.9788 20.4854 20.707L21.59 21.8117C20.1365 23.2661 18.3467 24.3394 16.3792 24.9367C14.4116 25.534 12.3272 25.6368 10.3105 25.2359C8.29372 24.8351 6.407 23.9429 4.81746 22.6386C3.22791 21.3343 1.98462 19.6581 1.19775 17.7584C0.410871 15.8588 0.104703 13.7943 0.306372 11.7481C0.50804 9.70181 1.21132 7.73688 2.35389 6.02737C3.49647 4.31787 5.04306 2.91658 6.85664 1.94766C8.67022 0.978742 10.6948 0.472111 12.751 0.472657V2.03516Z" fill="#40A6FF"/>
                                        <path d="M11.9688 5.16016C12.176 5.16016 12.3747 5.24247 12.5212 5.38898C12.6677 5.53549 12.75 5.73421 12.75 5.94141V14.082L17.825 16.982C17.9997 17.0874 18.1262 17.2569 18.1775 17.4544C18.2289 17.6518 18.201 17.8615 18.0998 18.0386C17.9986 18.2157 17.8321 18.3462 17.6359 18.4022C17.4398 18.4582 17.2295 18.4353 17.05 18.3383L11.5812 15.2133C11.4617 15.145 11.3623 15.0463 11.2931 14.9272C11.224 14.8081 11.1875 14.6729 11.1875 14.5352V5.94141C11.1875 5.73421 11.2698 5.53549 11.4163 5.38898C11.5628 5.24247 11.7615 5.16016 11.9688 5.16016Z" fill="#40A6FF"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_421_17501">
                                            <rect width="25" height="25" fill="white" transform="translate(0.25 0.472656)"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                            </div>
                        </a>

                    </div>
                    <div class="private_link_wrapper">
                        <a href="<?=$Router->writelink(79)?>" class="private_tab h4_title">
                            <div class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm private_link_icon">
                                <svg class="fill_stroke" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 26 26" fill="none">
                                    <g clip-path="url(#clip0_421_17507)">
                                        <path d="M15.6733 15.5605C15.3071 15.1455 14.9043 14.7793 14.4648 14.4619C14.0254 14.1445 13.5534 13.8719 13.0488 13.644C12.5443 13.4162 12.0316 13.2493 11.5107 13.1436C10.9899 13.0378 10.4447 12.9808 9.875 12.9727C9.15885 12.9727 8.46712 13.0662 7.7998 13.2534C7.13249 13.4406 6.50993 13.701 5.93213 14.0347C5.35433 14.3683 4.82943 14.7752 4.35742 15.2554C3.88542 15.7355 3.47852 16.2645 3.13672 16.8423C2.79492 17.4201 2.53044 18.0426 2.34326 18.71C2.15609 19.3773 2.0625 20.069 2.0625 20.7852H0.5C0.5 19.8086 0.642415 18.8687 0.927246 17.9653C1.21208 17.062 1.62305 16.2279 2.16016 15.4629C2.69727 14.6979 3.33203 14.0184 4.06445 13.4243C4.79688 12.8302 5.62695 12.3623 6.55469 12.0205C5.63509 11.4183 4.91895 10.6615 4.40625 9.75C3.89355 8.83854 3.63314 7.82943 3.625 6.72266C3.625 5.86003 3.78776 5.05029 4.11328 4.29346C4.4388 3.53662 4.88232 2.87337 5.44385 2.30371C6.00537 1.73405 6.66862 1.28646 7.43359 0.960938C8.19857 0.635417 9.01237 0.472656 9.875 0.472656C10.7376 0.472656 11.5474 0.635417 12.3042 0.960938C13.061 1.28646 13.7243 1.72998 14.2939 2.2915C14.8636 2.85303 15.3112 3.51628 15.6367 4.28125C15.9622 5.04622 16.125 5.86003 16.125 6.72266C16.125 7.25977 16.0599 7.78467 15.9297 8.29736C15.7995 8.81006 15.6042 9.29427 15.3438 9.75C15.0833 10.2057 14.7782 10.6248 14.4282 11.0073C14.0783 11.3898 13.6673 11.7275 13.1953 12.0205C13.8789 12.2809 14.5218 12.6146 15.124 13.0215C15.7262 13.4284 16.2756 13.9045 16.772 14.4497L15.6733 15.5605ZM5.1875 6.72266C5.1875 7.3737 5.30957 7.97998 5.55371 8.5415C5.79785 9.10303 6.13151 9.59945 6.55469 10.0308C6.97786 10.4621 7.47428 10.7998 8.04395 11.0439C8.61361 11.2881 9.22396 11.4102 9.875 11.4102C10.5179 11.4102 11.1242 11.2881 11.6938 11.0439C12.2635 10.7998 12.7599 10.4661 13.1831 10.043C13.6063 9.61979 13.944 9.12337 14.1963 8.55371C14.4486 7.98405 14.5706 7.3737 14.5625 6.72266C14.5625 6.07975 14.4404 5.47347 14.1963 4.90381C13.9521 4.33415 13.6185 3.83773 13.1953 3.41455C12.7721 2.99137 12.2716 2.65365 11.6938 2.40137C11.116 2.14909 10.5098 2.02702 9.875 2.03516C9.22396 2.03516 8.61768 2.15723 8.05615 2.40137C7.49463 2.64551 6.99821 2.97917 6.56689 3.40234C6.13558 3.82552 5.79785 4.32601 5.55371 4.90381C5.30957 5.48161 5.1875 6.08789 5.1875 6.72266ZM23.0586 11.4102C23.4004 11.4102 23.7178 11.4712 24.0107 11.5933C24.3037 11.7153 24.5641 11.8822 24.792 12.0938C25.0199 12.3053 25.1908 12.5617 25.3047 12.8628C25.4186 13.1639 25.4837 13.4854 25.5 13.8271C25.5 14.1445 25.439 14.4538 25.3169 14.7549C25.1948 15.056 25.0199 15.3205 24.792 15.5483L16.0396 24.3008L11.4375 25.4482L12.585 20.8462L21.3374 12.106C21.5734 11.87 21.8379 11.695 22.1309 11.5811C22.4238 11.4671 22.7331 11.4102 23.0586 11.4102ZM23.6812 14.4497C23.8521 14.2788 23.9375 14.0713 23.9375 13.8271C23.9375 13.5749 23.8561 13.3714 23.6934 13.2168C23.5306 13.0622 23.319 12.9808 23.0586 12.9727C22.9447 12.9727 22.8348 12.9889 22.729 13.0215C22.6232 13.054 22.5296 13.1151 22.4482 13.2046L14.001 21.6519L13.5859 23.2998L15.2339 22.8848L23.6812 14.4497Z" fill="#40A6FF"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_421_17507">
                                            <rect width="25" height="25" fill="white" transform="translate(0.5 0.472656)"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                            </div>
                        </a>

                    </div>
                    <div class="private_link_wrapper">
                        <a href="<?=$Router->writelink(82)?>" class="private_tab h4_title">
                            <div class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm private_link_icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 26 26" fill="none">
                                    <g clip-path="url(#clip0_421_17510)">
                                        <path d="M23.0712 4.49023H3.42829C2.44207 4.49023 1.64258 5.28973 1.64258 6.27595V19.6688C1.64258 20.655 2.44207 21.4545 3.42829 21.4545H23.0712C24.0574 21.4545 24.8569 20.655 24.8569 19.6688V6.27595C24.8569 5.28973 24.0574 4.49023 23.0712 4.49023Z" stroke="#40A6FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M1.64258 10.7402H24.8569M17.714 16.9902H20.3926" stroke="#40A6FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_421_17510">
                                            <rect width="25" height="25" fill="white" transform="translate(0.75 0.472656)"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                            </div>
                        </a>
                    </div>
                    <div class="private_link_wrapper">
                        <a href="<?=$Router->writelink(81)?>" class="private_tab h4_title active">
                            <div class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm private_link_icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="47" height="18" viewBox="0 0 47 18" fill="none">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.42523 4.96542L1.74021 1.66329H36.6912C39.0667 1.66329 41.094 3.38602 41.4815 5.73579L41.5007 5.85238H39.7771V6.31782H41.5775L43.2206 16.2822H39.0933L38.8916 15.8434C38.3853 14.7421 37.2855 14.0361 36.0746 14.0361C34.6295 14.0361 33.3765 15.0359 33.0523 16.4459L33.007 16.6433L33.9136 16.8519L33.959 16.6545C34.1862 15.6663 35.0638 14.967 36.0746 14.967C36.9216 14.967 37.6917 15.4608 38.0464 16.2324L38.4972 17.2131H44.317L42.5203 6.31782H45.7516V9.63933H46.2168V5.85238H42.4436L42.3994 5.58424C41.9379 2.7859 39.523 0.732422 36.6912 0.732422H1.69527C0.928516 0.732422 0.508292 1.62318 0.990735 2.21651L3.84254 5.72376L3.84322 5.7246L6.08427 8.49372L8.70987 11.5415C8.88235 11.7417 9.13344 11.857 9.39777 11.857H24.7999C25.093 11.857 25.368 11.7154 25.5386 11.4769L27.6785 8.4845L33.467 8.40149L33.4536 7.47072L27.654 7.55389C27.3654 7.55801 27.0962 7.69921 26.9283 7.93387L24.7885 10.9261H9.40796L7.44284 8.64503L17.4627 8.48784L17.4481 7.55709L6.66029 7.72632L5.17924 5.89629H17.4554V4.96542H4.42523Z" fill="#40A6FF"/>
                                </svg>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="page_content_wrapper">
            <div class="container">
                <?$getBonuses = $Db->getOne("SELECT miles FROM `".DB_PREFIX."_clients` WHERE id = '".$User->id."' ");
                if ((int)$getBonuses['miles'] < (int)$GLOBALS['site_settings']['FIRST_BONUS']){?>
                    <div class="private_empty_block">
                        <div class="private_empry_block_title h2_title">
                            <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_U_VAS_POKI_NEMA_BONUSIV']?>
                        </div>
                        <div class="private_empry_block_subtitle">
                            <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_ZA_KOZHEN_KUPLENIJ_KVITOK']?>
                        </div>
                    </div>
                <?}else{?>
                    <div class="current_bonuses_wrapper">
                        <div class="current_bonuses_txt">
                            <div class="current_bonuses_title h2_title">
                                <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_VASHI_BONUSI']?>
                            </div>
                            <div class="current_bonuses_subtitle par">
                                <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_ZA_KOZHEN_KUPLENIJ_KVITOK']?>
                            </div>
                        </div>
                        <div class="current_bonuses_path_wrapper">
                            <div class="current_bonuses_path">
                                <div class="bonus_checkpoint_wrapper">
                                    <div class="bonus_checkpoint active">
                                        <img src="<?= asset('images/legacy/common/maxtrans_logo.svg'); ?>" alt="" class="fit_img">
                                    </div>
                                </div>
                                <div class="progress_wrapper">
                                    <div class="bonuses_progressbar"></div>
                                    <div class="progress active" style="width: 35%"></div>
                                    <div class="progress_percentage par">
                                        35%
                                    </div>
                                </div>
                                <div class="bonus_checkpoint_wrapper">
                                    <div class="bonus_checkpoint">
                                        <img src="<?= asset('images/legacy/common/maxtrans_logo.svg'); ?>" alt="" class="fit_img">
                                    </div>
                                </div>
                                <div class="progress_wrapper">
                                    <div class="bonuses_progressbar"></div>
                                    <div class="progress"></div>
                                </div>
                                <div class="bonus_checkpoint_wrapper">
                                    <div class="bonus_checkpoint">
                                        <img src="<?= asset('images/legacy/common/maxtrans_logo.svg'); ?>" alt="" class="fit_img">
                                    </div>
                                </div>
                            </div>
                            <div class="bonuses_checkpoints_titles">
                                <div class="bonus_checkpoint_title par">
                                    <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_VI_DOSYAGLI_PERSHOGO_BONUSU']?>
                                    <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_VITAEMO_ZNIZHKA'].' '.$GLOBALS['site_settings']['FIRST_BONUS_DISCOUNT']?>%
                                </div>
                                <div class="bonus_checkpoint_title par hidden-xs">
                                    <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_VI_DOSYAGLI_DRUGOGO_BONUSU']?>
                                    <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_VITAEMO_ZNIZHKA'].' '.$GLOBALS['site_settings']['SECOND_BONUS_DISCOUNT']?>%
                                </div>
                                <div class="bonus_checkpoint_title par hidden-xs">
                                    <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_VI_DOSYAGLI_TRETIOGO_BONUSU']?>
                                    <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_VITAEMO_ZNIZHKA'].' '.$GLOBALS['site_settings']['THIRD_BONUS_DISCOUNT']?>%
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bonuses_wrapper">
                        <div class="bonuses_block_title par">
                            <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_VI_MATIMETE_MOZHLIVISTI']?>
                        </div>
                        <div class="flex-row gap-30">
                            <div class="col-xl-4">
                                <div class="bonus">
                                    <div class="bonus_for h4_title">
                                        <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_BONUS_ZA']?>
                                    </div>
                                    <div class="bonus_mileage h1_title">
                                        <?=$GLOBALS['site_settings']['FIRST_BONUS'].' '.$GLOBALS['dictionary']['MSG_MSG_BONUSES_KM']?>
                                    </div>
                                    <div class="bonus_value par">
                                        <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_ZNIZHKA_NA_BILETI'].' '.$GLOBALS['site_settings']['FIRST_BONUS_DISCOUNT']?>%
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="bonus">
                                    <div class="bonus_for h4_title">
                                        <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_BONUS_ZA']?>
                                    </div>
                                    <div class="bonus_mileage h1_title">
                                        <?=$GLOBALS['site_settings']['SECOND_BONUS'].' '.$GLOBALS['dictionary']['MSG_MSG_BONUSES_KM']?>
                                    </div>
                                    <div class="bonus_value par">
                                        <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_ZNIZHKA_NA_BILETI'].' '.$GLOBALS['site_settings']['SECOND_BONUS_DISCOUNT']?>%
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="bonus">
                                    <div class="bonus_for h4_title">
                                        <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_BONUS_ZA']?>
                                    </div>
                                    <div class="bonus_mileage h1_title">
                                        <?=$GLOBALS['site_settings']['THIRD_BONUS'].' '.$GLOBALS['dictionary']['MSG_MSG_BONUSES_KM']?>
                                    </div>
                                    <div class="bonus_value par">
                                        <?=$GLOBALS['dictionary']['MSG_MSG_BONUSES_ZNIZHKA_NA_BILETI'].' '.$GLOBALS['site_settings']['THIRD_BONUS_DISCOUNT']?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?}?>
            </div>
            <div class="main_filter_wrapper">
                <div class="container">
                    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/blocks/filter.php'?>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/blocks/footer.php'?>
    </div>
</div>
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/blocks/footer_scripts.php'?>
<script>
    $('.private_links').slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        dots: false,
        arrows: false,
        infinite: false,
        variableWidth: true,
        touchMove:false,
        swipe: false,
        responsive: [
            {
                breakpoint: 992,
                settings: {
                    infinite: false,
                    touchMove:true,
                    swipe: true,
                    slidesToShow: 1
                }
            },
        ],
    });

    $(document).ready(function () {
        if ($(window).width() < 992) {
            $('.purchase_steps').slick('slickGoTo', 3, true)
        }
    });
</script>
</body>
</html>
