$(document).on("click", ".add-wage-cut", function () {
    let person_name = $(".full-name").text();
    let personel_id = $(this).data("id");
    if (!checkPersonId(personel_id)) {
      return;
    } 

  $("#wage_cut_modal").modal("show");
    $("#person_name_wage_cut").text(person_name);
    $("#person_id_wage_cut").val(personel_id);
  });
  
  //Modaldaki kaydet butonuna tıklanınca
  $(document).on("click", "#wage_cut_addButton", function () {
    let form = $("#wage_cut_modalForm");
    let urlParams = new URLSearchParams(window.location.search);
    let page = urlParams.get("p");
  
    let formData = new FormData(form[0]);
    formData.append("action", "saveWageCut");
    formData.append("page", page);
  
    // for (let pair of formData.entries()) {
    //   console.log(pair[0] + ", " + pair[1]);
    // }
  
    fetch("api/persons/wage_cut.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
          
          var wage_cut = data.wagecut_data;
          var income_expense = data.income_expense;
          
  
        //$("#total_payment").text(income_expense.total_payment);
        $("#total_income").text(income_expense.total_income);
        $("#total_expense").text(income_expense.total_expense);
        $("#balance").text(income_expense.balance);
  
  
  
        var table = $("#person_paymentTable").DataTable();
        table.row
          .add([
            wage_cut.id,
            wage_cut.gun,
            wage_cut.turu,
            wage_cut.ay,
            wage_cut.yil,
            wage_cut.kategori,
            wage_cut.tutar,
            wage_cut.aciklama,
            wage_cut.created_at,
            `<div class="dropdown">
                        <button class="btn dropdown-toggle align-text-top"
                            data-bs-toggle="dropdown">İşlem</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item route-link"
                                data-page="reports/ysc&id=<?php echo $item->id ?>" href="#">
                                <i class="ti ti-edit icon me-3"></i> Güncelle
                            </a>
                            <a class="dropdown-item delete-payment" href="#" data-id='${wage_cut.id}'>
                                <i class="ti ti-trash icon me-3"></i> Sil
                            </a>
                        </div>
                    </div>`,
          ])
          .order([0, 'desc'])
          .draw(false);
//    table.order([0, 'desc']).draw(false);
        
  
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
            $("#wage_cut-modal").modal("hide");
            form.trigger("reset");
          }
        });
      });
  });
  