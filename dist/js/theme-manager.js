/**
 * Puantor Theme Manager (Tema Özelleştirici Yönetimi - Sekmeli & Gelişmiş)
 */

(function() {
    'use strict';

    // Tema - Yazı Tipi Eşleştirme Haritası
    window.themePresetFonts = {
        'kode': 'inter',
        'cyber-neon': 'space-grotesk',
        'tokyo-gece': 'geist',
        'nordic-polar': 'plus-jakarta-sans',
        'sunset-horizon': 'poppins',
        'emerald-luxe': 'manrope',
        'dracula-pro': 'outfit',
        'minimal-slate': 'inter',
        'pastel-matcha': 'nunito',
        'velvet-berry': 'outfit',
        'midnight-sapphire': 'lexend',
        'warm-cappuccino': 'figtree',
        'neon-mint': 'urbanist',
        'electric-indigo': 'dm-sans',
        'ersan-gold': 'outfit',
        'zumrut': 'plus-jakarta-sans',
        'kraliyet-moru': 'outfit',
        'rose': 'poppins',
        'sade-beyaz': 'inter',
        'koyu-gece': 'geist',
        'safir-okyanus': 'outfit',
        'gun-batimi': 'poppins',
        'gece-altini': 'montserrat',
        'mistik-bordo': 'montserrat',
        'nordik-cam': 'plus-jakarta-sans',
        'soft-lavanta': 'outfit',
        'soft-adacayi': 'plus-jakarta-sans',
        'soft-seftali': 'poppins',
        'soft-buz-mavisi': 'inter',
        'soft-vizon': 'montserrat',
        'modern-celik': 'dm-sans',
        'antrasit-zumrut': 'manrope',
        'dumanli-bordo': 'figtree',
        'grafiti-mor': 'space-grotesk',
        'kul-amber': 'urbanist',
        'petrol-tas': 'plus-jakarta-sans',
        'platin-mavi': 'sora',
        'titan-okyanus': 'outfit',
        'gradient-mor': 'outfit',
        'gradient-safir': 'plus-jakarta-sans',
        'gradient-zumrut': 'manrope',
        'gradient-yakut': 'figtree',
        'gradient-amber': 'urbanist',
        'gradient-petrol': 'space-grotesk',
        'gradient-lacivert': 'sora',
        'gradient-titanyum': 'dm-sans',
        'gradient-magenta': 'outfit',
        'gradient-altin': 'montserrat'
    };

    // Tema - Yazı Kalınlığı Haritası
    window.themePresetWeights = {
        'ersan-gold': '500',
        'cyber-neon': '500',
        'dracula-pro': '500'
    };

    // Hazır Tema -> Topbar & Sidebar Eşleştirme Haritası
    window.presetTopbarSidebarMap = {
        'kode': { topbar: 'beyaz', sidebar: 'klasik-koyu' },
        'cyber-neon': { topbar: 'cyber-dark', sidebar: 'cyber-dark' },
        'tokyo-gece': { topbar: 'tokyo-night', sidebar: 'tokyo-dark' },
        'nordic-polar': { topbar: 'polar-light', sidebar: 'polar-dark' },
        'sunset-horizon': { topbar: 'amber', sidebar: 'koyu-volkan' },
        'emerald-luxe': { topbar: 'zumrut', sidebar: 'koyu-zumrut' },
        'dracula-pro': { topbar: 'dracula-top', sidebar: 'dracula-side' },
        'minimal-slate': { topbar: 'beyaz', sidebar: 'slate-light' },
        'pastel-matcha': { topbar: 'matcha-light', sidebar: 'matcha-dark' },
        'velvet-berry': { topbar: 'mor', sidebar: 'koyu-bordo' },
        'midnight-sapphire': { topbar: 'safir', sidebar: 'safir-deep' },
        'warm-cappuccino': { topbar: 'warm-light', sidebar: 'warm-dark' },
        'neon-mint': { topbar: 'petrol', sidebar: 'mint-side' },
        'electric-indigo': { topbar: 'indigo', sidebar: 'koyu-mor' },
        'ersan-gold': { topbar: 'beyaz', sidebar: 'klasik-koyu' },
        'zumrut': { topbar: 'zumrut', sidebar: 'koyu-zumrut' },
        'kraliyet-moru': { topbar: 'mor', sidebar: 'koyu-mor' },
        'rose': { topbar: 'rose', sidebar: 'klasik-koyu' },
        'sade-beyaz': { topbar: 'beyaz', sidebar: 'sade-beyaz' },
        'koyu-gece': { topbar: 'oniks', sidebar: 'grafit-gri' },
        'safir-okyanus': { topbar: 'safir', sidebar: 'klasik-koyu' },
        'gun-batimi': { topbar: 'amber', sidebar: 'duman-gri' },
        'gece-altini': { topbar: 'oniks', sidebar: 'grafit-gri' },
        'mistik-bordo': { topbar: 'bordo', sidebar: 'koyu-bordo' },
        'nordik-cam': { topbar: 'nordik', sidebar: 'koyu-zumrut' },
        'soft-lavanta': { topbar: 'lavanta', sidebar: 'koyu-mor' },
        'soft-adacayi': { topbar: 'adacayi', sidebar: 'koyu-zumrut' },
        'soft-seftali': { topbar: 'amber', sidebar: 'duman-gri' },
        'soft-buz-mavisi': { topbar: 'buz-mavisi', sidebar: 'klasik-koyu' },
        'soft-vizon': { topbar: 'vizon', sidebar: 'slate-gri' },
        'modern-celik': { topbar: 'mavi', sidebar: 'slate-gri' },
        'antrasit-zumrut': { topbar: 'zumrut', sidebar: 'antrasit-gri' },
        'dumanli-bordo': { topbar: 'bordo', sidebar: 'duman-gri' },
        'grafiti-mor': { topbar: 'mor', sidebar: 'grafit-gri' },
        'kul-amber': { topbar: 'amber', sidebar: 'slate-gri' },
        'petrol-tas': { topbar: 'petrol', sidebar: 'antrasit-gri' },
        'platin-mavi': { topbar: 'safir', sidebar: 'platin-gri' },
        'titan-okyanus': { topbar: 'lacivert', sidebar: 'titan-gri' },
        'gradient-mor': { topbar: 'gradient-mor', sidebar: 'koyu-mor' },
        'gradient-safir': { topbar: 'gradient-safir', sidebar: 'koyu-okyanus' },
        'gradient-zumrut': { topbar: 'gradient-zumrut', sidebar: 'koyu-zumrut' },
        'gradient-yakut': { topbar: 'gradient-yakut', sidebar: 'koyu-bordo' },
        'gradient-amber': { topbar: 'gradient-amber', sidebar: 'koyu-volkan' },
        'gradient-petrol': { topbar: 'gradient-petrol', sidebar: 'koyu-petrol' },
        'gradient-lacivert': { topbar: 'gradient-lacivert', sidebar: 'koyu-nebula' },
        'gradient-titanyum': { topbar: 'gradient-titanyum', sidebar: 'grafit-gri' },
        'gradient-magenta': { topbar: 'gradient-magenta', sidebar: 'koyu-magenta' },
        'gradient-altin': { topbar: 'gradient-altin', sidebar: 'grafit-gri' }
    };

    // Renk dönüştürücü
    window.hexToRgba = function(hex, alpha) {
        if (!hex) return '';
        hex = hex.replace('#', '');
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        var r = parseInt(hex.substring(0, 2), 16) || 0;
        var g = parseInt(hex.substring(2, 4), 16) || 0;
        var b = parseInt(hex.substring(4, 6), 16) || 0;
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + (alpha !== undefined ? alpha : 1) + ')';
    };

    function setCookie(name, value) {
        try {
            document.cookie = name + "=" + encodeURIComponent(value) + "; path=/; max-age=31536000; SameSite=Lax";
        } catch(e) {}
    }

    function syncThemeCookie(theme) {
        setCookie("app_theme", theme);
    }

    // Drawer Açma / Kapama
    window.openThemeCustomizer = function() {
        var drawer = document.getElementById('theme-customizer-drawer');
        var backdrop = document.getElementById('theme-customizer-backdrop');
        if (drawer) drawer.classList.add('open');
        if (backdrop) backdrop.classList.add('open');
        window.syncAllThemeControls();
    };

    window.closeThemeCustomizer = function() {
        var drawer = document.getElementById('theme-customizer-drawer');
        var backdrop = document.getElementById('theme-customizer-backdrop');
        if (drawer) drawer.classList.remove('open');
        if (backdrop) backdrop.classList.remove('open');
    };

    // ==========================================
    // SEKME DEĞİŞTİRME FONKSİYONU
    // ==========================================
    window.switchThemeCustomizerTab = function(tabId) {
        // Sekme butonlarını güncelle
        document.querySelectorAll('.theme-customizer-tab-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
        });

        // Sekme içeriklerini güncelle
        document.querySelectorAll('.theme-customizer-tab-pane').forEach(function(pane) {
            pane.classList.toggle('active', pane.id === tabId);
        });
    };

    // ==========================================
    // HAZIR TEMA KATEGORİ FİLTRELEME
    // ==========================================
    window.filterThemePresets = function(category) {
        // Pill butonlarını güncelle
        document.querySelectorAll('.theme-preset-pill').forEach(function(pill) {
            pill.classList.toggle('active', pill.getAttribute('data-filter') === category);
        });

        // Kartları filtrele
        document.querySelectorAll('.theme-preset-card').forEach(function(card) {
            var cardCat = card.getAttribute('data-category') || 'modern';
            if (category === 'all' || cardCat === category) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    };

    // ==========================================
    // GÖRÜNÜM MODU SEÇİMİ (AÇIK / KOYU / OTO)
    // ==========================================
    window.selectThemeMode = function(mode) {
        var targetMode = mode;
        if (mode === 'auto') {
            targetMode = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
        }

        try {
            localStorage.setItem('theme_mode_choice', mode);
            localStorage.setItem('theme', targetMode);
            setCookie('theme', targetMode);
            syncThemeCookie(targetMode);
        } catch (e) {}

        document.documentElement.setAttribute('data-bs-theme', targetMode);
        if (document.body) document.body.setAttribute('data-bs-theme', targetMode);

        window.syncActiveModeButtons();
    };

    // 1. Hazır Tema Seçme
    window.selectThemePreset = function(presetName) {
        if (!presetName) return;
        try {
            localStorage.setItem('app_theme_preset', presetName);
            setCookie('app_theme_preset', presetName);
            localStorage.removeItem('app_primary_manual');
            localStorage.removeItem('app_primary_color');
            localStorage.removeItem('app_primary_name');
        } catch (e) {}

        var html = document.documentElement;
        html.removeAttribute('style');
        html.setAttribute('data-theme-preset', presetName);
        if (document.body) {
            document.body.removeAttribute('style');
            document.body.setAttribute('data-theme-preset', presetName);
        }

        var mapping = window.presetTopbarSidebarMap[presetName] || { topbar: 'beyaz', sidebar: 'klasik-koyu' };
        window.selectTopbarTheme(mapping.topbar, false);
        window.selectSidebarTheme(mapping.sidebar, false);

        var suggestedFont = window.themePresetFonts[presetName] || 'outfit';
        window.selectThemeFont(suggestedFont, false);

        var suggestedWeight = window.themePresetWeights[presetName] || '400';
        window.selectThemeWeight(suggestedWeight, false);

        // Koyu hazır temalarda Dark modu aktif et; açık temalarda Light modu aktif et
        var darkPresets = ['koyu-gece', 'gece-altini', 'cyber-neon', 'tokyo-gece', 'dracula-pro', 'midnight-sapphire'];
        var targetMode = darkPresets.indexOf(presetName) !== -1 ? 'dark' : 'light';
        
        document.documentElement.setAttribute('data-bs-theme', targetMode);
        if (document.body) document.body.setAttribute('data-bs-theme', targetMode);
        try { localStorage.setItem('theme', targetMode); } catch(e){}
        syncThemeCookie(targetMode);

        window.syncAllThemeControls();
    };

    // 2. Topbar Rengi
    window.selectTopbarTheme = function(topbarName, isManual) {
        if (!topbarName) return;
        try {
            if (isManual) localStorage.setItem('app_topbar_theme_manual', 'true');
            localStorage.setItem('app_topbar_theme', topbarName);
            setCookie('app_topbar_theme', topbarName);
        } catch (e) {}

        document.documentElement.setAttribute('data-topbar-theme', topbarName);
        if (document.body) document.body.setAttribute('data-topbar-theme', topbarName);
        window.syncActiveTopbarButtons();
    };

    // 3. Sidebar Rengi
    window.selectSidebarTheme = function(sidebarName, isManual) {
        if (!sidebarName) return;
        try {
            if (isManual) localStorage.setItem('app_sidebar_theme_manual', 'true');
            localStorage.setItem('app_sidebar_theme', sidebarName);
            setCookie('app_sidebar_theme', sidebarName);
        } catch (e) {}

        document.documentElement.setAttribute('data-sidebar-theme', sidebarName);
        if (document.body) document.body.setAttribute('data-sidebar-theme', sidebarName);

        var isLight = (sidebarName === 'sade-beyaz' || sidebarName === 'slate-light' || sidebarName === 'platin-gri');
        var navbar = document.getElementById('navbar');
        if (navbar) {
            navbar.setAttribute('data-sidebar-theme', sidebarName);
            navbar.setAttribute('data-bs-theme', isLight ? 'light' : 'dark');
        }
        window.syncActiveSidebarButtons();
    };

    // 4. Vurgu Rengi (Primary)
    window.selectPrimaryTheme = function(colorHex, colorName, isManual) {
        if (!colorHex) return;
        try {
            if (isManual) {
                localStorage.setItem('app_primary_manual', 'true');
                localStorage.setItem('app_primary_color', colorHex);
                localStorage.setItem('app_primary_name', colorName || 'custom');
            }
        } catch (e) {}

        var shadow = window.hexToRgba(colorHex, 0.3);
        var light = window.hexToRgba(colorHex, 0.12);

        var html = document.documentElement;
        html.style.setProperty('--theme-primary', colorHex);
        html.style.setProperty('--theme-primary-hover', colorHex);
        html.style.setProperty('--theme-primary-shadow', shadow);
        html.style.setProperty('--theme-primary-light', light);
        html.style.setProperty('--focus-color', colorHex);
        html.style.setProperty('--tblr-primary', colorHex);

        var hexLabel = document.getElementById('customPrimaryHexLabel');
        if (hexLabel) hexLabel.textContent = colorHex.toUpperCase();
        var picker = document.getElementById('customPrimaryColorPicker');
        if (picker) picker.value = colorHex;

        window.syncActivePrimaryButtons();
    };

    // 4.1. Sidebar Aktif Menü Rengi (Sidebar Active BG)
    window.selectSidebarActiveBg = function(colorValue, name, textColor, isManual) {
        if (!colorValue) return;
        try {
            if (isManual) {
                localStorage.setItem('app_sidebar_active_manual', 'true');
                localStorage.setItem('app_sidebar_active_bg', colorValue);
                localStorage.setItem('app_sidebar_active_name', name || 'custom');
                localStorage.setItem('app_sidebar_active_color', textColor || '#ffffff');
                setCookie('app_sidebar_active_bg', colorValue);
                setCookie('app_sidebar_active_name', name || 'custom');
                setCookie('app_sidebar_active_color', textColor || '#ffffff');
            }
        } catch (e) {}

        var html = document.documentElement;
        html.setAttribute('data-sidebar-active', name || 'custom');
        if (document.body) document.body.setAttribute('data-sidebar-active', name || 'custom');

        if (colorValue) {
            html.style.setProperty('--sidebar-active-bg', colorValue);
            html.style.setProperty('--sidebar-active-color', textColor || '#ffffff');
            var navbar = document.getElementById('navbar');
            if (navbar) {
                navbar.style.setProperty('--sidebar-active-bg', colorValue);
                navbar.style.setProperty('--sidebar-active-color', textColor || '#ffffff');
            }
        }

        if (name === 'custom') {
            var picker = document.getElementById('customSidebarActiveColorPicker');
            if (picker) picker.value = colorValue;
            var hexLabel = document.getElementById('customSidebarActiveHexLabel');
            if (hexLabel) hexLabel.textContent = colorValue.toUpperCase();
        } else if (name === 'primary') {
            var hexLabel = document.getElementById('customSidebarActiveHexLabel');
            if (hexLabel) hexLabel.textContent = 'PRIMARY';
        } else {
            var hexLabel = document.getElementById('customSidebarActiveHexLabel');
            if (hexLabel) hexLabel.textContent = name.toUpperCase();
        }

        window.syncActiveSidebarActiveButtons();
    };

    // 5. Yazı Tipi (Font)
    window.selectThemeFont = function(fontName, isManual) {
        if (!fontName) return;
        try {
            if (isManual) localStorage.setItem('app_theme_font_manual', 'true');
            localStorage.setItem('app_theme_font', fontName);
            setCookie('app_theme_font', fontName);
        } catch (e) {}

        document.documentElement.setAttribute('data-theme-font', fontName);
        if (document.body) document.body.setAttribute('data-theme-font', fontName);
        window.syncActiveThemeFontButtons();
    };

    // 6. Yazı Tipi Kalınlığı (Font Weight)
    window.selectThemeWeight = function(weightName, isManual) {
        if (!weightName) return;
        try {
            if (isManual) localStorage.setItem('app_theme_weight_manual', 'true');
            localStorage.setItem('app_theme_weight', weightName);
            setCookie('app_theme_weight', weightName);
        } catch (e) {}

        document.documentElement.setAttribute('data-theme-weight', weightName);
        if (document.body) document.body.setAttribute('data-theme-weight', weightName);
        window.syncActiveThemeWeightButtons();
    };

    // 7. Yazı Boyutu Ölçeği (Font Scale)
    window.selectThemeFontSize = function(scale, isManual) {
        if (!scale) return;
        try {
            if (isManual) localStorage.setItem('app_font_scale_manual', 'true');
            localStorage.setItem('app_font_scale', scale);
            setCookie('app_font_scale', scale);
        } catch (e) {}

        document.documentElement.setAttribute('data-font-size-scale', scale);
        if (document.body) document.body.setAttribute('data-font-size-scale', scale);
        window.syncActiveFontSizeButtons();
    };

    // 8. İkon Çizgi Kalınlığı (Icon Stroke)
    window.selectIconStroke = function(stroke, isManual) {
        if (!stroke) return;
        try {
            if (isManual) localStorage.setItem('app_icon_stroke_manual', 'true');
            localStorage.setItem('app_icon_stroke', stroke);
            setCookie('app_icon_stroke', stroke);
        } catch (e) {}

        document.documentElement.setAttribute('data-icon-stroke', stroke);
        if (document.body) document.body.setAttribute('data-icon-stroke', stroke);
        window.syncActiveIconStrokeButtons();
    };

    // 9. Köşe Yuvarlaklığı (Border Radius)
    window.selectThemeRadius = function(radius, isManual) {
        if (!radius) return;
        try {
            if (isManual) localStorage.setItem('app_theme_radius_manual', 'true');
            localStorage.setItem('app_theme_radius', radius);
            setCookie('app_theme_radius', radius);
        } catch (e) {}

        document.documentElement.setAttribute('data-theme-radius', radius);
        if (document.body) document.body.setAttribute('data-theme-radius', radius);
        window.syncActiveRadiusButtons();
    };

    // 10. Tablo Satır Yoğunluğu (Density)
    window.selectThemeDensity = function(density, isManual) {
        if (!density) return;
        try {
            if (isManual) localStorage.setItem('app_table_density_manual', 'true');
            localStorage.setItem('app_table_density', density);
            setCookie('app_table_density', density);
        } catch (e) {}

        document.documentElement.setAttribute('data-table-density', density);
        if (document.body) document.body.setAttribute('data-table-density', density);
        window.syncActiveDensityButtons();
    };

    // 11. Sidebar Arka Plan Efekti / Animasyonu
    window.selectSidebarEffect = function(effectName, isManual) {
        effectName = effectName || 'constellation';
        try {
            if (isManual) localStorage.setItem('app_sidebar_effect_manual', 'true');
            localStorage.setItem('app_sidebar_effect', effectName);
            setCookie('app_sidebar_effect', effectName);
        } catch (e) {}

        if (window.SidebarParticles && typeof window.SidebarParticles.setEffect === 'function') {
            window.SidebarParticles.setEffect(effectName, false);
        }
        window.syncActiveSidebarEffectButtons();
    };

    // 12. Tema Ayarlarını Sıfırlama
    window.resetThemeCustomizer = function() {
        try {
            var keys = [
                'app_theme_preset', 'app_primary_manual', 'app_primary_color', 'app_primary_name',
                'app_topbar_theme_manual', 'app_topbar_theme', 'app_sidebar_theme_manual', 'app_sidebar_theme',
                'app_sidebar_active_manual', 'app_sidebar_active_bg', 'app_sidebar_active_name', 'app_sidebar_active_color',
                'app_theme_font_manual', 'app_theme_font', 'app_theme_weight_manual', 'app_theme_weight',
                'app_font_scale_manual', 'app_font_scale', 'app_icon_stroke_manual', 'app_icon_stroke',
                'app_theme_radius_manual', 'app_theme_radius', 'app_table_density_manual', 'app_table_density',
                'theme_mode_choice'
            ];
            keys.forEach(function(k) {
                localStorage.removeItem(k);
            });
        } catch (e) {}

        window.selectThemePreset('ersan-gold');
        window.selectSidebarActiveBg('rgba(255, 255, 255, 0.18)', 'soft-white', '#ffffff', false);
        window.selectThemeRadius('default', false);
        window.selectThemeDensity('normal', false);
        window.selectThemeFontSize('100', false);
        window.selectIconStroke('1.5', false);
        window.selectThemeMode('light');

        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'Tema Sıfırlandı',
                text: 'Varsayılan tema başarıyla uygulandı.',
                timer: 1500,
                showConfirmButton: false
            });
        }
    };

    // Senkronizasyon Fonksiyonları
    window.syncActiveThemePresetCard = function() {
        var activePreset = localStorage.getItem('app_theme_preset') || document.documentElement.getAttribute('data-theme-preset') || 'ersan-gold';
        document.querySelectorAll('.theme-preset-card').forEach(function(card) {
            card.classList.toggle('active', card.getAttribute('data-preset') === activePreset);
        });
    };

    window.syncActiveTopbarButtons = function() {
        var activeTopbar = localStorage.getItem('app_topbar_theme') || document.documentElement.getAttribute('data-topbar-theme') || 'beyaz';
        document.querySelectorAll('[data-topbar]').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-topbar') === activeTopbar);
        });
    };

    window.syncActiveSidebarButtons = function() {
        var activeSidebar = localStorage.getItem('app_sidebar_theme') || document.documentElement.getAttribute('data-sidebar-theme') || 'klasik-koyu';
        document.querySelectorAll('[data-sidebar]').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-sidebar') === activeSidebar);
        });
    };

    window.syncActivePrimaryButtons = function() {
        var activePrimaryName = localStorage.getItem('app_primary_name');
        document.querySelectorAll('[data-primary]').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-primary') === activePrimaryName);
        });
    };

    window.syncActiveThemeFontButtons = function() {
        var activeFont = localStorage.getItem('app_theme_font') || document.documentElement.getAttribute('data-theme-font') || 'outfit';
        document.querySelectorAll('.theme-font-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-font') === activeFont);
        });
    };

    window.syncActiveThemeWeightButtons = function() {
        var activeWeight = localStorage.getItem('app_theme_weight') || document.documentElement.getAttribute('data-theme-weight') || '500';
        document.querySelectorAll('.theme-weight-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-weight') === activeWeight);
        });
    };

    window.syncActiveModeButtons = function() {
        var choice = localStorage.getItem('theme_mode_choice') || (document.documentElement.getAttribute('data-bs-theme') || 'light');
        document.querySelectorAll('[data-mode-btn]').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-mode-btn') === choice);
        });
    };

    window.syncActiveFontSizeButtons = function() {
        var activeScale = localStorage.getItem('app_font_scale') || document.documentElement.getAttribute('data-font-size-scale') || '100';
        document.querySelectorAll('[data-scale]').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-scale') === activeScale);
        });
    };

    window.syncActiveIconStrokeButtons = function() {
        var activeStroke = localStorage.getItem('app_icon_stroke') || document.documentElement.getAttribute('data-icon-stroke') || '1.5';
        document.querySelectorAll('[data-stroke]').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-stroke') === activeStroke);
        });
    };

    window.syncActiveRadiusButtons = function() {
        var activeRadius = localStorage.getItem('app_theme_radius') || document.documentElement.getAttribute('data-theme-radius') || 'default';
        document.querySelectorAll('[data-radius]').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-radius') === activeRadius);
        });
    };

    window.syncActiveDensityButtons = function() {
        var activeDensity = localStorage.getItem('app_table_density') || document.documentElement.getAttribute('data-table-density') || 'normal';
        document.querySelectorAll('[data-density]').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-density') === activeDensity);
        });
    };

    window.syncActiveSidebarActiveButtons = function() {
        var activeSidebarActiveName = localStorage.getItem('app_sidebar_active_name') || 'primary';
        document.querySelectorAll('[data-sidebar-active]').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-sidebar-active') === activeSidebarActiveName);
        });
    };

    window.syncActiveSidebarEffectButtons = function() {
        var activeEffect = localStorage.getItem('app_sidebar_effect') || 'constellation';
        document.querySelectorAll('[data-sidebar-effect]').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-sidebar-effect') === activeEffect);
        });
    };

    window.syncAllThemeControls = function() {
        window.syncActiveThemePresetCard();
        window.syncActiveTopbarButtons();
        window.syncActiveSidebarButtons();
        window.syncActiveSidebarActiveButtons();
        window.syncActiveSidebarEffectButtons();
        window.syncActivePrimaryButtons();
        window.syncActiveThemeFontButtons();
        window.syncActiveThemeWeightButtons();
        window.syncActiveModeButtons();
        window.syncActiveFontSizeButtons();
        window.syncActiveIconStrokeButtons();
        window.syncActiveRadiusButtons();
        window.syncActiveDensityButtons();
    };

    // DOM Yüklendiğinde Başlat
    function initThemeManager() {
        var activePreset = localStorage.getItem('app_theme_preset') || 'ersan-gold';
        var darkPresets = ['koyu-gece', 'gece-altini', 'cyber-neon', 'tokyo-gece', 'dracula-pro', 'midnight-sapphire'];
        var isDarkPreset = darkPresets.indexOf(activePreset) !== -1;
        var expectedMode = isDarkPreset ? 'dark' : (localStorage.getItem('theme') || 'light');
        if (!isDarkPreset && localStorage.getItem('app_theme_preset') && localStorage.getItem('theme') !== 'dark') {
            expectedMode = 'light';
        }
        document.documentElement.setAttribute('data-bs-theme', expectedMode);
        if (document.body) document.body.setAttribute('data-bs-theme', expectedMode);

        var activeFont = localStorage.getItem('app_theme_font') || (window.themePresetFonts[activePreset] || 'outfit');
        document.documentElement.setAttribute('data-theme-font', activeFont);
        if (document.body) document.body.setAttribute('data-theme-font', activeFont);

        var activeWeight = localStorage.getItem('app_theme_weight') || (window.themePresetWeights[activePreset] || '500');
        document.documentElement.setAttribute('data-theme-weight', activeWeight);
        if (document.body) document.body.setAttribute('data-theme-weight', activeWeight);

        var activeRadius = localStorage.getItem('app_theme_radius') || 'default';
        document.documentElement.setAttribute('data-theme-radius', activeRadius);
        if (document.body) document.body.setAttribute('data-theme-radius', activeRadius);

        var activeDensity = localStorage.getItem('app_table_density') || 'normal';
        document.documentElement.setAttribute('data-table-density', activeDensity);
        if (document.body) document.body.setAttribute('data-table-density', activeDensity);

        var activeScale = localStorage.getItem('app_font_scale') || '100';
        document.documentElement.setAttribute('data-font-size-scale', activeScale);
        if (document.body) document.body.setAttribute('data-font-size-scale', activeScale);

        var activeStroke = localStorage.getItem('app_icon_stroke') || '1.5';
        document.documentElement.setAttribute('data-icon-stroke', activeStroke);
        if (document.body) document.body.setAttribute('data-icon-stroke', activeStroke);

        // Özel Primary Color varsa uygula
        var customPrimary = localStorage.getItem('app_primary_color');
        if (customPrimary) {
            window.selectPrimaryTheme(customPrimary, localStorage.getItem('app_primary_name'), false);
        }

        // Sidebar Aktif Menü Rengi Senkronizasyonu
        var savedSidebarActiveBg = localStorage.getItem('app_sidebar_active_bg') || 'rgba(255, 255, 255, 0.18)';
        var savedSidebarActiveName = localStorage.getItem('app_sidebar_active_name') || 'soft-white';
        var savedSidebarActiveColor = localStorage.getItem('app_sidebar_active_color') || '#ffffff';
        window.selectSidebarActiveBg(savedSidebarActiveBg, savedSidebarActiveName, savedSidebarActiveColor, false);

        // Sidebar zemin ve tema senkronizasyonu
        var activeSidebar = localStorage.getItem('app_sidebar_theme') || document.documentElement.getAttribute('data-sidebar-theme') || 'klasik-koyu';
        var isLightSidebar = (activeSidebar === 'sade-beyaz' || activeSidebar === 'slate-light' || activeSidebar === 'platin-gri');
        var navbarEl = document.getElementById('navbar');
        if (navbarEl) {
            navbarEl.setAttribute('data-sidebar-theme', activeSidebar);
            navbarEl.setAttribute('data-bs-theme', isLightSidebar ? 'light' : 'dark');
        }

        window.syncAllThemeControls();

        // Üst bardaki karanlık/aydınlık mod toggle butonlarına tıklandığında localStorage'ı ve cookie'yi güncelle
        document.querySelectorAll('.js-theme-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var currentTheme = localStorage.getItem('theme') || (document.body ? document.body.getAttribute('data-bs-theme') : 'light');
                var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem('theme', newTheme); } catch(e){}
                syncThemeCookie(newTheme);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeManager);
    } else {
        initThemeManager();
    }
})();
