<?
class CMain
{
    var $lang;

    public function GetPageIncData( $inc_id ){
        global $db;
        $Lang = $this->lang;
        if( !$Lang ){
            $Lang = $this->GetDefaultLanguage();
            $Lang = $Lang['code'];
        }
        $res_page = mysqli_query($db, "SELECT text_".$Lang." FROM `".DB_PREFIX."_pages_inc` WHERE `id`='".(int)$inc_id."' OR `code`='".$inc_id."'");
        if (!$res_page){
            return 'No data';
        }
        elseif ( $page = mysqli_fetch_array($res_page) ){
            return $page['text_'.$Lang];
        }else{
            return 'Errorr';
        }
    }

    public function GetDefineSettings(){
        global $db;
        $ar_define_settings = array();
        $res_settings = mysqli_query($db, "SELECT * FROM `".DB_PREFIX."_settings`");
        while ( $settings = mysqli_fetch_assoc($res_settings) ){
            $ar_define_settings[$settings['code']] = $settings['value'];
        }
        return $ar_define_settings;
    }

    public function GetDefineLangTerms($LNG){
        global $db;
        $Lang = $LNG;
        if( !$LNG ){
            $Lang = $this->GetDefaultLanguage();
            $Lang = $Lang['code'];
        }
        $ar_define_terms = array();
        $res_terms = mysqli_query($db, "SELECT id, title_".$Lang." AS title, code  FROM `".DB_PREFIX."_dictionary`");
        while ( $terms = mysqli_fetch_array($res_terms) ){
            $ar_define_terms[$terms['code']] = $terms['title'];
        }
        return $ar_define_terms;
    }

    public function GetDefaultLanguage()
    {
        global $db;
        $getlang = mysqli_query($db, "SELECT * FROM `" .  DB_PREFIX . "_site_languages`WHERE is_default = 1 ");
        return mysqli_fetch_assoc($getlang);
    }

    public function inject()
    {
        $keys = array(
            'substring',
            'extractvalue',
            ';',
            'truncate',
            'information_schema',
            'concat',
            'concat_ws',
            'unhex',
            'outfile',
            '/*',
            '0x',
            '\\',
            'column',
            'table_schema',
            'columns',
            '--',
            '(',
            ')',
            '<',
            '>',
            'prompt',
            '0x31303235343830303536',
            '%3E',
            '%3C',
            '%29',
            '%28',
            '%27',
            '%22',
            'iframe',
            '{',
            '}',
            '%7D',
            '%7B',
            '”',
            '“',
            'document.',
            '%3b'
        );

        foreach ($keys as $key => $value) {
            if( strripos($_SERVER['REQUEST_URI'], $value) ){
                return true;
            }
        }

        return false;
    }

    public function findInjection()
    {
        $keys = array(
            'substring',
            'extractvalue',
            'truncate',
            'information_schema',
            'concat',
            'concat_ws',
            'unhex',
            'script',
            'iframe',
            '/*',
            '\\',
            'column',
            'table_schema',
            'columns',
            'outfile',
            '--',
            '(',
            ')',
            '<',
            '>',
            'prompt',
            '0x'
        );

        $isDanger = false;

        foreach ($_POST as $postData) {
            foreach ($keys as $key => $value) {
                if( strripos($postData, $value) ){
                    $isDanger = true;
                }
            }
        }

        return $isDanger;

    }
}
?>
