$("#projects").on("change", function () {
  Route();
});

$("#team_id").on("change", function () {
  Route();
});

// Yıl değiştiği zaman sayfayı yeniden yükle
$("#year").on("change", function () {
  Route();
});

// Ay değiştiği zaman sayfayı yeniden yükle
$("#months").on("change", function () {
  Route();
});

function Route() {
  var form = $("#bordroInfoForm");
  form.find("input[name='action']").remove();
  form.submit();
}

// Bordro hesapla butonuna tıklandığında
$(document).on("click", "#payroll_calculate", function (e) {
  e.preventDefault();
  let form = $("#bordroInfoForm");
  form.find("input[name='action']").remove();
  form.append('<input type="hidden" name="action" value="payroll_calculate">');
  form.submit();
});

// Personelleri güncelle butonuna tıklandığında
$(document).on("click", "#update_personnel", function (e) {
  e.preventDefault();
  let form = $("#bordroInfoForm");
  form.find("input[name='action']").remove();
  form.append('<input type="hidden" name="action" value="update_personnel">');
  form.submit();
});

// Özet kartları daraltma/genişletme
$(document).ready(function() {
  var $summaryToggle = $('#togglePayrollSummary');

  function syncSummaryToggle() {
    var isCollapsed = document.documentElement.classList.contains('payroll-summary-collapsed');
    $summaryToggle
      .attr('aria-expanded', String(!isCollapsed))
      .attr('aria-label', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle')
      .attr('title', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle');
    $summaryToggle.find('i')
      .toggleClass('ti-chevron-up', !isCollapsed)
      .toggleClass('ti-chevron-down', isCollapsed);
  }

  syncSummaryToggle();

  $summaryToggle.on('click', function() {
    var isCollapsed = document.documentElement.classList.toggle('payroll-summary-collapsed');
    try {
      localStorage.setItem('payroll_summary_collapsed', isCollapsed ? '1' : '0');
    } catch (e) {}
    syncSummaryToggle();
  });
});

// Checkbox tümünü seç / kaldır
$(document).on("change", ".select-all-payrolls", function () {
  var checked = $(this).is(":checked");
  $(".payroll-row-check").prop("checked", checked);
});

$(document).on("change", ".payroll-row-check", function () {
  var total = $(".payroll-row-check").length;
  var checked = $(".payroll-row-check:checked").length;
  $(".select-all-payrolls").prop("checked", total > 0 && total === checked);
});

// Toplu bordro yazdırma
function openBulkPrint(ids) {
  var m = $("#months").val() || "";
  var y = $("#year").val() || "";
  window.open("index.php?p=raporlar/bordro-yazdir&ids=" + ids.join(",") + "&month=" + encodeURIComponent(m) + "&year=" + encodeURIComponent(y), "_blank");
}

$(document).on("click", "#btnPrintBulkPayrolls", function (e) {
  e.preventDefault();
  var selectedIds = [];
  $(".payroll-row-check:checked").each(function () {
    selectedIds.push($(this).val());
  });

  if (selectedIds.length === 0) {
    if (typeof Swal !== "undefined") {
      Swal.fire({
        title: "Toplu Bordro Yazdır",
        text: "Hiçbir personel seçmediniz. Tüm listedeki personellerin bordrolarını yazdırmak istiyor musunuz?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#1e293b",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Evet, Tümünü Yazdır",
        cancelButtonText: "Vazgeç"
      }).then(function (result) {
        if (result.isConfirmed) {
          var allIds = [];
          $(".payroll-row-check").each(function () {
            allIds.push($(this).val());
          });
          if (allIds.length === 0) {
            Swal.fire("Uyarı", "Yazdırılacak personel bulunamadı.", "warning");
            return;
          }
          openBulkPrint(allIds);
        }
      });
    } else {
      var allIds = [];
      $(".payroll-row-check").each(function () {
        allIds.push($(this).val());
      });
      if (allIds.length > 0) openBulkPrint(allIds);
    }
  } else {
    openBulkPrint(selectedIds);
  }
});

let payrollDetailRequest = null;

function payrollDetailLoading() {
  return `
    <div class="d-flex flex-column align-items-center justify-content-center py-5" style="min-height: 320px;">
      <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Yükleniyor</span></div>
      <div class="fw-medium mt-3">Bordro detayları hazırlanıyor</div>
      <div class="text-muted small mt-1">Lütfen kısa bir süre bekleyin...</div>
    </div>`;
}

$(document).on("keydown", ".view-payroll-detail", function (event) {
  if (event.key === "Enter" || event.key === " ") {
    event.preventDefault();
    $(this).trigger("click");
  }
});

$(document).on("click", ".view-payroll-detail", function () {
  let id = $(this).data("id");
  let month = $(this).data("month");
  let year = $(this).data("year");
  $("#payroll-detail-modal").data("detail-trigger", this);

  if (payrollDetailRequest) {
    payrollDetailRequest.abort();
  }

  $("#payroll-detail-period").text("Gelir, kesinti ve puantaj dökümü");
  $("#payroll-detail-content").html(payrollDetailLoading());
  $("#print-detailed-payroll").prop("disabled", true);

  payrollDetailRequest = $.ajax({
    url: "api/bordro/detail.php",
    type: "POST",
    data: {
      id: id,
      month: month,
      year: year
    },
    success: function (data) {
      $("#payroll-detail-content").html(data);
      $("#print-detailed-payroll").prop("disabled", false);
    },
    error: function (_xhr, status) {
      if (status === "abort") return;

      $("#payroll-detail-content").html(`
        <div class="d-flex flex-column align-items-center justify-content-center text-center py-5" style="min-height: 280px;">
          <span class="avatar avatar-lg bg-danger-lt text-danger mb-3"><i class="ti ti-alert-triangle fs-1"></i></span>
          <h3 class="mb-1">Detaylar yüklenemedi</h3>
          <p class="text-muted mb-3">Bağlantınızı kontrol edip yeniden deneyin.</p>
          <button type="button" class="btn btn-outline-primary" id="retry-payroll-detail"><i class="ti ti-refresh me-2"></i>Yeniden Dene</button>
        </div>`);
    },
    complete: function () {
      payrollDetailRequest = null;
    }
  });
});

$(document).on("click", "#retry-payroll-detail", function () {
  const trigger = $("#payroll-detail-modal").data("detail-trigger");
  if (trigger) $(trigger).trigger("click");
});

$("#payroll-detail-modal").on("hidden.bs.modal", function () {
  if (payrollDetailRequest) {
    payrollDetailRequest.abort();
    payrollDetailRequest = null;
  }

  $("#payroll-detail-content [data-bs-toggle='popover']").each(function () {
    const popover = bootstrap.Popover.getInstance(this);
    if (popover) popover.dispose();
  });
  $("#print-detailed-payroll").prop("disabled", true);
});

$(document).on("click", ".delete-payroll-transaction", async function () {
  const button = this;
  const label = $(button).data("label") || "Bu bordro hareketi";

  const confirmation = await Swal.fire({
    icon: "warning",
    title: "Hareket silinsin mi?",
    text: label + " kalıcı olarak silinecek.",
    showCancelButton: true,
    confirmButtonColor: "#d63939",
    cancelButtonText: "Vazgeç",
    confirmButtonText: "Evet, sil"
  });

  if (!confirmation.isConfirmed) return;

  const formData = new FormData();
  formData.append("id", $(button).data("id"));
  formData.append("source", $(button).data("source"));
  formData.append("month", $(button).data("month"));
  formData.append("year", $(button).data("year"));

  $(button).prop("disabled", true);

  try {
    const response = await fetch("api/bordro/delete-transaction.php", {
      method: "POST",
      body: formData
    });
    const result = await response.json();

    if (!response.ok || result.status !== "success") {
      throw new Error(result.message || "Bordro hareketi silinemedi.");
    }

    const formatPayrollMoney = function (amount) {
      return new Intl.NumberFormat("tr-TR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount) + " ₺";
    };

    const setPayrollSummaryAmount = function (selector, amount) {
      const normalizedAmount = Math.abs(amount) < 0.005 ? 0 : amount;
      $(selector)
        .attr("data-amount", normalizedAmount)
        .text(formatPayrollMoney(normalizedAmount));
      return normalizedAmount;
    };

    let totalIncome = Number($("#payroll-total-income").attr("data-amount")) || 0;
    let totalExpense = Number($("#payroll-total-expense").attr("data-amount")) || 0;
    const deletedAmount = Number(result.amount) || 0;

    if (result.transaction_kind === "income") {
      totalIncome = Math.max(0, totalIncome - deletedAmount);
    } else {
      totalExpense = Math.max(0, totalExpense - deletedAmount);
    }

    totalIncome = setPayrollSummaryAmount("#payroll-total-income", totalIncome);
    totalExpense = setPayrollSummaryAmount("#payroll-total-expense", totalExpense);
    setPayrollSummaryAmount("#payroll-total-net", totalIncome - totalExpense);

    const detailTrigger = $("#payroll-detail-modal").data("detail-trigger");
    if (detailTrigger) {
      $(detailTrigger).trigger("click");
    }

    if ($.fn.DataTable && $.fn.DataTable.isDataTable("#bordroTable")) {
      $("#bordroTable").DataTable().ajax.reload(null, false);
    }

    await Swal.fire({
      icon: "success",
      title: "Silindi",
      text: result.message,
      timer: 1200,
      showConfirmButton: false
    });
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Hata",
      text: error.message || "Bordro hareketi silinemedi."
    });
    $(button).prop("disabled", false);
  }
});

// Bordro detayını yazdır
$(document).on("click", "#print-detailed-payroll", function () {
  let $content = $("#payroll-detail-content").clone();
  $content.find("script, .no-print").remove();
  $content.find("#puantaj-list-view").show();
  $content.find("#puantaj-calendar-view").hide();
  $content.find(".empty-puantaj-record").show();
  let content = $content.html();
  let printWindow = window.open('', '', 'height=600,width=800');

  if (!printWindow) {
    Swal.fire({ icon: 'warning', title: 'Yazdırma penceresi açılamadı', text: 'Tarayıcınızın açılır pencere iznini kontrol edin.' });
    return;
  }

  printWindow.document.write('<html><head><title>Bordro Detayı</title>');
  printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css">');
  printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">');
  printWindow.document.write('<style>@page{size:A4;margin:12mm}body{font-size:11px}.payroll-attendance-scroll{max-height:none!important;overflow:visible!important}.card{break-inside:avoid}.row{--tblr-gutter-x:.75rem;--tblr-gutter-y:.75rem}</style>');
  printWindow.document.write('</head><body class="p-4">');
  printWindow.document.write(content);
  printWindow.document.write('</body></html>');
  printWindow.document.close();
  printWindow.focus();
  setTimeout(() => {
    printWindow.print();
  }, 500);
});

// Bordro DataTable ve Sütun Görünürlüğü
$(document).ready(function () {
  if (!$('#bordroTable').length) return;

  // Varsa eski arama satırlarını DOM'dan temizle
  $('#bordroTable thead .search-input-row').remove();

  var table = $('#bordroTable').DataTable({
    autoWidth: false,
    colReorder: true,
    ordering: true,
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    order: [[1, 'asc']],
    columnDefs: [
      { targets: [0, 13], orderable: false, searchable: false },
      { targets: [0, 1], className: 'text-center' },
      { targets: 6, width: '125px', className: 'text-truncate' },
      { targets: [9, 10, 11, 12], className: 'text-end' },
      { targets: 13, width: '95px', className: 'text-end no-export actions-column' }
    ],
    language: {
      url: 'src/tr.json'
    },
    initComplete: function () {
      var api = this.api();
      if (typeof window.initDataTableColumnFilters === 'function') {
        window.initDataTableColumnFilters($('#bordroTable'), api);
      }
      if (typeof window.initPuantorDTManager === 'function') {
        window.initPuantorDTManager($('#bordroTable'), api);
      }
    },
    drawCallback: function () {
      $('.select-all-payrolls').prop('checked', false);
    }
  });

  // Hızlı Genel Arama
  var searchTimer = null;
  $('#payroll-fast-search').on('input', function () {
    var val = this.value;
    $('#payroll-search-clear').toggleClass('d-none', val.length === 0);
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
      table.search(val).draw();
    }, 300);
  });

  $('#payroll-search-clear').on('click', function () {
    clearTimeout(searchTimer);
    $('#payroll-fast-search').val('').trigger('focus');
    $(this).addClass('d-none');
    table.search('').draw();
  });

  function renderBordroColvisMenu() {
    var skipTitles = ['İşlem', 'İşlemler', 'Seç', 'Aksiyon', 'Aksiyonlar', 'Sıra', '#'];
    var menuHtml = '';
    var settings = table ? table.settings()[0] : null;
    if (!settings || !settings.aoColumns) return;

    settings.aoColumns.forEach(function (colConfig, idx) {
      var $th = $(colConfig.nTh);
      var origIdx = colConfig._crOriginalIdx !== undefined ? colConfig._crOriginalIdx : idx;
      
      // Başlık metnini temizle
      var title = colConfig.sTitle || $th.find('.dt-header-title').text().trim() || $th.clone().find('.dt-col-filter-btn, .dt-column-order, .dt-col-resizer, input, button').remove().end().text().trim();
      title = title.replace(/\s+/g, ' ').trim();

      if ($th.hasClass('no-export') || $th.hasClass('actions-column') || $th.find('input[type="checkbox"]').length > 0 || !title) {
        return;
      }
      if (skipTitles.indexOf(title) !== -1) return;

      var isVisible = colConfig.bVisible !== false;
      menuHtml += `
        <label class="dropdown-item d-flex align-items-center cursor-pointer py-1.5 px-3 rounded-2" style="font-size:0.85rem;">
          <div class="form-check mb-0 w-100">
            <input class="form-check-input bordro-col-trigger" type="checkbox" id="bordroColCheck_${origIdx}" data-column="${origIdx}" data-orig-idx="${origIdx}" ${isVisible ? 'checked' : ''}>
            <span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">${title}</span>
          </div>
        </label>`;
    });

    menuHtml += `
      <div class="dropdown-divider my-1"></div>
      <button type="button" class="dropdown-item text-danger py-1.5 px-3 rounded-2" id="resetBordroTableColumnsBtn" style="font-size: 0.8rem;">
        <i class="ti ti-rotate-2 me-1"></i> Görünümü Sıfırla
      </button>
    `;

    $('#bordroColvisMenu').html(menuHtml);
  }

  // Sayfa açılışında ve menü her tıklandığında anında render et
  renderBordroColvisMenu();
  $('#colvisDropdownBtn').on('click', function () {
    renderBordroColvisMenu();
  });
  $('#colvisDropdownBtn').parent().on('show.bs.dropdown', function () {
    renderBordroColvisMenu();
  });

  // Görünümü Sıfırla Butonu
  $(document).on('click', '#resetBordroTableColumnsBtn', function(e) {
    e.preventDefault();
    if (typeof window.resetPuantorDTState === 'function') {
      window.resetPuantorDTState($('#bordroTable'), table, function() {
        table.columns().visible(true, true);
        table.columns.adjust().draw(false);
        renderBordroColvisMenu();
      });
    }
  });

  $(document).off('click.bordroCol').on('click.bordroCol', '#bordroColvisMenu', function (e) {
    e.stopPropagation();
  });

  // Tabloda Sağ Tık (Custom Context Menu)
  $(document).on('contextmenu', '#bordroTable tbody tr', function (e) {
    var $tr = $(this);
    var id = $tr.attr('data-id');
    if (!id) return;

    e.preventDefault();
    $('#bordroTable tbody tr').removeClass('context-menu-active');
    $tr.addClass('context-menu-active');

    var personName = $tr.attr('data-person-name') || 'Personel İşlemleri';
    var iban = $tr.attr('data-iban') || '';
    var balance = $tr.attr('data-balance') || '0,00 ₺';
    var balanceRaw = parseFloat($tr.attr('data-balance-raw') || 0);
    var month = $tr.attr('data-month') || '';
    var year = $tr.attr('data-year') || '';
    var projectId = $tr.attr('data-project-id') || '0';
    var canPay = $tr.attr('data-can-pay') === '1';
    var canIncome = $tr.attr('data-can-income') === '1';

    var $contextMenu = $('#customContextMenu');
    if (!$contextMenu.length) {
      $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
    }

    var safePersonName = $('<div>').text(personName).html();
    var safeBalance = $('<div>').text(balance).html();
    var safeIban = $('<div>').text(iban).html();

    var menuHtml = `
      <div class="cm-header"><i class="ti ti-user me-1"></i> ${safePersonName}</div>
      <div class="d-flex align-items-center justify-content-between px-3 py-1 mb-1 border-bottom" style="font-size: 11.5px; border-color: #f1f5f9 !important;">
        <span class="text-secondary">Kalan Bakiye:</span>
        <span class="fw-bold ${balanceRaw > 0 ? 'text-danger' : (balanceRaw < 0 ? 'text-primary' : 'text-success')}">${safeBalance}</span>
      </div>

      ${canPay ? `
      <a href="#" class="add-payment" data-id="${id}" data-name="${safePersonName}" data-balance="${safeBalance}" data-bs-toggle="modal" data-bs-target="#payment-modal">
        <i class="ti ti-cash-register text-success"></i> Ödeme Yap
      </a>
      ` : ''}
      ${canIncome ? `
      <a href="#" class="add-income" data-id="${id}" data-name="${safePersonName}" data-balance="${safeBalance}" data-bs-toggle="modal" data-bs-target="#income_modal">
        <i class="ti ti-download text-primary"></i> Gelir Ekle
      </a>
      <a href="#" class="add-wage-cut" data-id="${id}" data-name="${safePersonName}" data-balance="${safeBalance}" data-bs-toggle="modal" data-bs-target="#wage_cut_modal">
        <i class="ti ti-cut text-danger"></i> Kesinti Ekle
      </a>
      ` : ''}

      <div class="cm-divider"></div>

      <a href="#" class="route-link" data-page="puantaj/list">
        <i class="ti ti-calendar-event text-secondary"></i> Puantaj Sayfası
      </a>

      <div class="cm-divider"></div>

      <a href="#" class="cm-copy-text" data-copy="${safePersonName}" data-label="Personel Adı">
        <i class="ti ti-copy text-muted"></i> Adı Kopyala
      </a>
      ${iban ? `
      <a href="#" class="cm-copy-text" data-copy="${safeIban}" data-label="IBAN">
        <i class="ti ti-credit-card text-muted"></i> IBAN Kopyala
      </a>
      ` : ''}

      <div class="cm-divider"></div>

      <a href="#" class="cm-danger delete-monthly-payroll" data-id="${id}" data-month="${month}" data-year="${year}" data-project-id="${projectId}">
        <i class="ti ti-trash"></i> Bordrodan Çıkar
      </a>
    `;

    $contextMenu.html(menuHtml);
    $contextMenu.css({ display: 'block', opacity: 0 });

    var menuWidth = $contextMenu.outerWidth();
    var menuHeight = $contextMenu.outerHeight();
    var clickX = e.clientX;
    var clickY = e.clientY;
    var windowWidth = $(window).width();
    var windowHeight = $(window).height();

    var posX = (clickX + menuWidth > windowWidth) ? windowWidth - menuWidth - 10 : clickX;
    var posY = (clickY + menuHeight > windowHeight) ? windowHeight - menuHeight - 10 : clickY;

    $contextMenu.css({
      top: posY + 'px',
      left: posX + 'px',
      opacity: 1
    });
  });

  // Dışarı tıklanınca kapat
  $(document).on('click', function (e) {
    if (!$(e.target).closest('#customContextMenu').length) {
      $('#customContextMenu').hide();
      $('#bordroTable tbody tr').removeClass('context-menu-active');
    }
  });

  // Menü içindeki linklere tıklanınca kapat
  $(document).on('click', '#customContextMenu a, #customContextMenu button', function () {
    $('#customContextMenu').hide();
    $('#bordroTable tbody tr').removeClass('context-menu-active');
  });

  // Sayfa kaydırılınca veya odak değişince kapat
  $(window).on('scroll resize blur', function () {
    $('#customContextMenu').hide();
    $('#bordroTable tbody tr').removeClass('context-menu-active');
  });

  // Metin kopyalama
  $(document).on('click', '#customContextMenu .cm-copy-text', function (e) {
    e.preventDefault();
    var text = $(this).attr('data-copy') || '';
    var label = $(this).attr('data-label') || 'Metin';
    if (navigator.clipboard && text) {
      navigator.clipboard.writeText(text).then(function () {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: 'success',
            title: label + ' panoya kopyalandı',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
          });
        }
      });
    }
  });
});

