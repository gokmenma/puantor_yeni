$(document).on("click", ".add-payment", function () {
  let personel_id = $(this).data("id");
  if (!checkPersonId(personel_id)) {
    return;
  } 

  
$("#payment-modal").modal("show")

  let personel_name = $(".full-name").text();
  let balance = $("#balance").text();
  $("#person_id_payment").val(personel_id);
  $("#person_name_payment").text(personel_name);
  $("#person_payment_balance").text("Bakiye :" + balance);
});

$(document).on("click", "#payment_addButton", function () {
  var urlParams = new URLSearchParams(window.location.search);
  var page = urlParams.get("p");
  var form = $("#payment_modalForm");

  form.validate({
    rules: {
      payment_amount: {
        required: true,
        number: true,
      },
      payment_type: {
        required: true,
      },
    },
    messages: {
      payment_amount: {
        required: "Lütfen bir miktar giriniz.",
        number: "Lütfen geçerli bir miktar giriniz.",
      },
      payment_type: {
        required: "Lütfen ödeme adını giriniz.",
      },
    },
  });

  if (!form.valid()) {
    return;
  }

  var formData = new FormData(form[0]);

  formData.append("action", "savePayment");
  formData.append("page", page);

  for (var pair of formData.entries()) {
    console.log(pair[0] + ", " + pair[1]);
  }

  fetch("api/persons/payment.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        // console.log(data);
        //$("#total_payment").text(data.income_expense.total_payment);
        $("#total_income").text(data.income_expense.total_income);
        $("#total_expense").text(data.income_expense.total_expense);
        $("#balance").text(data.income_expense.balance);
        // DataTables row addition removed because location.reload() automatically redraws the updated table.

        // $("#payment-modal").modal("hide");
        form.trigger("reset");
        Swal.fire({
          icon: "success",
          title: "Başarılı!",
          text: data.message || "Ödeme başarıyla eklendi.",
        }).then(() => {
          location.reload();
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Hata!",
          text: "Ödeme eklenirken bir hata oluştu.",
        });
      }
    });
});

//Kalan bakiye tutarı ile ödeme alanını doldurma
$(document).on("click", "#person_payment_balance", function () {
  let balanceText = $(this).text();
  let balanceNumber = parseFloat(
    balanceText.replace(/[^\d,-]/g, "").replace(",", ".")
  );
  
  if(balanceNumber < 0){
    return
  }
  $("#payment_amount").val(balanceNumber);
  $("#payment_type").val("Bakiye Ödemesi").focus();
});



