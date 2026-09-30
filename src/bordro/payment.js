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

  var $modal = $("#pay_to_persons-modal");
  var $form = $("#payToPersonsForm");

  if ($modal.length > 0) {
    // Form Doğrulama
    $form.validate({
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
        if (element.hasClass("select2") || element.hasClass("select2-hidden-accessible")) {
          error.insertAfter(element.next(".select2"));
        } else {
          error.insertAfter(element);
        }
      }
    });

    // Para Maskesi Başlatma
    function initPayToPersonsMasks() {
      if ($.fn.inputmask) {
        $modal.find("input.money").each(function () {
          if (!this._inputmask) {
            $(this).inputmask("decimal", {
              radixPoint: ",",
              groupSeparator: ".",
              digits: 2,
              autoGroup: true,
              rightAlign: true,
              placeholder: "0,00"
            });
          }
        });
      }
    }

    // Dinamik Toplam ve Seçili Personel Sayısı Güncelleme
    function updatePayToPersonsSummary() {
      var total = 0;
      var count = 0;
      
      $modal.find("tbody tr.bulk-pay-row").each(function () {
        var $row = $(this);
        var $input = $row.find("input.bulk-pay-input");
        var val = $input.val();
        
        if (val && val.trim() !== "") {
          var cleanAmount = parseFloat(val.replace(/\./g, "").replace(",", ".")) || 0;
          if (cleanAmount > 0) {
            total += cleanAmount;
            count++;
            $row.addClass("row-has-amount");
          } else {
            $row.removeClass("row-has-amount");
          }
        } else {
          $row.removeClass("row-has-amount");
        }
      });

      var formattedTotal = total.toLocaleString("tr-TR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });

      $("#payToPersonsTotal").text(formattedTotal);
      $("#selectedPersonCount").text(count);
    }

    // Canlı Arama ve Filtreleme
    var filterOnlyBalance = false;
    function applyPayFilters() {
      var searchTerm = ($("#payToPersonsSearch").val() || "").trim().toLowerCase();
      var visibleCount = 0;

      $modal.find("tbody tr.bulk-pay-row").each(function () {
        var $row = $(this);
        var searchData = ($row.attr("data-search") || "").toLowerCase();
        var hasBalance = $row.attr("data-has-balance") === "1";

        var matchesSearch = !searchTerm || searchData.indexOf(searchTerm) > -1;
        var matchesBalance = !filterOnlyBalance || hasBalance;

        if (matchesSearch && matchesBalance) {
          $row.show();
          visibleCount++;
        } else {
          $row.hide();
        }
      });

      $("#visibleRowCount").text(visibleCount);
      if (searchTerm) {
        $("#clearPaySearch").show();
      } else {
        $("#clearPaySearch").hide();
      }
    }

    // Arama Input Olayı
    $(document).on("input", "#payToPersonsSearch", function () {
      applyPayFilters();
    });

    // Arama Temizleme
    $(document).on("click", "#clearPaySearch", function () {
      $("#payToPersonsSearch").val("").trigger("input").focus();
    });

    // Yalnızca Bakiyesi Olanlar Filtre Butonu
    $(document).on("click", "#btnToggleBalanceFilter", function () {
      filterOnlyBalance = !filterOnlyBalance;
      var $btn = $(this);
      if (filterOnlyBalance) {
        $btn.removeClass("btn-outline-secondary").addClass("btn-primary text-white");
        $("#filterBtnText").text("Tümünü Göster");
      } else {
        $btn.removeClass("btn-primary text-white").addClass("btn-outline-secondary");
        $("#filterBtnText").text("Yalnızca Alacağı Olanlar");
      }
      applyPayFilters();
    });

    // Tek Satır Bakiye Aktarma Butonu
    $(document).on("click", ".btn-transfer-balance", function (e) {
      e.preventDefault();
      var $row = $(this).closest("tr");
      var balanceRaw = $row.attr("data-balance");
      var balanceNum = parseFloat(balanceRaw) || 0;
      if (balanceNum > 0) {
        var formatted = balanceNum.toLocaleString("tr-TR", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });
        var $input = $row.find("input.bulk-pay-input");
        $input.val(formatted).trigger("input");
        
        $input.addClass("border-success");
        setTimeout(function () {
          $input.removeClass("border-success");
        }, 600);
      }
    });

    // Tüm Bakiyeleri Doldur Butonu
    $(document).on("click", "#btnFillAllBalances", function (e) {
      e.preventDefault();
      var filledCount = 0;
      $modal.find("tbody tr.bulk-pay-row").each(function () {
        var $row = $(this);
        // Eğer satır görünür ise veya arama yapılmamışsa
        if ($row.is(":visible")) {
          var balanceRaw = $row.attr("data-balance");
          var balanceNum = parseFloat(balanceRaw) || 0;
          if (balanceNum > 0) {
            var formatted = balanceNum.toLocaleString("tr-TR", {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            });
            $row.find("input.bulk-pay-input").val(formatted);
            filledCount++;
          }
        }
      });
      updatePayToPersonsSummary();
    });

    // Tüm Tutarları Sıfırla Butonu
    $(document).on("click", "#btnResetAllAmounts", function (e) {
      e.preventDefault();
      $modal.find("input.bulk-pay-input").val("");
      updatePayToPersonsSummary();
    });

    // Input Tutar Değişikliklerinde Toplam Güncelleme
    $(document).on("input change blur", "#pay_to_persons-modal input.bulk-pay-input", function () {
      updatePayToPersonsSummary();
    });

    // Modal Açıldığında
    $modal.on("shown.bs.modal", function () {
      if ($.fn.select2) {
        $modal.find(".select2").select2({
          dropdownParent: $modal,
          width: "100%"
        });
      }
      if (typeof flatpickr !== "undefined") {
        flatpickr("#tps_action_date", { dateFormat: "d.m.Y", locale: "tr" });
      }
      initPayToPersonsMasks();
      updatePayToPersonsSummary();
      applyPayFilters();
    });

    // Kaydetme İşlemi
    $(document).on("click", "#savePayToPersons", function () {
      if (!$form.valid()) {
        return;
      }

      var person_ids = [];
      var amounts = [];

      $modal.find("tbody tr.bulk-pay-row").each(function () {
        var $row = $(this);
        var personId = $row.attr("data-person-id");
        var $input = $row.find("input.bulk-pay-input");
        var val = $input.val();

        if (val && val.trim() !== "") {
          var cleanAmount = parseFloat(val.replace(/\./g, "").replace(",", ".")) || 0;
          if (cleanAmount > 0) {
            person_ids.push(personId);
            amounts.push(val);
          }
        }
      });

      if (person_ids.length === 0) {
        Swal.fire({
          title: "Uyarı",
          text: "Lütfen en az bir personel için ödenecek tutar giriniz.",
          icon: "warning",
          confirmButtonText: "Tamam"
        });
        return;
      }

      var $btn = $("#savePayToPersons");
      var originalBtnHtml = $btn.html();
      $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1.5" role="status" aria-hidden="true"></span> Kaydediliyor...');

      var formData = new FormData($form[0]);
      formData.append("person_ids", person_ids.join(","));
      formData.append("amounts", amounts.join(","));
      formData.append("action", "payToPersons");

      fetch("api/financial/transaction.php", {
        method: "POST",
        body: formData
      })
        .then((response) => response.json())
        .then((data) => {
          $btn.prop("disabled", false).html(originalBtnHtml);
          if (data.status === "success") {
            Swal.fire({
              title: "Başarılı!",
              text: data.message || "Toplu personel ödemesi başarıyla kaydedildi.",
              icon: "success",
              confirmButtonText: "Tamam"
            }).then(() => {
              $modal.modal("hide");
              location.reload();
            });
          } else {
            Swal.fire({
              title: "Hata",
              text: data.message || "Ödeme işlemi gerçekleştirilemedi.",
              icon: "error",
              confirmButtonText: "Tamam"
            });
          }
        })
        .catch((error) => {
          $btn.prop("disabled", false).html(originalBtnHtml);
          console.error("PayToPersons Error:", error);
          Swal.fire({
            title: "Hata",
            text: "Sistemde bir hata oluştu. Lütfen tekrar deneyin.",
            icon: "error",
            confirmButtonText: "Tamam"
          });
        });
    });
  }
});

