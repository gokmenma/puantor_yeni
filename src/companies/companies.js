$(document).on("click", "#btn-new-company", function(e) {
  e.preventDefault();
  
  // Reset form
  $("#companyForm")[0].reset();
  $("#company_id").val(0);
  $("#firm_cities").val("").trigger("change");
  $("#firm_towns").html("<option value=''>İlçe Seçiniz</option>").trigger("change");
  
  $("#company-modal-title").text("Yeni Firma Ekle");
  $("#company-modal").modal("show");
});

$(document).on("click", ".company-edit-btn", function(e) {
  e.preventDefault();
  let id = $(this).data("id");
  
  // Reset form
  $("#companyForm")[0].reset();
  $("#company_id").val(id);
  
  // Fetch details
  let formData = new FormData();
  formData.append("action", "getCompanyDetails");
  formData.append("id", id);
  
  fetch("/api/companies/companies.php", {
    method: "POST",
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.status === "success") {
        let company = data.company;
        
        // Populate fields
        $("#company_name").val(company.company_name);
        $("#yetkili").val(company.yetkili);
        $("#phone").val(company.phone);
        $("#email").val(company.email);
        $("#tax_office").val(company.tax_office);
        $("#tax_number").val(company.tax_number);
        $("#account_number").val(company.account_number);
        $("#description").val(company.description);
        $("#address").val(company.address);
        
        // Set city and towns without triggering AJAX reload
        $(document).off("change", "#firm_cities");
        
        $("#firm_cities").val(company.city).trigger("change");
        if (data.townOptions) {
          $("#firm_towns").html(data.townOptions).val(company.town).trigger("change");
        } else {
          $("#firm_towns").html("<option value=''>İlçe Seçiniz</option>").trigger("change");
        }
        
        // Rebind city change handler
        $(document).on("change", "#firm_cities", function () {
          getTowns($(this).val(), "#firm_towns");
        });
        
        $("#company-modal-title").text("Firma Düzenle: " + company.company_name);
        $("#company-modal").modal("show");
      } else {
        Swal.fire("Hata", data.message, "error");
      }
    })
    .catch(error => {
      console.error(error);
      Swal.fire("Hata", "Firma detayları alınırken bir hata oluştu.", "error");
    });
});

$(document).on("submit", "#companyForm", function (e) {
  e.preventDefault();
});

$(document).on("click", "#saveCompany", function (e) {
  e.preventDefault();
  var form = $("#companyForm");
  
  form.validate({
    rules: {
      company_name: {
        required: true
      }
    },
    messages: {
      company_name: {
        required: "Lütfen Firma Adı alanını doldurunuz."
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
      if (element.hasClass("select2")) {
        error.insertAfter(element.next("span"));
      } else if (element.parent().hasClass("input-icon")) {
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

  fetch("/api/companies/companies.php", {
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

$(document).on("click", ".delete-company", function () {
  let action = "deleteCompany";
  let confirmMessage = "Firma silinecektir!";
  let url = "/api/companies/companies.php";

  deleteRecord(this, action, confirmMessage, url);
});

$(document).on("change", "#firm_cities", function () {
  getTowns($(this).val(), "#firm_towns");
});

$(document).ready(function () {
  if (window.location.hash === "#new" || window.location.search.indexOf("new=1") !== -1) {
    if (window.location.hash === "#new") {
      history.replaceState("", document.title, window.location.pathname + window.location.search);
    }
    $("#btn-new-company").trigger("click");
  }
});