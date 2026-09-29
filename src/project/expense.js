$(document).on("click", ".add-expense", function () {
  let project_id = $(this).data("id");
  if (!checkId(project_id, "Projeyi")) {
    return;
  }
  let project_name = $(this).closest("tr").find("td:eq(3)").text() || $(".page-title").text().trim();

  let form = $("#expense_modalForm");
  form.trigger("reset");
  form.find('[name="expense_id"]').val(0);
  $("#expense_project_name").text(project_name);
  $("#expense_project_id").val(project_id);
  $("#expense_addButton").text("Masraf Ekle");

  $("#expense-modal").modal("show");
});

$(document).on("click", "#expense_addButton", function () {
  var form = $("#expense_modalForm");
  var urlParams = new URLSearchParams(window.location.search);
  var page = urlParams.get("p");

  addCustomValidationMethods(); //app.js içerisinde tanımlı(validNumber metodu)

  form.validate({
    rules: {
      expense_amount: {
        required: true,
        validNumber: true
      },
      expense_date: {
        required: true
      }
    },
    messages: {
      expense_amount: {
        required: "Lütfen miktarı girin",
        validNumber: "Geçerli bir miktar girin"
      },
      expense_date: {
        required: "Tarih seçin"
      }
    }
  });
  if (!form.valid()) {
    return;
  }

  var isEdit = form.find('[name="expense_id"]').val() != "0" && form.find('[name="expense_id"]').val() != "";
  var formData = new FormData(form[0]);
  formData.append("action", "add_expense");
  formData.append("page", page);

  //preloader göster
  $(".preloader").fadeIn();

  fetch("api/projects/expense.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      $(".preloader").fadeOut();
      if (data.status == "success") {
        if (typeof closeProjectModalSafely === "function") {
          closeProjectModalSafely("expense-modal");
        } else {
          $("#expense-modal").modal("hide");
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
            let expense = data.last_expense;
            if (expense) {
              expense.project_id = $("#expense_project_id").val();
              addDataToTable(expense);
            }
          }

          form.trigger("reset");
          form.find('[name="expense_id"]').val(0);
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
