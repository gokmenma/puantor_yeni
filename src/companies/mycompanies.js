// Logo Önizleme Yardımcı Fonksiyonları
function resetLogoPreview() {
  $("#brand_logo").val("");
  $("#logo-preview-img").attr("src", "").hide();
  $("#logo-preview-placeholder").show();
  $("#btn-remove-logo").hide();
}

function setLogoPreview(src) {
  if (src) {
    $("#logo-preview-img").attr("src", src).show();
    $("#logo-preview-placeholder").hide();
    $("#btn-remove-logo").show();
  } else {
    resetLogoPreview();
  }
}

// Logo Seç Butonu ve Dosya Değişimi
$(document).on("click", "#btn-browse-logo", function(e) {
  e.preventDefault();
  $("#brand_logo").trigger("click");
});

$(document).on("change", "#brand_logo", function(e) {
  var file = this.files && this.files[0];
  if (file) {
    // 2MB Boyut Kontrolü
    if (file.size > 2 * 1024 * 1024) {
      Swal.fire("Uyarı", "Seçilen logo 2MB boyutundan büyük olamaz.", "warning");
      resetLogoPreview();
      return;
    }
    
    // Dosya Türü Kontrolü
    if (!file.type.match("image.*")) {
      Swal.fire("Uyarı", "Lütfen geçerli bir görsel dosyası seçiniz (PNG, JPG, SVG, WEBP).", "warning");
      resetLogoPreview();
      return;
    }

    var reader = new FileReader();
    reader.onload = function(evt) {
      setLogoPreview(evt.target.result);
    };
    reader.readAsDataURL(file);
  } else {
    resetLogoPreview();
  }
});

// Logo Kaldır Butonu
$(document).on("click", "#btn-remove-logo", function(e) {
  e.preventDefault();
  resetLogoPreview();
});

// Drag & Drop Desteği
$(document).on("dragover dragenter", "#logoUploadDropzone", function(e) {
  e.preventDefault();
  e.stopPropagation();
  $(this).addClass("dragover");
});

$(document).on("dragleave drop", "#logoUploadDropzone", function(e) {
  e.preventDefault();
  e.stopPropagation();
  $(this).removeClass("dragover");
});

$(document).on("drop", "#logoUploadDropzone", function(e) {
  var dt = e.originalEvent.dataTransfer;
  if (dt && dt.files && dt.files.length) {
    var fileInput = document.getElementById("brand_logo");
    if (fileInput) {
      fileInput.files = dt.files;
      $("#brand_logo").trigger("change");
    }
  }
});

$(document).on("click", "#btn-new-mycompany, #btn-new-mycompany-header", function(e) {
  e.preventDefault();

  // Reset form
  $("#myFirmForm")[0].reset();
  $("#myfirm_id").val(0);
  resetLogoPreview();

  $("#mycompany-modal-icon").attr("class", "ti ti-building-plus fs-2");
  $("#mycompany-modal-title").text("Yeni Firma Ekle");
  $("#mycompany-modal-subtitle").text("Sisteme yeni şirket tanımlayabilir ve firma detaylarını düzenleyebilirsiniz.");
  $("#saveMyFirm").prop("disabled", false).html('<i class="ti ti-device-floppy me-2"></i><span>Değişiklikleri Kaydet</span>');
  
  $("#mycompany-modal").modal("show");
});

