
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

// Bordro detayı göster
$(document).on("click", ".view-payroll-detail", function () {
  let id = $(this).data("id");
  let month = $(this).data("month");
  let year = $(this).data("year");

  $("#payroll-detail-content").html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');

  $.ajax({
    url: "api/bordro/detail.php",
    type: "POST",
    data: {
      id: id,
      month: month,
      year: year
    },
    success: function (data) {
      $("#payroll-detail-content").html(data);
    },
    error: function () {
      $("#payroll-detail-content").html('<div class="alert alert-danger">Bordro detayları yüklenirken bir hata oluştu.</div>');
    }
  });
});

// Bordro detayını yazdır
$(document).on("click", "#print-detailed-payroll", function () {
  let content = $("#payroll-detail-content").html();
  let printWindow = window.open('', '', 'height=600,width=800');
  printWindow.document.write('<html><head><title>Bordro Detayı</title>');
  printWindow.document.write('<link rel="stylesheet" href="dist/css/tabler.min.css">');
  printWindow.document.write('</head><body class="p-4">');
  printWindow.document.write(content);
  printWindow.document.write('</body></html>');
  printWindow.document.close();
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
