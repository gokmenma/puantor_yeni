$(document).on("click", ".add-payment", function () {
  let personel_id = $(this).data("id");
  let personel_name = $(this).attr("data-name") || $(this).data("name") || $(this).closest("tr").attr("data-person-name") || $(this).closest("tr").find("td:eq(2)").text().trim() || $(this).closest("tr").find("td:eq(1)").text().trim();
  let balance = $(this).attr("data-balance") || "";
  $("#person_id_payment").val(personel_id);
  $("#person_name_payment").text(personel_name);

  $("#person_payment_balance").text("Bakiye :" + balance);
});

$(document).on("click", "#payment_addButton", function () {
  var urlParams = new URLSearchParams(window.location.search);
  var page = urlParams.get("p");

  var form = $("#payment_modalForm");
  addCustomValidationMethods(); // custom validation methodlarını çalıştır
  
  form.validate({
    rules: {
      payment_amount: {
        required: true,
        validNumber: true
      },
      payment_type: {
        required: true
      }
    },
    messages: {
      payment_amount: {
        required: "Lütfen bir miktar giriniz.",
        validNumber: "Lütfen geçerli bir miktar giriniz."
      },
      payment_type: {
        required: "Lütfen ödeme adını giriniz."
      }
    }
  });

  if (!form.valid()) {
    return;
  }

  var formData = new FormData(form[0]);

  formData.append("action", "savePayment");
  formData.append("page", page);

  // for (var pair of formData.entries()) {
  //     console.log(pair[0] + ', ' + pair[1]);
  // }

  fetch("api/persons/payment.php", {
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
          $("#payment-modal").modal("hide");
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
$(document).on("click", "#person_payment_balance", function () {
  let balanceText = $(this).text();
  let balanceNumber = parseFloat(
    balanceText.replace(/[^\d,-]/g, "").replace(",", ".")
  );

  if (balanceNumber < 0) {
    return;
  }
  let formattedVal = balanceNumber.toFixed(2).replace(".", ",");
  $("#payment_amount").val(formattedVal).trigger("input");
  $("#payment_type").val("Bakiye Ödemesi").focus();
});

// Toplu Personel Ödemesi Yap
$(document).ready(function () {
  if (typeof addCustomValidationMethods === "function") {
    addCustomValidationMethods();
  }
  if (typeof addCustomValidationValidValue === "function") {
    addCustomValidationValidValue();
  }

  if ($("#payToPersonsForm").length > 0) {
    $("#payToPersonsForm").validate({
      rules: {
        tps_action_date: {
          required: true
        },
        tps_cases: {
          required: true
        }
      },
      messages: {
        tps_action_date: {
          required: "Lütfen ödeme tarihini girin"
        },
        tps_cases: {
          required: "Lütfen ödeme yapılacak kasayı seçin"
        }
      },
      errorPlacement: function (error, element) {
        if (element.hasClass("select2")) {
          error.insertAfter(element.next("span"));
        } else {
          error.insertAfter(element);
        }
      }
    });

    var payToPersonsTable = null;

    function initPayToPersonsMasks() {
      if ($.fn.inputmask) {
        $("#payToPersons input.money").each(function () {
          if (!this._inputmask) {
            $(this).inputmask("decimal", {
              radixPoint: ",",
              groupSeparator: ".",
              digits: 2,
              autoGroup: true,
              rightAlign: false
            });
          }
        });
      }
    }

    if ($("#payToPersons").length > 0 && window.createDataTable) {
      payToPersonsTable = window.createDataTable("#payToPersons", {
        paging: false,
        scrollY: "350px",
        scrollCollapse: true,
        skipSearch: ["Personel", "Ödeme Tutarı"],
        layout: {
          bottomStart: "info",
          bottomEnd: null,
          topStart: null,
          topEnd: null
        },
        drawCallback: function () {
          initPayToPersonsMasks();
        }
      });

      // Hızlı ve debounced personel arama
      var paySearchTimeout = null;
      $(document).on("input", "#payToPersonsSearch", function () {
        var term = this.value;
        clearTimeout(paySearchTimeout);
        paySearchTimeout = setTimeout(function () {
          if (payToPersonsTable) {
            payToPersonsTable.search(term).draw();
          }
        }, 120);
      });

      // Ultra-hızlı ve debounced dinamik toplam hesaplama
      var payTotalTimeout = null;
      function updatePayToPersonsTotal() {
        clearTimeout(payTotalTimeout);
        payTotalTimeout = setTimeout(function () {
          var total = 0;
          var inputs = document.querySelectorAll("#payToPersons input.money");
          for (var i = 0; i < inputs.length; i++) {
            var val = inputs[i].value;
            if (val) {
              var cleanAmount = parseFloat(val.replace(/\./g, "").replace(",", ".")) || 0;
              total += cleanAmount;
            }
          }
          var formattedTotal = total.toLocaleString("tr-TR", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          });
          var totalEl = document.getElementById("payToPersonsTotal");
          if (totalEl) {
            totalEl.textContent = formattedTotal;
          }
        }, 30);
      }

      // Yalnızca input eventinde çalıştır
      $(document).on("input", "#payToPersons input.money", function () {
        updatePayToPersonsTotal();
      });

      // Modal açıldığında başlat
      $("#pay_to_persons-modal").on("shown.bs.modal", function () {
        if ($.fn.select2) {
          $("#pay_to_persons-modal .select2").select2({
            dropdownParent: $("#pay_to_persons-modal")
          });
        }
        if (typeof flatpickr !== 'undefined') {
          flatpickr("#tps_action_date", { dateFormat: "d.m.Y", locale: "tr" });
        }
        initPayToPersonsMasks();
        if (payToPersonsTable) {
          payToPersonsTable.columns.adjust().draw();
        }
        updatePayToPersonsTotal();
      });
    }

    $("#savePayToPersons").on("click", function () {
      if ($("#payToPersonsForm").valid()) {
        var person_ids = [];
        var amounts = [];

        var form = $("#payToPersonsForm");
        var formData = new FormData(form[0]);

        // Preloader göster
        $(".preloader").fadeIn();

        // Tüm satırlardaki değerleri topla
        var rows = document.querySelectorAll("#payToPersons tbody tr");
        for (var i = 0; i < rows.length; i++) {
          var row = rows[i];
          var idTd = row.querySelector("td[data-id]");
          var input = row.querySelector("input.money");
          if (idTd && input) {
            var person_id = idTd.getAttribute("data-id");
            var amountRaw = input.value;
            if (amountRaw && amountRaw !== "") {
              var cleanAmount = parseFloat(amountRaw.replace(/\./g, "").replace(",", ".")) || 0;
              if (cleanAmount > 0) {
                person_ids.push(person_id);
                amounts.push(amountRaw);
              }
            }
          }
        }

        if (person_ids.length === 0) {
          $(".preloader").fadeOut();
          Swal.fire({
            title: "Uyarı",
            text: "Lütfen en az bir personel için ödeme tutarı giriniz.",
            icon: "warning",
            confirmButtonText: "Tamam"
          });
          return;
        }

        formData.append("person_ids", person_ids.join(","));
        formData.append("amounts", amounts.join(","));
        formData.append("action", "payToPersons");

        fetch("api/financial/transaction.php", {
          method: "POST",
          body: formData
        })
          .then((response) => response.json())
          .then((data) => {
            $(".preloader").fadeOut();
            var title = data.status == "success" ? "Başarılı!" : "Hata";
            Swal.fire({
              title: title,
              text: data.message,
              icon: data.status,
              confirmButtonText: "Tamam"
            })
            .then((result) => {
              if (result.isConfirmed && data.status == "success") {
                location.reload();
              }
            });
          })
          .catch((error) => {
            $(".preloader").fadeOut();
            console.error("Error:", error);
            Swal.fire({
              title: "Hata",
              text: "Sistemde bir hata oluştu.",
              icon: "error",
              confirmButtonText: "Tamam"
            });
          });
      }
    });
  }
});

