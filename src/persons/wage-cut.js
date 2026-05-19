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
  
  
  
        // DataTables row addition removed because location.reload() automatically redraws the updated table.
        
  
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
          if (data.status == "success") {
            location.reload();
          } else {
            if (result.isConfirmed) {
              $("#wage_cut-modal").modal("hide");
              form.trigger("reset");
            }
          }
        });
      });
  });
  