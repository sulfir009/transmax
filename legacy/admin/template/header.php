<!-- Preloader -->
<div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="<?= asset('images/legacy/admin/template/dist/img/AdminLTELogo.png'); ?>" alt="AdminLTELogo"
         height="60" width="60">
</div>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand <?php echo $adminTheme['main_header_class'] ?>">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" href="#" role="button" onclick="toggleDarkMode(this)">
                <i class="<?php echo $adminTheme['i_class'] ?> fa-moon"></i>
            </a>
        </li>
        <? /* Notifications Dropdown Menu */ ?>
        <? /*
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge">15</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">15 Notifications</span>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-envelope mr-2"></i> 4 new messages
                    <span class="float-right text-muted text-sm">3 mins</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-users mr-2"></i> 8 friend requests
                    <span class="float-right text-muted text-sm">12 hours</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-file mr-2"></i> 3 new reports
                    <span class="float-right text-muted text-sm">2 days</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
            </div>
        </li>
        */ ?>
        <li class="nav-item">
            <a class="nav-link" href="/<?php echo ADMIN_PANEL . '/log_out.php' ?>" role="button">
                Выход
            </a>
        </li>
    </ul>
</nav>
<!-- /.navbar -->

<!-- Main Sidebar Container -->
<aside class="main-sidebar <?php echo $adminTheme['aside_class'] ?> elevation-4">
    <!-- Brand Logo -->
    <a href="/<?php echo ADMIN_PANEL ?>" class="brand-link">
        <img src="<?= asset('images/legacy/admin/template/dist/img/AdminLTELogo.png'); ?>" alt="AdminLTE Logo"
             class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Max Trans</span>
    </a>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?= asset('images/legacy/admin/images/admin_avatars/' . $Admin->image ?? ''); ?>"
                     class="img-circle elevation-2"
                     alt="User Image">
            </div>
            <div class="info">
                <a href="/<?= ADMIN_PANEL ?>/cruds/users/users/edit.php?id=<?= $Admin->id ?>"
                   class="d-block"><?php echo $Admin->name ?></a>
            </div>
        </div>
        <!-- Sidebar Menu -->
        <nav class="mt-2 <?php echo $adminTheme['aside_nav_class'] ?>">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
                     with font-awesome or any other icon font library -->
                <? $getMainMenuItems = $Db->getAll("SELECT id,title,link,image FROM `" . DB_PREFIX . "_menu_admin` WHERE active = '1' AND section_id = '0' AND
                 (
                            access = '" . $Admin->permissions . "'
                            OR access LIKE '%," . $Admin->permissions . ",%'
                            OR access LIKE '" . $Admin->permissions . ",%'
                            OR access LIKE '%," . $Admin->permissions . "'
                        )
                    ORDER BY sort DESC");
                foreach ($getMainMenuItems as $k => $mainMenuItem) {
                    $menuItemPath = str_replace('{ADMIN_PANEL}', ADMIN_PANEL, $mainMenuItem['link']);
                    $actClass = '';
                    if( substr_count($_SERVER['REQUEST_URI'], $menuItemPath) ){
                        $actClass = 'menu-is-opening menu-open';
                    }
                    ?>
                    <li class="nav-item <?=$actClass?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon <?=$mainMenuItem['image']?>"></i>
                            <p>
                                <?=$mainMenuItem['title']?>
                            </p>
                            <i class="right fas fa-angle-left"></i>
                        </a>
                        <ul class="nav nav-treeview">
                            <?$getSubmenuItems = $Db->getAll("SELECT title,link,image FROM `".DB_PREFIX."_menu_admin` WHERE active = '1' AND section_id = '".$mainMenuItem['id']."'
                            AND ( access = '" . $Admin->permissions . "'
                            OR access LIKE '%," . $Admin->permissions . ",%'
                            OR access LIKE '" . $Admin->permissions . ",%'
                            OR access LIKE '%," . $Admin->permissions . "' )
                            ORDER BY sort DESC");
                            foreach ($getSubmenuItems AS $key=>$submenuItem){
                                $submenuItemPath = str_replace('{ADMIN_PANEL}', ADMIN_PANEL, $submenuItem['link']);
                                $actSubclass = '';

                                if(rtrim($_SERVER['REQUEST_URI'], '/') == rtrim($submenuItemPath, '/') ){
                                    $actSubclass = 'active';
                                }?>
                            <li class="nav-item">
                                <a href="<?= '/' . ltrim(rtrim($submenuItemPath, '/'), '/') ?>" class="nav-link <?= $actSubclass ?>">
                                   <i class="far <?=$submenuItem['image']?>"></i>
                                    <p><?=$submenuItem['title']?></p>
                                </a>
                            </li>
                            <?}?>
                        </ul>
                    </li>
                <? } ?>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
