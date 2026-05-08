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
        $("#id").val(data.lastid);
      } else {
        title = "Hata!";
      }
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam"
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
