// Inject custom layout styles for DataTables footer controls (info, length, paging) in a single row
$(function() {
  $("<style>")
    .prop("type", "text/css")
    .html(`
      /* Global Layout & Padding Reset for DataTables & Tables */
      div.dt-container {
        margin: 0 !important;
        padding: 0 !important;
      }

      div.dt-container div.dt-layout-row {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
      }

      div.dt-container div.dt-layout-row.dt-empty-row,
      div.dt-container div.dt-layout-row:empty,
      div.dt-container div.dt-layout-row:first-child:not(.dt-layout-table) {
        display: none !important;
        margin: 0 !important;
        padding: 0 !important;
        height: 0 !important;
        min-height: 0 !important;
        overflow: hidden !important;
      }

      div.dt-container div.dt-layout-row.dt-layout-table {
        margin: 0 !important;
        padding: 0 !important;
      }

      div.dt-container div.dt-layout-row.dt-layout-table > div.dt-layout-cell {
        margin: 0 !important;
        padding: 0 !important;
      }

      div.dt-container table.dataTable,
      table.card-table.dataTable,
      table.card-table,
      table.dataTable {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100% !important;
      }

      .table-responsive {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
      }



      /* Card-body padding reset when it wraps table-responsive or DataTables */
      .card-body:has(> .table-responsive),
      .card-body:has(> .dt-container),
      .card-body:has(> div > .table-responsive),
      .card-body:has(> div > .dt-container) {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
      }

      /* Ensure card header bottom border and table top header sit flush */
      .card-header + .card-body .table-responsive,
      .card-header + .table-responsive {
        margin-top: 0 !important;
      }

      /* Footer controls layout - Pinned to bottom of card */
      div.dt-container .dt-layout-row:last-child,
      div.dt-container .dt-layout-row:has(.dt-paging),
      div.dt-container .dt-layout-row:has(.dt-info) {
        margin-top: auto !important;
        flex-shrink: 0 !important;
        position: relative !important;
        z-index: 11 !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        width: 100% !important;
        gap: 1rem !important;
        padding: 0.5rem 1.25rem !important;
        background-color: var(--tblr-bg-surface, #ffffff) !important;
        border-top: 1px solid var(--tblr-border-color, #dadcde) !important;
        box-sizing: border-box !important;
      }
      div.dt-container .dt-layout-row:last-child .dt-layout-start,
      div.dt-container .dt-layout-row:has(.dt-paging) .dt-layout-start {
        display: flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: 1.25rem !important;
        flex-wrap: wrap !important;
        margin-right: auto !important;
      }
      div.dt-container .dt-layout-row:last-child .dt-layout-start > *,
      div.dt-container .dt-layout-row:has(.dt-paging) .dt-layout-start > * {
        margin: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
      }
      div.dt-container .dt-layout-row:last-child .dt-layout-end,
      div.dt-container .dt-layout-row:has(.dt-paging) .dt-layout-end {
        margin-left: auto !important;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
      }
      div.dt-container .dt-layout-row:last-child .dt-layout-end .dt-paging,
      div.dt-container .dt-layout-row:has(.dt-paging) .dt-layout-end .dt-paging {
        margin: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
      }
      div.dt-container .dt-paging .dt-paging-button,
      div.dt-container .dt-paging .dt-paging-button.current,
      div.dt-container .dt-paging .dt-paging-button:first-child,
      div.dt-container .dt-paging .dt-paging-button:last-child,
      div.dt-container .dt-paging button,
      div.dt-container .dt-paging a {
        border-radius: 6px !important;
        -webkit-border-radius: 6px !important;
        border-top-left-radius: 6px !important;
        border-top-right-radius: 6px !important;
        border-bottom-left-radius: 6px !important;
        border-bottom-right-radius: 6px !important;
      }
      div.dt-container .dt-length select {
        padding: 0.2rem 1.75rem 0.2rem 0.6rem !important;
        font-size: 0.875rem !important;
        border-radius: 6px !important;
        margin: 0 0.25rem !important;
        display: inline-block !important;
        width: auto !important;
      }

      /* Ensure table row bottom borders are always visible and distinct */
      table.dataTable > tbody > tr > td,
      table.dataTable > tbody > tr > th {
        border-top: 1px solid rgba(98, 105, 118, 0.18) !important;
        border-bottom: none !important;
      }
      table.dataTable > tbody > tr:first-child > td,
      table.dataTable > tbody > tr:first-child > th {
        border-top: none !important;
      }
      table.dataTable > tbody > tr:last-child > td,
      table.dataTable > tbody > tr:last-child > th {
        border-bottom: 1px solid rgba(98, 105, 118, 0.18) !important;
      }
      table.dataTable > thead > tr > th,
      table.dataTable > thead > tr > td {
        border-bottom: 1px solid rgba(98, 105, 118, 0.14) !important;
      }
      table.dataTable > thead > tr.search-input-row > th,
      table.dataTable > thead > tr.search-input-row > td {
        border-bottom: 1px solid rgba(98, 105, 118, 0.12) !important;
        background-color: var(--tblr-bg-surface-secondary, #f8fafc) !important;
        padding: 4px 6px !important;
      }

      /* Ensure proper padding for first & last table columns in card tables so checkboxes & text are never clipped */
      table.dataTable > thead > tr > th:first-child,
      table.dataTable > tbody > tr > td:first-child {
        padding-left: 1.25rem !important;
      }
      table.dataTable > thead > tr > th:last-child,
      table.dataTable > tbody > tr > td:last-child {
        padding-right: 1.25rem !important;
      }

      /*
       * Viewport'a sığdırılan DataTable'larda yalnızca veri alanı kayar.
       * Header (çok satırlı arama başlığı dahil) kaydırma alanının üstünde,
       * DataTables bilgi/sayfalama satırı ise altında sabit kalır.
       */
      .dt-container.dt-viewport-managed {
        min-height: 0 !important;
        --dt-header-bg: #f8fafc;
        --dt-search-header-bg: #fbfcfe;
      }
      [data-bs-theme="dark"] .dt-container.dt-viewport-managed {
        --dt-header-bg: #1b2431;
        --dt-search-header-bg: #182230;
      }
      .dt-container.dt-viewport-managed .dt-layout-row.dt-layout-table {
        min-height: 0 !important;
      }
      .dt-container.dt-viewport-managed .dt-viewport-scroll {
        height: var(--dt-viewport-body-height) !important;
        max-height: var(--dt-viewport-body-height) !important;
        overflow-y: auto !important;
        overflow-x: auto !important;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
      }
      .dt-container.dt-viewport-managed .dt-layout-row.dt-layout-table.dt-viewport-scroll {
        display: block !important;
      }
      .dt-container.dt-viewport-managed .dt-viewport-scroll table.dataTable > thead > tr > th,
      .dt-container.dt-viewport-managed .dt-viewport-scroll table.dataTable > thead > tr > td {
        position: sticky !important;
        top: var(--dt-sticky-top, 0px) !important;
        z-index: 12 !important;
        background-color: var(--dt-header-bg) !important;
      }
      .dt-container.dt-viewport-managed .dt-viewport-scroll table.dataTable > thead > tr.search-input-row > th,
      .dt-container.dt-viewport-managed .dt-viewport-scroll table.dataTable > thead > tr.search-input-row > td {
        z-index: 13 !important;
        background-color: var(--dt-search-header-bg) !important;
      }
      .table-responsive.dt-viewport-host {
        overflow: hidden !important;
        min-height: 0 !important;
      }

      /* Global DataTable empty state */
      table.dataTable > tbody > tr > td.dt-empty,
      table.dataTable > tbody > tr > td.dataTables_empty {
        height: calc(var(--dt-viewport-body-height, 390px) - var(--dt-empty-header-offset, 76px)) !important;
        min-height: 240px !important;
        padding: 24px !important;
        text-align: center !important;
        vertical-align: middle !important;
        background: transparent !important;
        border: 0 !important;
      }
      .dt-empty-state {
        width: min(680px, calc(100% - 32px));
        min-height: 340px;
        margin: 0 auto;
        padding: 48px 40px;
        border: 1px solid rgba(98, 105, 118, 0.08);
        border-radius: 14px;
        background: linear-gradient(180deg, #fafbfc 0%, #f7f8fa 100%);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #182433;
        box-sizing: border-box;
        box-shadow: 0 8px 30px rgba(24, 36, 51, 0.035);
      }
      .dt-empty-state-icon {
        position: relative;
        isolation: isolate;
        width: 52px;
        height: 52px;
        margin-bottom: 20px;
        border: 1px solid rgba(98, 105, 118, 0.14);
        border-radius: 12px;
        background: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow:
          0 1px 2px rgba(24, 36, 51, 0.05),
          0 8px 18px rgba(24, 36, 51, 0.06);
      }
      .dt-empty-state-icon::before {
        content: "";
        position: absolute;
        inset: -9px;
        z-index: -1;
        border-radius: 16px;
        background: rgba(98, 105, 118, 0.035);
      }
      .dt-empty-state-icon .ti {
        font-size: 26px;
        color: #182433;
      }
      .dt-empty-state-title {
        margin-bottom: 8px;
        font-size: 1.125rem;
        line-height: 1.4;
        font-weight: 600;
        letter-spacing: -0.01em;
      }
      .dt-empty-state-description {
        max-width: 410px;
        font-size: 0.875rem;
        line-height: 1.65;
        color: #667085;
      }
      [data-bs-theme="dark"] .dt-empty-state {
        background: linear-gradient(180deg, #17212e 0%, #151f2c 100%);
        border-color: rgba(255, 255, 255, 0.05);
        color: #e6e7e9;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
      }
      [data-bs-theme="dark"] .dt-empty-state-icon {
        background: #1b2431;
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: none;
      }
      [data-bs-theme="dark"] .dt-empty-state-icon .ti {
        color: #cbd5e1;
      }
      [data-bs-theme="dark"] .dt-empty-state-description {
        color: #94a3b8;
      }
      @media (max-height: 720px) {
        .dt-empty-state {
          min-height: 260px;
          padding: 32px;
        }
      }
    `)
    .appendTo("head");
});

