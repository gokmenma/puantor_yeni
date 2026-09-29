$(document).on("click", ".add-progress-payment", function () {
  let project_id = $(this).data("id");
  if (!checkId(project_id, "Projeyi")) {
    return;
  }
  let project_name = $(this).closest("tr").find("td:eq(3)").text() || $(".page-title").text().trim();

  let form = $("#progress_payment_modalForm");
  form.trigger("reset");
  form.find('[name="progress_payment_id"]').val(0);
  $("#progress_payment_project_name").text(project_name);
  $("#progress_payment_project_id").val(project_id);
  $("#progress_payment_addButton").text("Hakediş Ekle");

  $("#progress-payment-modal").modal("show");
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

  var isEdit = form.find('[name="progress_payment_id"]').val() != "0" && form.find('[name="progress_payment_id"]').val() != "";
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
      $(".preloader").fadeOut();
      console.log(data);
      if (data.status == "success") {
        title = "Başarılı";
        
        if (typeof closeProjectModalSafely === "function") {
          closeProjectModalSafely("progress-payment-modal");
        } else {
          $("#progress-payment-modal").modal("hide");
          $(".modal-backdrop").remove();
          $("body").removeClass("modal-open").css({ overflow: "", paddingRight: "" });
        }

        if (page == "projects/manage") {
          let summary = data.summary;
          if (summary) {
            $("#total_income").text(summary.hakedis || "0,00 TRY");
            $("#balance").text(summary.balance || "0,00 TRY");
          }

          //Progress Barı güncelle
          let progress = data.progress;
          if (progress !== undefined && progress !== null) {
            $("#progress-bar").text(progress + "%");
            $(".progress-bar").css("width", progress + "%");
          }

          if (isEdit) {
            // Guncelleme yapildiysa sayfayi yenile
            setTimeout(() => { location.reload(); }, 1200);
          } else {
            let progress_payment = data.progress_payment;
            if (progress_payment) {
              progress_payment.project_id = $("#progress_payment_project_id").val();
              addDataToTable(progress_payment);
            }
          }

          form.trigger("reset");
          form.find('[name="progress_payment_id"]').val(0);
        }
      } else {
        title = "Hata";
      }

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
