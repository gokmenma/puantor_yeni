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

  // Sütun indeksleri (thead sırasına göre: 0=Checkbox, 1=Sıra, 2=Personel, 3=ÜcretTürü, 4=Görevi, 5=Ekip, 6=Proje, 7=IBAN, 8=İşeBaşlama, 9=Brüt, 10=İcra Kesintisi, 11=Ödenen, 12=Ödenecek, 13=İşlem)
  var columnConfig = {
    3: { label: 'Ücret Türü',         default: true  },
    4: { label: 'Görevi',              default: true  },
    5: { label: 'Ekip',               default: true  },
    6: { label: 'Proje',              default: true  },
    7: { label: 'IBAN',               default: false },
    8: { label: 'İşe Başlama Tarihi', default: true  },
    10: { label: 'İcra Kesintisi',    default: true  }
  };

  var savedVisibility = localStorage.getItem('bordro_column_visibility_v3');
  var visibilityState = savedVisibility ? JSON.parse(savedVisibility) : {};

  var menuHtml = '';
  $.each(columnConfig, function (idx, conf) {
    var isVisible = visibilityState.hasOwnProperty(idx) ? visibilityState[idx] : conf.default;
    table.column(idx).visible(isVisible, false);
    menuHtml += `
      <label class="dropdown-item d-flex align-items-center cursor-pointer py-1.5 px-3 rounded-2" style="font-size:0.85rem;">
        <div class="form-check mb-0 w-100">
          <input class="form-check-input bordro-col-trigger" type="checkbox" id="bordroColCheck_${idx}" data-column="${idx}" ${isVisible ? 'checked' : ''}>
          <span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">${conf.label}</span>
        </div>
      </label>`;
  });

  $('#bordroColvisMenu').html(menuHtml);
  table.columns.adjust();

  $(document).off('change.bordroCol').on('change.bordroCol', '.bordro-col-trigger', function () {
    var colIdx = parseInt($(this).data('column'));
    var isChecked = this.checked;
    table.column(colIdx).visible(isChecked);
    visibilityState[colIdx] = isChecked;
    localStorage.setItem('bordro_column_visibility_v3', JSON.stringify(visibilityState));
  });

  $(document).off('click.bordroCol').on('click.bordroCol', '#bordroColvisMenu', function (e) {
    e.stopPropagation();
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

    $('#modal-deductions-person-name').text('Yükleniyor...');
    $('#modal-deductions-total').text('0,00 ₺');
    $('#modal-deductions-table-body').html('<tr><td colspan="3" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Kesintiler yükleniyor...</td></tr>');
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
                $('#modal-deductions-person-name').html(`<strong>${res.person_name}</strong> <span class="badge bg-secondary-lt ms-2">${res.dosya_no}</span>`);
                $('#modal-deductions-total').text(res.total_amount || '0,00 ₺');
                if (res.file_id) {
                    currentDeductionsFileId = res.file_id;
                }

                const tbody = $('#modal-deductions-table-body');
                tbody.empty();

                if (!res.history || res.history.length === 0) {
                    tbody.html('<tr><td colspan="3" class="text-center py-4 text-muted"><i class="ti ti-folder-off fs-1 d-block mb-1 text-secondary"></i>Bu personele ait icra kesintisi bulunamadı.</td></tr>');
                } else {
                    res.history.forEach((h) => {
                        tbody.append(`
                            <tr>
                                <td class="ps-3 fw-bold">${h.donem}</td>
                                <td>
                                    <div class="font-weight-600">${h.aciklama || h.turu}</div>
                                    <div class="small text-muted">${h.created_at || ''}</div>
                                </td>
                                <td class="text-end pe-3 text-success font-weight-700">${h.tutar}</td>
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
