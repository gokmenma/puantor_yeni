$(document).on("click", ".add-expense", function () {
  let project_id = $(this).data("id");
  if (!checkId(project_id, "Projeyi")) {
    return;
  }
  $("#expense-modal").modal("show");

  let project_name = $(this).closest("tr").find("td:eq(3)").text();

  console.log(project_name);

  $("#expense_project_name").text(project_name);
  $("#expense_project_id").val(project_id);
});

// function addCustomValidationMethods() {
//     $.validator.addMethod(
//       "validNumber",
//       function (value, element) {
//         return this.optional(element) || /^[0-9.,]+$/.test(value);
//       },
//       "Lütfen geçerli bir sayı girin"
//     );
//   }

$(document).on("click", "#expense_addButton", function () {
  var form = $("#expense_modalForm");
  var urlParams = new URLSearchParams(window.location.search);
  var page = urlParams.get("p");

  var formData = new FormData(form[0]);
  formData.append("action", "add_expense");
  formData.append("page", page);

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

  fetch("api/projects/expense.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        if (page == "projects/manage") {
          let expense = data.last_expense;
          //Projenin gelir-gider, bakiye bilgilerini almak için
          expense.project_id = $("#expense_project_id").val();
          //gelen veriler ile birlikte tabloya satır ekleme
          addDataToTable(expense);
          //   let summary = data.summary;
          //   $("#expense_modalForm").trigger("reset");
          //   $("#project_total_expense").text(summary.total_expense);
          //   $("#project_balance").text(summary.balance);
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
