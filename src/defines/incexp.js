// Modal Select2 initialize
$(document).ready(function() {
  if ($.fn.select2 && $('#incExpModal').length) {
    $('#incExpModal .select2-modal').select2({
      dropdownParent: $('#incExpModal'),
      width: '100%'
    });
  }
});

// Modal gösterildiğinde eğer yeni ekleme ise form temizlenir
$(document).on("click", "#btnNewIncExp, #btnNewIncExpHeader", function () {
  $("#incExpModalTitle").html('<i class="ti ti-receipt-2 text-primary me-2"></i>Yeni Gelir/Gider Türü');
  $("#incExp_id").val("");
  $("#incexp_name").val("");
  $("#incexp_type").val("1").trigger("change");
  $("#description").val("");
});

// Güncelle butonuna tıklandığında veriler forma aktarılır ve modal açılır
$(document).on("click", ".btn-edit-incexp", function (e) {
  e.preventDefault();
  const btn = $(this);

  $("#incExpModalTitle").html('<i class="ti ti-edit text-primary me-2"></i>Gelir/Gider Türü Düzenle');
  $("#incExp_id").val(btn.data("id"));
  $("#incexp_name").val(btn.data("name"));
  $("#incexp_type").val(btn.data("type")).trigger("change");
  $("#description").val(btn.data("desc") || "");

  $("#incExpModal").modal("show");
});

// Kaydetme işlemi
$(document).on("click", "#saveIncExpType", function () {
  var form = $("#incExpModalForm");

  form.validate({
    rules: {
      incexp_name: {
        required: true
      },
      incexp_type: {
        required: true
      }
    },
    messages: {
      incexp_name: {
        required: "Gelir/Gider adı boş bırakılamaz."
      },
      incexp_type: {
        required: "Lütfen tür seçiniz."
      }
    },
    errorElement: 'span',
    errorClass: 'text-danger small mt-1 d-block'
  });

  if (!form.valid()) {
    return;
  }

  let formData = new FormData(form[0]);

  fetch("/api/defines/incexp.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      let title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        timer: data.status == "success" ? 1500 : undefined,
        showConfirmButton: data.status != "success"
      }).then(() => {
        if (data.status == "success") {
          $("#incExpModal").modal("hide");
          location.reload();
        }
      });
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        title: "Hata!",
        text: "Bir sunucu hatası oluştu.",
        icon: "error"
      });
    });
});

// Silme işlemi
$(document).on("click", ".delete-incexp", function (e) {
  e.preventDefault();
  let action = "deleteIncExpType";
  let confirmMessage = "Gelir/Gider tanımı silinecektir! Bu işlemi onaylıyor musunuz?";
  let url = "/api/defines/incexp.php";

  if (typeof deleteRecord === "function") {
    deleteRecord(this, action, confirmMessage, url);
  } else {
    let id = $(this).data("id");
    Swal.fire({
      title: "Emin misiniz?",
      text: confirmMessage,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Evet, Sil!",
      cancelButtonText: "İptal",
      customClass: {
        confirmButton: "btn btn-danger me-2",
        cancelButton: "btn btn-secondary"
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        let formData = new FormData();
        formData.append("action", action);
        formData.append("id", id);

        fetch(url, {
          method: "POST",
          body: formData
        })
          .then((res) => res.json())
          .then((data) => {
            Swal.fire({
              title: data.status === "success" ? "Silindi!" : "Hata!",
              text: data.message,
              icon: data.status
            }).then(() => {
              if (data.status === "success") {
                location.reload();
              }
            });
          })
          .catch((err) => {
            console.error("Error:", err);
            Swal.fire("Hata!", "İşlem sırasında bir hata meydana geldi.", "error");
          });
      }
    });
  }
});