if ($.fn && $.fn.dataTable) {
  $.extend(true, $.fn.dataTable.defaults, {
    pagingType: "simple_numbers",
    layout: {
      bottomStart: ["info", "pageLength"],
      bottomEnd: "paging",
      topStart: null,
      topEnd: null
    },
    language: {
      url: "src/tr.json",
      emptyTable: "Henüz kayıt yok",
      zeroRecords: "Sonuç bulunamadı"
    }
  });
}

if ($(".datatable").length > 0 || $("#puantajDataTable").length > 0 || $("#bankDataTable").length > 0) {

  // DataTables 2.x: arama satırını init ÖNCE ekle ki header yönetimi bozulmasın
  $(".datatable:not(#puantajTable)").each(function () {
    var $thead = $(this).find("thead");
    if ($thead.find(".search-input-row").length === 0) {
      var colCount = $thead.find("tr:first th, tr:first td").length;
      var $row = $('<tr class="search-input-row"></tr>');
      for (var c = 0; c < colCount; c++) {
        $row.append('<th class="search p-1"></th>');
      }
      $thead.append($row);
    }
  });

  var table = $(".datatable:not(#puantajTable)").DataTable({
    autoWidth: false,
    order: [],
    orderCellsTop: true,
    pagingType: "simple_numbers",
    language: {
      url: "src/tr.json"
    },
    //dom: "Bfrtip",
    buttons: [
      {
        extend: "excelHtml5",
        className: "d-none",
        title: "Personel Listesi",
        messageTop: "Tarih: " + new Date().toLocaleDateString("tr-TR"),
        exportOptions: {
          columns: ":visible:not(.no-export)"
        }
      },
      {
        extend: "pdfHtml5",
        className: "d-none",
        title: "Personel Listesi",
        messageTop: "Tarih: " + new Date().toLocaleDateString("tr-TR"),
        orientation: "landscape",
        pageSize: "A4",
        exportOptions: {
          columns: ":visible:not(.no-export)"
        },
        customize: function (doc) {
          doc.styles.tableHeader.fillColor = '#206bc4';
          doc.styles.tableHeader.color = 'white';
          doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
        }
      }
    ],
    layout: {
      bottomStart: ["info", "pageLength"],
      bottomEnd: "paging",
      topStart: null,
      topEnd: null
    },
    initComplete: function (settings, json) {
      var api = this.api();
      var tableId = settings.sTableId;

      var defaultSkipTitles = ['işlem', 'işlemler', 'seç', 'aksiyon', 'aksiyonlar'];
      api.columns().every(function () {
        let column = this;
        let colIdx = column.index();
        // orderCellsTop:true ile column.header() ilk satırı döndürür
        let title = $(column.header()).text().trim();
        let titleLower = title.toLowerCase();

        if (
          title &&
          defaultSkipTitles.indexOf(titleLower) === -1 &&
          $(column.header()).find('input[type="checkbox"]').length === 0
        ) {
          let input = document.createElement("input");
          input.type = "search";
          input.placeholder = title;
          input.classList.add("form-control");
          input.classList.add("form-control-sm");
          input.setAttribute("autocomplete", "search");
          input.setAttribute("name", "dt_filter_" + tableId + "_" + colIdx);
          input.setAttribute("data-lpignore", "true");
          input.setAttribute("data-1p-ignore", "true");
          input.setAttribute("data-bwignore", "true");

          // Mevcut arama satırındaki doğru hücreye yerleştir
          $("#" + tableId + " .search-input-row th:eq(" + colIdx + ")").html(input);

          $(input).on("keyup change search", function () {
            if (column.search() !== this.value) {
              column.search(this.value).draw();
            }
          });
        }
      });
    }
  });
  //Tüm tablolar için excel dışa aktarım butonu
  $("#export_excel").on("click", function (e) {
    e.preventDefault();
    table.button(".buttons-excel").trigger();
  });

  $("#export_pdf").on("click", function (e) {
    e.preventDefault();
    table.button(".buttons-pdf").trigger();
  });

  //Personelin çalışma bilgileri tablosu için
  $("#export_excel_puantaj_info").on("click", function () {
    var table_puantaj_info = $("#puantaj_info_table").DataTable();
    table_puantaj_info.button(".buttons-excel").trigger();
  });

  window.excelExportType = "code";
  window.getCellHour = function(id) {
    if (!id || !window.allPuantajTurleri || !window.allPuantajTurleri[id]) return "";
    var type = window.allPuantajTurleri[id];
    var operant = type.operant;
    if (operant === '+' || operant === '-' || operant === '*' || operant === '/') {
      var saat = parseFloat(String(type.EklenecekSaat).replace(',', '.')) || 0;
      var wHour = parseFloat(String(window.workHour || 8).replace(',', '.')) || 8;
      var res = 0;
      switch (operant) {
        case '+':
          res = saat + wHour;
          break;
        case '-':
          res = saat - wHour;
          break;
        case '*':
          res = saat * wHour;
          break;
        case '/':
          res = wHour !== 0 ? (saat / wHour) : 0;
          break;
      }
      return Number(res.toFixed(2));
    } else {
      return parseFloat(type.PuantajSaati) || 0;
    }
  };

  //Puantaj tablosu için
  window.initializePuantajDataTable = function () {
    if ($.fn.DataTable.isDataTable('#puantajTable')) {
      return $('#puantajTable').DataTable();
    }
    
    var puantaj_table = $("#puantajTable").DataTable({
      autoWidth: false,
      ordering: true,
      order: [[0, "asc"]],
      orderCellsTop: true,
      columnDefs: [
        { orderable: false, targets: "_all" },
        { defaultContent: "", targets: "_all" }
      ],
      pagingType: "simple_numbers",
      layout: {
        bottomStart: ["info", "pageLength"],
        bottomEnd: "paging",
        topStart: null,
        topEnd: "search"
      },
      language: {
        url: "src/tr.json"
      },
      buttons: [
        {
          extend: "excelHtml5",
          className: "d-none", // Butonu gizliyoruz
          exportOptions: {
            columns: ":visible:not(.no-export)", // .no-export sınıfına sahip sütunları dışa aktarma
            format: {
              body: function (data, row, column, node) {
                var $node = $(node);
                if ($node.hasClass("gun") || $node.hasClass("noselect")) {
                  var text = $node.text().trim();
                  if (text === "---") return "---";
                  if (window.excelExportType === "hour") {
                    var id = $node.attr("data-id");
                    if (id && id !== "0" && id !== "") {
                      var hr = window.getCellHour(id);
                      return hr !== "" ? hr : "";
                    }
                    return "";
                  } else {
                    return text;
                  }
                }
                return $node.text().trim();
              }
            }
          },
          customize: function (xlsx) {
            var sheet = xlsx.xl.worksheets['sheet1.xml'];
            var styles = xlsx.xl['styles.xml'];

            // 1. DOM'daki tüm görünür satır ve hücreleri topla (1:1 eşleşme için)
            var domRows = [];

            // Başlık satırı 1
            var headerRow1 = [];
            $('#puantajTable thead tr').eq(0).find('th:visible').each(function () {
              headerRow1.push($(this));
            });
            domRows.push(headerRow1);

            // Başlık satırı 2
            var headerRow2 = [];
            $('#puantajTable thead tr').eq(1).find('th:visible').each(function () {
              headerRow2.push($(this));
            });
            domRows.push(headerRow2);

            // Gövde satırları
            $('#puantajTable tbody tr').each(function () {
              var rowCells = [];
              $(this).find('td:visible').each(function () {
                rowCells.push($(this));
              });
              domRows.push(rowCells);
            });

            // 2. XML stillerini al
            var fills = $('fills', styles);
            var fillCount = parseInt(fills.attr('count'), 10);

            var fonts = $('fonts', styles);
            var fontCount = parseInt(fonts.attr('count'), 10);

            var cellXfs = $('cellXfs', styles);
            var xfCount = parseInt(cellXfs.attr('count'), 10);

            var styleRegistry = {};

            // Yardımcı Fonksiyonlar
            function excelColToIndex(colStr) {
              var match = colStr.match(/[A-Z]+/);
              if (!match) return 0;
              var letters = match[0];
              var index = 0;
              for (var i = 0; i < letters.length; i++) {
                index = index * 26 + (letters.charCodeAt(i) - 64);
              }
              return index - 1;
            }

            function rgbToHex(rgbStr) {
              if (!rgbStr) return null;
              if (rgbStr.startsWith('#')) {
                var hex = rgbStr.replace('#', '').toUpperCase();
                if (hex.length === 3) {
                  hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
                }
                return 'FF' + hex;
              }
              if (rgbStr === 'transparent' || rgbStr === 'rgba(0, 0, 0, 0)' || rgbStr === 'none') {
                return null;
              }
              var match = rgbStr.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)$/);
              if (match) {
                var r = parseInt(match[1], 10).toString(16).padStart(2, '0');
                var g = parseInt(match[2], 10).toString(16).padStart(2, '0');
                var b = parseInt(match[3], 10).toString(16).padStart(2, '0');
                return 'FF' + (r + g + b).toUpperCase();
              }
              return null;
            }

            function getOrCreateStyle(bgHex, fgHex) {
              var key = (bgHex || '') + '-' + (fgHex || '');
              if (styleRegistry[key] !== undefined) {
                return styleRegistry[key];
              }

              var fillId = 0;
              if (bgHex) {
                var newFill = '<fill><patternFill patternType="solid"><fgColor rgb="' + bgHex + '"/><bgColor indexed="64"/></patternFill></fill>';
                fills.append(newFill);
                fillId = fillCount;
                fillCount++;
                fills.attr('count', fillCount);
              }

              var fontId = 0;
              if (fgHex) {
                var baseFont = fonts.find('font').first().clone();
                baseFont.find('color').replaceWith('<color rgb="' + fgHex + '"/>');
                fonts.append(baseFont);
                fontId = fontCount;
                fontCount++;
                fonts.attr('count', fontCount);
              }

              var baseXf = cellXfs.find('xf').first().clone();
              if (bgHex) {
                baseXf.attr('fillId', fillId);
                baseXf.attr('applyFill', '1');
              }
              if (fgHex) {
                baseXf.attr('fontId', fontId);
                baseXf.attr('applyFont', '1');
              }

              cellXfs.append(baseXf);
              var styleId = xfCount;
              xfCount++;
              cellXfs.attr('count', xfCount);

              styleRegistry[key] = styleId;
              return styleId;
            }

            // 3. XML'deki satır ve hücreleri dön, stilleri ata
            $('row', sheet).each(function (rIdx) {
              var $row = $(this);
              var domRow = domRows[rIdx];
              if (!domRow) return;

              $row.find('c').each(function () {
                var $cell = $(this);
                var colLetter = $cell.attr('r').replace(/[0-9]/g, '');
                var colIdx = excelColToIndex(colLetter);
                var $domCell = domRow[colIdx];
                if (!$domCell) return;

                var bg = rgbToHex($domCell.css('background-color'));
                var fg = rgbToHex($domCell.css('color'));

                // Eğer beyaz ya da varsayılan arka plan ise stil oluşturup XML'i şişirme
                if (bg === 'FFFFFFFF') bg = null;
                if (fg === 'FF000000') fg = null;

                if (bg || fg) {
                  var styleId = getOrCreateStyle(bg, fg);
                  $cell.attr('s', styleId);
                }
              });
            });
          }
        }
      ],

      initComplete: function (settings, json) {
        var api = this.api();
        var tableId = settings.sTableId;

        api.columns().every(function () {
          let column = this;
          // 0, 1, 2 ve 3. kolonların index numarasına göre arama kutusu ekle
          if (column.index() >= 0 && column.index() <= 3) {
            // Create input element
            let input = document.createElement("input");
            // Set placeholder based on column index
            var placeholder = "";
            switch (column.index()) {
              case 0:
                placeholder = "Adı Soyadı";
                break;
              case 1:
                placeholder = "Unvanı";
                break;
              case 2:
                placeholder = "İş Grubu";
                break;
              case 3:
                placeholder = "Ekip";
                break;
            }
            input.placeholder = placeholder;
            input.classList.add("form-control");
            input.classList.add("form-control-sm");
            input.setAttribute("autocomplete", "off");

            // Append input element to the existing row (tr:eq(1) is the search row)
            $("#" + tableId + " thead tr:eq(1) th:eq(" + column.index() + ")").html(
              input
            );

            // Event listener for user input
            $(input).on("keyup change", function () {
              if (column.search() !== this.value) {
                column.search(this.value).draw();
              }
            });
          }
        });
      }
    });

    // Sütun görünürlüğünü çizimlerde uygula
    puantaj_table.on('draw.dt', function () {
      if (typeof window.applyColumnVisibility === 'function') {
        window.applyColumnVisibility();
      }
    });

    return puantaj_table;
  };

  if ($("#puantajTable").length > 0) {
    window.initializePuantajDataTable();
  }

  $(document).on("click", "#export_excel_puantaj", function (e) {
    e.preventDefault();
    if ($.fn.DataTable.isDataTable('#puantajTable')) {
      $('#puantajTable').DataTable().button(".buttons-excel").trigger();
    }
  });



  // Premium Puantaj Raporu (Puantaj İcmal) için gelişmiş başlatma
  if ($("#puantajDataTable").length > 0) {
    console.log("Raporlar: #puantajDataTable tespit edildi, başlatılıyor...");
    
    // ColReorder eklentisi yüklü mü kontrol et
    var isColReorderAvailable = ($.fn.dataTable && $.fn.dataTable.ColReorder);
    if (!isColReorderAvailable) {
        console.warn("Raporlar: ColReorder eklentisi bulunamadı, sürükleme çalışmayabilir.");
    }

    // Statik PHP değişkenleri yerine DOM'dan veri çekme (Month/Year)
    var reportBadge = $(".badge.bg-blue-lt").first().text().trim() || "Rapor";
    var excelFileName = "Puantaj_Raporu_" + reportBadge.replace(/\s+/g, "_");
    var pdfTitle = "Puantaj Raporu - " + reportBadge;

    var commonExportOptions = {
      columns: ":visible",
      format: {
        body: function (data, row, column, node) {
          var $node = $(node);
          var nameSpan = $node.find(".fw-semibold");
          if (nameSpan.length > 0) return nameSpan.text().trim();
          var text = $node.text().trim();
          return text === "-" || text === "0" ? "" : text;
        },
        header: function (data, column, node) {
          return $(node).text().trim();
        }
      }
    };

    var reportTable = $("#puantajDataTable").DataTable({
      language: { url: "src/tr.json" },
      pageLength: 50,
      responsive: false,
      scrollX: true,
      colReorder: isColReorderAvailable ? true : false,
      stateSave: true,
      pagingType: "simple_numbers",
      layout: {
        topStart: null,
        topEnd: null,
        bottomStart: ["info", "pageLength"],
        bottomEnd: "paging"
      },
      buttons: [
        {
          extend: "excelHtml5",
          className: "d-none",
          title: excelFileName,
          exportOptions: commonExportOptions
        },
        {
          extend: "pdfHtml5",
          className: "d-none",
          orientation: "landscape",
          pageSize: "A4",
          title: pdfTitle,
          exportOptions: commonExportOptions,
          customize: function (doc) {
            doc.defaultStyle.fontSize = 8;
            doc.styles.tableHeader.fontSize = 8.5;
            doc.styles.tableHeader.bold = true;
            doc.styles.tableHeader.fillColor = "#1e293b";
            doc.styles.tableHeader.color = "white";
            doc.styles.tableHeader.alignment = "center";
            doc.pageMargins = [15, 20, 15, 20];
            doc.content[1].table.widths = Array(
              doc.content[1].table.body[0].length + 1
            )
              .join("*")
              .split("");

            var rowCount = doc.content[1].table.body.length;
            for (var i = 1; i < rowCount; i++) {
              var rowData = doc.content[1].table.body[i];
              for (var j = 0; j < rowData.length; j++) {
                rowData[j].alignment = j === 0 || j === 4 ? "left" : "center";
                if (i % 2 === 0) rowData[j].fillColor = "#f8fafc";
              }
            }

            var objLayout = {
              hLineWidth: function (i) {
                return 0.5;
              },
              vLineWidth: function (i) {
                return 0.5;
              },
              hLineColor: function (i) {
                return "#e2e8f0";
              },
              vLineColor: function (i) {
                return "#e2e8f0";
              },
              paddingTop: function (i) {
                return 4;
              },
              paddingBottom: function (i) {
                return 4;
              }
            };
            doc.content[1].layout = objLayout;
          }
        },
        {
          extend: "pdfHtml5",
          className: "d-none",
          orientation: "landscape",
          pageSize: "A3",
          title: "Puantaj Raporu"
        }
      ],
      columnDefs: [{ targets: [1, 2, 3, 4], visible: false }],
      initComplete: function () {
        var api = this.api();
        console.log("Raporlar: Tablo hazır, menü ve arama inşa ediliyor...");

        // Sütun Başlığı Araması Ekle (Sütunla beraber taşınması için TH içine ekliyoruz)
        api.columns().every(function () {
            var column = this;
            var $header = $(column.header());
            var title = $header.text().trim();
            
            if ($header.find(".column-search-input").length === 0) {
                // Mevcut içeriği bir span içine alalım ki üstte kalsın
                var headerText = $header.html();
                $header.html('<div class="d-flex flex-column">' + 
                             '<span class="mb-1">' + headerText + '</span>' + 
                             '</div>');
                
                // Tüm kolonlar için arama kutusu ekle
                if (title) {
                    var input = $('<input type="text" class="form-control form-control-sm column-search-input" placeholder="Ara..." style="font-size: 9px; height: 20px; padding: 2px 5px; font-weight: normal; text-transform: none;" />');
                    
                    // Tıklayınca sıralama yapılmasını engellemek için
                    input.on("click", function(e) { e.stopPropagation(); });
                    
                    input.on("keyup change clear", function () {
                        if (column.search() !== this.value) {
                            column.search(this.value).draw();
                        }
                    });
                    $header.find(".d-flex").append(input);
                } else {
                    // Boşluk bırak (hizalama için)
                    $header.find(".d-flex").append('<div style="height: 20px;"></div>');
                }
            }
        });

        // Menüyü temizle
        $("#customColvisMenu").empty();
        api.columns().every(function (idx) {
          if (idx === 0) return;
          
          var column = this;
          var title = $(column.header()).text().trim() || ("Sütun " + idx);
          var isVisible = column.visible();
          
          var itemHtml = `
              <label class="dropdown-item d-flex align-items-center cursor-pointer py-2 px-3 rounded-2" style="font-size: 0.85rem;">
                  <div class="form-check mb-0">
                      <input class="form-check-input col-visibility-trigger" type="checkbox" id="colCheck_${idx}" data-column="${idx}" ${isVisible ? "checked" : ""}>
                      <span class="form-check-label fw-medium ms-1 text-secondary" style="user-select:none;">
                          ${title}
                      </span>
                  </div>
              </label>`;
          
          // Daha garanti bir ekleme yöntemi
          var menuEl = document.getElementById('customColvisMenu');
          if(menuEl) {
              var div = document.createElement('div');
              div.innerHTML = itemHtml;
              menuEl.appendChild(div.firstElementChild);
          }
        });

        $("#customReportActions").removeClass("d-none").addClass("d-flex");
      }
    });

    $("#customBtnExcel")
      .off("click")
      .on("click", function () {
        reportTable.button(".buttons-excel").trigger();
      });
    $("#customBtnPdf")
      .off("click")
      .on("click", function () {
        reportTable.button(".buttons-pdf").trigger();
      });

    $(document)
      .off("change", ".col-visibility-trigger")
      .on("change", ".col-visibility-trigger", function () {
        var colIdx = $(this).data("column");
        reportTable.column(colIdx).visible(this.checked);
      });
  }

  // Bank List Report (#bankDataTable) advanced initialization
  if ($("#bankDataTable").length > 0) {
    console.log("Raporlar: #bankDataTable tespit edildi, başlatılıyor...");
    
    var isColReorderAvailable = ($.fn.dataTable && $.fn.dataTable.ColReorder);

    var reportBadge = $(".badge.bg-info-lt").first().text().trim() || "Banka_Raporu";
    var excelFileName = "Banka_Odeme_Listesi_" + reportBadge.replace(/\s+/g, "_");
    var pdfTitle = "Banka Ödeme Listesi - " + reportBadge;

    var commonExportOptions = {
      columns: ":visible",
      format: {
        body: function (data, row, column, node) {
          var $node = $(node);
          var nameSpan = $node.find(".fw-semibold");
          if (nameSpan.length > 0) return nameSpan.text().trim();
          var text = $node.text().trim();
          return text === "-" || text === "0" ? "" : text;
        },
        header: function (data, column, node) {
          return $(node).text().trim();
        }
      }
    };

    var bankTable = $("#bankDataTable").DataTable({
      language: { url: "src/tr.json" },
      pageLength: 50,
      responsive: false,
      scrollX: true,
      colReorder: isColReorderAvailable ? true : false,
      stateSave: true,
      pagingType: "simple_numbers",
      layout: {
        topStart: null,
        topEnd: null,
        bottomStart: ["info", "pageLength"],
        bottomEnd: "paging"
      },
      buttons: [
        {
          extend: "excelHtml5",
          className: "d-none",
          title: excelFileName,
          exportOptions: commonExportOptions
        },
        {
          extend: "pdfHtml5",
          className: "d-none",
          orientation: "landscape",
          pageSize: "A4",
          title: pdfTitle,
          exportOptions: commonExportOptions,
          customize: function (doc) {
            doc.defaultStyle.fontSize = 9;
            doc.styles.tableHeader.fontSize = 10;
            doc.styles.tableHeader.bold = true;
            doc.styles.tableHeader.fillColor = "#1e293b";
            doc.styles.tableHeader.color = "white";
            doc.styles.tableHeader.alignment = "center";
            doc.pageMargins = [20, 20, 20, 20];
            doc.content[1].table.widths = Array(
              doc.content[1].table.body[0].length + 1
            )
              .join("*")
              .split("");

            var rowCount = doc.content[1].table.body.length;
            for (var i = 1; i < rowCount; i++) {
              var rowData = doc.content[1].table.body[i];
              for (var j = 0; j < rowData.length; j++) {
                if (j === 0) {
                  rowData[j].alignment = "left";
                } else if (j === rowData.length - 1) {
                  rowData[j].alignment = "right";
                } else {
                  rowData[j].alignment = "center";
                }
                if (i % 2 === 0) rowData[j].fillColor = "#f8fafc";
              }
            }

            var objLayout = {
              hLineWidth: function (i) {
                return 0.5;
              },
              vLineWidth: function (i) {
                return 0.5;
              },
              hLineColor: function (i) {
                return "#e2e8f0";
              },
              vLineColor: function (i) {
                return "#e2e8f0";
              },
              paddingTop: function (i) {
                return 6;
              },
              paddingBottom: function (i) {
                return 6;
              }
            };
            doc.content[1].layout = objLayout;
          }
        }
      ],
      columnDefs: [{ targets: [1, 3, 4, 5], visible: false }],
      initComplete: function () {
        var api = this.api();
        console.log("Raporlar: Banka tablosu hazır, menü ve arama inşa ediliyor...");

        api.columns().every(function () {
          var column = this;
          var $header = $(column.header());
          var title = $header.text().trim();
          
          if ($header.find(".column-search-input").length === 0) {
            var headerText = $header.html();
            $header.html('<div class="d-flex flex-column">' + 
                         '<span class="mb-1">' + headerText + '</span>' + 
                         '</div>');
            
            if (title) {
              var input = $('<input type="text" class="form-control form-control-sm column-search-input" placeholder="Ara..." style="font-size: 9px; height: 20px; padding: 2px 5px; font-weight: normal; text-transform: none;" />');
              
              input.on("click", function(e) { e.stopPropagation(); });
              
              input.on("keyup change clear", function () {
                if (column.search() !== this.value) {
                  column.search(this.value).draw();
                }
              });
              $header.find(".d-flex").append(input);
            } else {
              $header.find(".d-flex").append('<div style="height: 20px;"></div>');
            }
          }
        });

        $("#customBankColvisMenu").empty();
        api.columns().every(function (idx) {
          if (idx === 0) return;
          
          var column = this;
          var title = $(column.header()).text().trim() || ("Sütun " + idx);
          var isVisible = column.visible();
          
          var itemHtml = `
              <label class="dropdown-item d-flex align-items-center cursor-pointer py-2 px-3 rounded-2" style="font-size: 0.85rem;">
                  <div class="form-check mb-0">
                      <input class="form-check-input bank-col-visibility-trigger" type="checkbox" id="bankColCheck_${idx}" data-column="${idx}" ${isVisible ? "checked" : ""}>
                      <span class="form-check-label fw-medium ms-1 text-secondary" style="user-select:none;">
                          ${title}
                      </span>
                  </div>
              </label>`;
          
          var menuEl = document.getElementById('customBankColvisMenu');
          if(menuEl) {
              var div = document.createElement('div');
              div.innerHTML = itemHtml;
              menuEl.appendChild(div.firstElementChild);
          }
        });

        $("#customBankReportActions").removeClass("d-none").addClass("d-flex");
      }
    });

    $("#customBankBtnExcel")
      .off("click")
      .on("click", function () {
        bankTable.button(".buttons-excel").trigger();
      });
    $("#customBankBtnPdf")
      .off("click")
      .on("click", function () {
        bankTable.button(".buttons-pdf").trigger();
      });

    $(document)
      .off("change", ".bank-col-visibility-trigger")
      .on("change", ".bank-col-visibility-trigger", function () {
        var colIdx = $(this).data("column");
        bankTable.column(colIdx).visible(this.checked);
      });
  }
}

