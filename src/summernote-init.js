// Summernote global Tabler-ikon ayarları — tüm sayfalarda geçerli
// vendor-scripts.php tarafından Summernote JS'den hemen sonra yüklenir

(function ($) {
    // Tabler icon font <i> self-closing değil, kapanış etiketi gerektirir
    var orig = $.summernote.ui_template;
    $.summernote.ui_template = function (options) {
        var ui = orig.call(this, options);
        ui.icon = function (iconClassName, tagName) {
            tagName = tagName || 'i';
            return '<' + tagName + ' class="' + iconClassName + '"></' + tagName + '>';
        };
        return ui;
    };

    // Global toolbar + ikon haritası — her sayfada tekrar yazmaya gerek yok
    $.extend(true, $.summernote.options, {
        toolbar: [
            ['style',  ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['color',  ['color']],
            ['para',   ['ul', 'ol']],
            ['insert', ['link']],
            ['view',   ['undo', 'redo', 'fullscreen', 'codeview']]
        ],
        icons: {
            'align':         'ti ti-align-left',
            'alignCenter':   'ti ti-align-center',
            'alignJustify':  'ti ti-align-justified',
            'alignLeft':     'ti ti-align-left',
            'alignRight':    'ti ti-align-right',
            'arrowsAlt':     'ti ti-arrows-maximize',
            'bold':          'ti ti-bold',
            'caret':         'ti ti-chevron-down',
            'circle':        'ti ti-circle',
            'close':         'ti ti-x',
            'code':          'ti ti-code',
            'eraser':        'ti ti-eraser',
            'floatLeft':     'ti ti-layout-align-left',
            'floatRight':    'ti ti-layout-align-right',
            'font':          'ti ti-typography',
            'frame':         'ti ti-border-outer',
            'indent':        'ti ti-indent-increase',
            'italic':        'ti ti-italic',
            'link':          'ti ti-link',
            'magic':         'ti ti-wand',
            'menuCheck':     'ti ti-check',
            'minus':         'ti ti-minus',
            'orderedlist':   'ti ti-list-numbers',
            'outdent':       'ti ti-indent-decrease',
            'pencil':        'ti ti-pencil',
            'picture':       'ti ti-photo',
            'question':      'ti ti-help',
            'redo':          'ti ti-arrow-forward-up',
            'rollback':      'ti ti-rotate',
            'square':        'ti ti-square',
            'strikethrough': 'ti ti-strikethrough',
            'subscript':     'ti ti-subscript',
            'superscript':   'ti ti-superscript',
            'table':         'ti ti-table',
            'textHeight':    'ti ti-text-size',
            'trash':         'ti ti-trash',
            'underline':     'ti ti-underline',
            'undo':          'ti ti-arrow-back-up',
            'unlink':        'ti ti-link-off',
            'unorderedlist': 'ti ti-list',
            'video':         'ti ti-video'
        }
    });
}(jQuery));
