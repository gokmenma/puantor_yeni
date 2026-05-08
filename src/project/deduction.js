$(document).on("click", ".add-deduction", function () {
  let project_id = $(this).data("id");
  if (!checkId(project_id, "Projeyi")) {
    return;
  }
  $("#deduction-modal").modal("show");

  let project_name = $(this).closest("tr").find("td:eq(3)").text();

  console.log(project_name);

  $("#deduction_project_name").text(project_name);
  $("#deduction_project_id").val(project_id);
});

$(document).on("click", "#deduction_addButton", function () {
  var form = $("#deduction_modalForm");
  var urlParams = new URLSearchParams(window.location.search);
  var page = urlParams.get("p");

  var formData = new FormData(form[0]);
  formData.append("action", "add_deduction");
  formData.append("page", page);

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

  fetch("api/projects/deduction.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        if (page == "projects/manage") {
          let deduction = data.last_deduction;
          //console.log(data);
          //Projenin gelir-gider, bakiye bilgilerini almak için
          deduction.project_id = $("#deduction_project_id").val();
          //gelen veriler ile birlikte tabloya satır ekleme
          addDataToTable(deduction); 
            let summary = data.summary;
            $("#deduction_modalForm").trigger("reset");
            $("#total_expense").text(summary.kesinti);
            $("#balance").text(summary.balance);
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
