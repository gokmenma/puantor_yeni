<?php

/**
 * SUPERADMIN MENÜ VE SAYFA İZİN LİSTESİ
 * ======================================
 *
 * Bir modülü superadmin panelinden ÇIKARMAK için:
 *   Aşağıdaki "modules" bölümünden o modüle ait bloğun tamamını silin.
 *   Başka bir dosyada veya listede değişiklik yapmanız gerekmez.
 *
 * Bir modülü superadmin paneline EKLEMEK için:
 *   "modules" bölümüne aşağıdaki örneğe benzer tek bir blok ekleyin:
 *
 *   [
 *       'menu_titles' => ['Örnek Menü'],
 *       'page_prefixes' => ['ornek-modul/'],
 *   ],
 *
 * "menu_titles":
 *   Veritabanındaki menu.page_name değeridir. Sol menüde hangi üst menünün
 *   gösterileceğini belirler. Yazım, boşluklar ve Türkçe karakterler birebir
 *   aynı olmalıdır.
 *
 * "page_prefixes":
 *   Bu başlangıca sahip bütün sayfaları açar. Örneğin "users/" değeri;
 *   users/list, users/manage ve users/roles/list sayfalarını birlikte açar.
 *
 * "exact_pages":
 *   Bir modülün tamamı yerine yalnızca belirtilen sayfaları açmak için
 *   kullanılır. Destek Yönetimi bloğu bunun örneğidir.
 *
 * Değişiklikten sonra:
 *   Açık sayfayı normal yenileyin. Eski menü ekranda kalırsa Ctrl+F5 ile
 *   zorla yenileyin. PHP/Apache önbelleği kullanılan canlı sunucularda
 *   PHP servisini yeniden yüklemek gerekebilir.
 *
 * Güvenlik:
 *   Burada bulunmayan menü ve sayfalar superadmin için varsayılan olarak
 *   kapalıdır. Normal firma kullanıcılarının rol/yetki akışı etkilenmez.
 */
return [
    // Oturum ve yönlendirme için gerekli, sol menü oluşturmayan sistem sayfaları.
    'utility_pages' => [
        '',
        'home',
        'authorize',
        'logout',
        '404',
        'under-maintance',
    ],

    // Her modül tek bloktur; ekleme/çıkarma yalnızca buradan yapılır.
    'modules' => [
        //ana sayfa
        [
            'menu_titles' => ['Admin Ana Sayfa', 'Ana Sayfa'],
            'exact_pages' => ['admin-home'],
        ],
        [
            'menu_titles' => ['Tanımlamalar'],
            'page_prefixes' => ['defines/'],
        ],
        [
            'menu_titles' => ['Görevler'],
            'page_prefixes' => ['gorevler/'],
        ],
        [
            'menu_titles' => ['Kullanıcılar'],
            'page_prefixes' => ['users/'],
        ],
        [
            'menu_titles' => ['Duyurular'],
            'page_prefixes' => ['duyurular/'],
        ],
        [
            'menu_titles' => ['Bildirimler'],
            'page_prefixes' => ['bildirimler/'],
        ],
        [
            'menu_titles' => ['Sistem Aktiviteleri'],
            'page_prefixes' => ['activities/'],
        ],
        [
            'menu_titles' => ['Abonelik İşlemleri'],
            'page_prefixes' => ['abonelik-islemleri/'],
        ],
        [
            'menu_titles' => ['Destek Yönetimi'],
            'exact_pages' => [
                'supports/admin-tickets',
                'supports/admin-ticket-view',
            ],
        ],
        [
            'menu_titles' => ['Mail İşlemleri'],
            'page_prefixes' => ['mail-islemleri/'],
        ],
        [
            'menu_titles' => ['Ayarlar'],
            'page_prefixes' => ['settings/'],
        ],
        [
            'menu_titles' => [
                'KVKK Merkezi',
                'KVKK Talepleri',
                'KVKK Veri İhlalleri',
            ],
            'page_prefixes' => ['kvkk/'],
        ],
    ],
];
