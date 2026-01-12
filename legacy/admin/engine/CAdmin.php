<?php

class CAdmin
{
    var $id;
    var $lang;
    var $langs;
    var $permissions;
    var $name;
    var $theme;
    var $image;

    function __construct(){
        global $db;
        $getCurrentLangs = mysqli_query($db, " SELECT * FROM `" .  DB_PREFIX . "_site_languages`ORDER BY sort DESC ");
        while( $cLang = mysqli_fetch_assoc($getCurrentLangs) ){
            $this->langs[$cLang['code']] = $cLang;
        }

        $getAdminInfo = mysqli_query($db, "SELECT `id`,`lang`,`usergroup`,`name`,`theme`,`image` FROM `" .  DB_PREFIX . "_users`WHERE login_hash = '".$_SESSION['admin']['hash']."' LIMIT 1");
        $adminInfo = mysqli_fetch_assoc($getAdminInfo);
        $this->id = $adminInfo['id'];
        $this->lang = $adminInfo['lang'];
        $this->permissions = $adminInfo['usergroup'];
        $this->name = $adminInfo['name'];
        $this->theme = $adminInfo['theme'];
        $this->image = $adminInfo['image'];
    }

    /* функция для проверки прав доступа */
    public function CheckPermission ( $group_id ){
        global $db;
        if($_SESSION['admin'] ){
            $mem = $_SESSION['admin']['hash'];
            if( is_array($group_id) ){
                $group =  implode(',',$group_id);
                $user_group_res = mysqli_query($db, "SELECT id FROM `" .  DB_PREFIX . "_users`WHERE login_hash='".$mem."' AND usergroup IN (".$group.")");
            }else{
                if( substr_count($group_id, ',')>0 ){
                    $group_id = preg_replace('/[^\d,]/','',$group_id);
                    $user_group_res = mysqli_query($db, "SELECT id FROM `" .  DB_PREFIX . "_users`WHERE login_hash='".$mem."' AND usergroup IN (".$group_id.")");
                    if( mysqli_num_rows($user_group_res) ){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    $user_group_res = mysqli_query($db, "SELECT id FROM `" .  DB_PREFIX . "_users`WHERE login_hash='".$mem."' AND usergroup='".(int)$group_id."'");
                }
            }
            if( mysqli_num_rows($user_group_res) )
                return true;
            else
                return false;
        }
        else
            return false;
    }
}
?>