if ($(".select2").length > 0) {
  $(".select2").each(function () {
    var $el = $(this);
    if ($el.hasClass('select2-hidden-accessible')) {
      return;
    }
    
    var placeholder = $el.attr('data-placeholder') || "";
    var allowClear = $el.attr('data-allow-clear') === 'true';
    
    var options = {
      placeholder: placeholder,
      allowClear: allowClear,
      width: '100%'
    };
    
    var $modal = $el.closest('.modal');
    if ($modal.length > 0) {
      options.dropdownParent = $el.parent();
    }
    
    if ($el.hasClass('islem')) {
      options.tags = true;
    }
    
    $el.select2(options);
  });
}
$(document).ready(function () {
  if ($(".summernote").length > 0) {
    var summernoteHeight = $(window).height() * 0.24; // Set height to 30% of window height
    $(".summernote").summernote({
      height: summernoteHeight,
      fontNames: [
        "inter",
        "Arial",
        "Arial Black",
        "Comic Sans MS",
        "Courier New"
      ],
      addDefaultFonts: "inter",
      callbacks: {
        onInit: function () {
          $(".summernote").summernote("height", summernoteHeight);
          $(".summernote").summernote("fontName", "inter");
        }
      }
    });
  }
});

if ($(".flatpickr").length > 0) {
  $(".flatpickr").flatpickr({
    dateFormat: "d.m.Y",
    locale: "tr" // locale for this instance only
  });
}

