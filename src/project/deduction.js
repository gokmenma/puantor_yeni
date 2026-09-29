$(document).on("click", ".add-deduction", function () {
  let project_id = $(this).data("id");
  if (!checkId(project_id, "Projeyi")) {
    return;
  }
  let project_name = $(this).closest("tr").find("td:eq(3)").text() || $(".page-title").text().trim();

  let form = $("#deduction_modalForm");
  form.trigger("reset");
  form.find('[name="deduction_id"]').val(0);
  $("#deduction_project_name").text(project_name);
  $("#deduction_project_id").val(project_id);
  $("#deduction_addButton").text("Kesinti Ekle");

  $("#deduction-modal").modal("show");
});

$(document).on("click", "#deduction_addButton", function () {
  var form = $("#deduction_modalForm");
  var urlParams = new URLSearchParams(window.location.search);
  var page = urlParams.get("p");

  addCustomValidationMethods(); //app.js içerisinde tanımlı(validNumber metodu)
  addCustomValidationValidValue(); //app.js içerisinde tanımlı(validValue metodu)
  form.validate({
    rules: {
      deduction_amount: {
        required: true,
        validNumber: true
      },
      deduction_date: {
        required: true
      },
      deduction_cases: {
        validValue: true
      }
    },
    messages: {
      deduction_amount: {
        required: "Lütfen miktarı girin",
        validNumber: "Geçerli bir miktar girin"
      },
      deduction_date: {
        required: "Tarih seçin"
      },
      deduction_cases: {
        validValue: "Lütfen bir seçim yapın"
      }
    }
  });
  if (!form.valid()) {
    return;
  }

  var isEdit = form.find('[name="deduction_id"]').val() != "0" && form.find('[name="deduction_id"]').val() != "";
  var formData = new FormData(form[0]);
  formData.append("action", "add_deduction");
  formData.append("page", page);

  //preloader göster
  $(".preloader").fadeIn();

  fetch("api/projects/deduction.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      $(".preloader").fadeOut();
      if (data.status == "success") {
        if (typeof closeProjectModalSafely === "function") {
          closeProjectModalSafely("deduction-modal");
        } else {
          $("#deduction-modal").modal("hide");
          $(".modal-backdrop").remove();
          $("body").removeClass("modal-open").css({ overflow: "", paddingRight: "" });
        }

        if (page == "projects/manage") {
          let summary = data.summary;
          if (summary) {
            $("#total_expense").text(summary.kesinti || "0,00 TRY");
            $("#balance").text(summary.balance || "0,00 TRY");
          }

          if (isEdit) {
            setTimeout(() => { location.reload(); }, 1200);
          } else {
            let deduction = data.last_deduction;
            if (deduction) {
              deduction.project_id = $("#deduction_project_id").val();
              addDataToTable(deduction); 
            }
          }

          form.trigger("reset");
          form.find('[name="deduction_id"]').val(0);
        }
      }
      let title = data.status == "success" ? "Başarılı!" : "Hata!";
      swal
        .fire({
          title: title,
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        })
        .then((result) => {
          if (page == "projects/list") {
            location.reload();
          }
        });
    });
});
