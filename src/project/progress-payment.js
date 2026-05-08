$(document).on("click", ".add-progress-payment", function () {
  let project_id = $(this).data("id");
  if (!checkId(project_id, "Projeyi")) {
    return;
  }
  $("#progress-payment-modal").modal("show");
console.log(project_id);

  let project_name = $(this).closest("tr").find("td:eq(3)").text();

  $("#progress_payment_project_name").text(project_name);
  $("#progress_payment_project_id").val(project_id);
});

$(document).on("click", "#progress_payment_addButton", function () {
  //sayfa url'sindeki parametreleri almak için
  var urlParams = new URLSearchParams(window.location.search);
  var page = urlParams.get("p");

  addCustomValidationMethods(); //app.js içerisinde tanımlı(validNumber metodu)
  addCustomValidationValidValue(); //app.js içerisinde tanımlı(validValue metodu)

  var form = $("#progress_payment_modalForm");

  form.validate({
    rules: {
      progress_payment_amount: {
        required: true,
        validNumber: true
      },
      progress_payment_date: {
        required: true
      },
      progress_payment_cases: {
        validValue: true
      }
    },
    messages: {
      progress_payment_amount: {
        required: "Lütfen miktarı girin",
        validNumber: "Geçerli bir miktar girin"
      },
      progress_payment_date: {
        required: "Tarih seçin"
      },
      progress_payment_cases: {
        validValue: "Lütfen bir seçim yapın"
      }
    }
  });
  if (!form.valid()) {
    return;
  }

  var formData = new FormData(form[0]);
  formData.append("page", page);
  formData.append("action", "add_progress_payment");

  //preloader göster
  $(".preloader").fadeIn();

  fetch("api/projects/progress-payment.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      /*
      @ projects/manage hakediş bilgileri alanında hakediş eklerken 
      @ tabloya ekleme yapmak ve özet bilgileri sayfa yenilenmeden getirmek için 
      */
      console.log(data);
      if (data.status == "success") {
        title = "Başarılı";

        if (page == "projects/manage") {
          let progress_payment = data.progress_payment;
          //Projenin gelir-gider, bakiye bilgilerini almak için
          progress_payment.project_id = $("#progress_payment_project_id").val();
          //gelen veriler ile birlikte tabloya satır ekleme
          addDataToTable(progress_payment);

          let summary = data.summary;
          $("#progress_payment_modalForm").trigger("reset");
          $("#total_income").text(summary.hakedis);
          $("#balance").text(summary.balance);

          //Progress Barı güncelle
          let progress = data.progress;
          $("#progress-bar").text(progress + "%");
          $(".progress-bar").css("width", progress + "%");
        }
      } else {
        title = "Hata";
      }
      //preloader gizle
      $(".preloader").fadeOut();
      swal
        .fire({
          title: title,
          text: data.message,
          icon: data.status
        })
        .then((result) => {
          if (page == "projects/list") {
            location.reload();
          }
        });
    });
});
