<?php

//Model sayfaya dahil edilir
require_once "Model/Menus.php";
require_once "Model/Auths.php";

//Modelden yeni bir nesne oluşturulur
$menus = new Menus();
$Auths = new Auths();

//Kommit kontrol
?>

<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark" id="navbar">
    <!-- <aside class="navbar navbar-vertical navbar-expand-lg navbar-transparent"> -->
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="index.php?p=home">
                <img src="./static/Logo-aiv5.svg" width="300" height="80" class="navbar-brand-image"
                    style="width:160px;height:60px">
            </a>
        </h1>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <div id="menu-search-container">
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i class="ti ti-search text-secondary"></i>
                    </span>
                    <input type="search" id="menu-search-input" class="form-control text-white" placeholder="Menüde ara..." aria-label="Menü ara">
                </div>
            </div>
            <ul class="navbar-nav 1">

                <?php

                //Aktif sayfa alınır
                $active_page = $_GET['p'] ?? '';

                //Menü isimleri Model altındakii Menus.php sayfası ile tablodan getirilir
                $top_menus = $menus->getMenus();

                //Gelen menü isimlerinde döngüye girilir
                foreach ($top_menus as $menu) {



                    //Eğer menü yetkiye tabi ise yetki kontrolü yapılır
                    if ($menu->is_authorize == 1) {
                        //Sayfa Adından Auths tablosundaki title alanı ile sorgulanarak yetki id alınır
                        $auth_id = $Auths->getAuthIdByTitle($menu->page_name)?->id ?? 0;

                        //Yetki id'si gelen sayfa için yetki kontrolü yapılır
                        if (!$Auths->AuthorizeByAuthId($auth_id)) {
                            continue;
                        }
                    }


                    // echo "<pre>";
                    // print_r("yetki var mı :" . $auth_id);
                    // echo "</pre>";
                
                    //Eğer aktif sayfa menü ismi ile aynı ise active classı eklenir
                    if ($active_page == $menu->page_link) {
                        $active = 'active';
                    } else {
                        $active = '';
                    }

                    //Menü altında başka menüler var mı kontrol edilir
                    $sub_menus = $menus->getSubMenusisMenu($menu->id);

                    //Menü altında başka menüler var ve menü olarak görünür ise 
                    //üst menü için aşağı açılan ok oluşturulur
                    if (count($sub_menus) > 0) {
                        $dropdown = 'dropdown' ?? '';
                        $dropdown_toogle = 'dropdown-toggle' ?? '';
                    } else {
                        $dropdown = '' ?? '';
                        $dropdown_toogle = '' ?? '';
                    }

                    //Menü altında başka menüler var mı kontrol edilir
                    // ve menü olarak görünür ise dropdown menü oluşturulur
                    $sub_menus = $menus->getSubMenus($menu->id);


                    $active_id = 0;
                    foreach ($sub_menus as $sub_menu) {
                        //Aktif sayfa döngüdeki sayfa ise show classı eklenir
                        if ($active_page == $sub_menu->page_link) {
                            $show = 'show';
                            $active = 'active';
                            $active_id = $menu->id;
                        } elseif ($sub_menu->parent_id != $active_id) {
                            $show = '';
                            // $active = '';
                        }
                    }


                    ?>


                    <!-- Menü oluşturulur -->
                    <li class="nav-item <?php echo $active ?> dropdown ">

                        <a class="nav-link <?php echo $dropdown_toogle; ?>"
                            href="index.php?p=<?php echo $menu->page_link ?>" data-bs-toggle="<?php echo $dropdown; ?>"
                            data-bs-auto-close="false" role="button" aria-expanded="false">

                            <span class="nav-link-icon d-md-none d-lg-inline-block"  data-tooltip-location="right">
                                <i class="ti ti-<?php echo $menu->icon; ?> icon" ></i>
                            </span>
                            <span class="nav-link-title">
                                <?php echo $menu->page_name; ?>
                            </span>
                        </a>



                        <!-- Menü altında başka menüler varsa dropdown menü oluşturulur -->
                        <div class="dropdown-menu <?php echo $show ?? ''; ?>">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    <?php foreach ($sub_menus as $sub_menu) {

                                        //Eğer menü yetkiye tabi ise yetki kontrolü yapılır
                                        if ($sub_menu->is_authorize == 1) {
                                            //Sayfa Adından Auths tablosundaki title alanı ile sorgulanarak yetki id alınır
                                            //Menü adı ile Auts tablosundaki title alanı aynı olmalı
                                            $auth_id = $Auths->getAuthIdByTitle($sub_menu->page_name)?->id ?? 0;

                                            //Yetki id'si gelen sayfa için yetki kontrolü yapılır
                                            if (!$Auths->AuthorizeByAuthId($auth_id)) {
                                                continue;
                                            }
                                        }

                                        $active_link = $active_page == $sub_menu->page_link ? 'active-link' : '';
                                        //Menu altında göstermek istemiyorsak veritabanındaki isMenu alanı 0 yapılır
                                        if ($sub_menu->isMenu > 0) { ?>
                                            <a class="dropdown-item <?php echo $active_link ?>"
                                                href="index.php?p=<?php echo $sub_menu->page_link ?>">
                                                <?php echo $sub_menu->page_name; ?>
                                            </a>
                                        <?php }
                                    } ?>
                                </div>
                            </div>
                        </div>

                        <!-- Sub-menu End -->
                    </li>
                    </a>
                <?php } ?>
            </ul>
        </div>
    </div>