// Bordro kayıtlarını sil
$(document).on("click", ".delete-monthly-payroll", function (e) {
  e.preventDefault();
  let id = $(this).data("id");
  let month = $(this).data("month");
  let year = $(this).data("year");
  let project_id = $(this).data("project-id");

  Swal.fire({
    title: 'Emin misiniz?',
    text: "Bu personelin bu aya ait tüm puantaj, maaş, gelir ve kesinti kayıtları silinecek ve personel bordrodan (projeden) çıkarılacaktır!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Evet, çıkar!',
    cancelButtonText: 'İptal'
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "api/bordro/delete.php",
        type: "POST",
        data: {
          id: id,
          month: month,
          year: year,
          project_id: project_id
        },
        dataType: "json",
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire(
              'Silindi!',
              response.message,
              'success'
            ).then(() => {
              Route();
            });
          } else {
            Swal.fire(
              'Hata!',
              response.message,
              'error'
            );
          }
        },
        error: function () {
          Swal.fire(
            'Hata!',
            'Bir hata oluştu.',
            'error'
          );
        }
      });
    }
  });
});

// Dinamik olarak DataTables sayfalama/sıralama işlemlerinde popover'ları doğru şekilde başlatmak için olay delegasyonu
$(document).on('mouseenter', '[data-bs-toggle="popover"]', function () {
  if (window.bootstrap && window.bootstrap.Popover) {
    var popover = window.bootstrap.Popover.getInstance(this);
    if (!popover) {
      popover = new window.bootstrap.Popover(this, {
        trigger: 'hover',
        placement: 'top',
        html: true
      });
      popover.show();
    }
  }
});

