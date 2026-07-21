
// $(document).on("click", "#wage_cut_addButton", function () {
//   let form = $("#wage_cut_modalForm");
//   addWageCutorIncome(form);
// });
// $(document).on("click", ".add-wage-cut", function () {
//   let personel_id = $(this).data("id");
//   let personel_name = $(this).closest("tr").find("td:eq(1)").text();
//   $("#person_id_wage_cut").val(personel_id);
//   $("#person_name_wage_cut").text(personel_name);
// });


$("#projects").on("change", function () {
  Route();
});

$("#team_id").on("change", function () {
  Route();
});

//Yıl değiştiği zaman sayfayı yeniden yükle
$("#year").on("change", function () {
  Route();
});

//Ay değiştiği zaman sayfayı yeniden yükle
$("#months").on("change", function () {
  Route();
});

function Route() {
  var form = $("#bordroInfoForm");
  form.find("input[name='action']").remove();
  form.submit();
}


// Bordro hesapla butonuna tıklandığında
$(document).on("click", "#payroll_calculate", function () {
  //POST işlemi için form oluşturuluyor
  let form = $("#bordroInfoForm");
  form.append('<input type="hidden" name="action" value="payroll_calculate">');
  form.submit();
});

// Personelleri güncelle butonuna tıklandığında
$(document).on("click", "#update_personnel", function () {
  //POST işlemi için form oluşturuluyor
  let form = $("#bordroInfoForm");
  form.append('<input type="hidden" name="action" value="update_personnel">');
  form.submit();
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

// Bordro sütun görünürlüğü
$(function () {
  var table = $('#bordroTable').DataTable();

  // Sütun indeksleri (thead sırasına göre: 0=Sıra, 1=Personel, 2=ÜcretTürü, 3=Görevi, 4=Ekip, 5=Proje, 6=IBAN, 7=İşeBaşlama, 8=Brüt, 9=Ödenen, 10=Ödenecek, 11=İşlem)
  var columnConfig = {
    2: { label: 'Ücret Türü',         default: true  },
    3: { label: 'Görevi',              default: true  },
    4: { label: 'Ekip',               default: false },
    5: { label: 'Proje',              default: false },
    6: { label: 'IBAN',               default: false },
    7: { label: 'İşe Başlama Tarihi', default: true  }
  };

  var savedVisibility = localStorage.getItem('bordro_column_visibility');
  var visibilityState = savedVisibility ? JSON.parse(savedVisibility) : {};

  var menuHtml = '';
  $.each(columnConfig, function (idx, conf) {
    var isVisible = visibilityState.hasOwnProperty(idx) ? visibilityState[idx] : conf.default;
    table.column(idx).visible(isVisible, false);
    menuHtml += `
      <label class="dropdown-item d-flex align-items-center cursor-pointer py-1 px-3 rounded-2" style="font-size:0.85rem;">
        <div class="form-check mb-0 w-100">
          <input class="form-check-input bordro-col-trigger" type="checkbox" data-column="${idx}" ${isVisible ? 'checked' : ''}>
          <span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">${conf.label}</span>
        </div>
      </label>`;
  });

  $('#bordroColvisMenu').html(menuHtml);
  table.columns.adjust().draw(false);

  $(document).on('change', '.bordro-col-trigger', function () {
    var colIdx = parseInt($(this).data('column'));
    var isChecked = this.checked;
    table.column(colIdx).visible(isChecked);
    visibilityState[colIdx] = isChecked;
    localStorage.setItem('bordro_column_visibility', JSON.stringify(visibilityState));
  });

  $(document).on('click', '#bordroColvisMenu', function (e) {
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

