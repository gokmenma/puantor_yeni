$(document).on("click", ".add-payment", function () {
  let project_id = $(this).data("id");
  if (!checkId(project_id, "Projeyi")) {
    return;
  }
  let project_name = $(this).closest("tr").find("td:eq(3)").text() || $(".page-title").text().trim();

  let form = $("#payment_modalForm");
  form.trigger("reset");
  form.find('[name="payment_id"]').val(0);
  $("#payment_project_name").text(project_name);
  $("#payment_project_id").val(project_id);
  $("#payment_addButton").text("Ödeme Ekle");

  $("#payment-modal").modal("show");
});

$(document).on("click", "#payment_addButton", function () {
  var form = $("#payment_modalForm");
  var urlParams = new URLSearchParams(window.location.search);
  var page = urlParams.get("p");
  addCustomValidationMethods(); //app.js içerisinde tanımlı(validNumber metodu)
  addCustomValidationValidValue(); //app.js içerisinde tanımlı(validValue metodu)

  form.validate({
    rules: {
      payment_amount: {
        required: true,
        validNumber: true
      },
      payment_date: {
        required: true
      },
      payment_cases: {
        validValue: true
      }
    },
    messages: {
      payment_amount: {
        required: "Lütfen miktarı girin",
        number: "Geçerli bir miktar girin"
      },
      payment_date: {
        required: "Tarih seçin"
      },
      payment_cases: {
        validValue: "Lütfen bir seçim yapın"
      }
    }
  });
  if (!form.valid()) {
    return;
  }

  var isEdit = form.find('[name="payment_id"]').val() != "0" && form.find('[name="payment_id"]').val() != "";
  var formData = new FormData(form[0]);
  formData.append("action", "add_payment");
  formData.append("page", page);

  //preloader göster
  $(".preloader").fadeIn();

  fetch("api/projects/payment.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      $(".preloader").fadeOut();
      if (data.status == "success") {
        title = "Başarılı";
       
        if (typeof closeProjectModalSafely === "function") {
          closeProjectModalSafely("payment-modal");
        } else {
          $("#payment-modal").modal("hide");
          $(".modal-backdrop").remove();
          $("body").removeClass("modal-open").css({ overflow: "", paddingRight: "" });
        }

        if (page == "projects/manage") {
          let summary = data.summary;
          if (summary) {
            $("#total_payment").text(summary.gelir || "0,00 TRY");
            $("#balance").text(summary.balance || "0,00 TRY");
          }

          if (isEdit) {
            setTimeout(() => { location.reload(); }, 1200);
          } else {
            let payment = data.last_payment;
            if (payment) {
              payment.project_id = $("#payment_project_id").val();
              addDataToTable(payment);
            }
          }

          form.trigger("reset");
          form.find('[name="payment_id"]').val(0);
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
        .then(() => {
          if (page == "projects/list") {
            location.reload();
          }
        });
    });
});