// İcra Kesintileri Detay Modalı (Bordro Sayfası)
let currentDeductionsFileId = null;
let currentDeductionsPersonId = null;

$(document).on('click', '.btn-view-icra-deductions', function(e) {
    e.preventDefault();
    const personId = $(this).data('person-id') || '';
    const fileId = $(this).data('file-id') || '';
    currentDeductionsFileId = fileId;
    currentDeductionsPersonId = personId;

    $('#modal-deductions-person-name').html('<span class="fs-4 text-muted">Yükleniyor...</span>');
    $('#modal-deductions-count').text('Yükleniyor...');
    $('#modal-deductions-total').text('0,00 ₺');
    $('#modal-deductions-table-body').html(`
        <tr>
            <td colspan="3" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                <span class="font-13">Kesinti kayıtları yükleniyor...</span>
            </td>
        </tr>
    `);
    $('#deductionsHistoryModal').modal('show');

    $.ajax({
        url: 'api/persons/icra.php',
        type: 'POST',
        data: {
            action: 'deductions_history',
            file_id: fileId,
            person_id: personId
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                $('#modal-deductions-person-name').html(`
                    <strong class="fs-3 text-dark">${res.person_name}</strong> 
                    <span class="badge bg-secondary-lt text-dark border border-secondary-subtle px-2.5 py-1 font-12 fw-medium">${res.dosya_no}</span>
                `);
                const count = (res.history && res.history.length) ? res.history.length : 0;
                $('#modal-deductions-count').text(`${count} adet kesinti kaydı`);
                $('#modal-deductions-total').text(res.total_amount || '0,00 ₺');
                if (res.file_id) {
                    currentDeductionsFileId = res.file_id;
                }

                const tbody = $('#modal-deductions-table-body');
                tbody.empty();

                if (!res.history || res.history.length === 0) {
                    tbody.html(`
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                <div class="avatar avatar-md rounded-circle bg-light text-secondary mb-2 mx-auto" style="width: 48px; height: 48px;">
                                    <i class="ti ti-receipt-off" style="font-size: 24px;"></i>
                                </div>
                                <div class="fw-semibold text-dark font-14">Kesinti Kaydı Bulunamadı</div>
                                <div class="small text-muted mt-1">Bu personele ait icra kesintisi bulunamadı.</div>
                            </td>
                        </tr>
                    `);
                } else {
                    res.history.forEach((h) => {
                        tbody.append(`
                            <tr>
                                <td class="ps-3 py-2.5">
                                    <span class="badge bg-blue-lt text-primary px-2.5 py-1 font-12 fw-semibold">
                                        <i class="ti ti-calendar-event me-1"></i>${h.donem}
                                    </span>
                                </td>
                                <td class="py-2.5">
                                    <div class="fw-semibold text-dark font-13">${h.aciklama || h.turu || 'İcra Kesintisi'}</div>
                                    ${h.created_at ? `<div class="small text-muted d-flex align-items-center gap-1 mt-0.5" style="font-size: 11.5px;"><i class="ti ti-clock text-secondary" style="font-size: 12px;"></i> ${h.created_at}</div>` : ''}
                                </td>
                                <td class="text-end pe-3 py-2.5">
                                    <span class="fw-bold text-success font-14">${h.tutar}</span>
                                </td>
                            </tr>
                        `);
                    });
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Hata!', res.message || 'Kesintiler yüklenemedi.', 'error');
                }
            }
        },
        error: function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Hata!', 'Sunucu hatası oluştu.', 'error');
            }
        }
    });
});

$(document).on('click', '#btn-modal-print-deductions', function() {
    if (currentDeductionsFileId) {
        window.open('print_icra.php?id=' + encodeURIComponent(currentDeductionsFileId), '_blank');
    } else if (currentDeductionsPersonId) {
        window.open('print_icra.php?person_id=' + encodeURIComponent(currentDeductionsPersonId), '_blank');
    } else {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Uyarı', 'Lütfen önce bir icra dosyası veya personel seçiniz.', 'warning');
        }
    }
});

$(document).on('click', '#btn-modal-excel-deductions', function() {
    if (currentDeductionsFileId) {
        window.location.href = 'pages/persons/icra-export-xls.php?id=' + encodeURIComponent(currentDeductionsFileId);
    } else if (currentDeductionsPersonId) {
        window.location.href = 'pages/persons/icra-export-xls.php?person_id=' + encodeURIComponent(currentDeductionsPersonId);
    } else {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Uyarı', 'Lütfen önce bir icra dosyası veya personel seçiniz.', 'warning');
        }
    }
});