function formatNumber(num) {
  return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
}

$(document).on("click", ".route-link", function () {
  var page = $(this).data("page");
  var link = "index.php?p=" + page;

  window.location = link;
});


function dtSearchInput(tableId, column, value) {}

//Geri dönüş yapmadan kayıt silme işlemi
function deleteRecord(
  button = this,
  action = null,
  confirmMessage = "Kayıt silinecektir!",
  url = "/api/ajax.php"
) {
  // Butonun bulunduğu satırın referansını al
  var row = $(button).closest("tr");

  //Tablo adı butonun içinde bulunduğu tablo
  var tableName = $(button).closest("table")[0].id;
  var table = $("#" + tableName).DataTable();

  var tableRow = table.row(row);

  var id = $(button).data("id");

  //formData objesi oluştur
  const formData = new FormData();
  //formData objesine action ve id elemanlarını ekle
  formData.append("action", action);
  formData.append("id", id);
  formData.append("csrf_token", document.querySelector('meta[name="csrf-token"]')?.content || "");

  // console.log(url);

  AlertConfirm(confirmMessage).then((result) => {
    fetch(url, {
      method: "POST",
      body: formData
    })
      //Gelen yanıtı json'a çevir
      .then((response) => response.json())

      //Sonuc olumlu ise success toast mesajı göster
      .then((data) => {
        // console.log(data);

        if (data.status == "success") {
          title = "Başarılı!";
          icon = "success";
        } else {
          title = "Hata!";
          icon = "error";
        }
        Swal.fire({
          title: title,
          html: data.message,
          icon: icon
        }).then((result) => {
          if (result.isConfirmed) {
            if (data.status == "success") tableRow.remove().draw(false);
            return data;
          }
        });
        // createToast("success", data.message);
      })

      //Sonuc olumsuz ise error toast mesajı göster
      .catch((error) => alert("Error deleting : " + error));
  });
}