</aside>

<script>
$(document).ready(function() {
    var $searchInput = $('#menu-search-input');
    var $menuItems = $('#sidebar-menu .navbar-nav > li.nav-item');

    // Store original dropdown menu show states
    $menuItems.each(function() {
        var $dropdownMenu = $(this).find('.dropdown-menu');
        if ($dropdownMenu.length) {
            $dropdownMenu.attr('data-original-show', $dropdownMenu.hasClass('show') ? 'true' : 'false');
        }
    });

    // Turkish character aware lowercase function
    function toTurkishLowercase(str) {
        if (!str) return '';
        return str.replace(/I/g, 'ı')
                  .replace(/İ/g, 'i')
                  .replace(/Ğ/g, 'ğ')
                  .replace(/Ü/g, 'ü')
                  .replace(/Ş/g, 'ş')
                  .replace(/Ö/g, 'ö')
                  .replace(/Ç/g, 'ç')
                  .toLowerCase();
    }

    $searchInput.on('input search', function() {
        var query = toTurkishLowercase($(this).val().trim());

        if (query === '') {
            // Restore default menu visibility and dropdown expand states
            $menuItems.show();
            $menuItems.find('.dropdown-item').show();
            $menuItems.each(function() {
                var $dropdownMenu = $(this).find('.dropdown-menu');
                if ($dropdownMenu.length) {
                    var originalShow = $dropdownMenu.attr('data-original-show') === 'true';
                    if (originalShow) {
                        $dropdownMenu.addClass('show');
                    } else {
                        $dropdownMenu.removeClass('show');
                    }
                }
            });
            return;
        }

        $menuItems.each(function() {
            var $item = $(this);
            var parentTitle = toTurkishLowercase($item.find('.nav-link-title').text());
            var parentMatched = parentTitle.indexOf(query) !== -1;
            
            var $submenus = $item.find('.dropdown-item');
            var anySubMatched = false;

            if ($submenus.length > 0) {
                $submenus.each(function() {
                    var $sub = $(this);
                    var subText = toTurkishLowercase($sub.text());
                    var subMatched = subText.indexOf(query) !== -1;
                    
                    if (subMatched) {
                        anySubMatched = true;
                        $sub.show();
                    } else {
                        $sub.hide();
                    }
                });

                if (parentMatched || anySubMatched) {
                    $item.show();
                    var $dropdownMenu = $item.find('.dropdown-menu');
                    if (anySubMatched) {
                        $dropdownMenu.addClass('show');
                    } else {
                        // If parent matched but no submenu items matched, show all submenus
                        $submenus.show();
                        $dropdownMenu.addClass('show');
                    }
                } else {
                    $item.hide();
                }
            } else {
                if (parentMatched) {
                    $item.show();
                } else {
                    $item.hide();
                }
            }
        });
    });
});
</script>
