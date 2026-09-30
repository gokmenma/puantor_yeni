/**
 * Puantor Centralized DataTable Column Manager
 * 
 * Modül Özellikleri:
 * 1. Drag-to-Hide: Kolon başlığını tablo gövdesine sürükleyip bırakınca o kolon kesin gizlenir.
 * 2. Çift Yönlü Senkronizasyon (Two-way Sync): Sütunlar menüsü ile tablo görünürlüğü %100 senkron çalışır.
 * 3. Column Reorder: Kolon başlıklarını yatay sürükle-bırak ile istenen sıraya alabilme.
 * 4. Column Resize & Text Wrap: Kolon başlıkları çok satırlı kırılır, serbestçe daraltılabilir.
 * 5. Kalıcılık (DB Persistence): Kullanıcı hangi bilgisayardan girerse girsin aynı görünümü görmesi.
 * 6. Yüksek Performans: RequestAnimationFrame ve Debounced API kaydı.
 */
(function ($) {
    'use strict';

    window.PuantorDTManager = window.PuantorDTManager || {};

    var stateCache = {};
    var saveTimers = {};

    // Gelişmiş CSS Stilleri
    var managerStyles = `
    <style id="dt-col-manager-styles">
    /* Başlıkların Serbestçe Satıra Bölünmesi & Resimdeki Gibi Flex Düzeni */
    table.dataTable thead th,
    table.dataTable thead td,
    .data-table thead th {
        position: relative !important;
        white-space: normal !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
        text-wrap: balance !important;
        vertical-align: middle !important;
        padding: 7px 9px !important;
        min-width: 0 !important;
    }
    
    /* Checkbox ve Aksiyon Sütunlarında Sol Boşluğu Normale Döndür */
    table.dataTable thead th:first-child,
    table.dataTable thead th.no-export,
    table.dataTable thead th.actions-column,
    table.dataTable thead th.no-sort,
    .data-table thead th:first-child,
    .data-table thead th.no-export,
    .data-table thead th.actions-column,
    .data-table thead th.no-sort {
        padding: 6px 4px !important;
    }

    .dt-header-content {
        white-space: normal !important;
        word-break: normal !important;
        min-width: 0 !important;
        width: 100% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 6px !important;
    }
    .dt-header-title-wrap {
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        min-width: 0 !important;
        flex: 1 1 auto !important;
    }
    .dt-header-title-wrap span.dt-column-order,
    table.dataTable thead th span.dt-column-order,
    table.dataTable thead td span.dt-column-order,
    .data-table thead th span.dt-column-order {
        position: static !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 14px !important;
        height: 14px !important;
        min-width: 14px !important;
        min-height: 14px !important;
        max-width: 14px !important;
        max-height: 14px !important;
        flex-shrink: 0 !important;
        background-repeat: no-repeat !important;
        background-position: center center !important;
        background-size: 14px 14px !important;
        pointer-events: none !important;
        opacity: 0.55 !important;
        margin: 0 !important;
        padding: 0 !important;
        transform: none !important;
    }
    table.dataTable thead > tr > th.dt-orderable-asc:not(.dt-ordering-asc):not(.dt-ordering-desc) span.dt-column-order,
    table.dataTable thead > tr > th.dt-orderable-desc:not(.dt-ordering-asc):not(.dt-ordering-desc) span.dt-column-order,
    table.dataTable thead > tr > td.dt-orderable-asc:not(.dt-ordering-asc):not(.dt-ordering-desc) span.dt-column-order,
    table.dataTable thead > tr > td.dt-orderable-desc:not(.dt-ordering-asc):not(.dt-ordering-desc) span.dt-column-order,
    table.dataTable thead > tr > th.sorting:not(.sorting_asc):not(.sorting_desc) span.dt-column-order,
    table.dataTable thead > tr > td.sorting:not(.sorting_asc):not(.sorting_desc) span.dt-column-order,
    .data-table thead > tr > th.dt-orderable-asc:not(.dt-ordering-asc):not(.dt-ordering-desc) span.dt-column-order,
    .data-table thead > tr > th.dt-orderable-desc:not(.dt-ordering-asc):not(.dt-ordering-desc) span.dt-column-order {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' width='14' height='14' fill='none' stroke='%2394a3b8' stroke-width='1.75' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 11.5V4.5M5 4.5L2.5 7M5 4.5L7.5 7'/%3E%3Cpath d='M11 4.5v7m0 0l-2.5-2.5m2.5 2.5l2.5-2.5'/%3E%3C/svg%3E") !important;
        opacity: 0.55 !important;
    }
    table.dataTable thead > tr > th.dt-ordering-asc span.dt-column-order,
    table.dataTable thead > tr > th.sorting_asc span.dt-column-order,
    table.dataTable thead > tr > td.dt-ordering-asc span.dt-column-order,
    table.dataTable thead > tr > td.sorting_asc span.dt-column-order,
    .data-table thead > tr > th.dt-ordering-asc span.dt-column-order,
    .data-table thead > tr > th.sorting_asc span.dt-column-order {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' width='14' height='14' fill='none' stroke='%230054a6' stroke-width='2.25' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 13.5V2.5M8 2.5L3.5 7M8 2.5L12.5 7'/%3E%3C/svg%3E") !important;
        opacity: 1 !important;
    }
    table.dataTable thead > tr > th.dt-ordering-desc span.dt-column-order,
    table.dataTable thead > tr > th.sorting_desc span.dt-column-order,
    table.dataTable thead > tr > td.dt-ordering-desc span.dt-column-order,
    table.dataTable thead > tr > td.sorting_desc span.dt-column-order,
    .data-table thead > tr > th.dt-ordering-desc span.dt-column-order,
    .data-table thead > tr > th.sorting_desc span.dt-column-order {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' width='14' height='14' fill='none' stroke='%230054a6' stroke-width='2.25' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 2.5v11m0 0l-4.5-4.5m4.5 4.5l4.5-4.5'/%3E%3C/svg%3E") !important;
        opacity: 1 !important;
    }
    .dt-header-title {
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        text-wrap: wrap !important;
        line-height: 1.25 !important;
        display: inline-block !important;
        min-width: 0 !important;
        flex: 1 1 auto;
        font-size: 11.5px !important;
        font-weight: 700 !important;
        letter-spacing: 0.3px !important;
        padding-left: 0px !important;
    }

    /* DataTable Resizer Tutamaçları */
    .dt-col-resizer {
        position: absolute;
        top: 0;
        right: -3px;
        width: 8px;
        bottom: 0;
        cursor: col-resize !important;
        user-select: none !important;
        z-index: 20;
        transition: background-color 0.15s ease;
    }
    .dt-col-resizer:hover,
    .dt-col-resizer.resizing {
        background-color: #0054a6 !important;
        width: 4px;
        right: -2px;
    }
    .dt-resize-guide {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #0054a6;
        z-index: 9999;
        pointer-events: none;
        display: none;
    }

    /* Drag & Drop to Hide Overlay */
    .dt-hide-dropzone-overlay {
        position: absolute;
        top: 40px;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(254, 242, 242, 0.94);
        border: 2px dashed #dc2626;
        border-radius: 8px;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: #dc2626;
        font-weight: 600;
        font-size: 14px;
        pointer-events: none;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.15s ease, visibility 0.15s ease;
        box-shadow: inset 0 0 24px rgba(220, 38, 38, 0.12);
    }
    [data-bs-theme="dark"] .dt-hide-dropzone-overlay {
        background: rgba(45, 10, 10, 0.95);
        border-color: #f87171;
        color: #f87171;
        box-shadow: inset 0 0 24px rgba(248, 113, 113, 0.2);
    }
    .dt-hide-dropzone-overlay.active {
        opacity: 1;
        visibility: visible;
        pointer-events: all;
    }
    .dt-hide-dropzone-overlay i {
        font-size: 32px;
        animation: dtPulse 1.2s infinite alternate ease-in-out;
    }
    @keyframes dtPulse {
        0% { transform: scale(1); }
        100% { transform: scale(1.15); }
    }

    /* Sürüklenebilir Başlık */
    th.dt-draggable-header {
        cursor: grab;
    }
    th.dt-draggable-header:active {
        cursor: grabbing;
    }

    /* ColReorder Sürükleme Kutusunu (Klon Tablo) Tablo Başlığı Hücresi Olarak Gösterme */
    table.dtcr-cloned,
    table[id].dtcr-cloned,
    table.dtcr-cloned.dataTable,
    table.dtcr-cloned.data-table,
    table#bordroTable.dtcr-cloned,
    table#persons.dtcr-cloned,
    .dtcr-cloned,
    .dtcr-cloned table,
    div.dt-colreorder-drag,
    div.dt-colreorder-drag table,
    div.dt-colreorder-floating,
    div.dt-colreorder-floating table,
    div.dt-colreorder-moving,
    .dt-colreorder-clone {
        position: absolute !important;
        width: auto !important;
        min-width: 0 !important;
        max-width: 260px !important;
        height: auto !important;
        display: table !important;
        table-layout: auto !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        box-sizing: border-box !important;
        background: #f8fafc !important;
        border: 1px solid #94a3b8 !important;
        border-radius: 6px !important;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25) !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: hidden !important;
        opacity: 0.96 !important;
        pointer-events: none !important;
        z-index: 9999999 !important;
        transform: none !important;
    }
    [data-bs-theme="dark"] table.dtcr-cloned,
    [data-bs-theme="dark"] table[id].dtcr-cloned,
    [data-bs-theme="dark"] .dtcr-cloned,
    [data-bs-theme="dark"] div.dt-colreorder-drag,
    [data-bs-theme="dark"] div.dt-colreorder-floating {
        background: #1e293b !important;
        border-color: #64748b !important;
        color: #f1f5f9 !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6) !important;
    }
    
    /* Klonun Gövde Satırlarını Kesin Gizle (Upuzun uzamayı önler) */
    table.dtcr-cloned tbody,
    .dtcr-cloned tbody,
    div.dt-colreorder-floating tbody,
    div.dt-colreorder-drag tbody,
    .dt-colreorder-clone tbody {
        display: none !important;
    }

    table.dtcr-cloned thead,
    .dtcr-cloned thead,
    table.dtcr-cloned tr,
    .dtcr-cloned tr {
        background: transparent !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
        display: table-row !important;
    }
    table.dtcr-cloned th,
    table.dtcr-cloned td,
    .dtcr-cloned th,
    .dtcr-cloned td {
        display: table-cell !important;
        vertical-align: middle !important;
        width: auto !important;
        max-width: 260px !important;
        min-width: 0 !important;
        padding: 7px 12px !important;
        margin: 0 !important;
        white-space: nowrap !important;
        text-overflow: ellipsis !important;
        overflow: hidden !important;
        background: #f8fafc !important;
        border: none !important;
        font-size: 11.5px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        text-transform: uppercase !important;
        letter-spacing: 0.4px !important;
        text-align: inherit !important;
        box-sizing: border-box !important;
    }
    [data-bs-theme="dark"] table.dtcr-cloned th,
    [data-bs-theme="dark"] table.dtcr-cloned td,
    [data-bs-theme="dark"] .dtcr-cloned th,
    [data-bs-theme="dark"] .dtcr-cloned td {
        background: #1e293b !important;
        color: #f1f5f9 !important;
    }
    table.dtcr-cloned .dt-col-filter-btn,
    table.dtcr-cloned .dt-col-resizer,
    .dtcr-cloned .dt-col-filter-btn,
    .dtcr-cloned .dt-col-resizer {
        display: none !important;
    }

    /* Sürükleme esnasında tooltip balonlarını tamamen gizle */
    body.dtcr-dragging .tooltip,
    body.dtcr-dragging [role="tooltip"],
    body.dtcr-dragging .bs-tooltip-auto,
    body.dtcr-dragging .bs-tooltip-top {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
    }

    /* Toast Bildirimi */
    .dt-toast-custom {
        border-radius: 8px !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.12) !important;
        font-size: 13px !important;
    }
    </style>
    `;

    if (!$('#dt-col-manager-styles').length) {
        $('head').append(managerStyles);
    }

    // ColReorder Klon Tablosu Boyut Düzeltici (Zero-Delay MutationObserver)
    if (window.MutationObserver) {
        var colCloneObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node && node.nodeType === 1) {
                        var $n = $(node);
                        if ($n.hasClass('dtcr-cloned') || $n.is('table.dtcr-cloned') || $n.find('.dtcr-cloned, table.dtcr-cloned').length) {
                            var $clones = $n.hasClass('dtcr-cloned') ? $n : $n.find('.dtcr-cloned, table.dtcr-cloned');
                            
                            // Sürükleme anında oluşan tüm açık tooltip'leri yok et
                            $('.tooltip, [role="tooltip"]').remove();

                            $clones.each(function () {
                                $(this).removeAttr('id'); // ID'ye özel width:100% kurallarını ez
                                $(this).removeAttr('title').removeAttr('data-bs-original-title').removeAttr('data-tooltip').removeAttr('aria-describedby');
                                $(this).find('*').removeAttr('title').removeAttr('data-bs-original-title').removeAttr('data-tooltip').removeAttr('aria-describedby');
                                
                                this.style.setProperty('width', 'auto', 'important');
                                this.style.setProperty('max-width', '260px', 'important');
                                this.style.setProperty('height', 'auto', 'important');
                                this.style.setProperty('display', 'table', 'important');
                                
                                $(this).find('tbody').remove();
                                $(this).find('thead, tr').css({ display: 'table-row', width: 'auto', maxWidth: '260px' });
                                $(this).find('th, td').css({ display: 'table-cell', width: 'auto', maxWidth: '260px', whiteSpace: 'nowrap' });
                                $(this).find('.dt-col-filter-btn, .dt-col-resizer, input, button').remove();
                            });
                        }
                    }
                });
            });
        });
        colCloneObserver.observe(document.documentElement, { childList: true, subtree: true });
    }

    // Tıklanan konumu orantısal olarak takip et (en alttan tutulursa altı, ortadan tutulursa ortası imlece yapışır)
    var dragRatio = { x: 0.5, y: 0.5 };
    $(document).on('mousedown.dtcrRatio touchstart.dtcrRatio', 'th, .dt-draggable-header', function (e) {
        var $th = $(this).closest('th');
        if (!$th.length) return;
        var offset = $th.offset();
        var pageX = e.pageX !== undefined ? e.pageX : (e.originalEvent && e.originalEvent.touches && e.originalEvent.touches[0] ? e.originalEvent.touches[0].pageX : 0);
        var pageY = e.pageY !== undefined ? e.pageY : (e.originalEvent && e.originalEvent.touches && e.originalEvent.touches[0] ? e.originalEvent.touches[0].pageY : 0);
        var w = $th.outerWidth();
        var h = $th.outerHeight();
        if (w > 0 && h > 0) {
            dragRatio.x = Math.max(0.05, Math.min(0.95, (pageX - offset.left) / w));
            dragRatio.y = Math.max(0.05, Math.min(0.95, (pageY - offset.top) / h));
        }
    });

    // Taşınan başlığı kullanıcının tuttuğu tam noktaya sabitle
    $(document).on('mousemove.dtcrCursorAlign touchmove.dtcrCursorAlign', function (e) {
        if (!$('body').hasClass('dtcr-dragging')) return;
        var $clone = $('.dtcr-cloned');
        if ($clone.length) {
            var pageX = e.pageX !== undefined ? e.pageX : (e.originalEvent && e.originalEvent.touches && e.originalEvent.touches[0] ? e.originalEvent.touches[0].pageX : 0);
            var pageY = e.pageY !== undefined ? e.pageY : (e.originalEvent && e.originalEvent.touches && e.originalEvent.touches[0] ? e.originalEvent.touches[0].pageY : 0);
            if (pageX && pageY) {
                var w = $clone.outerWidth() || 130;
                var h = $clone.outerHeight() || 34;
                var offsetX = Math.round(w * dragRatio.x);
                var offsetY = Math.round(h * dragRatio.y);
                $clone.css({
                    left: (pageX - offsetX) + 'px',
                    top: (pageY - offsetY) + 'px'
                });
            }
        }
    });

    /**
     * Tablo için benzersiz tableKey üretir
     */
    function getTableKey($table) {
        var explicitKey = $table.attr('data-table-key');
        if (explicitKey) return explicitKey;

        var tableId = $table.attr('id') || 'table';
        var pagePath = window.location.pathname.replace(/[^a-zA-Z0-9_-]/g, '_');
        var pageParam = '';
        try {
            var urlParams = new URLSearchParams(window.location.search);
            pageParam = urlParams.get('p') || urlParams.get('page') || '';
            if (pageParam) {
                pageParam = '_' + pageParam.replace(/[^a-zA-Z0-9_-]/g, '_');
            }
        } catch (e) {}

        return (pagePath + pageParam + '_' + tableId).replace(/^_+|_+$/g, '');
    }

    /**
     * DataTables Orijinal (Data) Sütun Görünürlüğünü Okur (%100 Güvenli)
     */
    function getDtColumnVisibility(dtInstance, colIdx) {
        try {
            var settings = dtInstance.settings()[0];
            if (settings && settings.aoColumns) {
                for (var i = 0; i < settings.aoColumns.length; i++) {
                    var colCfg = settings.aoColumns[i];
                    var origIdx = colCfg._crOriginalIdx !== undefined ? colCfg._crOriginalIdx : i;
                    if (origIdx === colIdx) {
                        return colCfg.bVisible !== false;
                    }
                }
            }
        } catch (e) {}
        try {
            return dtInstance.column(colIdx).visible();
        } catch (e) {
            return true;
        }
    }

    /**
     * Sayfadaki Sütunlar Menüsü Checkbox'larını Senkronize Eder
     */
    function syncColvisMenuCheckboxes($table, dtInstance) {
        var settings = dtInstance.settings()[0];
        if (!settings || !settings.aoColumns) return;
        settings.aoColumns.forEach(function (colConfig, currentIdx) {
            var origIdx = colConfig._crOriginalIdx !== undefined ? colConfig._crOriginalIdx : currentIdx;
            var isVisible = colConfig.bVisible !== false;
            $('input[data-column="' + origIdx + '"], input[data-orig-idx="' + origIdx + '"], input[data-column-idx="' + origIdx + '"], input[id$="ColCheck_' + origIdx + '"], input[id^="colCheck_' + origIdx + '"]').prop('checked', isVisible);
        });
    }

    /**
     * DB'den veya Önceden Yüklenmiş PHP Verisinden tablo ayarlarını senkron okur (Zero-Flicker)
     */
    function loadStateFromDB(tableKey, callback) {
        if (stateCache[tableKey]) {
            if (callback) callback(stateCache[tableKey]);
            return;
        }

        // 1. PHP Preloaded Global Veri (0ms / Zero-Flicker)
        if (window.__PRELOADED_DT_STATES__ && window.__PRELOADED_DT_STATES__[tableKey]) {
            stateCache[tableKey] = window.__PRELOADED_DT_STATES__[tableKey];
            if (callback) callback(stateCache[tableKey]);
            return;
        }

        // 2. localStorage Önbelleği
        try {
            var localData = localStorage.getItem('puantor_dt_state_' + tableKey);
            if (localData) {
                var parsed = JSON.parse(localData);
                if (parsed && typeof parsed === 'object') {
                    stateCache[tableKey] = parsed;
                    if (callback) callback(parsed);
                    return;
                }
            }
        } catch (e) {}

        // 3. Fallback: AJAX İsteği
        $.ajax({
            url: 'api/datatable/state.php',
            type: 'GET',
            data: { table_key: tableKey },
            dataType: 'json',
            success: function (res) {
                if (res && res.status === 'success' && res.data) {
                    stateCache[tableKey] = res.data;
                    try { localStorage.setItem('puantor_dt_state_' + tableKey, JSON.stringify(res.data)); } catch (e) {}
                    if (callback) callback(res.data);
                } else {
                    if (callback) callback(null);
                }
            },
            error: function () {
                if (callback) callback(null);
            }
        });
    }

    /**
     * DB'ye ve localStorage'a tablo ayarlarını kaydeder (Debounced - 600ms)
     */
    function saveStateToDB(tableKey, stateData) {
        stateCache[tableKey] = stateData;
        try { localStorage.setItem('puantor_dt_state_' + tableKey, JSON.stringify(stateData)); } catch (e) {}

        if (saveTimers[tableKey]) {
            clearTimeout(saveTimers[tableKey]);
        }

        saveTimers[tableKey] = setTimeout(function () {
            $.ajax({
                url: 'api/datatable/state.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    table_key: tableKey,
                    state_data: stateData
                }),
                dataType: 'json',
                success: function () {},
                error: function (xhr, err) {
                    console.warn('[PuantorDTManager] State save error:', err);
                }
            });
        }, 600);
    }

    /**
     * Mevcut DataTables durumunu okur ve kaydeder
     */
    function persistCurrentState($table, dtInstance) {
        var tableKey = getTableKey($table);
        var state = {
            visibility: {},
            widths: {},
            order: [],
            pageLength: dtInstance.page.len ? dtInstance.page.len() : 25
        };

        var settings = dtInstance.settings()[0];
        if (settings && settings.aoColumns) {
            settings.aoColumns.forEach(function (colConfig, currentIdx) {
                var origIdx = colConfig._crOriginalIdx !== undefined ? colConfig._crOriginalIdx : currentIdx;
                state.visibility[origIdx] = colConfig.bVisible !== false;
            });
        }

        $table.find('thead tr:first th').each(function () {
            var colIdx = $(this).attr('data-column-index');
            if (colIdx !== undefined) {
                var w = $(this).outerWidth();
                state.widths[colIdx] = Math.round(w);
            }
        });

        if (dtInstance.colReorder && typeof dtInstance.colReorder.order === 'function') {
            state.order = dtInstance.colReorder.order();
        }

        saveStateToDB(tableKey, state);
        syncColvisMenuCheckboxes($table, dtInstance);
    }

    /**
     * Kayıtlı durumu tabloya uygular
     */
    function applySavedState($table, dtInstance, savedState) {
        if (!savedState) return;

        var settings = dtInstance.settings()[0];
        if (!settings || !settings.aoColumns) return;

        // 1. Sıralama (ColReorder)
        if (savedState.order && savedState.order.length && dtInstance.colReorder) {
            try {
                var currentOrder = dtInstance.colReorder.order();
                if (JSON.stringify(currentOrder) !== JSON.stringify(savedState.order)) {
                    dtInstance.colReorder.order(savedState.order, true);
                }
            } catch (e) {
                console.warn('[PuantorDTManager] ColReorder apply error:', e);
            }
        }

        // 2. Görünürlük (Orijinal indeks üzerinden aoColumns[c]'yi bul ve kesin uygula)
        if (savedState.visibility) {
            $.each(savedState.visibility, function (colKey, isVisible) {
                var targetOrigIdx = parseInt(colKey, 10);
                if (isNaN(targetOrigIdx)) return;

                var shouldBeVisible = (isVisible === true || isVisible === 'true' || isVisible === 1);

                for (var c = 0; c < settings.aoColumns.length; c++) {
                    var colCfg = settings.aoColumns[c];
                    var cOrig = colCfg._crOriginalIdx !== undefined ? colCfg._crOriginalIdx : c;
                    if (cOrig === targetOrigIdx) {
                        dtInstance.column(c).visible(shouldBeVisible, false);
                        break;
                    }
                }
            });
        }

        // 3. Genişlikler
        if (savedState.widths) {
            $.each(savedState.widths, function (colIdx, width) {
                var idx = parseInt(colIdx, 10);
                if (!isNaN(idx) && width > 20) {
                    var $th = $table.find('thead tr:first th[data-column-index="' + idx + '"]');
                    if ($th.length) {
                        $th.css({ width: width + 'px', minWidth: '0px' });
                    }
                }
            });
        }

        if (savedState.pageLength && dtInstance.page.len) {
            if (dtInstance.page.len() !== savedState.pageLength) {
                dtInstance.page.len(savedState.pageLength);
            }
        }

        dtInstance.columns.adjust().draw(false);
        syncColvisMenuCheckboxes($table, dtInstance);
    }

    /**
     * Tabloya Kolon Boyutlandırma (Column Resize) Ekler
     */
    function initColumnResizing($table, dtInstance) {
        var $thead = $table.find('thead');
        var $tableContainer = $table.closest('.dt-container, .table-responsive, .card-table, div');

        var $guide = $tableContainer.find('.dt-resize-guide');
        if (!$guide.length) {
            $guide = $('<div class="dt-resize-guide"></div>').appendTo($tableContainer);
        }

        $thead.find('tr:first th').each(function () {
            var $th = $(this);
            var colIdx = dtInstance.column($th).index();
            if (colIdx !== undefined && colIdx !== null) {
                $th.attr('data-column-index', colIdx);
            }

            if ($th.hasClass('no-resize') || $th.find('.dt-col-resizer').length) return;

            var $resizer = $('<div class="dt-col-resizer" title="Genişletmek için sürükleyin, sıfırlamak için çift tıklayın"></div>');
            $th.append($resizer);

            $resizer.on('mousedown', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var startX = e.pageX;
                var startWidth = $th.outerWidth();

                $resizer.addClass('resizing');
                $('body').css('cursor', 'col-resize');

                var containerOffset = $tableContainer.offset();
                var thOffset = $th.offset();

                $guide.css({
                    left: (thOffset.left + startWidth - containerOffset.left) + 'px',
                    top: 0,
                    height: $tableContainer.outerHeight() + 'px',
                    display: 'block'
                });

                var currentWidth = startWidth;

                function onMouseMove(moveEvent) {
                    var delta = moveEvent.pageX - startX;
                    currentWidth = Math.max(30, startWidth + delta);

                    $guide.css('left', (thOffset.left + currentWidth - containerOffset.left) + 'px');
                }

                function onMouseUp() {
                    $(document).off('mousemove', onMouseMove);
                    $(document).off('mouseup', onMouseUp);

                    $resizer.removeClass('resizing');
                    $('body').css('cursor', '');
                    $guide.hide();

                    $th.css({
                        width: currentWidth + 'px',
                        minWidth: '0px'
                    });

                    dtInstance.columns.adjust();
                    persistCurrentState($table, dtInstance);
                }

                $(document).on('mousemove', onMouseMove);
                $(document).on('mouseup', onMouseUp);
            });

            // Çift tıklama: Genişliği otomatik sıfırla
            $resizer.on('dblclick', function (e) {
                e.preventDefault();
                e.stopPropagation();

                $th.css({ width: '', minWidth: '0px' });
                dtInstance.columns.adjust();
                persistCurrentState($table, dtInstance);
            });
        });
    }

    /**
     * Tabloya Drag-to-Hide & Column Reorder Entegrasyonu
     */
    function initDragAndDropFeatures($table, dtInstance) {
        var $thead = $table.find('thead');
        var $tableWrapper = $table.closest('.dt-container, .card, .table-responsive, div');
        var tableKey = getTableKey($table);

        var $dropzone = $tableWrapper.find('.dt-hide-dropzone-overlay');
        if (!$dropzone.length) {
            $dropzone = $(`
                <div class="dt-hide-dropzone-overlay">
                    <i class="ti ti-eye-off"></i>
                    <span>Sütunu gizlemek için buraya bırakın</span>
                </div>
            `).appendTo($tableWrapper.css('position', 'relative'));
        }

        function hideColumnAction(thNode, titleText) {
            var targetCol = dtInstance.column(thNode);
            if (targetCol && targetCol.length) {
                // Sütunu DataTables API ile görünmez yap ve render et
                targetCol.visible(false, true);
                dtInstance.columns.adjust().draw(false);

                syncColvisMenuCheckboxes($table, dtInstance);

                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: `"${titleText || 'Sütun'}" gizlendi`,
                        html: '<span class="text-secondary small">Geri getirmek için Sütunlar menüsünü kullanabilirsiniz.</span>',
                        showConfirmButton: false,
                        timer: 2800,
                        timerProgressBar: true,
                        customClass: { popup: 'dt-toast-custom' }
                    });
                }

                persistCurrentState($table, dtInstance);
            }
        }

        $thead.find('tr:first th').each(function () {
            var $th = $(this);
            var title = $th.find('.dt-header-title').text().trim() || $th.text().trim();

            if ($th.hasClass('no-export') || $th.hasClass('actions-column') || $th.find('input[type="checkbox"]').length > 0) {
                return;
            }

            $th.addClass('dt-draggable-header');

            $th.off('mousedown.dtColHide').on('mousedown.dtColHide', function (e) {
                if ($(e.target).closest('.dt-col-filter-btn, .dt-col-resizer, .select2, input, button').length) {
                    return;
                }

                var thElement = this;
                var thTitle = title;
                var theadOffset = $thead.offset();
                var theadBottom = theadOffset.top + $thead.outerHeight();
                var isBelowHeader = false;

                function onDocMouseMove(moveEvent) {
                    if (moveEvent.pageY > theadBottom + 25) {
                        isBelowHeader = true;
                        $dropzone.addClass('active');
                    } else {
                        isBelowHeader = false;
                        $dropzone.removeClass('active');
                    }
                }

                function onDocMouseUp(upEvent) {
                    $(document).off('mousemove.dtColHide', onDocMouseMove);
                    $(document).off('mouseup.dtColHide', onDocMouseUp);

                    $dropzone.removeClass('active');

                    if (isBelowHeader) {
                        setTimeout(function () {
                            $('.dt-colreorder-drag, .dt-colreorder-moving, .dt-colreorder-floating, .dt-colreorder-insert').remove();
                        }, 10);

                        hideColumnAction(thElement, thTitle);
                    }
                }

                $(document).on('mousemove.dtColHide', onDocMouseMove);
                $(document).on('mouseup.dtColHide', onDocMouseUp);
            });
        });
    }

    /**
     * Merkezi Başlatıcı Fonksiyon
     */
    window.initPuantorDTManager = function ($table, dtInstance) {
        if (!$table || !$table.length || !dtInstance) return;

        var tableKey = getTableKey($table);

        loadStateFromDB(tableKey, function (savedState) {
            if (savedState) {
                applySavedState($table, dtInstance, savedState);
            } else {
                syncColvisMenuCheckboxes($table, dtInstance);
            }

            initColumnResizing($table, dtInstance);
            initDragAndDropFeatures($table, dtInstance);
        });

        // DataTables Column-Visibility Olayında Çift Yönlü Senkronizasyon
        $table.off('column-visibility.dt.manager').on('column-visibility.dt.manager', function (e, settings, colIdx, state) {
            setTimeout(function () {
                initColumnResizing($table, dtInstance);
                initDragAndDropFeatures($table, dtInstance);
                syncColvisMenuCheckboxes($table, dtInstance);
                persistCurrentState($table, dtInstance);
            }, 50);
        });

        // DataTables Column-Reorder Olayı
        $table.off('column-reorder.dt.manager').on('column-reorder.dt.manager', function () {
            setTimeout(function () {
                initColumnResizing($table, dtInstance);
                initDragAndDropFeatures($table, dtInstance);
                syncColvisMenuCheckboxes($table, dtInstance);
                persistCurrentState($table, dtInstance);
            }, 50);
        });
    };

    // Sayfadaki Sütun Menüsü Checkbox Değişim Dinleyicisi (Evrensel, Tekil ve Kesin Çalışan Handler)
    $(document).off('change.dtColGlobalTrigger').on('change.dtColGlobalTrigger', '.bordro-col-trigger, .persons-col-trigger, input.dt-colvis-trigger', function (e) {
        var $chk = $(this);
        var origIdx = parseInt($chk.data('column') !== undefined ? $chk.data('column') : ($chk.data('orig-idx') !== undefined ? $chk.data('orig-idx') : $chk.data('column-idx')), 10);
        var isChecked = this.checked;

        if (isNaN(origIdx)) return;

        var $menu = $chk.closest('.dropdown-menu');
        var $activeTable = null;
        if ($menu.attr('id') === 'bordroColvisMenu') {
            $activeTable = $('#bordroTable');
        } else if ($menu.attr('id') === 'personsColvisMenu') {
            $activeTable = $('#persons');
        } else {
            $activeTable = $chk.closest('.card, .page-wrapper, body').find('table.dataTable:visible:first');
            if (!$activeTable.length) $activeTable = $('table.dataTable:first');
        }

        if (!$activeTable || !$activeTable.length) return;

        var dt = $activeTable.DataTable();
        if (!dt) return;

        var settings = dt.settings()[0];
        if (!settings || !settings.aoColumns) return;

        // Orijinal indekse göre aoColumns[c]'yi bul ve gizle/göster
        for (var c = 0; c < settings.aoColumns.length; c++) {
            var colCfg = settings.aoColumns[c];
            var cOrig = colCfg._crOriginalIdx !== undefined ? colCfg._crOriginalIdx : c;
            if (cOrig === origIdx) {
                dt.column(c).visible(isChecked, true);
                break;
            }
        }

        dt.columns.adjust().draw(false);
        persistCurrentState($activeTable, dt);
    });

    // Global reset fonksiyonu
    window.resetPuantorDTState = function ($table, dtInstance, callback) {
        var tableKey = getTableKey($table);
        $.ajax({
            url: 'api/datatable/state.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'reset',
                table_key: tableKey
            }),
            dataType: 'json',
            success: function () {
                delete stateCache[tableKey];
                try { localStorage.removeItem('puantor_dt_state_' + tableKey); } catch (e) {}
                if (dtInstance.colReorder && typeof dtInstance.colReorder.reset === 'function') {
                    dtInstance.colReorder.reset();
                }
                dtInstance.columns().visible(true, true);
                $table.find('thead th').css({ width: '', minWidth: '0px' });
                dtInstance.columns.adjust().draw(false);
                syncColvisMenuCheckboxes($table, dtInstance);

                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Tablo görünümü varsayılana sıfırlandı.',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
                if (callback) callback();
            }
        });
    };

})(jQuery);
