window.lastAddPaymentTarget = null;

function fillPaymentModalData(sourceEl) {
  if (!sourceEl) return;
  let $el = $(sourceEl);
  let $tr = $el.closest("tr");

  let personel_id = $el.attr("data-id") || $el.data("id") || $tr.attr("data-id") || $tr.data("id");
  let personel_name = $el.attr("data-name") || $el.data("name") || $tr.attr("data-person-name") || $tr.find("td:eq(2)").text().trim() || $tr.find("td:eq(1)").text().trim();
  let balance = $el.attr("data-balance") || $el.data("balance") || $tr.attr("data-balance") || "";
  let rawBalance = $el.attr("data-balance-raw") || $el.data("balance-raw") || $tr.attr("data-balance-raw") || $tr.data("balance-raw");

  if (personel_id) {
    $("#person_id_payment").val(personel_id);
  }
  if (personel_name) {
    $("#person_name_payment").text(personel_name);
  }
  if (balance) {
    $("#person_payment_balance").text(balance);
  }

  // Bakiyeyi doğrudan ödeme tutarı alanına aktar (negatif tutarlar da mutlak değer olarak aktarılır)
  let balanceNumber = NaN;
  if (rawBalance !== undefined && rawBalance !== null && rawBalance !== "") {
    balanceNumber = parseFloat(rawBalance);
  } else if (balance) {
    let clean = String(balance).replace(/[^\d,-]/g, "").replace(",", ".");
    balanceNumber = parseFloat(clean);
  }

  let absAmount = Math.abs(balanceNumber);

  if (!isNaN(absAmount) && absAmount > 0) {
    let formattedVal = new Intl.NumberFormat('tr-TR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(absAmount);
    let simpleVal = absAmount.toFixed(2).replace(".", ",");

    let $amountInput = $("#payment_amount");
    $amountInput.val(formattedVal);
    if (!$amountInput.val()) {
      $amountInput.val(simpleVal);
    }
    $amountInput.trigger("input").trigger("change");

    let $typeInput = $("#payment_type");
    if (!$typeInput.val() || $typeInput.val() === "Maaş / Bakiye Ödemesi" || $typeInput.val() === "Bakiye Ödemesi") {
      $typeInput.val("Maaş / Bakiye Ödemesi");
    }
  } else {
    $("#payment_amount").val("0,00").trigger("input");
    let $typeInput = $("#payment_type");
    if (!$typeInput.val()) {
      $typeInput.val("Maaş / Bakiye Ödemesi");
    }
  }
}

$(document).on("click", ".add-payment", function (e) {
  if (e) {
    e.stopPropagation();
  }
  window.lastAddPaymentTarget = this;
  fillPaymentModalData(this);
});

$(document).on("show.bs.modal", "#payment-modal", function (e) {
  let target = e.relatedTarget || window.lastAddPaymentTarget;
  if (target) {
    fillPaymentModalData(target);
  }
});

$(document).on("shown.bs.modal", "#payment-modal", function (e) {
  let target = e.relatedTarget || window.lastAddPaymentTarget;
  if (target) {
    fillPaymentModalData(target);
  }
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
    String(balanceText).replace(/[^\d,-]/g, "").replace(",", ".")
  );

  let absAmount = Math.abs(balanceNumber);
  if (isNaN(absAmount) || absAmount <= 0) {
    return;
  }
  let formattedVal = new Intl.NumberFormat('tr-TR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(absAmount);
  let simpleVal = absAmount.toFixed(2).replace(".", ",");
  let $amountInput = $("#payment_amount");
  $amountInput.val(formattedVal);
  if (!$amountInput.val()) {
    $amountInput.val(simpleVal);
  }
  $amountInput.trigger("input").trigger("change");
  $("#payment_type").val("Maaş / Bakiye Ödemesi").focus();
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

    function escapeHtml(text) {
      if (!text) return "";
      return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    // Modal açıldığında canlı / güncel verileri sunucudan yükleme
    function loadPayToPersonsLiveBalances() {
      var month = $modal.find('input[name="period_month"]').val() || $("#months").val() || "";
      var year = $modal.find('input[name="period_year"]').val() || $("#year").val() || "";
      var projectId = $("#projects").val() || "";
      var teamId = $("#team_id").val() || "";

      var $tbody = $("#payToPersonsTableBody");
      $tbody.html(`
        <tr id="bulkPayLoadingRow">
          <td colspan="5" class="text-center py-4 text-muted small">
            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
            Güncel personel hakediş ve bakiye bilgileri yükleniyor...
          </td>
        </tr>
      `);

      $.ajax({
        url: "api/bordro/get-bulk-pay-data.php",
        type: "POST",
        data: {
          month: month,
          year: year,
          project_id: projectId,
          team_id: teamId
        },
        dataType: "json",
        success: function (res) {
          if (res.status === "success" && Array.isArray(res.persons)) {
            if (res.persons.length === 0) {
              $tbody.html(`
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted small">
                    <i class="ti ti-info-circle fs-2 d-block mb-1 text-secondary"></i>
                    Bu dönem için listelenecek personel bulunamadı.
                  </td>
                </tr>
              `);
              $("#totalPersonBadge").text("0 Personel");
              $("#visibleRowCount").text("0");
              updatePayToPersonsSummary();
              return;
            }

            var rowsHtml = "";
            res.persons.forEach(function (person) {
              var hasBalance = person.has_balance;
              var rawBalanceStr = Number(person.kalan).toFixed(2);
              var jobOrTc = person.job_name 
                ? `<div class="person-subtext">${escapeHtml(person.job_name)}</div>`
                : (person.tc_no ? `<div class="person-subtext">TC: ${escapeHtml(person.tc_no)}</div>` : "");

              rowsHtml += `
                <tr class="bulk-pay-row" 
                    data-person-id="${person.id}" 
                    data-balance="${rawBalanceStr}"
                    data-has-balance="${hasBalance ? '1' : '0'}"
                    data-search="${escapeHtml(person.search_data)}">
                    
                    <td class="ps-3.5 py-2.5">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar rounded-circle bg-${person.color}-lt person-avatar shadow-xs">
                                ${escapeHtml(person.initials)}
                            </span>
                            <div class="person-info">
                                <div class="person-name">
                                    ${escapeHtml(person.full_name)}
                                </div>
                                ${jobOrTc}
                            </div>
                        </div>
                    </td>

                    <td class="text-end py-2.5 text-muted small fw-medium d-none d-md-table-cell" style="font-size: 13px;">
                        ${escapeHtml(person.formatted_gelir)}
                    </td>

                    <td class="text-end py-2.5 text-muted small fw-medium d-none d-md-table-cell" style="font-size: 13px;">
                        ${escapeHtml(person.formatted_odenen)}
                    </td>

                    <td class="text-end py-2.5">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <span class="balance-text ${hasBalance ? 'fw-bold text-dark' : 'text-muted'}" style="font-size: 13px;">
                                ${escapeHtml(person.formatted_kalan)}
                            </span>
                            ${hasBalance ? `
                                <button type="button" class="btn btn-xs btn-outline-primary btn-transfer-balance py-1 px-1.5 shadow-none" 
                                    title="Bu bakiyeyi ödeme tutarına aktar" 
                                    style="border-radius: 6px; font-size: 11px; height: 26px; min-width: 26px;">
                                    <i class="ti ti-arrow-right" style="font-size: 13px;"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>

                    <td class="pe-3 py-2.5">
                        <div class="input-icon ms-auto" style="max-width: 140px;">
                            <span class="input-icon-addon text-muted fw-bold" style="font-size: 13px; left: 8px; min-width: auto;">₺</span>
                            <input type="text" class="form-control text-end money bulk-pay-input" 
                                placeholder="0,00" 
                                data-person-id="${person.id}"
                                data-raw-balance="${rawBalanceStr}"
                                style="font-size: 13px; font-weight: 600; height: 32px; padding-left: 24px; padding-right: 10px;">
                        </div>
                    </td>
                </tr>
              `;
            });

            $tbody.html(rowsHtml);
            $("#totalRowCount").text(res.persons.length);
            initPayToPersonsMasks();
            updatePayToPersonsSummary();
            applyPayFilters();
          }
        },
        error: function () {
          $tbody.html(`
            <tr>
              <td colspan="5" class="text-center py-4 text-danger small">
                <i class="ti ti-alert-triangle fs-2 d-block mb-1"></i>
                Güncel veriler yüklenirken bir hata oluştu.
              </td>
            </tr>
          `);
        }
      });
    }

    // Modal Açıldığında
    $modal.on("show.bs.modal", function () {
      loadPayToPersonsLiveBalances();
    });

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

      var payments = [];

      $modal.find("tbody tr.bulk-pay-row").each(function () {
        var $row = $(this);
        var personId = $row.attr("data-person-id");
        var $input = $row.find("input.bulk-pay-input");
        var val = $input.val();

        if (val && val.trim() !== "") {
          var cleanAmount = parseFloat(val.replace(/\./g, "").replace(",", ".")) || 0;
          if (cleanAmount > 0) {
            payments.push({
              person_id: parseInt(personId, 10),
              amount: cleanAmount
            });
          }
        }
      });

      if (payments.length === 0) {
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
      formData.append("payments_json", JSON.stringify(payments));
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

