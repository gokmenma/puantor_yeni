$(document).on("click", "#saveProject", function () {
  var form = $("#projectForm");
  let formData = new FormData(form[0]);
  // for (var pair of formData.entries()) {
  //   console.log(pair[0] + ", " + pair[1]);
  // }

  form.validate({
    rules: {
      project_name: {
        required: true
      },
      project_status: {
        required: true
      }
    },
    messages: {
      project_name: {
        required: "Proje adı zorunludur."
      },
      project_status: {
        required: "Proje Durumunu seçiniz."
      }
    },
    errorPlacement: function (error, element) {
      if (element.hasClass("select2")) {
        // select2 konteynerini bul
        var container = element.next(".select2-container");
        // Hata mesajını, select2 konteynerinin sonuna ekler
        error.insertAfter(container);
      } else {
        // Diğer tüm durumlar için varsayılan davranış
        error.insertAfter(element);
      }
    }
  });

  if (!form.valid()) {
    return false;
  }

  fetch("/api/projects/projects.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      if (data.status == "success") {
        $("#id").val(data.lastInsertId);
        title = "Başarılı!";
      } else {
        title = "Hata!";
      }
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam"
      });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).on("click", "#savePersontoProject", function () {
  var checkedItems = [];
  $("#addPersontoProject tbody tr").each(function () {
    var checkbox = $(this).find("input[type='checkbox']");
    if (checkbox.prop("checked")) {
      checkedItems.push(checkbox.val());
    }
  });

  let formData = new FormData();
  formData.append("project_id", $("#project_id").val());
  formData.append("person_id", checkedItems);
  formData.append("action", "addPersonToProject");

  fetch("/api/projects/project-person.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      if (data.status == "success") {
        title = "Başarılı!";
      } else {
        title = "Hata!";
      }
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam"
      });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).ready(function () {
  // "Tümünü Seç" checkbox'ının durumunu kontrol edin
  $("#allPersonCheck").change(function () {
    // "Tümünü Seç" checkbox'ının durumu true ise tüm personel checkbox'larını işaretleyin, değilse işaretlerini kaldırın
    var isChecked = $(this).is(":checked");
    $("#addPersontoProject .form-check-input").prop("checked", isChecked);
  });
});

$(document).on("change", "#project_city", function () {
  //İl id'si alınır ilce selectine ilceler yüklenir

  getTowns($(this).val(), "#project_town");
});

$(document).on("click", ".delete-project", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteProject";
  let confirmMessage = "Proje silinecektir!";
  let url = "/api/projects/projects.php";

  deleteRecord(this, action, confirmMessage, url);
});

$(document).on("click", ".delete-project-action", async function () {
  //işlem türünü al,tablonun 2. sütununda bulunan veriyi al
  let type = $(this).closest("tr").find("td:eq(2)").text();

  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteProjectAction";
  let confirmMessage = type + " silinecektir!";
  let project_id = $(this).attr("data-project");
  let url = "/api/projects/projects.php?project_id=" + project_id;

  const result = await deleteRecordByReturn(this, action, confirmMessage, url);

  console.log(result);

  if (result.status == "success") {
    $("#total_income").text(result.summary.hakedis);
    $("#total_payment").text(result.summary.gelir);
    $("#total_expense").text(result.summary.kesinti);
    $("#balance").text(result.summary.balance);
    $("#progress-bar").text(result.progress + "%");
    $(".progress-bar").css("width", result.progress + "%");
  }
});

$(document).ready(function () {
  // DataTable'ı başlat
  var table = $("#projectTable").DataTable();

  // Radyo butonuna tıklama olayını dinle
  $(".form-selectgroup-input").on("change", function () {
    var type = $(this).attr("data-type");
    //Eğer tümü ise tüm filtreleri kaldır
    if (type == "Tümü") {
      table.column(1).search("").draw();
      return;
    }
    if (this.checked) {
      // DataTable'da filtreleme yap
      table.column(1).search(type).draw();
    }
  });

  $(document).ready(function () {
    // Sayfa yüklendiğinde tabloyu filtrele
    filterTableByCheckedRadio();
  });

  function filterTableByCheckedRadio() {
    //tabloda 1'den fazla satır varsa
    if (table.rows().count() > 0) {
      var checkedRadio = $(".form-selectgroup-input:checked");
      if (checkedRadio.length > 0) {
        var type = checkedRadio.attr("data-type");
        if (type == "Tümü") {
          table.column(1).search("").draw();
        } else {
          table.column(1).search(type).draw();
        }
      }
    }
  }
});

//Project manage sayfasında Ödeme, hakediş gibi verileri ekledikten sonra tabloya eklemek için
//expense.js ve progress-payment.js ve payment.js dosyalarında kullanılan addDataToTable fonksiyonu
// deduction.js dosyasında kullanıldı
function addDataToTable(data) {
  var table = $("#project_paymentTable").DataTable();
  table.row
    .add([
      table.rows().count() + 1, // Sıra numarası
      data.tarih,
      data.turu,
      data.ay,
      data.yil,
      data.tutar,
      data.aciklama,
      data.created_at,
      `<div class="dropdown">
                <button class="btn dropdown-toggle align-text-top"
                    data-bs-toggle="dropdown">İşlem</button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item edit-payment"
                        data-id='${data.id}'>
                        <i class="ti ti-edit icon me-3"></i> Güncelle
                    </a>
                    <a class="dropdown-item delete-project-action" href="#" data-id='${data.id}' data-project='${data.project_id}'>
                        <i class="ti ti-trash icon me-3"></i> Sil
                    </a>
                </div>
            </div>`
    ])
    .order([7, "desc"])
    .draw(false);
}
