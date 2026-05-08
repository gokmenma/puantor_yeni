$(document).on("click", ".add-income", function () {
  let person_name = $(".full-name").text();
  let personel_id = $(this).data("id");
  if (!checkPersonId(personel_id)) {
    return;
  } 

  $("#income_modal").modal("show")
  $("#person_name_income").text(person_name);
  $("#person_id_income").val(personel_id);
});

//Modaldaki kaydet butonuna tıklanınca
$(document).on("click", "#income_addButton", function () {
  var urlParams = new URLSearchParams(window.location.search);
  var page = urlParams.get("p");


  let form = $("#income_modalForm");

  let formData = new FormData(form[0]);
  formData.append("action", "saveIncome");
  formData.append("page", page);


  fetch("api/persons/income.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      var income = data.income_data;
      var income_expense = data.income_expense;

      //$("#total_payment").text(income_expense.total_payment);
      $("#total_income").text(income_expense.total_income);
      $("#total_expense").text(income_expense.total_expense);
      $("#balance").text(income_expense.balance);

      var table = $("#person_paymentTable").DataTable();
      table.row
        .add([
          income.id,
          income.gun,
          income.turu,
          income.ay,
          income.yil,
          `<i class='ti ti-upload icon color-yellow me-1' ></i>
          ${income.kategori}`,
          income.tutar,
          income.aciklama,
          income.created_at,
          `<div class="dropdown">
                      <button class="btn dropdown-toggle align-text-top"
                          data-bs-toggle="dropdown">İşlem</button>
                      <div class="dropdown-menu dropdown-menu-end">
                          <a class="dropdown-item route-link"
                              data-page="reports/ysc&id=<?php echo $item->id ?>" href="#">
                              <i class="ti ti-edit icon me-3"></i> Güncelle
                          </a>
                          <a class="dropdown-item delete-payment" href="#" data-id='${income.id}'>
                              <i class="ti ti-trash icon me-3"></i> Sil
                          </a>
                      </div>
                  </div>`,
        ])
        .order([8, "desc"])
        .draw(false);

      if (data.status == "success") {
        title = "Başarılı!";
      } else {
        title = "Hata!";
      }
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam",
      }).then((result) => {
        if (result.isConfirmed) {
          $("#income-modal").modal("hide");
          form.trigger("reset");
        }
      });
    });
});


