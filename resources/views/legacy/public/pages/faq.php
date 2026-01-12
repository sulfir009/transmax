<!DOCTYPE html>
<html lang="<?php echo $Router->lang?>">
<head>
    <?php echo  view('layout.components.header.head', [
        'page_data' => $page_data,
    ])->render(); ?>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <?php echo  view('layout.components.header.header', [
            'page_data' => $page_data,
        ])->render(); ?></div>
    <div class="content">
        <div class="page_content_wrapper">
            <div class="faq_txt_wrapper">
                <div class="container">
                    <div class="flex-row gap-30 faq_info_blocks">
                        <div class="col-xl-6">
                            <div class="faq_txt_block">
                                <?php $faqInfo = $Db->getOne("SELECT image,title_".$Router->lang." AS title, text_".$Router->lang." AS text FROM `".DB_PREFIX."_faq_txt`");?>
                                <div class="faq_block_title h2_title"><?php echo $faqInfo['title']?></div>
                                <div class="faq_block_txt par">
                                    <?php echo $faqInfo['text']?>
                                </div>
                                <a href="<?php echo $Router->writelink(76)?>" class="faq_booking_link h4_title flex_ac blue_btn">
                                    <?php echo $GLOBALS['dictionary']['MSG___ZABRONYUVATI_BILET']?>
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="faq_img">
                                <img src="<?php echo  asset('images/legacy/upload/wellcome/' . $faqInfo['image']); ?>" alt="faq_img" class="fit_img">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="faq_wrapper">
                <div class="container">
                    <div class="faq">
                        <?php $getFaq = $Db->getAll("SELECT question_".$Router->lang." AS question, answer_".$Router->lang." AS answer FROM mt_faq WHERE active = '1' ORDER BY sort DESC");
                        foreach ($getFaq AS $k=>$faq){?>
                            <div class="question_wrapper">
                                <div class="question h4_title" onclick="toggleAnswer(this)">
                                    <?php echo $faq['question']?>
                                    <button class="toggle_answer_btn"></button>
                                </div>
                                <div class="answer par">
                                    <?php echo $faq['answer']?>
                                </div>
                            </div>
                        <?php }?>
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
<script>
    function toggleAnswer(item){
        $(item).next().slideToggle();
        $(item).find('.toggle_answer_btn').toggleClass('active');
    }
</script>
</body>
</html>