//Geri dönüş yaparak kayıt silme işlemi
async function deleteRecordByReturn(
  button,
  action = null,
  confirmMessage = "Kayıt silinecektir!",
  url = "/api/ajax.php"
) {
  // Butonun bulunduğu satırın referansını al
  var row = $(button).closest("tr");

  //Tablo adı butonun içinde bulunduğu tablo
  var tableName = $(button).closest("table")[0].id;
  var table = $("#" + tableName).DataTable();

  var tableRow = table.row(row);

  var id = $(button).data("id");

  //formData objesi oluştur
  const formData = new FormData();
  //formData objesine action ve id elemanlarını ekle
  formData.append("action", action);
  formData.append("id", id);

  const result = await AlertConfirm(confirmMessage);
  if (result) {
    try {
      const response = await fetch(url, {
        method: "POST",
        body: formData
      });
      const data = await response.json();

      let title, icon;
      if (data.status == "success") {
        title = "Başarılı!";
        icon = "success";
      } else {
        title = "Hata!";
        icon = "error";
      }

      await Swal.fire({
        title: title,
        text: data.message,
        icon: icon
      });

      if (data.status == "success") {
        tableRow.remove().draw(false);
      }

      return data;
    } catch (error) {
      console.error("Error deleting:", error);
      return { status: "error", message: "Bir hata oluştu." };
    }
  }
}

