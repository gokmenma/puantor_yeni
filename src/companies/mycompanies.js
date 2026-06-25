$(document).on("click", "#btn-new-mycompany", function(e) {
  e.preventDefault();

  // Reset form
  $("#myFirmForm")[0].reset();
  $("#myfirm_id").val(0);
  $("#logo-preview-img").attr("src", "").hide();

  $("#mycompany-modal-title").text("Yeni Firma Ekle");
  $("#mycompany-modal").modal("show");
});

$(document).on("click", ".mycompany-edit-btn", function(e) {
  e.preventDefault();
  let id = $(this).data("id");

  // Reset form
  $("#myFirmForm")[0].reset();
  $("#myfirm_id").val(id);
  $("#logo-preview-img").attr("src", "").hide();

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
        $("#start_budget").val(myfirm.start_budget);
        $("#description").val(myfirm.description);

        if (myfirm.brand_logo) {
          $("#logo-preview-img").attr("src", "/uploads/" + myfirm.brand_logo).show();
        }

        $("#mycompany-modal-title").text("Firma Düzenle: " + myfirm.firm_name);
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

  let formData = new FormData(form[0]);

  fetch("/api/companies/mycompanies.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
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
    });
});

$(document).on("click", ".delete-mycompany", function () {
  let action = "deleteMyCompany";
  let confirmMessage = "Firma silinecektir!";
  let url = "/api/companies/mycompanies.php";

  deleteRecord(this, action, confirmMessage, url);
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
