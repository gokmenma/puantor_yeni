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

var cpFlatpickr = null;

function openCompanyPaymentModal(mode) {
  $("#cp-modal-title").text(mode === "add" ? "Ödeme Ekle" : "Ödeme Düzenle");
  $("#company-payment-modal").modal("show");
}

function initCpModalPlugins() {
  if (cpFlatpickr) {
    cpFlatpickr.destroy();
    cpFlatpickr = null;
  }
  if (typeof flatpickr !== "undefined") {
    cpFlatpickr = flatpickr("#cp_date", { dateFormat: "d.m.Y", locale: "tr" });
  }
  if (typeof $.fn.select2 !== "undefined") {
    $("#cp_case_id").select2({ dropdownParent: $("#company-payment-modal"), width: "100%" });
  }
}

$(document).on("shown.bs.modal", "#company-payment-modal", function () {
  initCpModalPlugins();
  if (cpFlatpickr && $("#cp_date").val()) {
    cpFlatpickr.setDate($("#cp_date").val(), false, "d.m.Y");
  }
});

$(document).on("click", "#btn-add-company-payment, #btn-add-company-payment-top", function () {
  $("#companyPaymentForm")[0].reset();
  $("#cp_id").val("0");
  $("input[name='cp_type_id'][value='1']").prop("checked", true);
  $("#cp_date").val(new Date().toLocaleDateString("tr-TR", { day: "2-digit", month: "2-digit", year: "numeric" }));
  openCompanyPaymentModal("add");
});

$(document).on("click", ".edit-company-payment", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  var fd = new FormData();
  fd.append("action", "getCompanyPayment");
  fd.append("id", id);
  fetch("/api/companies/companies.php", { method: "POST", body: fd })
    .then((r) => r.json())
    .then((data) => {
      if (data.status !== "success") {
        Swal.fire("Hata", data.message, "error");
        return;
      }
      $("#cp_id").val(id);
      $("input[name='cp_type_id'][value='" + data.type_id + "']").prop("checked", true);
      $("#cp_amount").val(data.amount);
      $("#cp_date").val(data.date);
      $("#cp_description").val(data.description);
      openCompanyPaymentModal("edit");
      setTimeout(function () {
        if (cpFlatpickr) cpFlatpickr.setDate(data.date, false, "d.m.Y");
        if (typeof $.fn.select2 !== "undefined") {
          $("#cp_case_id").val(data.case_id).trigger("change");
        } else {
          $("#cp_case_id").val(data.case_id);
        }
      }, 100);
    })
    .catch(() => Swal.fire("Hata", "Veri alınamadı.", "error"));
});

$(document).on("click", "#saveCompanyPayment", function () {
  var caseVal = $("#cp_case_id").val();
  if (!caseVal || caseVal === "0") {
    Swal.fire("Hata", "Kasa seçiniz.", "error");
    return;
  }
  if (!$("#cp_amount").val()) {
    Swal.fire("Hata", "Tutar giriniz.", "error");
    return;
  }

  var formData = new FormData($("#companyPaymentForm")[0]);

  fetch("/api/companies/companies.php", { method: "POST", body: formData })
    .then((r) => r.json())
    .then((data) => {
      if (data.status === "success") {
        Swal.fire({ title: "Başarılı!", text: data.message, icon: "success", confirmButtonText: "Tamam" }).then(() => {
          location.reload();
        });
      } else {
        Swal.fire("Hata", data.message, "error");
      }
    })
    .catch(() => Swal.fire("Hata", "Bir hata oluştu.", "error"));
});

$(document).on("click", ".delete-company-payment", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  AlertConfirm("Bu ödeme kaydı silinecektir!").then((confirmed) => {
    if (!confirmed) return;
    var fd = new FormData();
    fd.append("action", "deleteCompanyPayment");
    fd.append("id", id);
    fetch("/api/companies/companies.php", { method: "POST", body: fd })
      .then((r) => r.json())
      .then((data) => {
        if (data.status === "success") {
          Swal.fire({ title: "Silindi!", text: data.message, icon: "success", confirmButtonText: "Tamam" }).then(() => {
            location.reload();
          });
        } else {
          Swal.fire("Hata", data.message, "error");
        }
      });
  });
});

$(document).ready(function () {
  if (window.location.hash === "#new" || window.location.search.indexOf("new=1") !== -1) {
    if (window.location.hash === "#new") {
      history.replaceState("", document.title, window.location.pathname + window.location.search);
    }
    $("#btn-new-company").trigger("click");
  }
});