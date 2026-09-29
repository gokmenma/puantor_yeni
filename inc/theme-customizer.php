<?php
// Tema Özelleştirici Sağ Çekmece Paneli (Drawer - Tabbed System)
?>
<!-- Tema Özelleştirici Backdrop -->
<div class="theme-customizer-backdrop" id="theme-customizer-backdrop" onclick="closeThemeCustomizer();"></div>

<!-- Tema Özelleştirici Sağ Çekmece Paneli -->
<div class="theme-customizer-drawer" id="theme-customizer-drawer">
    <!-- Header -->
    <div class="theme-customizer-header">
        <h5 class="theme-customizer-title">
            <i class="ti ti-palette text-primary" style="font-size: 1.25rem;"></i>
            <span>Tema Özelleştirici</span>
        </h5>
        <button type="button" class="theme-customizer-close" onclick="closeThemeCustomizer();" title="Kapat">
            <i class="ti ti-x"></i>
        </button>
    </div>

    <!-- Sekmeler (Tabs Bar) -->
    <div class="theme-customizer-tabs">
        <button type="button" class="theme-customizer-tab-btn active" data-tab="tab-presets" onclick="switchThemeCustomizerTab('tab-presets');">
            <i class="ti ti-layout-grid"></i>
            <span>Temalar</span>
        </button>
        <button type="button" class="theme-customizer-tab-btn" data-tab="tab-colors" onclick="switchThemeCustomizerTab('tab-colors');">
            <i class="ti ti-color-swatch"></i>
            <span>Renkler</span>
        </button>
        <button type="button" class="theme-customizer-tab-btn" data-tab="tab-typography" onclick="switchThemeCustomizerTab('tab-typography');">
            <i class="ti ti-typography"></i>
            <span>Font & İkon</span>
        </button>
        <button type="button" class="theme-customizer-tab-btn" data-tab="tab-layout" onclick="switchThemeCustomizerTab('tab-layout');">
            <i class="ti ti-adjustments-horizontal"></i>
            <span>Arayüz</span>
        </button>
    </div>
    
    <!-- Body / Content -->
    <div class="theme-customizer-body">

        <!-- ==========================================
             SEKME 1: HAZIR TEMALAR (PRESETS)
             ========================================== -->
        <div class="theme-customizer-tab-pane active" id="tab-presets">
            <!-- Aydınlık / Karanlık Mod Hızlı Seçim -->
            <div class="theme-customizer-section-title-wrap">
                <h6 class="theme-customizer-section-title">Görünüm Modu</h6>
                <span class="theme-customizer-badge bg-primary-lt text-primary">Mod</span>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center" data-mode-btn="light" onclick="selectThemeMode('light');">
                        <i class="ti ti-sun text-warning"></i>
                        <span>Açık</span>
                    </button>
                </div>
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center" data-mode-btn="dark" onclick="selectThemeMode('dark');">
                        <i class="ti ti-moon text-primary"></i>
                        <span>Koyu</span>
                    </button>
                </div>
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center" data-mode-btn="auto" onclick="selectThemeMode('auto');">
                        <i class="ti ti-device-desktop text-secondary"></i>
                        <span>Oto</span>
                    </button>
                </div>
            </div>

            <!-- Kategori Filtreleme Butonları -->
            <div class="theme-customizer-section-title-wrap">
                <h6 class="theme-customizer-section-title">Hazır Tasarımlar</h6>
                <span class="theme-customizer-badge bg-azure-lt text-azure">Koleksiyon</span>
            </div>
            
            <div class="theme-preset-pills">
                <button type="button" class="theme-preset-pill active" data-filter="all" onclick="filterThemePresets('all');">Tümü</button>
                <button type="button" class="theme-preset-pill" data-filter="modern" onclick="filterThemePresets('modern');">Modern SaaS</button>
                <button type="button" class="theme-preset-pill" data-filter="dark" onclick="filterThemePresets('dark');">Koyu/Gece</button>
                <button type="button" class="theme-preset-pill" data-filter="soft" onclick="filterThemePresets('soft');">Pastel & Soft</button>
                <button type="button" class="theme-preset-pill" data-filter="gradient" onclick="filterThemePresets('gradient');">Gradient</button>
            </div>
            
            <div class="theme-presets-scroll-wrap">
                <div class="theme-presets-grid">
                    <!-- 1. Tabler Orijinal / Kode -->
                    <div class="theme-preset-card" data-preset="kode" data-category="modern" onclick="selectThemePreset('kode');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #ffffff; border-bottom: 1px solid #e2e8f0;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1e1e2d;"></div>
                                <div class="theme-preview-content" style="background: #f8fafc;">
                                    <div class="theme-preview-pill" style="background: #206bc4;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Tabler Orijinal</div>
                    </div>

                    <!-- 2. Cyber Neon (Yeni) -->
                    <div class="theme-preset-card" data-preset="cyber-neon" data-category="dark" onclick="selectThemePreset('cyber-neon');">
                        <div class="theme-preview-box" style="background: #090d16;">
                            <div class="theme-preview-header" style="background: #090d16; border-bottom: 1px solid #06b6d4;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #090d16; border-right: 1px solid #1e293b;"></div>
                                <div class="theme-preview-content" style="background: #0d1322;">
                                    <div class="theme-preview-pill" style="background: #06b6d4;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Cyber Neon</div>
                    </div>

                    <!-- 3. Tokyo Gece (Yeni) -->
                    <div class="theme-preset-card" data-preset="tokyo-gece" data-category="dark" onclick="selectThemePreset('tokyo-gece');">
                        <div class="theme-preview-box" style="background: #1a1b26;">
                            <div class="theme-preview-header" style="background: #1a1b26;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #16161e;"></div>
                                <div class="theme-preview-content" style="background: #1a1b26;">
                                    <div class="theme-preview-pill" style="background: #7aa2f7;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Tokyo Gece</div>
                    </div>

                    <!-- 4. Nordic Polar (Yeni) -->
                    <div class="theme-preset-card" data-preset="nordic-polar" data-category="modern" onclick="selectThemePreset('nordic-polar');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #f0f9ff; border-bottom: 1px solid #bae6fd;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #0c1a29;"></div>
                                <div class="theme-preview-content" style="background: #f8fafc;">
                                    <div class="theme-preview-pill" style="background: #0ea5e9;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Nordic Polar</div>
                    </div>

                    <!-- 5. Sunset Horizon (Yeni) -->
                    <div class="theme-preset-card" data-preset="sunset-horizon" data-category="modern" onclick="selectThemePreset('sunset-horizon');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #ea580c;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1c1917;"></div>
                                <div class="theme-preview-content" style="background: #fff7ed;">
                                    <div class="theme-preview-pill" style="background: #f97316;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Sunset Horizon</div>
                    </div>

                    <!-- 6. Emerald Luxe (Yeni) -->
                    <div class="theme-preset-card" data-preset="emerald-luxe" data-category="modern" onclick="selectThemePreset('emerald-luxe');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #064e3b;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #022c22;"></div>
                                <div class="theme-preview-content" style="background: #f0fdf4;">
                                    <div class="theme-preview-pill" style="background: #10b981;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Emerald Luxe</div>
                    </div>

                    <!-- 7. Dracula Pro (Yeni) -->
                    <div class="theme-preset-card" data-preset="dracula-pro" data-category="dark" onclick="selectThemePreset('dracula-pro');">
                        <div class="theme-preview-box" style="background: #282a36;">
                            <div class="theme-preview-header" style="background: #282a36;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1e1f29;"></div>
                                <div class="theme-preview-content" style="background: #282a36;">
                                    <div class="theme-preview-pill" style="background: #bd93f9;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Dracula Pro</div>
                    </div>

                    <!-- 8. Minimal Slate (Yeni) -->
                    <div class="theme-preset-card" data-preset="minimal-slate" data-category="modern" onclick="selectThemePreset('minimal-slate');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #ffffff; border-bottom: 1px solid #e2e8f0;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #f8fafc; border-right: 1px solid #e2e8f0;"></div>
                                <div class="theme-preview-content" style="background: #ffffff;">
                                    <div class="theme-preview-pill" style="background: #0f172a;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Minimal Slate</div>
                    </div>

                    <!-- 9. Pastel Matcha (Yeni) -->
                    <div class="theme-preset-card" data-preset="pastel-matcha" data-category="soft" onclick="selectThemePreset('pastel-matcha');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #f7fee7; border-bottom: 1px solid #d9f99d;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #142609;"></div>
                                <div class="theme-preview-content" style="background: #f7fee7;">
                                    <div class="theme-preview-pill" style="background: #65a30d;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Pastel Matcha</div>
                    </div>

                    <!-- 10. Velvet Berry (Yeni) -->
                    <div class="theme-preset-card" data-preset="velvet-berry" data-category="gradient" onclick="selectThemePreset('velvet-berry');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: linear-gradient(135deg, #240827 0%, #c026d3 100%);"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #240827;"></div>
                                <div class="theme-preview-content" style="background: #fdf4ff;">
                                    <div class="theme-preview-pill" style="background: #c026d3;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Velvet Berry</div>
                    </div>

                    <!-- 11. Midnight Sapphire (Yeni) -->
                    <div class="theme-preset-card" data-preset="midnight-sapphire" data-category="dark" onclick="selectThemePreset('midnight-sapphire');">
                        <div class="theme-preview-box" style="background: #050e1a;">
                            <div class="theme-preview-header" style="background: #0b192c;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #050e1a;"></div>
                                <div class="theme-preview-content" style="background: #091424;">
                                    <div class="theme-preview-pill" style="background: #38bdf8;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Midnight Safir</div>
                    </div>

                    <!-- 12. Warm Cappuccino (Yeni) -->
                    <div class="theme-preset-card" data-preset="warm-cappuccino" data-category="soft" onclick="selectThemePreset('warm-cappuccino');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #fef3c7; border-bottom: 1px solid #fde68a;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #291e12;"></div>
                                <div class="theme-preview-content" style="background: #fffbeb;">
                                    <div class="theme-preview-pill" style="background: #b45309;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Cappuccino</div>
                    </div>

                    <!-- 13. Neon Mint (Yeni) -->
                    <div class="theme-preset-card" data-preset="neon-mint" data-category="modern" onclick="selectThemePreset('neon-mint');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #042f2e;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #081f1d;"></div>
                                <div class="theme-preview-content" style="background: #f0fdfa;">
                                    <div class="theme-preview-pill" style="background: #14b8a6;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Neon Mint</div>
                    </div>

                    <!-- 14. Electric Indigo (Yeni) -->
                    <div class="theme-preset-card" data-preset="electric-indigo" data-category="modern" onclick="selectThemePreset('electric-indigo');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #4f46e5;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1e1b4b;"></div>
                                <div class="theme-preview-content" style="background: #eef2ff;">
                                    <div class="theme-preview-pill" style="background: #6366f1;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Electric Indigo</div>
                    </div>

                    <!-- 15. Ersan Gold -->
                    <div class="theme-preset-card" data-preset="ersan-gold" data-category="modern" onclick="selectThemePreset('ersan-gold');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #ffffff; border-bottom: 1px solid #e2e8f0;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1e293b;"></div>
                                <div class="theme-preview-content" style="background: #f8fafc;">
                                    <div class="theme-preview-pill" style="background: #475569;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Ersan Gold</div>
                    </div>

                    <!-- 16. Zümrüt -->
                    <div class="theme-preset-card" data-preset="zumrut" data-category="modern" onclick="selectThemePreset('zumrut');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #059669;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #132a24;"></div>
                                <div class="theme-preview-content" style="background: #f0fdf4;">
                                    <div class="theme-preview-pill" style="background: #10b981;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Zümrüt Yeşil</div>
                    </div>

                    <!-- 17. Kraliyet Moru -->
                    <div class="theme-preset-card" data-preset="kraliyet-moru" data-category="modern" onclick="selectThemePreset('kraliyet-moru');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #5b21b6;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1e1b4b;"></div>
                                <div class="theme-preview-content" style="background: #faf5ff;">
                                    <div class="theme-preview-pill" style="background: #7c3aed;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Kraliyet Moru</div>
                    </div>

                    <!-- 18. Rose -->
                    <div class="theme-preset-card" data-preset="rose" data-category="modern" onclick="selectThemePreset('rose');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #e11d48;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1f1924;"></div>
                                <div class="theme-preview-content" style="background: #fff1f2;">
                                    <div class="theme-preview-pill" style="background: #e11d48;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Rose Pembe</div>
                    </div>

                    <!-- 19. Sade Beyaz -->
                    <div class="theme-preset-card" data-preset="sade-beyaz" data-category="soft" onclick="selectThemePreset('sade-beyaz');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #ffffff; border-bottom: 1px solid #e2e8f0;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #ffffff; border-right: 1px solid #e2e8f0;"></div>
                                <div class="theme-preview-content" style="background: #f8fafc;">
                                    <div class="theme-preview-pill" style="background: #0f172a;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Sade Beyaz</div>
                    </div>

                    <!-- 20. Koyu Gece -->
                    <div class="theme-preset-card" data-preset="koyu-gece" data-category="dark" onclick="selectThemePreset('koyu-gece');">
                        <div class="theme-preview-box" style="background: #0b0f19;">
                            <div class="theme-preview-header" style="background: #1e293b;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #0f172a;"></div>
                                <div class="theme-preview-content" style="background: #0b0f19;">
                                    <div class="theme-preview-pill" style="background: #06b6d4;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Koyu Gece</div>
                    </div>

                    <!-- 21. Safir Okyanus -->
                    <div class="theme-preset-card" data-preset="safir-okyanus" data-category="modern" onclick="selectThemePreset('safir-okyanus');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #0284c7;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #0f172a;"></div>
                                <div class="theme-preview-content" style="background: #f0f9ff;">
                                    <div class="theme-preview-pill" style="background: #0284c7;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Safir Okyanus</div>
                    </div>

                    <!-- 22. Gün Batımı -->
                    <div class="theme-preset-card" data-preset="gun-batimi" data-category="modern" onclick="selectThemePreset('gun-batimi');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #ea580c;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1c1917;"></div>
                                <div class="theme-preview-content" style="background: #fff7ed;">
                                    <div class="theme-preview-pill" style="background: #ea580c;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Gün Batımı</div>
                    </div>

                    <!-- 23. Gece Altını -->
                    <div class="theme-preset-card" data-preset="gece-altini" data-category="dark" onclick="selectThemePreset('gece-altini');">
                        <div class="theme-preview-box" style="background: #18181b;">
                            <div class="theme-preview-header" style="background: #18181b;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #09090b;"></div>
                                <div class="theme-preview-content" style="background: #18181b;">
                                    <div class="theme-preview-pill" style="background: #eab308;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Gece Altını</div>
                    </div>

                    <!-- 24. Soft Lavanta -->
                    <div class="theme-preset-card" data-preset="soft-lavanta" data-category="soft" onclick="selectThemePreset('soft-lavanta');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #f5f3ff; border-bottom: 1px solid #e9d5ff;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1e1b4b;"></div>
                                <div class="theme-preview-content" style="background: #faf5ff;">
                                    <div class="theme-preview-pill" style="background: #8b5cf6;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Soft Lavanta</div>
                    </div>

                    <!-- 25. Soft Adaçayı -->
                    <div class="theme-preset-card" data-preset="soft-adacayi" data-category="soft" onclick="selectThemePreset('soft-adacayi');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #f0fdfa; border-bottom: 1px solid #ccfbf1;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #132a24;"></div>
                                <div class="theme-preview-content" style="background: #f0fdf4;">
                                    <div class="theme-preview-pill" style="background: #14b8a6;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Soft Adaçayı</div>
                    </div>

                    <!-- 26. Modern Çelik -->
                    <div class="theme-preset-card" data-preset="modern-celik" data-category="modern" onclick="selectThemePreset('modern-celik');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: #2563eb;"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #334155;"></div>
                                <div class="theme-preview-content" style="background: #f8fafc;">
                                    <div class="theme-preview-pill" style="background: #2563eb;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Modern Çelik</div>
                    </div>

                    <!-- 27. Gradient Mor -->
                    <div class="theme-preset-card" data-preset="gradient-mor" data-category="gradient" onclick="selectThemePreset('gradient-mor');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: linear-gradient(135deg, #1e1b4b 0%, #7c3aed 100%);"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1e1b4b;"></div>
                                <div class="theme-preview-content" style="background: #faf5ff;">
                                    <div class="theme-preview-pill" style="background: #7c3aed;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Grad. Mor</div>
                    </div>

                    <!-- 28. Gradient Safir -->
                    <div class="theme-preset-card" data-preset="gradient-safir" data-category="gradient" onclick="selectThemePreset('gradient-safir');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: linear-gradient(135deg, #0f172a 0%, #0284c7 100%);"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #0f172a;"></div>
                                <div class="theme-preview-content" style="background: #f0f9ff;">
                                    <div class="theme-preview-pill" style="background: #0284c7;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Grad. Safir</div>
                    </div>

                    <!-- 29. Gradient Zümrüt -->
                    <div class="theme-preset-card" data-preset="gradient-zumrut" data-category="gradient" onclick="selectThemePreset('gradient-zumrut');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: linear-gradient(135deg, #132a24 0%, #059669 100%);"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #132a24;"></div>
                                <div class="theme-preview-content" style="background: #f0fdf4;">
                                    <div class="theme-preview-pill" style="background: #059669;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Grad. Zümrüt</div>
                    </div>

                    <!-- 30. Gradient Yakut -->
                    <div class="theme-preset-card" data-preset="gradient-yakut" data-category="gradient" onclick="selectThemePreset('gradient-yakut');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: linear-gradient(135deg, #1e1117 0%, #e11d48 100%);"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1e1117;"></div>
                                <div class="theme-preview-content" style="background: #fff1f2;">
                                    <div class="theme-preview-pill" style="background: #e11d48;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Grad. Yakut</div>
                    </div>

                    <!-- 31. Gradient Amber -->
                    <div class="theme-preset-card" data-preset="gradient-amber" data-category="gradient" onclick="selectThemePreset('gradient-amber');">
                        <div class="theme-preview-box">
                            <div class="theme-preview-header" style="background: linear-gradient(135deg, #1c1917 0%, #ea580c 100%);"></div>
                            <div class="theme-preview-body">
                                <div class="theme-preview-sidebar" style="background: #1c1917;"></div>
                                <div class="theme-preview-content" style="background: #fff7ed;">
                                    <div class="theme-preview-pill" style="background: #ea580c;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-preset-name">Grad. Amber</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             SEKME 2: RENKLER (COLORS & PALETTE)
             ========================================== -->
        <div class="theme-customizer-tab-pane" id="tab-colors">
            <!-- 1. Vurgu / Birincil Renk (Primary) -->
            <div class="theme-customizer-section-title-wrap">
                <h6 class="theme-customizer-section-title">Vurgu & Buton Rengi (Primary)</h6>
                <span class="theme-customizer-badge bg-warning-lt text-warning">Primary</span>
            </div>
            <div class="theme-color-palette-grid mb-3">
                <button type="button" class="theme-color-swatch-btn" data-primary="mavi" onclick="selectPrimaryTheme('#2563eb', 'mavi', true);">
                    <span class="theme-color-dot" style="background: #2563eb;"></span>
                    <span class="theme-color-label">Mavi</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-primary="zumrut" onclick="selectPrimaryTheme('#059669', 'zumrut', true);">
                    <span class="theme-color-dot" style="background: #059669;"></span>
                    <span class="theme-color-label">Zümrüt</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-primary="mor" onclick="selectPrimaryTheme('#7c3aed', 'mor', true);">
                    <span class="theme-color-dot" style="background: #7c3aed;"></span>
                    <span class="theme-color-label">Mor</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-primary="rose" onclick="selectPrimaryTheme('#e11d48', 'rose', true);">
                    <span class="theme-color-dot" style="background: #e11d48;"></span>
                    <span class="theme-color-label">Rose</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-primary="amber" onclick="selectPrimaryTheme('#ea580c', 'amber', true);">
                    <span class="theme-color-dot" style="background: #ea580c;"></span>
                    <span class="theme-color-label">Amber</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-primary="safir" onclick="selectPrimaryTheme('#0284c7', 'safir', true);">
                    <span class="theme-color-dot" style="background: #0284c7;"></span>
                    <span class="theme-color-label">Safir</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-primary="cyan" onclick="selectPrimaryTheme('#06b6d4', 'cyan', true);">
                    <span class="theme-color-dot" style="background: #06b6d4;"></span>
                    <span class="theme-color-label">Cyan Neon</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-primary="indigo" onclick="selectPrimaryTheme('#6366f1', 'indigo', true);">
                    <span class="theme-color-dot" style="background: #6366f1;"></span>
                    <span class="theme-color-label">İndigo</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-primary="mint" onclick="selectPrimaryTheme('#14b8a6', 'mint', true);">
                    <span class="theme-color-dot" style="background: #14b8a6;"></span>
                    <span class="theme-color-label">Mint Yeşil</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-primary="altin" onclick="selectPrimaryTheme('#d97706', 'altin', true);">
                    <span class="theme-color-dot" style="background: #d97706;"></span>
                    <span class="theme-color-label">Sıcak Altın</span>
                </button>
                <!-- Özel HEX Renk Seçici -->
                <div style="grid-column: span 2; display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.03); border: 1px dashed #cbd5e1; padding: 6px 12px; border-radius: 8px; margin-top: 2px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="color" id="customPrimaryColorPicker" style="width: 26px; height: 26px; border: none; border-radius: 4px; cursor: pointer; padding: 0; background: transparent;" value="#206bc4" oninput="selectPrimaryTheme(this.value, 'custom', true);">
                        <label for="customPrimaryColorPicker" style="margin: 0; font-size: 11.5px; font-weight: 600; cursor: pointer;">Özel Renk Seç (HEX)</label>
                    </div>
                    <span id="customPrimaryHexLabel" style="font-size: 11px; font-family: monospace; color: #64748b; font-weight: 600;">#206BC4</span>
                </div>
            </div>

            <!-- 2. Topbar (Üst Menü) Rengi -->
            <div class="theme-customizer-section-title-wrap mt-3">
                <h6 class="theme-customizer-section-title">Topbar (Üst Menü) Rengi</h6>
                <span class="theme-customizer-badge bg-blue-lt text-blue">Üst Bar</span>
            </div>
            <div class="theme-color-palette-grid mb-3">
                <button type="button" class="theme-color-swatch-btn" data-topbar="beyaz" onclick="selectTopbarTheme('beyaz', true);">
                    <span class="theme-color-dot" style="background: #ffffff; border: 1px solid #cbd5e1;"></span>
                    <span class="theme-color-label">Beyaz</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="mavi" onclick="selectTopbarTheme('mavi', true);">
                    <span class="theme-color-dot" style="background: #2563eb;"></span>
                    <span class="theme-color-label">Mavi</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="indigo" onclick="selectTopbarTheme('indigo', true);">
                    <span class="theme-color-dot" style="background: #4f46e5;"></span>
                    <span class="theme-color-label">İndigo</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="zumrut" onclick="selectTopbarTheme('zumrut', true);">
                    <span class="theme-color-dot" style="background: #059669;"></span>
                    <span class="theme-color-label">Zümrüt</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="mor" onclick="selectTopbarTheme('mor', true);">
                    <span class="theme-color-dot" style="background: #7c3aed;"></span>
                    <span class="theme-color-label">Mor</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="rose" onclick="selectTopbarTheme('rose', true);">
                    <span class="theme-color-dot" style="background: #e11d48;"></span>
                    <span class="theme-color-label">Rose</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="bordo" onclick="selectTopbarTheme('bordo', true);">
                    <span class="theme-color-dot" style="background: #9f1239;"></span>
                    <span class="theme-color-label">Bordo</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="amber" onclick="selectTopbarTheme('amber', true);">
                    <span class="theme-color-dot" style="background: #ea580c;"></span>
                    <span class="theme-color-label">Amber</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="safir" onclick="selectTopbarTheme('safir', true);">
                    <span class="theme-color-dot" style="background: #0284c7;"></span>
                    <span class="theme-color-label">Safir</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="oniks" onclick="selectTopbarTheme('oniks', true);">
                    <span class="theme-color-dot" style="background: #18181b;"></span>
                    <span class="theme-color-label">Koyu Oniks</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="tokyo-night" onclick="selectTopbarTheme('tokyo-night', true);">
                    <span class="theme-color-dot" style="background: #1a1b26;"></span>
                    <span class="theme-color-label">Tokyo Night</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="cyber-dark" onclick="selectTopbarTheme('cyber-dark', true);">
                    <span class="theme-color-dot" style="background: #090d16;"></span>
                    <span class="theme-color-label">Cyber Dark</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="polar-light" onclick="selectTopbarTheme('polar-light', true);">
                    <span class="theme-color-dot" style="background: #f0f9ff; border: 1px solid #bae6fd;"></span>
                    <span class="theme-color-label">Polar Light</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="matcha-light" onclick="selectTopbarTheme('matcha-light', true);">
                    <span class="theme-color-dot" style="background: #f7fee7; border: 1px solid #d9f99d;"></span>
                    <span class="theme-color-label">Matcha Light</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="gradient-mor" onclick="selectTopbarTheme('gradient-mor', true);">
                    <span class="theme-color-dot" style="background: linear-gradient(135deg, #1e1b4b 0%, #7c3aed 100%);"></span>
                    <span class="theme-color-label">Grad. Mor</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-topbar="gradient-safir" onclick="selectTopbarTheme('gradient-safir', true);">
                    <span class="theme-color-dot" style="background: linear-gradient(135deg, #0f172a 0%, #0284c7 100%);"></span>
                    <span class="theme-color-label">Grad. Safir</span>
                </button>
            </div>

            <!-- 3. Sidebar (Sol Menü) Rengi -->
            <div class="theme-customizer-section-title-wrap mt-3">
                <h6 class="theme-customizer-section-title">Sidebar (Sol Menü) Rengi</h6>
                <span class="theme-customizer-badge bg-secondary-lt text-secondary">Sol Menü</span>
            </div>
            <div class="theme-color-palette-grid">
                <button type="button" class="theme-color-swatch-btn" data-sidebar="klasik-koyu" onclick="selectSidebarTheme('klasik-koyu', true);">
                    <span class="theme-color-dot" style="background: #1e1e2d;"></span>
                    <span class="theme-color-label">Klasik Koyu</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="slate-gri" onclick="selectSidebarTheme('slate-gri', true);">
                    <span class="theme-color-dot" style="background: #334155;"></span>
                    <span class="theme-color-label">Slate Gri</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="antrasit-gri" onclick="selectSidebarTheme('antrasit-gri', true);">
                    <span class="theme-color-dot" style="background: #374151;"></span>
                    <span class="theme-color-label">Antrasit Gri</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="grafit-gri" onclick="selectSidebarTheme('grafit-gri', true);">
                    <span class="theme-color-dot" style="background: #27272a;"></span>
                    <span class="theme-color-label">Grafit Gri</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="tokyo-dark" onclick="selectSidebarTheme('tokyo-dark', true);">
                    <span class="theme-color-dot" style="background: #16161e;"></span>
                    <span class="theme-color-label">Tokyo Dark</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="cyber-dark" onclick="selectSidebarTheme('cyber-dark', true);">
                    <span class="theme-color-dot" style="background: #090d16;"></span>
                    <span class="theme-color-label">Cyber Dark</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-zumrut" onclick="selectSidebarTheme('koyu-zumrut', true);">
                    <span class="theme-color-dot" style="background: #132a24;"></span>
                    <span class="theme-color-label">Koyu Zümrüt</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-mor" onclick="selectSidebarTheme('koyu-mor', true);">
                    <span class="theme-color-dot" style="background: #1e1b4b;"></span>
                    <span class="theme-color-label">Koyu Mor</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-bordo" onclick="selectSidebarTheme('koyu-bordo', true);">
                    <span class="theme-color-dot" style="background: #1e1117;"></span>
                    <span class="theme-color-label">Koyu Bordo</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="koyu-okyanus" onclick="selectSidebarTheme('koyu-okyanus', true);">
                    <span class="theme-color-dot" style="background: #0f172a;"></span>
                    <span class="theme-color-label">Koyu Okyanus</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="polar-dark" onclick="selectSidebarTheme('polar-dark', true);">
                    <span class="theme-color-dot" style="background: #0c1a29;"></span>
                    <span class="theme-color-label">Polar Dark</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="warm-dark" onclick="selectSidebarTheme('warm-dark', true);">
                    <span class="theme-color-dot" style="background: #291e12;"></span>
                    <span class="theme-color-label">Warm Dark</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="slate-light" onclick="selectSidebarTheme('slate-light', true);">
                    <span class="theme-color-dot" style="background: #f8fafc; border: 1px solid #cbd5e1;"></span>
                    <span class="theme-color-label">Slate Açık</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar="sade-beyaz" onclick="selectSidebarTheme('sade-beyaz', true);">
                    <span class="theme-color-dot" style="background: #ffffff; border: 1px solid #cbd5e1;"></span>
                    <span class="theme-color-label">Sade Beyaz</span>
                </button>
            </div>

            <!-- 4. Sidebar Aktif Menü Rengi -->
            <div class="theme-customizer-section-title-wrap mt-3">
                <h6 class="theme-customizer-section-title">Sidebar Aktif Menü Rengi</h6>
                <span class="theme-customizer-badge bg-primary-lt text-primary">Aktif Öğe</span>
            </div>
            <div class="theme-color-palette-grid">
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="soft-white" onclick="selectSidebarActiveBg('rgba(255, 255, 255, 0.18)', 'soft-white', '#ffffff', true);">
                    <span class="theme-color-dot" style="background: rgba(255, 255, 255, 0.28); border: 1.5px solid #ffffff;"></span>
                    <span class="theme-color-label">Soft Beyaz (Modern)</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="soft-slate" onclick="selectSidebarActiveBg('rgba(255, 255, 255, 0.12)', 'soft-slate', '#ffffff', true);">
                    <span class="theme-color-dot" style="background: rgba(255, 255, 255, 0.14); border: 1px solid #cbd5e1;"></span>
                    <span class="theme-color-label">Soft Gri</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="primary" onclick="selectSidebarActiveBg('var(--theme-primary, #206bc4)', 'primary', '#ffffff', true);">
                    <span class="theme-color-dot" style="background: var(--theme-primary, #206bc4);"></span>
                    <span class="theme-color-label">Primary (Dolgu)</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="subtle-primary" onclick="selectSidebarActiveBg('var(--theme-primary-light, rgba(32, 107, 196, 0.15))', 'subtle-primary', 'var(--theme-primary, #206bc4)', true);">
                    <span class="theme-color-dot" style="background: var(--theme-primary-light, #e8f0fe); border: 1px solid var(--theme-primary, #206bc4);"></span>
                    <span class="theme-color-label">Soft Vurgu</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="dark-slate" onclick="selectSidebarActiveBg('#1e293b', 'dark-slate', '#ffffff', true);">
                    <span class="theme-color-dot" style="background: #1e293b;"></span>
                    <span class="theme-color-label">Koyu Slate</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="dark-night" onclick="selectSidebarActiveBg('#0f172a', 'dark-night', '#ffffff', true);">
                    <span class="theme-color-dot" style="background: #0f172a;"></span>
                    <span class="theme-color-label">Gece Koyu</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="grafit" onclick="selectSidebarActiveBg('#27272a', 'grafit', '#ffffff', true);">
                    <span class="theme-color-dot" style="background: #27272a;"></span>
                    <span class="theme-color-label">Grafit</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="emerald" onclick="selectSidebarActiveBg('#059669', 'emerald', '#ffffff', true);">
                    <span class="theme-color-dot" style="background: #059669;"></span>
                    <span class="theme-color-label">Zümrüt Yeşil</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="mor" onclick="selectSidebarActiveBg('#7c3aed', 'mor', '#ffffff', true);">
                    <span class="theme-color-dot" style="background: #7c3aed;"></span>
                    <span class="theme-color-label">Mor</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="rose" onclick="selectSidebarActiveBg('#e11d48', 'rose', '#ffffff', true);">
                    <span class="theme-color-dot" style="background: #e11d48;"></span>
                    <span class="theme-color-label">Rose Pembe</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="amber" onclick="selectSidebarActiveBg('#ea580c', 'amber', '#ffffff', true);">
                    <span class="theme-color-dot" style="background: #ea580c;"></span>
                    <span class="theme-color-label">Amber</span>
                </button>
                <button type="button" class="theme-color-swatch-btn" data-sidebar-active="indigo" onclick="selectSidebarActiveBg('#4f46e5', 'indigo', '#ffffff', true);">
                    <span class="theme-color-dot" style="background: #4f46e5;"></span>
                    <span class="theme-color-label">İndigo</span>
                </button>
                <!-- Özel Aktif Menü HEX Renk Seçici -->
                <div style="grid-column: span 2; display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.03); border: 1px dashed #cbd5e1; padding: 6px 12px; border-radius: 8px; margin-top: 2px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="color" id="customSidebarActiveColorPicker" style="width: 26px; height: 26px; border: none; border-radius: 4px; cursor: pointer; padding: 0; background: transparent;" value="#206bc4" oninput="selectSidebarActiveBg(this.value, 'custom', '#ffffff', true);">
                        <label for="customSidebarActiveColorPicker" style="margin: 0; font-size: 11.5px; font-weight: 600; cursor: pointer;">Özel Aktif Menü Rengi (HEX)</label>
                    </div>
                    <span id="customSidebarActiveHexLabel" style="font-size: 11px; font-family: monospace; color: #64748b; font-weight: 600;">PRIMARY</span>
                </div>
            </div>
        </div>

        <!-- ==========================================
             SEKME 3: FONT & İKON (TYPOGRAPHY)
             ========================================== -->
        <div class="theme-customizer-tab-pane" id="tab-typography">
            <!-- 1. Yazı Tipi (Font) Seçimi -->
            <div class="theme-customizer-section-title-wrap">
                <h6 class="theme-customizer-section-title">Yazı Tipi (Font Ailesi)</h6>
                <span class="theme-customizer-badge bg-azure-lt text-azure">Google Fonts</span>
            </div>
            <div class="theme-fonts-grid mb-3">
                <button type="button" class="theme-font-btn" data-font="inter" onclick="selectThemeFont('inter', true);" style="font-family: 'Inter', sans-serif;">
                    <span class="theme-font-name">Inter</span>
                    <span class="theme-font-sample">Modern UI</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="plus-jakarta-sans" onclick="selectThemeFont('plus-jakarta-sans', true);" style="font-family: 'Plus Jakarta Sans', sans-serif;">
                    <span class="theme-font-name">Plus Jakarta</span>
                    <span class="theme-font-sample">Kurumsal & SaaS</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="outfit" onclick="selectThemeFont('outfit', true);" style="font-family: 'Outfit', sans-serif;">
                    <span class="theme-font-name">Outfit</span>
                    <span class="theme-font-sample">Estetik & Yuvarlak</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="poppins" onclick="selectThemeFont('poppins', true);" style="font-family: 'Poppins', sans-serif;">
                    <span class="theme-font-name">Poppins</span>
                    <span class="theme-font-sample">Geometrik & Canlı</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="lexend" onclick="selectThemeFont('lexend', true);" style="font-family: 'Lexend', sans-serif;">
                    <span class="theme-font-name">Lexend</span>
                    <span class="theme-font-sample">Yüksek Okunabilirlik</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="nunito" onclick="selectThemeFont('nunito', true);" style="font-family: 'Nunito', sans-serif;">
                    <span class="theme-font-name">Nunito</span>
                    <span class="theme-font-sample">Dostane & Yumuşak</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="geist" onclick="selectThemeFont('geist', true);" style="font-family: 'Geist', sans-serif;">
                    <span class="theme-font-name">Geist</span>
                    <span class="theme-font-sample">Minimal & Tech</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="montserrat" onclick="selectThemeFont('montserrat', true);" style="font-family: 'Montserrat', sans-serif;">
                    <span class="theme-font-name">Montserrat</span>
                    <span class="theme-font-sample">Prestij & Şık</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="dm-sans" onclick="selectThemeFont('dm-sans', true);" style="font-family: 'DM Sans', sans-serif;">
                    <span class="theme-font-name">DM Sans</span>
                    <span class="theme-font-sample">SaaS & Ultra Temiz</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="manrope" onclick="selectThemeFont('manrope', true);" style="font-family: 'Manrope', sans-serif;">
                    <span class="theme-font-name">Manrope</span>
                    <span class="theme-font-sample">Modern & Pro</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="figtree" onclick="selectThemeFont('figtree', true);" style="font-family: 'Figtree', sans-serif;">
                    <span class="theme-font-name">Figtree</span>
                    <span class="theme-font-sample">Dengeli & Net</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="sora" onclick="selectThemeFont('sora', true);" style="font-family: 'Sora', sans-serif;">
                    <span class="theme-font-name">Sora</span>
                    <span class="theme-font-sample">Fütüristik & Şık</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="urbanist" onclick="selectThemeFont('urbanist', true);" style="font-family: 'Urbanist', sans-serif;">
                    <span class="theme-font-name">Urbanist</span>
                    <span class="theme-font-sample">Zarif Geometri</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="work-sans" onclick="selectThemeFont('work-sans', true);" style="font-family: 'Work Sans', sans-serif;">
                    <span class="theme-font-name">Work Sans</span>
                    <span class="theme-font-sample">İş Arayüzü</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="raleway" onclick="selectThemeFont('raleway', true);" style="font-family: 'Raleway', sans-serif;">
                    <span class="theme-font-name">Raleway</span>
                    <span class="theme-font-sample">Zarif Başlıklar</span>
                </button>
                <button type="button" class="theme-font-btn" data-font="cabin" onclick="selectThemeFont('cabin', true);" style="font-family: 'Cabin', sans-serif;">
                    <span class="theme-font-name">Cabin</span>
                    <span class="theme-font-sample">Hümanist UI</span>
                </button>
            </div>

            <!-- 2. Yazı Tipi Kalınlığı -->
            <div class="theme-customizer-section-title-wrap mt-3">
                <h6 class="theme-customizer-section-title">Yazı Tipi Kalınlığı</h6>
                <span class="theme-customizer-badge bg-teal-lt text-teal">Kalınlık</span>
            </div>
            <div class="theme-weights-grid mb-3">
                <button type="button" class="theme-weight-btn" data-weight="300" onclick="selectThemeWeight('300', true);">
                    <span class="theme-weight-name" style="font-weight: 300;">İnce (300)</span>
                    <span class="theme-weight-sample">Minimal & Hafif</span>
                </button>
                <button type="button" class="theme-weight-btn" data-weight="400" onclick="selectThemeWeight('400', true);">
                    <span class="theme-weight-name" style="font-weight: 400;">Normal (400)</span>
                    <span class="theme-weight-sample">Varsayılan</span>
                </button>
                <button type="button" class="theme-weight-btn" data-weight="500" onclick="selectThemeWeight('500', true);">
                    <span class="theme-weight-name" style="font-weight: 500;">Orta (500)</span>
                    <span class="theme-weight-sample">Belirgin & Net</span>
                </button>
                <button type="button" class="theme-weight-btn" data-weight="600" onclick="selectThemeWeight('600', true);">
                    <span class="theme-weight-name" style="font-weight: 600;">Yarı Kalın (600)</span>
                    <span class="theme-weight-sample">Tok & Güçlü</span>
                </button>
            </div>

            <!-- 3. Yazı Boyutu Ölçeği -->
            <div class="theme-customizer-section-title-wrap mt-3">
                <h6 class="theme-customizer-section-title">Yazı Boyutu Ölçeği</h6>
                <span class="theme-customizer-badge bg-purple-lt text-purple">Ölçek</span>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center" data-scale="90" onclick="selectThemeFontSize('90', true);">
                        <span style="font-size: 11px; font-weight: 600;">%90 Küçük</span>
                    </button>
                </div>
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center" data-scale="100" onclick="selectThemeFontSize('100', true);">
                        <span style="font-size: 12px; font-weight: 600;">%100 Normal</span>
                    </button>
                </div>
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center" data-scale="110" onclick="selectThemeFontSize('110', true);">
                        <span style="font-size: 13px; font-weight: 600;">%110 Büyük</span>
                    </button>
                </div>
            </div>

            <!-- 4. İkon Çizgi Kalınlığı -->
            <div class="theme-customizer-section-title-wrap mt-3">
                <h6 class="theme-customizer-section-title">İkon Çizgi Kalınlığı</h6>
                <span class="theme-customizer-badge bg-cyan-lt text-cyan">Tabler Icons</span>
            </div>
            <div class="row g-2">
                <div class="col-3">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center p-2" data-stroke="1.25" onclick="selectIconStroke('1.25', true);">
                        <i class="ti ti-star" style="font-size: 16px;"></i>
                        <span style="font-size: 11px; margin-left: 2px;">1.25</span>
                    </button>
                </div>
                <div class="col-3">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center p-2" data-stroke="1.5" onclick="selectIconStroke('1.5', true);">
                        <i class="ti ti-star" style="font-size: 16px;"></i>
                        <span style="font-size: 11px; margin-left: 2px;">1.50</span>
                    </button>
                </div>
                <div class="col-3">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center p-2" data-stroke="1.75" onclick="selectIconStroke('1.75', true);">
                        <i class="ti ti-star" style="font-size: 16px;"></i>
                        <span style="font-size: 11px; margin-left: 2px;">1.75</span>
                    </button>
                </div>
                <div class="col-3">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center p-2" data-stroke="2" onclick="selectIconStroke('2', true);">
                        <i class="ti ti-star" style="font-size: 16px;"></i>
                        <span style="font-size: 11px; margin-left: 2px;">2.00</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ==========================================
             SEKME 4: ARAYÜZ (LAYOUT & SHAPE)
             ========================================== -->
        <div class="theme-customizer-tab-pane" id="tab-layout">
            <!-- 1. Köşe Yuvarlaklığı (Border Radius) -->
            <div class="theme-customizer-section-title-wrap">
                <h6 class="theme-customizer-section-title">Köşe Yuvarlaklığı (Radius)</h6>
                <span class="theme-customizer-badge bg-orange-lt text-orange">Şekil</span>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center" data-radius="sharp" onclick="selectThemeRadius('sharp', true);" style="border-radius: 4px;">
                        <span>Keskin (4px)</span>
                    </button>
                </div>
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center" data-radius="default" onclick="selectThemeRadius('default', true);" style="border-radius: 8px;">
                        <span>Standart (8px)</span>
                    </button>
                </div>
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center" data-radius="modern" onclick="selectThemeRadius('modern', true);" style="border-radius: 12px;">
                        <span>Modern (12px)</span>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center" data-radius="rounded" onclick="selectThemeRadius('rounded', true);" style="border-radius: 16px;">
                        <span>Yuvarlak (16px)</span>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center" data-radius="pill" onclick="selectThemeRadius('pill', true);" style="border-radius: 20px;">
                        <span>Pill / Oval (20px)</span>
                    </button>
                </div>
            </div>

            <!-- 2. Tablo Satır Yoğunluğu -->
            <div class="theme-customizer-section-title-wrap mt-3">
                <h6 class="theme-customizer-section-title">Tablo Satır Yoğunluğu</h6>
                <span class="theme-customizer-badge bg-green-lt text-green">DataTable</span>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center flex-column py-2" data-density="compact" onclick="selectThemeDensity('compact', true);">
                        <i class="ti ti-layout-rows mb-1" style="font-size: 16px;"></i>
                        <span style="font-size: 11px;">Kompakt</span>
                    </button>
                </div>
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center flex-column py-2" data-density="normal" onclick="selectThemeDensity('normal', true);">
                        <i class="ti ti-layout-distribute-vertical mb-1" style="font-size: 16px;"></i>
                        <span style="font-size: 11px;">Standart</span>
                    </button>
                </div>
                <div class="col-4">
                    <button type="button" class="theme-color-swatch-btn w-100 justify-content-center flex-column py-2" data-density="relaxed" onclick="selectThemeDensity('relaxed', true);">
                        <i class="ti ti-layout-list mb-1" style="font-size: 16px;"></i>
                        <span style="font-size: 11px;">Ferah</span>
                    </button>
                </div>
            </div>

            <!-- Bilgilendirme Kutusu -->
            <div class="alert alert-info py-2 px-3 mb-0" style="font-size: 11.5px; border-radius: 8px;">
                <i class="ti ti-info-circle me-1"></i>
                Yaptığınız tüm tercihler anında tarayıcınıza kaydedilir ve sonraki girişlerinizde otomatik hatırlanır.
            </div>
        </div>

    </div>

    <!-- Footer / Action Buttons -->
    <div class="theme-customizer-footer">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="resetThemeCustomizer();" title="Varsayılan Ayarlara Dön">
            <i class="ti ti-refresh me-1"></i>
            Sıfırla
        </button>
        <button type="button" class="btn btn-sm btn-primary" onclick="closeThemeCustomizer();">
            <i class="ti ti-check me-1"></i>
            Tamam
        </button>
    </div>
</div>
