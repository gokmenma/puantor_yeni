$(document).on("click", ".add-payment", function () {
  let project_id = $(this).data("id");
  if (!checkId(project_id, "Projeyi")) {
    return;
  }
  $("#payment-modal").modal("show");
  let project_name = $(this).closest("tr").find("td:eq(3)").text();
  $("#payment_project_name").text(project_name);
  $("#payment_project_id").val(project_id);
});

$(document).on("click", "#payment_addButton", function () {
  var form = $("#payment_modalForm");
  var urlParams = new URLSearchParams(window.location.search);
  var page = urlParams.get("p");
  var formData = new FormData(form[0]);
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

  formData.append("action", "add_payment");
  formData.append("page", page);

  // for (var pair of formData.entries()) {
  //   console.log(pair[0] + ", " + pair[1]);
  // }

  fetch("api/projects/payment.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      /*
        projects/manage sayfasında eklenen ödemenin verilerini tabloya eklemek ve 
        özet bilgilerini sayfa yenilenmeden güncellemek için
      */
      if (data.status == "success") {
        title = "Başarılı";
       
        if (page == "projects/manage") {
          console.log(data);

          let payment = data.last_payment;
          //Projenin gelir-gider, bakiye bilgilerini almak için
          payment.project_id = $("#payment_project_id").val();
          //gelen veriler ile birlikte tabloya satır ekleme
          addDataToTable(payment);

          let summary = data.summary;
          //Ödemelerin toplamını ve bakiyeyi güncelle
          $("#total_payment").text(summary.gelir);
          $("#balance").text(summary.balance);
          $("#payment-modalForm").trigger("reset");
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
