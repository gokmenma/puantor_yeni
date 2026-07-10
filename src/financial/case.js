$(document).on("click", "#btn-new-case", function () {
  var form = $("#caseForm");
  form[0].reset();
  
  if (form.data('validator')) {
    form.data('validator').resetForm();
  }
  form.find('.is-invalid').removeClass('is-invalid');
  form.find('.error').removeClass('error');
  
  $("#case_id_input").val("0");
  $("#case_money_unit").val("1").trigger("change");
  $('#modal-user-ids-container select[name="user_ids[]"]').val([]).trigger("change");
  
  $("#default_case").prop("checked", false).prop("disabled", false);
  $("#caseModalTitle").text("Yeni Kasa");
  $("#case-modal").modal("show");
});

$(document).on("click", ".edit-case", function (e) {
  e.preventDefault();
  var case_id = $(this).data("id");
  var form = $("#caseForm");
  
  form[0].reset();
  if (form.data('validator')) {
    form.data('validator').resetForm();
  }
  form.find('.is-invalid').removeClass('is-invalid');
  form.find('.error').removeClass('error');
  
  var formData = new FormData();
  formData.append("id", case_id);
  formData.append("action", "getCase");
  
  fetch("/api/financial/case.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        var c = data.case;
        $("#case_id_input").val(case_id);
        $("#case_name").val(c.case_name);
        $("#bank_name").val(c.bank_name);
        $("#branch_name").val(c.branch_name);
        $("#description").val(c.description);
        
        $("#case_money_unit").val(c.case_money_unit).trigger("change");
        $('#modal-user-ids-container select[name="user_ids[]"]').val(c.user_ids).trigger("change");
        
        if (c.isDefault == 1) {
          $("#default_case").prop("checked", true).prop("disabled", true);
        } else {
          $("#default_case").prop("checked", false).prop("disabled", false);
        }
        
        $("#caseModalTitle").text("Kasa Güncelle");
        $("#case-modal").modal("show");
      } else {
        Swal.fire({
          title: "Hata!",
          text: data.message,
          icon: "error",
          confirmButtonText: "Tamam"
        });
      }
    });
});

$(document).on("click", "#saveCase", function () {
  var form = $("#caseForm");

  form.validate({
    rules: {
      case_name: {
        required: true
      }
    },
    messages: {
      case_name: {
        required: "Kasa Adı boş bırakılamaz!"
      }
    }
  });
  if (!form.valid()) return false;

  var formData = new FormData(form[0]);

  fetch("/api/financial/case.php", {
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
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam"
      }).then((result) => {
        if (data.status == "success") {
          $("#case-modal").modal("hide");
          location.reload();
        }
      });
    });
});

$(document).on("click", ".delete-case", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteCase";
  let confirmMessage = "Kasa, tüm hareketleri ile birlikte silinecektir!";
  let url = "/api/financial/case.php";

  deleteRecord(this, action, confirmMessage, url);
});

//Kasayı vavrsayılan yapma
$(document).on("click", ".default-case", function () {
  let case_id = $(this).data("id");
  var formData = new FormData();
  formData.append("case_id", case_id);
  formData.append("action", "defaultCase");

  for (var pair of formData.entries()) {
    console.log(pair[0] + ", " + pair[1]);
  }
  fetch("/api/financial/case.php", {
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
      }).then((result) => {
        if (result.isConfirmed) {
          location.reload();
        }
      });
    });
});

//Kasalar arasında para transferi
$(document).on("click", ".intercash-transfer", function () {
  let modal = $("#intercash_transfer-modal");
  let case_id = $(this).data("id");

  var formData = new FormData();
  formData.append("case_id", case_id);
  formData.append("action", "getCases");

  // API'ye istek gönderiliyor
  fetch("/api/financial/case.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        // Başarılı yanıt alındığında kasa seçenekleri oluşturuluyor
        select = "<option value=''>Kasa Seçiniz!!</option>";
        $.each(data.cases, function (index, value) {
          select +=
            "<option value='" + value.id + "'>" + value.case_name + "</option>";
        });

        // Kasa seçenekleri HTML'e ekleniyor ve modal gösteriliyor
        $("#it_from_cases").val(case_id);
        $("#it_to_case").html(select);

        modal.modal("show"); 
      } else {
        title = "Hata!";
        Swal.fire({
          title: title,
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        });
      }
    });
});

$(document).on("click", "#add-case-transfer", function () {
  var form = $("#caseTransferForm");
  var formData = new FormData(form[0]);
  formData.append("action", "intercashTransfer");

  fetch("/api/financial/case.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        Swal.fire({
          title: "Başarılı!",
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        }).then((result) => {
          if (result.isConfirmed) {
            location.reload();
          }
        });
      } else {
        Swal.fire({
          title: "Hata!",
          html: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        });
      }
    });
});
