
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