$(document).on("click", ".mycompany-edit-btn", function(e) {
  e.preventDefault();
  let id = $(this).data("id");

  // Reset form
  $("#myFirmForm")[0].reset();
  $("#myfirm_id").val(id);
  resetLogoPreview();

  $("#mycompany-modal-icon").attr("class", "ti ti-edit fs-2");
  $("#mycompany-modal-title").text("Firma Bilgilerini Düzenle");
  $("#mycompany-modal-subtitle").text("Firma detayları yükleniyor...");
  $("#saveMyFirm").prop("disabled", false).html('<i class="ti ti-device-floppy me-2"></i><span>Değişiklikleri Kaydet</span>');

  // Fetch details
  let formData = new FormData();
  formData.append("action", "getMyFirmDetails");
  formData.append("id", id);

  fetch("/api/companies/mycompanies.php", {
    method: "POST",
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.status === "success") {
        let myfirm = data.myfirm;

        // Populate fields
        $("#firm_name").val(myfirm.firm_name);
        $("#yetkili_adi").val(myfirm.yetkili_adi);
        $("#phone").val(myfirm.phone);
        $("#email").val(myfirm.email);
        $("#vergi_dairesi").val(myfirm.tax_office);
        $("#vergi_no").val(myfirm.tax_number);
        $("#description").val(myfirm.description);

        if (myfirm.brand_logo) {
          setLogoPreview("/uploads/" + myfirm.brand_logo);
        } else {
          resetLogoPreview();
        }

        $("#mycompany-modal-title").text("Firma Düzenle: " + myfirm.firm_name);
        $("#mycompany-modal-subtitle").text("Firma profil ve iletişim bilgilerini güncelleyebilirsiniz.");
        $("#mycompany-modal").modal("show");
      } else {
        Swal.fire("Hata", data.message, "error");
      }
    })
    .catch(error => {
      console.error(error);
      Swal.fire("Hata", "Firma detayları alınırken bir hata oluştu.", "error");
    });
});

$(document).on("submit", "#myFirmForm", function (e) {
  e.preventDefault();
});

$(document).on("click", "#saveMyFirm", function (e) {
  e.preventDefault();
  var form = $("#myFirmForm");

  form.validate({
    rules: {
      firm_name: {
        required: true
      },
      yetkili_adi: {
        required: true
      }
    },
    messages: {
      firm_name: {
        required: "Lütfen Firma Adı alanını doldurunuz."
      },
      yetkili_adi: {
        required: "Lütfen Yetkili Adı alanını doldurunuz."
      }
    },
    errorElement: 'div',
    errorClass: 'invalid-feedback d-block',
    highlight: function(element, errorClass, validClass) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function(element, errorClass, validClass) {
      $(element).removeClass('is-invalid');
    },
    errorPlacement: function (error, element) {
      if (element.parent().hasClass("input-icon")) {
        error.insertAfter(element.parent());
      } else {
        error.insertAfter(element);
      }
    }
  });

  if (!form.valid()) {
    return;
  }

  var $saveBtn = $("#saveMyFirm");
  $saveBtn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span><span>Kaydediliyor...</span>');

  let formData = new FormData(form[0]);

  fetch("/api/companies/mycompanies.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      $saveBtn.prop("disabled", false).html('<i class="ti ti-device-floppy me-2"></i><span>Değişiklikleri Kaydet</span>');
      
      let title, icon;
      if (data.status == "success") {
        title = "Başarılı!";
        icon = "success";
      } else {
        title = "Hata!";
        icon = "error";
      }
      Swal.fire({
        title: title,
        text: data.message,
        icon: icon,
        confirmButtonText: "Tamam",
      }).then((result) => {
        if (result.isConfirmed && data.status == "success") {
          location.reload();
        }
      });
    })
    .catch((err) => {
      $saveBtn.prop("disabled", false).html('<i class="ti ti-device-floppy me-2"></i><span>Değişiklikleri Kaydet</span>');
      Swal.fire("Hata", "İşlem sırasında bir hata oluştu.", "error");
    });
});

