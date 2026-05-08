$(document).on("click", ".add-income", function () {
    let personel_id = $(this).data("id");
    let personel_name = $(this).closest("tr").find("td:eq(1)").text();
    let balance = $(this).closest("tr").find("td:eq(9)").text();
    $("#person_id_income").val(personel_id);
    $("#person_name_income").text(personel_name);
    console.log(personel_name);
  
    $("#person_income_balance").text("Bakiye :" + balance);
  });
  
  $(document).on("click", "#income_addButton", function () {
    var urlParams = new URLSearchParams(window.location.search);
    var page = urlParams.get("p");
  
    var form = $("#income_modalForm");
    addCustomValidationMethods(); // custom validation methodlarını çalıştır
    form.validate({
      rules: {
        income_amount: {
          required: true,
          validNumber: true
        },
        income_type: {
          required: true
        }
      },
      messages: {
        income_amount: {
          required: "Lütfen bir miktar giriniz.",
          validNumber: "Lütfen geçerli bir miktar giriniz."
        },
        income_type: {
          required: "Lütfen ödeme adını giriniz."
        }
      }
    });
  
    if (!form.valid()) {
      return;
    }
  
    var formData = new FormData(form[0]);
  
    formData.append("action", "saveIncome");
    formData.append("page", page);
  
    // for (var pair of formData.entries()) {
    //     console.log(pair[0] + ', ' + pair[1]);
    // }
  
    fetch("api/persons/income.php", {
      method: "POST",
      body: formData
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status == "success") {
          console.log(data);
  
          form.trigger("reset");
          Swal.fire({
            icon: "success",
            title: "Başarılı!",
            text: data.message
          }).then(() => {
            $("#income-modal").modal("hide");
            location.reload();
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Hata!",
            text: data.message
          });
        }
      });
  });
  
  //Kalan bakiye tutarı ile ödeme alanını doldurma
  $(document).on("click", "#person_income_balance", function () {
    let balanceText = $(this).text();
    let balanceNumber = parseFloat(
      balanceText.replace(/[^\d,-]/g, "").replace(",", ".")
    );
  
    if (balanceNumber < 0) {
      return;
    }
    $("#income_amount").val(balanceNumber);
    $("#income_type").val("Bakiye Ödemesi").focus();
  });
  