function AlertConfirm(confirmMessage = "Emin misiniz?") {
  return new Promise((resolve, reject) => {
    Swal.fire({
      title: "Emin misiniz?",
      html: confirmMessage,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Evet, Sil!"
    }).then((result) => {
      if (result.isConfirmed) {
        resolve(true); // Kullanıcı onayladı, işlemi devam ettir
      } else {
        reject(false); // Kullanıcı onaylamadı, işlemi durdur
      }
    });
  });
}

$(document).on("change", "#myFirm", function () {
  var page = new URLSearchParams(window.location.search).get("p");
  window.location = "set-session.php?p=" + page + "&firm_id=" + $(this).val();
});

// function fadeOut(element, duration) {
//   var op = 1; // Opaklık başlangıç değeri
//   var interval = 50; // Milisaniye cinsinden aralık
//   var delta = interval / duration; // Her adımda azaltılacak opaklık miktarı

//   function reduceOpacity() {
//     op -= delta;
//     if (op <= 0) {
//       op = 0;
//       element.style.display = "none"; // Elementi gizle
//       clearInterval(fading); // Animasyonu durdur
//     }
//     element.style.opacity = op;
//   }

//   var fading = setInterval(reduceOpacity, interval);
// }

//İl seçildiğinde ilçeleri getir
function getTowns(cityId, targetElement) {
  var formData = new FormData();
  formData.append("city_id", cityId);
  formData.append("action", "getTowns");

  fetch("/api/il-ilce.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      let towns = data.towns;
      $(targetElement).html(towns);
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

//Personeli kaydedip kaydetmediğimize bakarız
function checkPersonId(id) {
  if (id == 0) {
    swal.fire({
      title: "Hata",
      icon: "warning",
      text: "Öncelikle personeli kaydetmeniz gerekir!"
    });
    return false;
  }
  return true;
}
//Personeli kaydedip kaydetmediğimize bakarız
function checkId(id, item) {
  if (id == 0) {
    swal.fire({
      title: "Hata",
      icon: "warning",
      text: "Öncelikle " + item + " kaydetmeniz gerekir!"
    });
    return false;
  }
  return true;
}

// Sayfanın herhangi bir yerine tıklandığında fab menüyü kapat
document.addEventListener("click", function (event) {
  if (event.target.closest(".fab-menu") === null) {
    const fabOptions = document.getElementById("fab-options");
    const mainIcon = document.getElementById("main-icon");
    const closeIcon = document.getElementById("close-icon");

    if (fabOptions.style.display === "block") {
      fabOptions.classList.remove("show");
      setTimeout(() => {
        fabOptions.style.display = "none";
      }, 300);
      mainIcon.style.opacity = 1;
      closeIcon.style.opacity = 0;
    }
  }
});

function toggleFabMenu() {
  const fabOptions = document.getElementById("fab-options");
  const mainIcon = document.getElementById("main-icon");
  const closeIcon = document.getElementById("close-icon");

  if (fabOptions.style.display === "none" || fabOptions.style.display === "") {
    fabOptions.style.display = "block";
    setTimeout(() => {
      fabOptions.classList.add("show");
    }, 10);
    mainIcon.style.opacity = 0;
    closeIcon.style.opacity = 1;
  } else {
    fabOptions.classList.remove("show");
    setTimeout(() => {
      fabOptions.style.display = "none";
    }, 300);
    mainIcon.style.opacity = 1;
    closeIcon.style.opacity = 0;
  }
}

function goWhatsApp() {
  const phoneNumber = "905079432723";
  const message = encodeURIComponent("Merhaba, Teknik desteğe ihtiyacım var");
  const url = `https://wa.me/send?phone=${phoneNumber}&text=${message}`;
  window.open(url, "_blank");
}

function previewImage(event) {
  var reader = new FileReader();
  reader.onload = function () {
    var output = document.querySelector(".brand-img img");
    output.src = reader.result;
  };
  reader.readAsDataURL(event.target.files[0]);
}

//para birimi mask
if ($(".money").length > 0) {
  //1.234,52 şeklinden regex yaz
  //$(".money").inputmask("9-a{1,3}9{1,3}"); //mask with dynamic syntax

  $(".money").inputmask("decimal", {
    radixPoint: ",",
    groupSeparator: ".",
    digits: 2,
    autoGroup: true,
    rightAlign: false,
    removeMaskOnSubmit: true
  });

  $(document).on("focus", ".money", function () {
    $(this).inputmask("decimal", {
      radixPoint: ",",
      groupSeparator: ".",
      digits: 2,
      autoGroup: true,
      rightAlign: false,
      removeMaskOnSubmit: true
    });
  });
  //Para birimi olan alanlarda virgülü noktaya çevir
  // $('.money').on('keyup', function () {
  //   var value = $(this).val();
  //   var value = value.replace(/,/g, '.');
  //   $(this).val(value);
  // });
}

//Jquery validate ile yapılan doğrulamalarda para birimi formatı için
function addCustomValidationMethods() {
  $.validator.addMethod(
    "validNumber",
    function (value, element) {
      return this.optional(element) || (/^[0-9.,]+$/.test(value) && parseFloat(value.replace(",", ".")) > 0);
    },
    "Lütfen geçerli bir sayı girin ve 0'dan büyük bir değer girin"
  );
}

//Jquery validate ile yapılan doğrulamalarda 0 olan değeri kabul etmemek için
function addCustomValidationValidValue() {
  $.validator.addMethod(
    "validValue",
    function (value, element) {
      return (
        this.optional(element) || parseFloat(value.replace(",", ".")) !== 0
      );
    },
    "Lütfen geçerli bir değer girin"
  );
}


    // let ec = new EventCalendar(document.getElementById('ec'), {
    //     view: 'dayGridMonth',
    //     selectable:true,
    //     events: [
    //         event = {
    //             title: 'Yapılacak İş',
    //             start: '2025-01-03',
    //             end: '2025-01-07',
    //             color: 'red'
    //         },
    //     ]
    // });


// Puantaj tablosunu manuel sıralamak için global fonksiyon
window.sortPuantaj = function(colIndex) {
    var table = $('#puantajTable').DataTable();
    var currentOrder = table.order()[0];
    var dir = 'asc';
    
    if (currentOrder && currentOrder[0] === colIndex) {
        dir = currentOrder[1] === 'asc' ? 'desc' : 'asc';
    }
    
    // Sıralamayı uygula
    table.order([colIndex, dir]).draw();
    
    // Görsel sınıfları manuel yönet (DataTables bazen gecikebiliyor)
    $('#puantajTable thead tr:eq(0) th').removeClass('sorting_asc sorting_desc');
    var activeTh = $('#puantajTable thead tr:eq(0) th').eq(colIndex);
    activeTh.addClass(dir === 'asc' ? 'sorting_asc' : 'sorting_desc');
};

// Global Dönem Seçimi Otomatik Yenileme
$(document).ready(function() {
    $(document).on('change select2:select', '#year, #months, select[name="year"], select[name="months"]', function() {
        var form = $(this).closest('form');
        if(form.length > 0) {
            if (form.attr('id') === 'puantajInfoForm') {
                return; // Let puantaj.js handle this dynamically via AJAX
            }
            form.submit();
        }
    });
});

/**
 * Merkezi DataTable fabrika fonksiyonu.
 * Ortak davranışları (arama satırı, dil, layout, initComplete) otomatik ekler.
 * Sadece değişen kısımları (ajax, columns vb.) parametre olarak geçin.
 *
 * Desteklenen ek seçenekler:
 *   skipSearch: string[] — arama kutusu eklenmeyecek sütun başlıkları (varsayılan: ['İşlem', 'Seç'])
 *   initComplete: function — kendi initComplete callback'iniz; arama satırı eklendikten SONRA çağrılır
 *
 * Diğer tüm DataTables seçenekleri doğrudan geçilebilir (ordering, ajax, columns, …)
 */
window.createDataTable = function (selector, userOptions) {
    userOptions = userOptions || {};

    var $table = $(selector);
    if (!$table.length || !$.fn.DataTable) return null;

    var skipTitles    = userOptions.skipSearch || ['İşlem', 'İşlemler', 'Seç', 'Aksiyon', 'Aksiyonlar'];
    var skipTitlesLower = skipTitles.map(function(s) { return s.toLowerCase(); });
    var userInitDone  = userOptions.initComplete || null;

    var $thead = $table.find('thead');
    if ($thead.find('.search-input-row').length === 0) {
        var colCount    = $thead.find('tr:first th, tr:first td').length;
        var $searchRow  = $('<tr class="search-input-row"></tr>');
        for (var c = 0; c < colCount; c++) {
            $searchRow.append('<th class="search p-1"></th>');
        }
        $thead.append($searchRow);
    }

    var config = $.extend({
        ordering:      false,
        orderCellsTop: true,
        autoWidth:     false,
        pagingType:    'simple_numbers',
        language:      { url: 'src/tr.json' },
        layout: {
            bottomStart: ['info', 'pageLength'],
            bottomEnd: 'paging',
            topStart: null,
            topEnd: null,
        },
    }, userOptions);

    config.initComplete = function (settings, json) {
        var api     = this.api();
        var tableId = settings.sTableId;

        api.columns().every(function () {
            var column     = this;
            var colIdx     = column.index();
            var $headerTh  = $($table.find('thead tr:first th').get(colIdx));
            var title      = $headerTh.text().trim();
            var titleLower = title.toLowerCase();

            if (!title || skipTitles.indexOf(title) !== -1 || skipTitlesLower.indexOf(titleLower) !== -1 || $headerTh.find('input[type="checkbox"]').length > 0) return;

            var input = document.createElement('input');
            input.type = 'search';
            input.placeholder = title;
            input.classList.add('form-control', 'form-control-sm');
            input.setAttribute('autocomplete', 'search');
            input.setAttribute('name', 'dt_filter_' + tableId + '_' + colIdx);
            input.setAttribute('data-lpignore', 'true');
            input.setAttribute('data-1p-ignore', 'true');
            input.setAttribute('data-bwignore', 'true');

            var $searchTh = $('#' + tableId + ' .search-input-row th:eq(' + colIdx + ')');
            $searchTh.html(input);

            // Copy style attributes (like width, max-width) from the header cell to search cell
            var styleAttr = $headerTh.attr('style');
            if (styleAttr) {
                $searchTh.attr('style', styleAttr);
            }

            // Force input to fill 100% of the cell width and allow shrinking (min-width: 0)
            input.setAttribute('style', 'width: 100% !important; min-width: 0 !important; max-width: 100% !important;');

            $(input).on('keyup change search', function () {
                if (column.search() !== this.value) {
                    column.search(this.value).draw();
                }
            });
        });

        // topStart/topEnd: null gibi tümüyle boş bırakılan layout satırlarını gizle
        // (DataTables bu satırları içerik olmasa da DOM'da bırakabiliyor, gereksiz boşluğa sebep oluyor)
        $table.closest('.dt-container').find('.dt-layout-row').not('.dt-layout-table').each(function () {
            var $row = $(this);
            var hasContent = $row.find('.dt-layout-cell').toArray().some(function (cell) {
                return $(cell).children().length > 0 || $.trim($(cell).text()) !== '';
            });
            if (!hasContent) {
                $row.addClass('dt-empty-row');
            }
        });

        if (typeof userInitDone === 'function') {
            userInitDone.call(this, settings, json);
        }

        if (typeof window.autoAdjustTableHeights === 'function') {
            setTimeout(window.autoAdjustTableHeights, 50);
        }
    };

    delete config.skipSearch;

    return $table.DataTable(config);
};

// Dynamic Seamless Animated Theme Switcher (No Page Reload & GPU Accelerated 60fps)
$(document).on('click', '.js-theme-toggle, a[aria-label="Enable dark mode"], a[aria-label="Enable light mode"]', function(e) {
    e.preventDefault();

    var currentTheme = document.body.getAttribute('data-bs-theme') || 'light';
    var targetTheme = currentTheme === 'dark' ? 'light' : 'dark';

    var applyThemeChange = function() {
        document.body.setAttribute('data-bs-theme', targetTheme);

        // Update tooltip titles
        if (targetTheme === 'dark') {
            $('.hide-theme-dark').attr('aria-label', 'Enable light mode').attr('data-bs-original-title', 'Enable light mode');
        } else {
            $('.hide-theme-light').attr('aria-label', 'Enable dark mode').attr('data-bs-original-title', 'Enable dark mode');
        }
    };

    if (document.startViewTransition) {
        // Native GPU cross-fade without layout reflows
        document.startViewTransition(function() {
            applyThemeChange();
        });
    } else {
        // High performance CSS fallback for major containers
        document.body.classList.add('theme-transitioning');
        applyThemeChange();
        setTimeout(function() {
            document.body.classList.remove('theme-transitioning');
        }, 300);
    }

    // Persist preference to session silently via API
    $.ajax({
        url: 'api/set-theme.php',
        type: 'GET',
        data: { theme: targetTheme },
        dataType: 'json'
    });
});

/**
 * Tüm DataTable örnekleri için merkezi boş durum görünümü.
 * Gerçekten boş tablolar ile aktif arama/filtre sonucu boş kalan tabloları ayırır.
 */
window.renderDataTableEmptyStates = function(table) {
    var $scope = table ? $(table) : $('table.dataTable');

    $scope.each(function() {
        var tableNode = this;
        if (!$.fn.DataTable || !$.fn.DataTable.isDataTable(tableNode)) return;

        var settings = $(tableNode).DataTable().settings()[0];
        var $emptyCell = $(tableNode).find('tbody td.dt-empty, tbody td.dataTables_empty');
        if (!$emptyCell.length) return;

        var recordsTotal = typeof settings.fnRecordsTotal === 'function'
            ? settings.fnRecordsTotal()
            : (settings._iRecordsTotal || 0);
        var recordsDisplay = typeof settings.fnRecordsDisplay === 'function'
            ? settings.fnRecordsDisplay()
            : (settings._iRecordsDisplay || 0);
        var isFilteredEmpty = recordsTotal > 0 && recordsDisplay === 0;
        var icon = isFilteredEmpty ? 'ti-search' : 'ti-inbox';
        var title = isFilteredEmpty ? 'Sonuç bulunamadı' : 'Henüz kayıt bulunmuyor';
        var description = isFilteredEmpty
            ? 'Arama veya filtre ölçütlerinizi değiştirin.<br>Farklı kriterlerle tekrar deneyebilirsiniz.'
            : 'Bu listede henüz görüntülenecek bir kayıt yok.<br>Yeni kayıtlar eklendiğinde burada listelenecek.';

        $emptyCell.html(
            '<div class="dt-empty-state" role="status">' +
                '<div class="dt-empty-state-icon" aria-hidden="true">' +
                    '<i class="ti ' + icon + '"></i>' +
                '</div>' +
                '<div class="dt-empty-state-title">' + title + '</div>' +
                '<div class="dt-empty-state-description">' + description + '</div>' +
            '</div>'
        );
    });
};

/**
 * Otomatik DataTable Yüksekliği Ayarlayıcı
 * Her tablonun sayfadaki gerçek konumuna göre veri alanının dikey yüksekliğini hesaplar.
 * Başlığı ve bilgi/sayfalama footer'ını kaydırma alanının dışında görünür tutar.
 */
window.autoAdjustTableHeights = function() {
    var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
    var bottomGap = 20;
    var minimumBodyHeight = 100;

    $('div.dt-container').each(function() {
        var $container = $(this);
        var tableId = $container.find('table.dataTable').first().attr('id') || '';
        var tableBottomGap = (tableId === 'persons' || tableId === 'bordroTable') ? 10 : bottomGap;

        // Modal içindeki küçük/seçim tabloları kendi tanımlı ölçülerini kullanmaya devam etsin.
        if (!$container.is(':visible') || $container.closest('.modal').length) return;

        var $tableRow = $container.children('.dt-layout-row.dt-layout-table').first();
        if (!$tableRow.length) return;

        // scrollX kullanan DataTables başlığı zaten ayrı üretir. Diğer tablolarda
        // tüm layout-table alanı kayar ve thead hücreleri sticky tutulur.
        var $dataTablesScrollBody = $tableRow.find('.dt-scroll-body').first();
        var $scrollArea = $dataTablesScrollBody.length ? $dataTablesScrollBody : $tableRow;
        var scrollElement = $scrollArea.get(0);
        if (!scrollElement) return;

        var scrollTop = scrollElement.getBoundingClientRect().top;
        var footerHeight = 0;

        // layout-table'dan sonra gelen tüm bilgi/sayfalama satırlarını hesaba kat.
        $tableRow.nextAll('.dt-layout-row:visible').each(function() {
            footerHeight += this.getBoundingClientRect().height;
        });

        var bodyHeight = Math.floor(viewportHeight - scrollTop - footerHeight - tableBottomGap);

        if (bodyHeight < minimumBodyHeight) {
            $container.removeClass('dt-viewport-managed')
                .css('--dt-viewport-body-height', '');
            $scrollArea.removeClass('dt-viewport-scroll');
            $container.closest('.table-responsive').removeClass('dt-viewport-host');
            return;
        }

        $container.addClass('dt-viewport-managed')
            .css('--dt-viewport-body-height', bodyHeight + 'px');
        $scrollArea.addClass('dt-viewport-scroll');
        $container.closest('.table-responsive').addClass('dt-viewport-host');

        // Sticky başlığın her satırı, kendinden önceki başlık satırlarının
        // toplam yüksekliği kadar aşağıda kalır (normal başlık + arama satırı vb.).
        if (!$dataTablesScrollBody.length) {
            var stickyTop = 0;
            $scrollArea.find('table.dataTable > thead > tr').each(function() {
                $(this).children('th, td').css('--dt-sticky-top', stickyTop + 'px');
                stickyTop += this.getBoundingClientRect().height;
            });
            $container.css('--dt-empty-header-offset', stickyTop + 'px');
        } else {
            $container.css('--dt-empty-header-offset', '0px');
        }
    });
};

var tableHeightTimer = null;
window.scheduleTableHeightAdjustment = function(delay) {
    clearTimeout(tableHeightTimer);
    tableHeightTimer = setTimeout(window.autoAdjustTableHeights, delay || 0);
};

$(document).ready(function() {
    window.renderDataTableEmptyStates();
    window.scheduleTableHeightAdjustment();
    window.scheduleTableHeightAdjustment(150);
    setTimeout(window.autoAdjustTableHeights, 500);
});

$(window).on('resize orientationchange', function() {
    window.scheduleTableHeightAdjustment(50);
});

$(document).on('shown.bs.tab shown.bs.collapse hidden.bs.collapse closed.bs.alert', function() {
    window.scheduleTableHeightAdjustment(50);
});

$(document).ajaxComplete(function() {
    window.scheduleTableHeightAdjustment(150);
});

$(document).on('init.dt draw.dt', function(e, settings) {
    window.renderDataTableEmptyStates(settings.nTable);
});

$(document).on('init.dt draw.dt column-visibility.dt responsive-display.dt', function() {
    window.scheduleTableHeightAdjustment(30);
});
