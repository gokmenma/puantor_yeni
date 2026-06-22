// API Path tespiti
const getApiPath = (endpoint) => {
  const isMobile = window.location.pathname.includes('/mobile/');
  const base = isMobile ? '../api/' : 'api/';
  return base + endpoint;
};

// Modal gösterim fonksiyonu
const showProjectModal = () => {
  const modalEl = document.getElementById('projectModal');
  if (!modalEl) return;
  
  if (window.bootstrap && window.bootstrap.Modal) {
    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  } else if (typeof $ !== 'undefined' && $.fn.modal) {
    $(modalEl).modal("show");
  }
};

$(document).on("click", "#addNewProject", function (e) {
  if (e) e.preventDefault();
  const form = $("#projectForm");
  if (form.length > 0) {
    form[0].reset();
  }
  $("#modal_project_id").val(0);
  $("#projectModalTitle").text("Yeni Proje Ekle");
  $("#modal_project_town").html('<option value="">İlçe seçiniz</option>');
  
  // Re-init flatpickr if needed
  if (typeof flatpickr !== 'undefined') {
    flatpickr(".flatpickr", { dateFormat: "d.m.Y", locale: "tr" });
  }
  
  showProjectModal();
});

$(document).on("click", ".update-project", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  var formData = new FormData();
  formData.append("action", "getProject");
  formData.append("id", id);

  fetch(getApiPath("projects/projects.php"), {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        var p = data.data;
        $("#modal_project_id").val(p.id);
        $("#projectModalTitle").text("Proje Güncelle: " + p.project_name);
        $("input[name='project_name']").val(p.project_name);
        $("input[name='project_type'][value='" + p.type + "']").prop("checked", true);
        $("select[name='project_company']").val(p.company_id).trigger("change");
        $("select[name='project_status']").val(p.status).trigger("change");
        $("input[name='start_date']").val(p.start_date);
        $("input[name='end_date']").val(p.end_date);
        $("input[name='budget']").val(p.budget);
        $("select[name='project_city']").val(p.city).trigger("change");
        $("input[name='email']").val(p.email);
        $("input[name='phone']").val(p.phone);
        $("input[name='account_number']").val(p.account_number);
        $("textarea[name='address']").val(p.address);
        $("textarea[name='project']").val(p.notes);

        // Set town
        var townOption = new Option(p.town_name, p.town, true, true);
        $("#modal_project_town").append(townOption).trigger("change");

        showProjectModal();
      }
    });
});

$(document).on("submit", "#projectForm", function (e) {
  e.preventDefault();
  var form = $(this);
  let formData = new FormData(form[0]);

  fetch(getApiPath("projects/projects.php"), {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: "Başarılı!",
            text: data.message,
            icon: "success"
          }).then(() => {
            location.reload();
          });
        } else {
          alert(data.message);
          location.reload();
        }
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire("Hata!", data.message, "error");
        } else {
          alert(data.message);
        }
      }
    });
});

$(document).on("change", "select[name='project_city']", function () {
  var cityId = $(this).val();
  var target = $(this).closest(".modal-body").length > 0 ? "#modal_project_town" : "#project_town";
  if (typeof getTowns === "function") {
    getTowns(cityId, target);
  }
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

  fetch(getApiPath("projects/project-person.php"), {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        title = "Başarılı!";
      } else {
        title = "Hata!";
      }
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: title,
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        });
      } else {
        alert(data.message);
      }
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
  if ($("#projectTable").length > 0 && $.fn.DataTable) {
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

    // Sayfa yüklendiğinde tabloyu filtrele
    filterTableByCheckedRadio();

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
