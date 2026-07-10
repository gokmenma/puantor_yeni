// Modal gösterildiğinde eğer yeni ekleme ise form temizlenir
$(document).on("click", "#btnNewIcraDairesi", function () {
  $("#icraDairesiModalTitle").html('<i class="ti ti-building-bank text-primary me-2"></i>Yeni İcra Dairesi');
  $("#id").val("");
  $("#daire_adi").val("");
  $("#sehir").val("");
  $("#iban").val("");
  $("#durum").val("Aktif");
});

// Güncelle butonuna tıklandığında veriler forma aktarılır ve modal açılır
$(document).on("click", ".btn-edit-icra-dairesi", function (e) {
  e.preventDefault();
  const btn = $(this);

  $("#icraDairesiModalTitle").html('<i class="ti ti-edit text-primary me-2"></i>İcra Dairesi Düzenle');
  $("#id").val(btn.data("id"));
  $("#daire_adi").val(btn.data("name"));
  $("#sehir").val(btn.data("city"));
  $("#iban").val(btn.data("iban"));
  $("#durum").val(btn.data("status") || "Aktif");

  $("#icraDairesiModal").modal("show");
});

$(document).on("click", "#saveIcraDairesi", function () {
  var form = $("#icraDairesiForm");
  let formData = new FormData(form[0]);

  form.validate({
    rules: {
      daire_adi: {
        required: true
      }
    },
    messages: {
      daire_adi: {
        required: "Daire adı boş bırakılamaz."
      }
    }
  });

  if (!form.valid()) {
    return;
  }

  fetch("/api/defines/icra-daireleri.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status
      }).then((result) => {
        if (data.status == "success") {
          $("#icraDairesiModal").modal("hide");
          location.reload();
        }
      });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).on("click", ".delete-icra-dairesi", function () {
  let action = "deleteIcraDairesi";
  let confirmMessage = "İcra dairesi tanımlaması silinecektir!";
  let url = "/api/defines/icra-daireleri.php";

  deleteRecord(this, action, confirmMessage, url);
});