$(document).on("click", ".delete-mycompany", function (e) {
  e.preventDefault();
  let id = $(this).data("id");

  Swal.fire({
    title: "Firma Silme Onayı",
    html: `
      <div class="text-start mb-2">
        <p class="text-danger fw-bold mb-2"><i class="ti ti-alert-triangle me-1"></i> Dikkat: Bu işlem firmayı ve bağlı tüm verilerini silecektir!</p>
        <p class="text-secondary small mb-3">Bu firma ve firmaya bağlı tüm veriler <strong>(Personeller, Puantajlar, Projeler, Kasalar, İzin Talepleri, Görevler vb.)</strong> silinecektir.</p>
        <label for="swal-firm-delete-password" class="form-label fw-bold text-dark">İşlemi onaylamak için hesap şifrenizi giriniz:</label>
        <input type="password" id="swal-firm-delete-password" class="form-control" placeholder="Hesap şifreniz">
      </div>
    `,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Evet, Şifre ile Sil",
    cancelButtonText: "İptal",
    customClass: {
      confirmButton: "btn btn-danger me-2",
      cancelButton: "btn btn-secondary"
    },
    buttonsStyling: false,
    preConfirm: () => {
      const password = $("#swal-firm-delete-password").val();
      if (!password) {
        Swal.showValidationMessage("Lütfen şifrenizi giriniz!");
        return false;
      }
      return password;
    }
  }).then((result) => {
    if (result.isConfirmed) {
      let password = result.value;
      let formData = new FormData();
      formData.append("action", "deleteMyCompany");
      formData.append("id", id);
      formData.append("password", password);

      fetch("/api/companies/mycompanies.php", {
        method: "POST",
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === "success") {
            Swal.fire({
              title: "Başarılı!",
              text: data.message,
              icon: "success",
              confirmButtonText: "Tamam"
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              title: "Hata!",
              text: data.message,
              icon: "error",
              confirmButtonText: "Tamam"
            });
          }
        })
        .catch(error => {
          console.error(error);
          Swal.fire({
            title: "Hata!",
            text: "Firma silinirken sunucu hatası oluştu.",
            icon: "error",
            confirmButtonText: "Tamam"
          });
        });
    }
  });
});

$(document).on("click", ".btn-new-firm-limit", function (e) {
  e.preventDefault();
  let limit = $(this).data("limit");
  Swal.fire({
    title: "Limit Aşımı!",
    text: "Paketinizin firma limiti (" + limit + ") dolmuştur. Yeni firma eklemek için lütfen paketinizi yükseltin.",
    icon: "warning",
    confirmButtonText: "Tamam"
  });
});

$(document).on("click", ".btn-set-default-firm, .btn-unset-default-firm", function (e) {
  e.preventDefault();
  let isUnset = $(this).hasClass("btn-unset-default-firm");
  let id = isUnset ? 0 : $(this).data("id");
  let title = isUnset ? "Varsayılan Firma Kaldırma" : "Varsayılan Firma Seçimi";
  let text = isUnset ? "Bu firmayı varsayılan firma tercihlerinizden çıkarmak istediğinize emin misiniz?" : "Bu firmayı varsayılan firma yapmak istediğinize emin misiniz?";

  Swal.fire({
    title: title,
    text: text,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Evet",
    cancelButtonText: "İptal",
    customClass: {
      confirmButton: "btn btn-primary me-2",
      cancelButton: "btn btn-secondary"
    },
    buttonsStyling: false
  }).then((result) => {
    if (result.isConfirmed) {
      let formData = new FormData();
      formData.append("action", "setDefaultCompany");
      formData.append("id", id);

      fetch("/api/companies/mycompanies.php", {
        method: "POST",
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === "success") {
            Swal.fire({
              title: "Başarılı!",
              text: data.message,
              icon: "success",
              confirmButtonText: "Tamam"
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              title: "Hata!",
              text: data.message,
              icon: "error",
              confirmButtonText: "Tamam"
            });
          }
        })
        .catch(error => {
          console.error(error);
          Swal.fire({
            title: "Hata!",
            text: "İşlem yapılırken bir hata oluştu.",
            icon: "error",
            confirmButtonText: "Tamam"
          });
        });
    }
  });
});

