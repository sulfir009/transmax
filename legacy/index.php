<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/config.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT'])  ."/" . ADMIN_PANEL ."/includes.php");

//защита от иньекций
if($Main->inject() ){
    abort(404);
}

$pageData = $Router->GetCPU();
$Router->lang = \App\Service\Site::lang();
//dd($_SESSION['last_lang']);
$Db->setlang($Router->lang);

if($pageData['status']==='404'){
    abort(404);
}

$Main->lang = \App\Service\Site::lang();
$_SESSION['≈'] = $Router->lang;


/*dd((new \App\Repository\Site\TranslationRepository())->getTranslationDictionary(\App\Service\Site::lang()));*/
$GLOBALS['dictionary'] = $Main->GetDefineLangTerms(\App\Service\Site::lang());
$page_data = $Router->GetPageData($pageData);
//dd($pageData['page']);
require_once(str_replace('public', 'resources/views/legacy', $_SERVER['DOCUMENT_ROOT'])."/public/pages" . $pageData['page']);
