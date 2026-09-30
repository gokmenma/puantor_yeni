/**
 * Puantor Centralized DataTable Column Header Filter Popover Plugin
 * Tabler SaaS / Aydınoğulları Excel-style column popover filter standard.
 * Features: Type detection (Date, Number, Text), Multi-Rule support (+ Kural Ekle, VE/VEYA logic), Flatpickr datepicker, and Server-Side/Client-Side engine.
 */
(function ($) {
    'use strict';

    var $popover = null;
    var activeColumn = null;
    var activeTable = null;
    var activeFilterBtn = null;
    var activeTableId = null;
    var activeColIdx = null;
    var activeColType = 'text';
    var activeColTitle = '';

    // tableId_colIdx -> { type: 'text'|'number'|'date', logic: 'and'|'or', rules: [ { operator, value } ], title, colIdx }
    var filterStates = {};
    var tableInstances = {};

    var OPERATOR_CONFIG = {
        date: [
            { val: 'equals', text: 'Eşittir (=)' },
            { val: 'after', text: 'Sonra (>)' },
            { val: 'before', text: 'Önce (<)' },
            { val: 'gte', text: 'Büyük Eşit (>=)' },
            { val: 'lte', text: 'Küçük Eşit (<=)' },
            { val: 'empty', text: 'Boş Olanlar' },
            { val: 'not_empty', text: 'Dolu Olanlar' }
        ],
        number: [
            { val: 'equals', text: 'Eşittir (=)' },
            { val: 'gt', text: 'Büyüktür (>)' },
            { val: 'lt', text: 'Küçüktür (<)' },
            { val: 'gte', text: 'Büyük Eşit (>=)' },
            { val: 'lte', text: 'Küçük Eşit (<=)' },
            { val: 'contains', text: 'İçerir' },
            { val: 'empty', text: 'Boş Olanlar' },
            { val: 'not_empty', text: 'Dolu Olanlar' }
        ],
        text: [
            { val: 'contains', text: 'İçerir' },
            { val: 'equals', text: 'Eşittir (=)' },
            { val: 'starts', text: 'İle Başlar' },
            { val: 'ends', text: 'İle Biter' },
            { val: 'not_contains', text: 'İçermez' },
            { val: 'empty', text: 'Boş Olanlar' },
            { val: 'not_empty', text: 'Dolu Olanlar' }
        ]
    };

    var OPERATOR_LABELS = {
        'contains': 'İçerir',
        'equals': 'Eşittir (=)',
        'after': 'Sonra (>)',
        'before': 'Önce (<)',
        'gt': 'Büyüktür (>)',
        'lt': 'Küçüktür (<)',
        'gte': 'Büyük Eşit (>=)',
        'lte': 'Küçük Eşit (<=)',
        'starts': 'İle Başlar',
        'ends': 'İle Biter',
        'not_contains': 'İçermez',
        'empty': 'Boş Olanlar',
        'not_empty': 'Dolu Olanlar'
    };

    var cssStyles = `
    <style id="dt-col-filter-styles">
    /* DataTables Başlık Hücresi ve Sıralama İkonu Düzeni (Resimdeki Gibi Flex) */
    table.dataTable thead > tr > th,
    .data-table thead > tr > th {
        position: relative !important;
        padding: 6px 8px !important;
        vertical-align: middle !important;
        cursor: pointer !important;
        user-select: none !important;
    }
    table.dataTable thead > tr > th:first-child,
    .data-table thead > tr > th:first-child {
        padding: 6px 4px !important;
    }

    .dt-header-content {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 6px !important;
        width: 100% !important;
        min-width: 0 !important;
    }
    .dt-header-title-wrap {
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        min-width: 0 !important;
        flex: 1 1 auto !important;
    }

    /* Sıralama İkonu (Resimdeki gibi sol tarafta doğal flex elemanı) */
    .dt-header-title-wrap span.dt-column-order,
    table.dataTable thead > tr > th span.dt-column-order,
    table.dataTable thead > tr > td span.dt-column-order,
    .data-table thead > tr > th span.dt-column-order {
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
        opacity: 0.65;
        margin: 0 !important;
        padding: 0 !important;
        transform: none !important;
    }
    table.dataTable thead > tr > th span.dt-column-order:before,
    table.dataTable thead > tr > th span.dt-column-order:after,
    table.dataTable thead > tr > td span.dt-column-order:before,
    table.dataTable thead > tr > td span.dt-column-order:after {
        display: none !important;
        content: "" !important;
        opacity: 0 !important;
    }
    /* 1. Boşta / Sıralanabilir (Default: Nötr Çift Ok ⇅) */
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
    /* 2. Artan Sıralama (Ascending / ASC: Yalnızca Yukarı Ok ↑) */
    table.dataTable thead > tr > th.dt-ordering-asc span.dt-column-order,
    table.dataTable thead > tr > th.sorting_asc span.dt-column-order,
    table.dataTable thead > tr > td.dt-ordering-asc span.dt-column-order,
    table.dataTable thead > tr > td.sorting_asc span.dt-column-order,
    .data-table thead > tr > th.dt-ordering-asc span.dt-column-order,
    .data-table thead > tr > th.sorting_asc span.dt-column-order {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' width='14' height='14' fill='none' stroke='%230054a6' stroke-width='2.25' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 13.5V2.5M8 2.5L3.5 7M8 2.5L12.5 7'/%3E%3C/svg%3E") !important;
        opacity: 1 !important;
    }
    /* 3. Azalan Sıralama (Descending / DESC: Yalnızca Aşağı Ok ↓) */
    table.dataTable thead > tr > th.dt-ordering-desc span.dt-column-order,
    table.dataTable thead > tr > th.sorting_desc span.dt-column-order,
    table.dataTable thead > tr > td.dt-ordering-desc span.dt-column-order,
    table.dataTable thead > tr > td.sorting_desc span.dt-column-order,
    .data-table thead > tr > th.dt-ordering-desc span.dt-column-order,
    .data-table thead > tr > th.sorting_desc span.dt-column-order {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' width='14' height='14' fill='none' stroke='%230054a6' stroke-width='2.25' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 2.5v11m0 0l-4.5-4.5m4.5 4.5l4.5-4.5'/%3E%3C/svg%3E") !important;
        opacity: 1 !important;
    }
    [data-bs-theme="dark"] table.dataTable thead > tr > th.dt-ordering-asc span.dt-column-order,
    [data-bs-theme="dark"] table.dataTable thead > tr > th.sorting_asc span.dt-column-order,
    [data-bs-theme="dark"] .data-table thead > tr > th.dt-ordering-asc span.dt-column-order,
    [data-bs-theme="dark"] .data-table thead > tr > th.sorting_asc span.dt-column-order {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' width='14' height='14' fill='none' stroke='%2338bdf8' stroke-width='2.25' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 13.5V2.5M8 2.5L3.5 7M8 2.5L12.5 7'/%3E%3C/svg%3E") !important;
        opacity: 1 !important;
    }
    [data-bs-theme="dark"] table.dataTable thead > tr > th.dt-ordering-desc span.dt-column-order,
    [data-bs-theme="dark"] table.dataTable thead > tr > th.sorting_desc span.dt-column-order,
    [data-bs-theme="dark"] .data-table thead > tr > th.dt-ordering-desc span.dt-column-order,
    [data-bs-theme="dark"] .data-table thead > tr > th.sorting_desc span.dt-column-order {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' width='14' height='14' fill='none' stroke='%2338bdf8' stroke-width='2.25' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 2.5v11m0 0l-4.5-4.5m4.5 4.5l4.5-4.5'/%3E%3C/svg%3E") !important;
        opacity: 1 !important;
    }
    .dt-col-filter-btn {
        width: 24px !important;
        height: 24px !important;
        min-width: 24px !important;
        min-height: 24px !important;
        max-width: 24px !important;
        max-height: 24px !important;
        padding: 0 !important;
        margin: 0 !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        background: #ffffff !important;
        color: #64748b !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        line-height: 1 !important;
        box-shadow: none !important;
        cursor: pointer !important;
        opacity: 0.85;
        transition: all 0.15s ease-in-out;
    }
    .dt-col-filter-btn i,
    .dt-col-filter-btn .ti {
        font-size: 13px !important;
        line-height: 1 !important;
    }
    .dt-col-filter-btn:hover {
        border-color: #206bc4 !important;
        color: #206bc4 !important;
        background: #f1f5f9 !important;
        opacity: 1;
    }
    .dt-col-filter-btn.active {
        background: #e0f2fe !important;
        border-color: #38bdf8 !important;
        color: #0284c7 !important;
        opacity: 1;
    }
    #dt-col-filter-popover {
        width: 290px !important;
        min-width: 290px !important;
        max-width: 330px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        border-radius: 10px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        font-family: inherit !important;
        z-index: 10500 !important;
    }
    #dt-col-filter-popover .dt-rules-container {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 260px;
        overflow-y: auto;
        padding-right: 2px;
    }
    #dt-col-filter-popover .dt-rule-row {
        display: flex;
        flex-direction: column;
        gap: 6px;
        position: relative;
        padding-bottom: 8px;
        border-bottom: 1px dashed #e2e8f0;
    }
    #dt-col-filter-popover .dt-rule-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    #dt-col-filter-popover .dt-rule-row-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
    }
    #dt-col-filter-popover .dt-rule-remove {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 13px;
        padding: 2px 4px;
        border-radius: 4px;
        line-height: 1;
        transition: background 0.15s;
    }
    #dt-col-filter-popover .dt-rule-remove:hover {
        background: #fee2e2;
    }
    #dt-col-filter-popover .dt-filter-condition-wrap {
        width: 100% !important;
    }
    #dt-col-filter-popover .dt-filter-input-wrap {
        width: 100% !important;
        position: relative;
    }
    #dt-col-filter-popover .dt-filter-calendar-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
        font-size: 13px;
    }
    #dt-col-filter-popover .dt-filter-val {
        height: 34px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        background-color: #ffffff !important;
        padding: 6px 10px !important;
        font-size: 12.5px !important;
        width: 100% !important;
        box-sizing: border-box !important;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    #dt-col-filter-popover .dt-filter-val:focus {
        border-color: #206bc4 !important;
        box-shadow: 0 0 0 2px rgba(32, 107, 196, 0.15) !important;
        outline: none;
    }
    #dt-col-filter-popover .dt-date-input {
        padding-right: 30px !important;
    }
    #dt-col-filter-popover .select2-container {
        width: 100% !important;
        display: block !important;
    }
    #dt-col-filter-popover .select2-container--default .select2-selection--single {
        height: 34px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        background-color: #ffffff !important;
        padding: 0 10px !important;
        display: flex !important;
        align-items: center !important;
        box-shadow: none !important;
        outline: none !important;
        width: 100% !important;
        box-sizing: border-box !important;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    }
    #dt-col-filter-popover .select2-container--default.select2-container--open .select2-selection--single,
    #dt-col-filter-popover .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #206bc4 !important;
        box-shadow: 0 0 0 2px rgba(32, 107, 196, 0.15) !important;
    }
    #dt-col-filter-popover .select2-container--default .select2-selection--single .select2-selection__rendered {
        font-size: 12.5px !important;
        color: #334155 !important;
        font-weight: 500 !important;
        line-height: 32px !important;
        padding-left: 0 !important;
        padding-right: 20px !important;
    }
    #dt-col-filter-popover .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px !important;
        right: 8px !important;
        top: 0 !important;
    }
    .dt-col-filter-select2-dropdown {
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        z-index: 10600 !important;
        background: #ffffff !important;
        padding: 4px 0 !important;
        font-family: inherit !important;
    }
    .dt-col-filter-select2-dropdown .select2-results__option {
        font-size: 12.5px !important;
        padding: 6px 12px !important;
        margin: 1px 4px !important;
        border-radius: 4px !important;
        color: #334155 !important;
        cursor: pointer !important;
    }
    .dt-col-filter-select2-dropdown .select2-results__option--highlighted[aria-selected] {
        background-color: #206bc4 !important;
        color: #ffffff !important;
    }
    .dt-col-filter-select2-dropdown .select2-results__option[aria-selected="true"] {
        background-color: #e0f2fe !important;
        color: #0284c7 !important;
        font-weight: 600 !important;
    }
    .dt-logic-wrap {
        margin: 8px 0 6px 0;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 4px 8px;
    }
    .dt-filter-add-rule-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: #206bc4 !important;
        text-decoration: none !important;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 4px;
        transition: color 0.15s;
    }
    .dt-filter-add-rule-btn:hover {
        color: #1a569d !important;
        text-decoration: underline !important;
    }

    /* Active Filters Bar (Aydınoğulları Standard) */
    .dt-active-filters-bar {
        display: none !important;
        background: #f8fafc;
        width: calc(100% - 16px);
        min-height: 44px;
        margin: 0 8px 8px;
        padding: 8px 14px;
        border: 1px solid #dbe3ec;
        border-radius: 8px;
        font-size: 12px;
        box-sizing: border-box;
    }
    .dt-active-filters-bar.has-active-filters {
        display: flex !important;
    }
    .dt-filter-chip {
        background: #ffffff !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 20px !important;
        padding: 3px 10px !important;
        font-size: 12px !important;
        font-weight: normal !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        transition: all 0.15s ease-in-out;
    }
    .dt-filter-chip strong {
        color: #0f172a;
        font-weight: 600;
    }
    .dt-filter-chip .dt-chip-close {
        color: #94a3b8;
        cursor: pointer;
        font-size: 14px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        width: 14px;
        height: 14px;
        transition: color 0.15s, background-color 0.15s;
    }
    .dt-filter-chip .dt-chip-close:hover {
        color: #e03131;
        background-color: #ffe3e3;
    }
    .dt-clear-all-filters-btn {
        color: #e03131 !important;
        text-decoration: none !important;
        font-weight: 600 !important;
        font-size: 12px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        cursor: pointer !important;
    }
    .dt-clear-all-filters-btn:hover {
        color: #c92a2a !important;
        text-decoration: underline !important;
    }

    /* Dark Mode Overrides */
    [data-bs-theme="dark"] #dt-col-filter-popover {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    [data-bs-theme="dark"] #dt-col-filter-popover .dt-col-filter-header {
        border-color: #334155 !important;
    }
    [data-bs-theme="dark"] #dt-col-filter-popover .dt-col-filter-title {
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #dt-col-filter-popover .dt-col-filter-footer {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    [data-bs-theme="dark"] #dt-col-filter-popover .dt-filter-val {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #dt-col-filter-popover .dt-rule-row {
        border-bottom-color: #334155 !important;
    }
    [data-bs-theme="dark"] #dt-col-filter-popover .dt-logic-wrap {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    [data-bs-theme="dark"] .dt-col-filter-select2-dropdown {
        background: #1e293b !important;
        border-color: #334155 !important;
    }
    [data-bs-theme="dark"] .dt-col-filter-select2-dropdown .select2-results__option {
        color: #e2e8f0 !important;
    }
    [data-bs-theme="dark"] .dt-active-filters-bar {
        background: #0f172a;
        border-color: #334155;
    }
    [data-bs-theme="dark"] .dt-filter-chip {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        border-color: #334155 !important;
    }
    [data-bs-theme="dark"] .dt-filter-chip strong {
        color: #f8fafc;
    }
    </style>
    `;

    if (!$('#dt-col-filter-styles').length) {
        $('head').append(cssStyles);
    }

    function toTrLower(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/İ/g, 'i')
            .replace(/I/g, 'ı')
            .replace(/Ğ/g, 'ğ')
            .replace(/Ü/g, 'ü')
            .replace(/Ş/g, 'ş')
            .replace(/Ö/g, 'ö')
            .replace(/Ç/g, 'ç')
            .toLowerCase()
            .trim();
    }

    function detectColumnType(th, title) {
        if (th && th.dataset && th.dataset.filterType) {
            return th.dataset.filterType;
        }
        var t = toTrLower(title);

        if (t.indexOf('tarih') !== -1 || t.indexOf('date') !== -1 || t.indexOf('bitis') !== -1 || t.indexOf('bitiş') !== -1 ||
            t.indexOf('baslangic') !== -1 || t.indexOf('başlangıç') !== -1 || t.indexOf('vade') !== -1 ||
            t.indexOf('zaman') !== -1 || t.indexOf('saat') !== -1 || t.indexOf('giriş') !== -1 || t.indexOf('giris') !== -1 ||
            t.indexOf('çıkış') !== -1 || t.indexOf('cikis') !== -1 || t.indexOf('dogum') !== -1 || t.indexOf('doğum') !== -1 ||
            t.indexOf('donem') !== -1 || t.indexOf('dönem') !== -1) {
            return 'date';
        }

        if (t.indexOf('tutar') !== -1 || t.indexOf('fiyat') !== -1 || t.indexOf('ucret') !== -1 || t.indexOf('ücret') !== -1 ||
            t.indexOf('adet') !== -1 || t.indexOf('miktar') !== -1 || t.indexOf('oran') !== -1 || t.indexOf('kdv') !== -1 ||
            t.indexOf('iskonto') !== -1 || t.indexOf('toplam') !== -1 || t.indexOf('bakiye') !== -1 || t.indexOf('maas') !== -1 ||
            t.indexOf('maaş') !== -1 || t.indexOf('kesinti') !== -1 || t.indexOf('net') !== -1 || t.indexOf('brut') !== -1 ||
            t.indexOf('brüt') !== -1 || t.indexOf('gunluk') !== -1 || t.indexOf('günlük') !== -1 || t.indexOf('saatlik') !== -1 ||
            t.indexOf('yevmiye') !== -1 || t.indexOf('avans') !== -1 || t.indexOf('mesai') !== -1 || t.indexOf('sira') !== -1 ||
            t.indexOf('sıra') !== -1 || t === '#' || t === 'id' || t.indexOf('gun') !== -1 || t.indexOf('gün') !== -1) {
            return 'number';
        }

        return 'text';
    }

    function extractCellTextFromRaw(raw) {
        if (raw === null || raw === undefined) return '';
        var str = String(raw).trim();
        if (!str) return '';

        if (str.indexOf('<') !== -1) {
            var temp = document.createElement('div');
            temp.innerHTML = str;
            $(temp).find('script, style, button, i, svg, .avatar, .user-mini-avatar').remove();
            str = temp.textContent || '';
        }
        return str.replace(/\s+/g, ' ').trim();
    }

    function parseNum(val) {
        if (val === null || val === undefined) return NaN;
        var str = String(val).replace(/<[^>]*>/g, '').trim();
        if (str === '') return NaN;
        str = str.replace(/[₺$€\s]/g, '');
        if (str.indexOf('.') !== -1 && str.indexOf(',') !== -1) {
            if (str.lastIndexOf('.') < str.lastIndexOf(',')) {
                str = str.replace(/\./g, '').replace(',', '.');
            } else {
                str = str.replace(/,/g, '');
            }
        } else if (str.indexOf(',') !== -1) {
            str = str.replace(',', '.');
        }
        str = str.replace(/[^0-9.-]/g, '');
        return parseFloat(str);
    }

    function parseDate(val) {
        if (!val) return NaN;
        var str = String(val).replace(/<[^>]*>/g, '').trim();
        if (!str) return NaN;

        var datePart = str.split(' ')[0];
        var parts = [];
        if (datePart.indexOf('.') !== -1) {
            parts = datePart.split('.');
        } else if (datePart.indexOf('-') !== -1) {
            parts = datePart.split('-');
        } else if (datePart.indexOf('/') !== -1) {
            parts = datePart.split('/');
        }

        if (parts.length === 3) {
            if (parts[0].length === 4) {
                // yyyy-mm-dd
                return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10)).getTime();
            }
            // dd.mm.yyyy
            return new Date(parseInt(parts[2], 10), parseInt(parts[1], 10) - 1, parseInt(parts[0], 10)).getTime();
        }

        var parsed = Date.parse(str);
        return isNaN(parsed) ? NaN : parsed;
    }

    function buildRuleRowHtml(type, isAdditional, ruleData) {
        var ops = OPERATOR_CONFIG[type] || OPERATOR_CONFIG.text;
        var selOp = ruleData ? ruleData.operator : ops[0].val;
        var val = ruleData ? (ruleData.value || '') : '';
        var isHidden = (selOp === 'empty' || selOp === 'not_empty') ? 'style="display:none;"' : '';

        var selectOptionsHtml = '';
        ops.forEach(function (op) {
            var isSel = op.val === selOp ? 'selected' : '';
            selectOptionsHtml += `<option value="${op.val}" ${isSel}>${op.text}</option>`;
        });

        var inputHtml = '';
        if (type === 'date') {
            inputHtml = `
            <div class="dt-filter-input-wrap" ${isHidden}>
                <input type="text" class="form-control form-control-sm dt-filter-val dt-date-input" placeholder="Tarih seçin..." autocomplete="off" value="${$('<div>').text(val).html()}" />
                <i class="ti ti-calendar dt-filter-calendar-icon"></i>
            </div>
            `;
        } else if (type === 'number') {
            inputHtml = `
            <div class="dt-filter-input-wrap" ${isHidden}>
                <input type="text" inputmode="decimal" class="form-control form-control-sm dt-filter-val" placeholder="Sayısal değer girin..." autocomplete="off" value="${$('<div>').text(val).html()}" />
            </div>
            `;
        } else {
            inputHtml = `
            <div class="dt-filter-input-wrap" ${isHidden}>
                <input type="text" class="form-control form-control-sm dt-filter-val" placeholder="Değer girin..." autocomplete="off" value="${$('<div>').text(val).html()}" />
            </div>
            `;
        }

        return `
        <div class="dt-rule-row">
            <div class="dt-rule-row-header">
                <div class="dt-filter-condition-wrap">
                    <select class="form-select form-select-sm dt-filter-condition" style="width: 100%;">
                        ${selectOptionsHtml}
                    </select>
                </div>
                ${isAdditional ? `
                    <button type="button" class="dt-rule-remove" title="Kuralı Sil">
                        <i class="ti ti-trash"></i>
                    </button>
                ` : ''}
            </div>
            ${inputHtml}
        </div>
        `;
    }

    function initRowControls($row, type) {
        // Select2 for condition select
        var $select = $row.find('.dt-filter-condition');
        if ($.fn.select2) {
            $select.select2({
                width: '100%',
                minimumResultsForSearch: -1,
                dropdownCssClass: 'dt-col-filter-select2-dropdown',
                dropdownParent: $popover
            });
        }

        $select.on('change', function () {
            var cond = $(this).val();
            var $inputWrap = $row.find('.dt-filter-input-wrap');
            if (cond === 'empty' || cond === 'not_empty') {
                $inputWrap.slideUp(150);
            } else {
                $inputWrap.slideDown(150);
            }
        });

        // Flatpickr for date inputs
        if (type === 'date' && window.flatpickr) {
            $row.find('input.dt-date-input').each(function () {
                var el = this;
                if (!el._flatpickr) {
                    flatpickr(el, {
                        dateFormat: 'd.m.Y',
                        allowInput: true,
                        locale: (typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.tr) ? flatpickr.l10ns.tr : 'tr'
                    });
                }
            });
        }
    }

    function updateLogicVisibility() {
        if (!$popover) return;
        var ruleCount = $popover.find('.dt-rule-row').length;
        var $logicWrap = $popover.find('.dt-logic-wrap');
        if (ruleCount > 1) {
            $logicWrap.slideDown(150);
        } else {
            $logicWrap.slideUp(150);
        }
    }

    function getPopover() {
        if ($popover && $popover.length) return $popover;

        var html = `
        <div id="dt-col-filter-popover" class="dt-col-filter-popover shadow-lg border" style="display: none; position: absolute; z-index: 10500;">
            <div class="dt-col-filter-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom" style="border-color: #f1f5f9 !important;">
                <span class="dt-col-filter-title fw-bold text-dark font-12" style="font-size: 12.5px; letter-spacing: 0.3px; text-transform: uppercase;">FİLTRE</span>
                <button type="button" class="btn-close dt-col-filter-close" aria-label="Kapat" style="font-size: 9px; cursor: pointer;"></button>
            </div>
            <div class="dt-col-filter-body p-3">
                <div class="dt-rules-container"></div>
                <div class="dt-logic-wrap" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted" style="font-size: 11px; font-weight: 600;">Kuralları Birleştir:</span>
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input dt-logic-radio" type="radio" name="dt_filter_logic" id="dt_logic_and" value="and" checked>
                                <label class="form-check-label" style="font-size: 11px; cursor: pointer;" for="dt_logic_and">VE</label>
                            </div>
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input dt-logic-radio" type="radio" name="dt_filter_logic" id="dt_logic_or" value="or">
                                <label class="form-check-label" style="font-size: 11px; cursor: pointer;" for="dt_logic_or">VEYA</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-start">
                    <a href="javascript:void(0);" class="dt-filter-add-rule-btn">
                        <i class="ti ti-plus"></i> Kural Ekle
                    </a>
                </div>
            </div>
            <div class="dt-col-filter-footer d-flex align-items-center justify-content-between px-3 py-2 border-top" style="background: #f8fafc; border-color: #f1f5f9 !important; border-bottom-left-radius: 9px; border-bottom-right-radius: 9px;">
                <button type="button" class="btn btn-sm btn-outline-secondary dt-filter-clear-btn" style="height: 30px; font-size: 12px; padding: 2px 12px; border-radius: 6px;">Temizle</button>
                <button type="button" class="btn btn-sm btn-dark dt-filter-apply-btn" style="height: 30px; font-size: 12px; padding: 2px 14px; border-radius: 6px; background-color: #1e293b;">Uygula</button>
            </div>
        </div>
        `;

        $popover = $(html).appendTo('body');

        // Add Rule Click
        $popover.find('.dt-filter-add-rule-btn').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $container = $popover.find('.dt-rules-container');
            var newRowHtml = buildRuleRowHtml(activeColType, true, null);
            var $newRow = $(newRowHtml).appendTo($container);
            initRowControls($newRow, activeColType);
            updateLogicVisibility();
            setTimeout(function () {
                $newRow.find('.dt-filter-val').focus();
            }, 50);
        });

        // Remove Rule Click (Delegated)
        $popover.on('click', '.dt-rule-remove', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $row = $(this).closest('.dt-rule-row');
            if ($.fn.select2) {
                try {
                    $row.find('.dt-filter-condition').select2('destroy');
                } catch (err) {}
            }
            $row.remove();
            updateLogicVisibility();
        });

        // Close Button
        $popover.find('.dt-col-filter-close').on('click', function (e) {
            e.stopPropagation();
            hidePopover();
        });

        // Clear Button inside Popover
        $popover.find('.dt-filter-clear-btn').on('click', function (e) {
            e.stopPropagation();
            if (!activeColumn || !activeTable) return;
            var tableId = activeTableId;
            var colIdx = activeColIdx;
            var stateKey = tableId + '_' + colIdx;

            delete filterStates[stateKey];
            activeColumn.search('').draw();

            if (activeFilterBtn) {
                activeFilterBtn.removeClass('active text-primary bg-primary-lt').addClass('text-muted');
            }
            updateActiveFiltersBar(tableId, activeTable);
            hidePopover();
        });

        // Apply Button
        $popover.find('.dt-filter-apply-btn').on('click', function (e) {
            e.stopPropagation();
            applyFilter();
        });

        // Enter Key
        $popover.on('keydown', '.dt-filter-val', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilter();
            }
        });

        // Stop propagation inside popover
        $popover.on('click', function (e) {
            e.stopPropagation();
        });

        // Close on click outside
        $(document).on('click', function (e) {
            if ($popover && $popover.is(':visible')) {
                if (!$(e.target).closest('#dt-col-filter-popover, .dt-col-filter-btn, .select2-container, .flatpickr-calendar').length) {
                    hidePopover();
                }
            }
        });

        // Close on escape key
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $popover && $popover.is(':visible')) {
                hidePopover();
            }
        });

        // Close on resize
        $(window).on('resize', function () {
            if ($popover && $popover.is(':visible')) {
                hidePopover();
            }
        });

        return $popover;
    }

    function hidePopover() {
        if ($popover) {
            if ($.fn.select2) {
                try {
                    $popover.find('.dt-filter-condition').select2('close');
                } catch (e) {}
            }
            $popover.hide();
        }
        activeColumn = null;
        activeTable = null;
        activeFilterBtn = null;
        activeTableId = null;
        activeColIdx = null;
    }

    function evaluateRule(cellRaw, rule, type) {
        var op = rule.operator;
        var cellClean = extractCellTextFromRaw(cellRaw);
        var cellText = toTrLower(cellClean);
        var isBlank = cellText === '' || cellText === '-' || cellText === 'null';

        if (op === 'empty') return isBlank;
        if (op === 'not_empty') return !isBlank;

        if (type === 'number') {
            var cellNum = parseNum(cellRaw);
            var targetNum = parseNum(rule.value);
            var targetValStr = String(rule.value || '').trim();

            if (op === 'contains') {
                var cClean = String(cellClean).replace(/[₺$€\s.]/g, '').replace(',', '.');
                var targetClean = targetValStr.replace(/[₺$€\s.]/g, '').replace(',', '.');
                if (cClean.indexOf(targetClean) !== -1) return true;
                if (!isNaN(cellNum) && !isNaN(targetNum)) {
                    if (String(cellNum).indexOf(targetClean) !== -1) return true;
                }
                return false;
            }

            if (isNaN(cellNum) || isNaN(targetNum)) return false;

            if (op === 'equals') {
                return Math.abs(cellNum - targetNum) < 0.01;
            }
            if (op === 'gt') return cellNum > targetNum;
            if (op === 'lt') return cellNum < targetNum;
            if (op === 'gte') return cellNum >= targetNum;
            if (op === 'lte') return cellNum <= targetNum;
            return false;
        }

        if (type === 'date') {
            var cellDate = parseDate(cellRaw);
            var targetDate = parseDate(rule.value);
            if (isNaN(cellDate) || isNaN(targetDate)) return false;

            var d1 = new Date(cellDate).setHours(0, 0, 0, 0);
            var d2 = new Date(targetDate).setHours(0, 0, 0, 0);

            if (op === 'equals') return d1 === d2;
            if (op === 'after' || op === 'gt') return d1 > d2;
            if (op === 'before' || op === 'lt') return d1 < d2;
            if (op === 'gte') return d1 >= d2;
            if (op === 'lte') return d1 <= d2;
            return false;
        }

        var targetText = toTrLower(rule.value);
        if (!targetText) return true;

        if (op === 'contains') return cellText.indexOf(targetText) !== -1;
        if (op === 'equals') return cellText === targetText;
        if (op === 'starts') return cellText.indexOf(targetText) === 0;
        if (op === 'ends') return cellText.slice(-targetText.length) === targetText;
        if (op === 'not_contains') return cellText.indexOf(targetText) === -1;

        return true;
    }

    // Register DataTables Ext Search for Client-Side Tables
    if ($.fn.dataTable && $.fn.dataTable.ext && $.fn.dataTable.ext.search) {
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex, rowData, counter) {
            var isServer = Boolean(
                settings.bServerSide || 
                (settings.oFeatures && settings.oFeatures.bServerSide) || 
                (settings.oInit && (settings.oInit.serverSide || settings.oInit.bServerSide))
            );
            if (isServer) {
                return true;
            }

            var tableId = settings.sTableId || (settings.nTable ? (settings.nTable.id || $(settings.nTable).attr('id')) : null);
            if (!tableId || !filterStates) return true;

            var prefix = tableId + '_';
            var activeKeys = Object.keys(filterStates).filter(function (k) {
                return k.indexOf(prefix) === 0 && filterStates[k] && filterStates[k].rules && filterStates[k].rules.length > 0;
            });

            if (activeKeys.length === 0) return true;

            for (var i = 0; i < activeKeys.length; i++) {
                var key = activeKeys[i];
                var filterDef = filterStates[key];
                var colIdx = filterDef.colIdx;
                var cellValue = data[colIdx] || '';
                var logic = filterDef.logic || (filterDef.type === 'text' ? 'or' : 'and');

                if (filterDef.rules && filterDef.rules.length > 0) {
                    if (logic === 'or') {
                        var passed = filterDef.rules.some(function (r) {
                            return evaluateRule(cellValue, r, filterDef.type);
                        });
                        if (!passed) return false;
                    } else {
                        var passed = filterDef.rules.every(function (r) {
                            return evaluateRule(cellValue, r, filterDef.type);
                        });
                        if (!passed) return false;
                    }
                }
            }

            return true;
        });
    }

    function updateActiveFiltersBar(tableId, dtInstance) {
        var $bar = $('#' + tableId + '_active_filters_bar');
        if (!$bar.length) return;

        var activeCount = 0;
        var $list = $bar.find('.dt-active-filters-list');
        $list.empty();
        $list.append('<span class="text-muted small fw-medium me-1" style="font-size: 12px;">Aktif filtreler:</span>');

        var prefix = tableId + '_';
        $.each(filterStates, function (key, filter) {
            if (key.indexOf(prefix) === 0 && filter && filter.rules && filter.rules.length > 0) {
                var title = filter.title || ('Sütun ' + filter.colIdx);
                var logicGlue = filter.logic === 'or' ? ' VEYA ' : ' VE ';

                var rulesDesc = filter.rules.map(function (r) {
                    var opLabel = OPERATOR_LABELS[r.operator] || r.operator;
                    if (r.operator === 'empty' || r.operator === 'not_empty') {
                        return opLabel;
                    }
                    return opLabel + ': ' + $('<div>').text(r.value).html();
                }).join(logicGlue);

                var displayText = '<strong>' + $('<div>').text(title).html() + ':</strong> ' + rulesDesc;

                var $chip = $(`
                    <span class="dt-filter-chip">
                        ${displayText}
                        <span class="dt-chip-close" data-table="${tableId}" data-col="${filter.colIdx}" title="Filtreyi Kaldır">&times;</span>
                    </span>
                `);
                $list.append($chip);
                activeCount++;
            }
        });

        if (activeCount > 0) {
            $bar.addClass('has-active-filters').show();
        } else {
            $bar.removeClass('has-active-filters').hide();
        }
    }

    function applyFilter() {
        if (!activeColumn || !activeTable) return;

        var tableId = activeTableId;
        var colIdx = activeColIdx;
        var colTitle = activeColTitle || ('Sütun ' + colIdx);
        var colType = activeColType || 'text';
        var stateKey = tableId + '_' + colIdx;

        var logic = $popover.find('.dt-logic-radio:checked').val() || (colType === 'text' ? 'or' : 'and');
        var rules = [];

        $popover.find('.dt-rule-row').each(function () {
            var $row = $(this);
            var operator = $row.find('.dt-filter-condition').val();
            var val = $.trim($row.find('.dt-filter-val').val());

            if (val !== '' || operator === 'empty' || operator === 'not_empty') {
                rules.push({
                    operator: operator,
                    value: val
                });
            }
        });

        if (rules.length === 0) {
            delete filterStates[stateKey];
            activeColumn.search('').draw();
            if (activeFilterBtn) {
                activeFilterBtn.removeClass('active text-primary bg-primary-lt').addClass('text-muted');
            }
            updateActiveFiltersBar(tableId, activeTable);
            hidePopover();
            return;
        }

        var filterData = {
            type: colType,
            colIdx: colIdx,
            title: colTitle,
            logic: logic,
            rules: rules
        };

        filterStates[stateKey] = filterData;

        // Check if server-side DataTable
        var isServer = Boolean(
            activeTable.init().serverSide || 
            (activeTable.settings()[0] && (activeTable.settings()[0].bServerSide || activeTable.settings()[0].oFeatures.bServerSide))
        );

        if (isServer) {
            // Encode structured rule into column search parameter for backend
            if (rules.length === 1 && rules[0].operator === 'contains') {
                activeColumn.search(rules[0].value, false, true).draw();
            } else if (rules.length === 1 && rules[0].operator === 'equals') {
                activeColumn.search('^' + rules[0].value + '$', true, false).draw();
            } else if (rules.length === 1 && rules[0].operator === 'empty') {
                activeColumn.search('^$', true, false).draw();
            } else if (rules.length === 1 && rules[0].operator === 'not_empty') {
                activeColumn.search('^(?!$).+', true, false).draw();
            } else if (rules.length === 1 && (rules[0].operator === 'gt' || rules[0].operator === 'after')) {
                activeColumn.search('> ' + rules[0].value, false, false).draw();
            } else if (rules.length === 1 && (rules[0].operator === 'lt' || rules[0].operator === 'before')) {
                activeColumn.search('< ' + rules[0].value, false, false).draw();
            } else if (rules.length === 1 && rules[0].operator === 'gte') {
                activeColumn.search('>= ' + rules[0].value, false, false).draw();
            } else if (rules.length === 1 && rules[0].operator === 'lte') {
                activeColumn.search('<= ' + rules[0].value, false, false).draw();
            } else {
                activeColumn.search(JSON.stringify(filterData), false, false).draw();
            }
        } else {
            // Client-side filtreyi ext.search uygular. Buraya stateKey yazmak,
            // DataTables'in standart sutun aramasini da etkinlestirip tum
            // satirlari (ornegin "projectTable_2" metni bulunmadigi icin)
            // daha ozel filtre calismadan eliyordu.
            activeColumn.search('');
            activeTable.draw();
        }

        if (activeFilterBtn) {
            activeFilterBtn.removeClass('text-muted').addClass('active text-primary bg-primary-lt');
        }

        updateActiveFiltersBar(tableId, activeTable);
        hidePopover();
    }

    window.initDataTableColumnFilters = function ($table, dtInstance) {
        if (!$table || !$table.length || !dtInstance) return;

        var pop = getPopover();
        var tableId = $table.attr('id') || 'table';
        tableInstances[tableId] = dtInstance;

        // Build Active Filters Bar if not already built
        var barId = tableId + '_active_filters_bar';
        var $existingBar = $('#' + barId);
        if (!$existingBar.length) {
            var barHtml = `
            <div id="${barId}" class="dt-active-filters-bar d-flex align-items-center justify-content-between flex-wrap gap-2" style="display: none;">
                <div class="d-flex align-items-center flex-wrap gap-2 dt-active-filters-list">
                    <span class="text-muted small fw-medium me-1" style="font-size: 12px;">Aktif filtreler:</span>
                </div>
                <div>
                    <a href="javascript:void(0);" class="dt-clear-all-filters-btn" data-table="${tableId}">
                        <i class="ti ti-filter-off"></i> Filtreleri Temizle
                    </a>
                </div>
            </div>
            `;
            var $targetContainer = $table.closest('.card-table-wrap').length 
                ? $table.closest('.card-table-wrap') 
                : ($table.closest('.table-responsive').length ? $table.closest('.table-responsive') : $table);
            $targetContainer.before(barHtml);
        }

        // Delegate Chip Close event
        $(document).off('click', '#' + barId + ' .dt-chip-close').on('click', '#' + barId + ' .dt-chip-close', function (e) {
            e.stopPropagation();
            var tId = $(this).data('table');
            var cIdx = parseInt($(this).data('col'), 10);
            var inst = tableInstances[tId];
            if (!inst) return;

            var key = tId + '_' + cIdx;
            delete filterStates[key];

            inst.column(cIdx).search('').draw();

            var $btn = $('#' + tId).find('.dt-col-filter-btn[data-column="' + cIdx + '"]');
            if ($btn.length) {
                $btn.removeClass('active text-primary bg-primary-lt').addClass('text-muted');
            }

            updateActiveFiltersBar(tId, inst);
        });

        // Delegate Clear All event
        $(document).off('click', '#' + barId + ' .dt-clear-all-filters-btn').on('click', '#' + barId + ' .dt-clear-all-filters-btn', function (e) {
            e.stopPropagation();
            var tId = $(this).data('table');
            var inst = tableInstances[tId];
            if (!inst) return;

            var prefix = tId + '_';
            $.each(filterStates, function (key, filter) {
                if (key.indexOf(prefix) === 0 && filter) {
                    inst.column(filter.colIdx).search('');
                    delete filterStates[key];
                }
            });

            $('#' + tId).find('.dt-col-filter-btn').removeClass('active text-primary bg-primary-lt').addClass('text-muted');
            inst.draw();
            updateActiveFiltersBar(tId, inst);
        });

        function renderHeaderFilters() {
            var skipTitles = ['İşlem', 'İşlemler', 'Seç', 'Aksiyon', 'Aksiyonlar', 'Sıra', '#'];

            dtInstance.columns().every(function () {
                var colIdx = this.index();
                var header = this.header();
                if (!header) return;
                var $th = $(header);

                var stateKey = tableId + '_' + colIdx;
                var isFiltered = filterStates[stateKey] && filterStates[stateKey].rules && filterStates[stateKey].rules.length > 0;

                var $existingBtn = $th.find('.dt-col-filter-btn');
                if ($existingBtn.length > 0) {
                    if (isFiltered) {
                        $existingBtn.addClass('active text-primary bg-primary-lt').removeClass('text-muted');
                    } else {
                        $existingBtn.removeClass('active text-primary bg-primary-lt').addClass('text-muted');
                    }
                    return;
                }

                var title = $th.text().trim();
                var isSkip = false;

                if ($th.hasClass('no-export') || $th.hasClass('no-filter') || $th.find('input[type="checkbox"]').length > 0) {
                    isSkip = true;
                }
                if (skipTitles.indexOf(title) !== -1 || title === '') {
                    isSkip = true;
                }

                if (isSkip) return;

                var detectedType = detectColumnType(header, title);
                $th.attr('data-filter-type', detectedType);

                var isOrderable = $th.hasClass('dt-orderable-asc') || $th.hasClass('dt-orderable-desc') || $th.hasClass('dt-ordering-asc') || $th.hasClass('dt-ordering-desc') || $th.hasClass('sorting') || $th.hasClass('sorting_asc') || $th.hasClass('sorting_desc');
                var currentHtml = $th.html();
                var $clean = $('<div>').html(currentHtml);
                $clean.find('.dt-column-order, .dt-col-filter-btn').remove();
                var cleanTitleHtml = $clean.html().trim();

                var $wrapper = $('<div class="dt-header-content d-flex align-items-center justify-content-between gap-1 w-100"></div>');
                var $titleWrap = $('<div class="dt-header-title-wrap d-inline-flex align-items-center gap-1.5 flex-grow-1 min-w-0"></div>');
                if (isOrderable) {
                    $titleWrap.append('<span class="dt-column-order"></span>');
                }
                var $titleSpan = $('<span class="dt-header-title text-wrap"></span>').html(cleanTitleHtml);
                $titleWrap.append($titleSpan);

                var $actionsSpan = $('<span class="dt-header-actions d-inline-flex align-items-center gap-1 ms-auto flex-shrink-0"></span>');

                var $btn = $(`
                    <button type="button" class="dt-col-filter-btn ${isFiltered ? 'active text-primary bg-primary-lt' : 'text-muted'}" title="Filtrele" data-column="${colIdx}" data-title="${title}" data-filter-type="${detectedType}">
                        <i class="ti ti-filter"></i>
                    </button>
                `);

                $actionsSpan.append($btn);
                $wrapper.append($titleWrap).append($actionsSpan);
                $th.empty().append($wrapper);

                $btn.on('click', function (e) {
                    e.stopPropagation();
                    var colIdx = parseInt($(this).data('column'), 10);
                    var colTitle = $(this).data('title') || ('Sütun ' + colIdx);
                    var colType = $(this).data('filter-type') || 'text';
                    var column = dtInstance.column(colIdx);

                    activeColumn = column;
                    activeTable = dtInstance;
                    activeFilterBtn = $(this);
                    activeTableId = tableId;
                    activeColIdx = colIdx;
                    activeColType = colType;
                    activeColTitle = colTitle;

                    pop.find('.dt-col-filter-title').text(colTitle || 'FİLTRE');

                    var key = tableId + '_' + colIdx;
                    var saved = filterStates[key] || null;

                    var $container = pop.find('.dt-rules-container');
                    // Destroy any old Select2 instances
                    $container.find('.dt-filter-condition').each(function () {
                        if ($.fn.select2) {
                            try { $(this).select2('destroy'); } catch (err) {}
                        }
                    });
                    $container.empty();

                    if (saved && saved.rules && saved.rules.length > 0) {
                        saved.rules.forEach(function (r, rIdx) {
                            var rowHtml = buildRuleRowHtml(colType, rIdx > 0, r);
                            var $row = $(rowHtml).appendTo($container);
                            initRowControls($row, colType);
                        });
                        pop.find('.dt-logic-radio[value="' + (saved.logic || 'and') + '"]').prop('checked', true);
                    } else {
                        var rowHtml = buildRuleRowHtml(colType, false, null);
                        var $row = $(rowHtml).appendTo($container);
                        initRowControls($row, colType);
                        pop.find('.dt-logic-radio[value="' + (colType === 'text' ? 'or' : 'and') + '"]').prop('checked', true);
                    }

                    updateLogicVisibility();

                    // Positioning
                    var btnOffset = $(this).offset();
                    var btnHeight = $(this).outerHeight();
                    var popWidth = pop.outerWidth() || 290;
                    var windowWidth = $(window).width();

                    var posX = btnOffset.left;
                    if (posX + popWidth > windowWidth - 15) {
                        posX = windowWidth - popWidth - 15;
                    }
                    if (posX < 10) posX = 10;
                    var posY = btnOffset.top + btnHeight + 6;

                    pop.css({
                        top: posY + 'px',
                        left: posX + 'px',
                        display: 'block'
                    });

                    setTimeout(function () {
                        pop.find('.dt-filter-val').first().focus();
                    }, 50);
                });
            });
        }

        renderHeaderFilters();

        $table.off('column-visibility.dt.colFilter').on('column-visibility.dt.colFilter', function () {
            setTimeout(renderHeaderFilters, 50);
        });

        updateActiveFiltersBar(tableId, dtInstance);
    };

})(jQuery);
