var hasProcess = false;
var transactionTable = null;

$(document).ready(function () {
  initTransactionDataTable();
  initTransactionModals();

  addCustomValidationMethods();
  addCustomValidationValidValue();

  //genel modal form kontrolleri
  $("#transactionModalForm").validate({
    rules: {
      amount: {
        required: true,
        validNumber: true
      },
      gm_case_id: {
        required: true,
        validValue: true
      },
      gm_incexp_type: {
        required: true,
        validValue: true
      }
    },
    messages: {
      amount: {
        required: "Lütfen tutar giriniz",
        validNumber: "Lütfen geçerli bir tutar giriniz!"
      },
      gm_case_id: {
        required: "Lütfen bir kasa seçiniz!",
        validValue: "Lütfen bir kasa seçiniz!"
      },
      gm_incexp_type: {
        required: "İşlem Türünü seçiniz!",
        validValue: "İşlem Türünü seçiniz!"
      }
    },
    errorPlacement: function (error, element) {
      customErrorPlacement(error, element);
    }
  });
});

$(document).on("page:loaded content:loaded", function () {
  initTransactionDataTable();
  initTransactionModals();
});

/**
 * DataTable Başlatma ve Sütun Yönetimi (ServerSide)
 */
function initTransactionDataTable() {
  var $table = $("#transactionTable");
  if (!$table.length) return;

  if (typeof $ !== "undefined" && $.fn && $.fn.DataTable && $.fn.DataTable.isDataTable("#transactionTable")) {
    transactionTable = $("#transactionTable").DataTable();
    return;
  }

  var options = {
    processing: true,
    serverSide: true,
    autoWidth: false,
    searchDelay: 400,
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    order: [[3, "desc"]],
    ajax: {
      url: "api/financial/list.php",
      type: "POST",
      data: function (d) {
        d.case_id = $("#firm_cases").val() || "";
        d.transaction_type = $('input[name="transaction_type_filter"]:checked').val() || "";
      }
    },
    columnDefs: [
      { targets: [0, 8], orderable: false, searchable: false },
      { targets: 0, className: "text-center no-export" },
      { targets: [1, 3], className: "text-center" },
      { targets: [6], className: "text-end" },
      { targets: [8], className: "text-end no-export actions-column" }
    ],
    language: {
      url: "src/tr.json",
      processing: '<span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...'
    },
    initComplete: function () {
      var api = this.api();
      buildTransactionColvisMenu(api);
      if (typeof window.initDataTableColumnFilters === "function") {
        window.initDataTableColumnFilters($("#transactionTable"), api);
      }
    },
    drawCallback: function (settings) {
      $(".select-all-transactions").prop("checked", false);
      if (typeof toggleBulkDeleteTransactionsButton === "function") {
        toggleBulkDeleteTransactionsButton();
      }
      if (settings && settings.json && settings.json.stats) {
        updateTransactionSummaryCards(settings.json.stats);
      }
    }
  };

  $table.find("thead .search-input-row").remove();

  transactionTable = $("#transactionTable").DataTable(options);
}

function updateTransactionSummaryCards(stats) {
  if (!stats) return;
  if ($("#kpiTotalTransactions").length) {
    $("#kpiTotalTransactions").text(stats.formatted_total_count || "0");
  }
  if ($("#kpiTotalIncome").length) {
    $("#kpiTotalIncome").text(stats.formatted_total_income || "0,00 ₺");
  }
  if ($("#kpiIncomeCount").length) {
    $("#kpiIncomeCount").text((stats.income_count || 0) + " Adet");
  }
  if ($("#kpiTotalExpense").length) {
    $("#kpiTotalExpense").text(stats.formatted_total_expense || "0,00 ₺");
  }
  if ($("#kpiExpenseCount").length) {
    $("#kpiExpenseCount").text((stats.expense_count || 0) + " Adet");
  }
  if ($("#kpiNetBalance").length) {
    var net = parseFloat(stats.net_balance) || 0;
    $("#kpiNetBalance")
      .text(stats.formatted_net_balance || "0,00 ₺")
      .removeClass("text-success text-danger")
      .addClass(net >= 0 ? "text-success" : "text-danger");
  }
  if ($("#kpiNetBalanceBadge").length) {
    var net = parseFloat(stats.net_balance) || 0;
    $("#kpiNetBalanceBadge")
      .text(net >= 0 ? "+ Fazla" : "- Açık")
      .removeClass("bg-success-lt text-success bg-danger-lt text-danger")
      .addClass(net >= 0 ? "bg-success-lt text-success" : "bg-danger-lt text-danger");
  }
}

/**
 * Sütun Göster / Gizle Menüsü
 */
function buildTransactionColvisMenu(api) {
  var $menu = $("#transactionColvisMenu");
  if (!$menu.length || !api) return;

  var columnConfig = {
    2: { label: "Kasa", default: true },
    3: { label: "Tarih", default: true },
    4: { label: "İşlem Türü", default: true },
    5: { label: "Hesap / Muhatap", default: true },
    6: { label: "Tutar", default: true },
    7: { label: "Açıklama", default: true }
  };

  var savedVisibility = localStorage.getItem("transactions_column_visibility");
  var visibilityState = savedVisibility ? JSON.parse(savedVisibility) : {};

  var menuHtml = "";
  $.each(columnConfig, function (idx, conf) {
    var isVisible = visibilityState.hasOwnProperty(idx) ? visibilityState[idx] : conf.default;
    api.column(idx).visible(isVisible, false);

    menuHtml += `
      <label class="dropdown-item d-flex align-items-center cursor-pointer py-1.5 px-3 rounded-2" style="font-size: 0.85rem;">
        <div class="form-check mb-0 w-100">
          <input class="form-check-input transactions-col-trigger" type="checkbox" id="colCheck_${idx}" data-column="${idx}" ${isVisible ? "checked" : ""}>
          <span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">
            ${conf.label}
          </span>
        </div>
      </label>`;
  });

  $menu.html(menuHtml);
  api.columns.adjust();
}

// Sütun Görünürlüğü Değiştiğinde
$(document).on("change", ".transactions-col-trigger", function () {
  if (!transactionTable) {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable("#transactionTable")) {
      transactionTable = $("#transactionTable").DataTable();
    }
  }
  if (!transactionTable) return;

  var colIdx = parseInt($(this).data("column"));
  var isChecked = this.checked;
  transactionTable.column(colIdx).visible(isChecked);

  var savedVisibility = localStorage.getItem("transactions_column_visibility");
  var visibilityState = savedVisibility ? JSON.parse(savedVisibility) : {};
  visibilityState[colIdx] = isChecked;
  localStorage.setItem("transactions_column_visibility", JSON.stringify(visibilityState));
});

$(document).on("click", "#transactionColvisMenu", function (e) {
  e.stopPropagation();
});

// Checkbox Seçim Yönetimi (Tümünü Seç / Tekil Seçim)
$(document).on("change", ".select-all-transactions", function () {
  var isChecked = $(this).prop("checked");
  $(".transaction-checkbox:not(:disabled)").prop("checked", isChecked);
  toggleBulkDeleteTransactionsButton();
});

$(document).on("change", ".transaction-checkbox", function () {
  var total = $(".transaction-checkbox:not(:disabled)").length;
  var checked = $(".transaction-checkbox:checked").length;
  $(".select-all-transactions").prop("checked", total > 0 && total === checked);
  toggleBulkDeleteTransactionsButton();
});

function toggleBulkDeleteTransactionsButton() {
  var selectedCount = $(".transaction-checkbox:checked").length;
  if (selectedCount > 0) {
    $("#btnDeleteSelectedTransactions").removeClass("d-none");
    $("#btnDeleteSelectedTransactions").html(
      '<i class="ti ti-trash icon me-1"></i> Seçilenleri Sil (' + selectedCount + ')'
    );
  } else {
    $("#btnDeleteSelectedTransactions").addClass("d-none");
  }
}

// Toplu Kasa Hareketi Silme Aksiyonu
$(document).on("click", "#btnDeleteSelectedTransactions", function () {
  var selectedItems = [];
  $(".transaction-checkbox:checked").each(function () {
    selectedItems.push({
      id: $(this).val(),
      type: $(this).data("type") || "",
      table: $(this).data("table") || ""
    });
  });

  if (selectedItems.length === 0) {
    Swal.fire({
      title: "Hata!",
      text: "Lütfen silmek istediğiniz kasa hareketlerini seçin.",
      icon: "error"
    });
    return;
  }

  Swal.fire({
    title: "Emin misiniz?",
    html: "Seçilen <strong>" + selectedItems.length + "</strong> adet kasa hareketi silinecektir!<br><span class=\"text-danger\">Bu işlem geri alınamaz!</span>",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Evet, Sil!",
    cancelButtonText: "İptal"
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: "Siliniyor...",
        text: "Lütfen bekleyin.",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      var formData = new FormData();
      formData.append("action", "bulkDeleteTransactions");
      selectedItems.forEach(function (item, idx) {
        formData.append("items[" + idx + "][id]", item.id);
        formData.append("items[" + idx + "][type]", item.type);
        formData.append("items[" + idx + "][table]", item.table);
      });

      fetch("/api/financial/transaction.php", {
        method: "POST",
        body: formData
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            Swal.fire({
              title: "Başarılı!",
              text: data.message,
              icon: "success"
            });
            if (transactionTable) {
              transactionTable.ajax.reload(null, false);
            }
          } else {
            Swal.fire({
              title: "Hata!",
              text: data.message || "Silme işlemi sırasında bir hata oluştu.",
              icon: "error"
            });
          }
        })
        .catch((error) => {
          console.error("Bulk delete error:", error);
          Swal.fire({
            title: "Hata!",
            text: "Sunucu ile iletişim kurulurken bir hata oluştu.",
            icon: "error"
          });
        });
    }
  });
});

// Tür Filtresi (Tümü / Gelir / Gider)
$(document).on("change", ".type-filter", function () {
  if (!transactionTable) {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable("#transactionTable")) {
      transactionTable = $("#transactionTable").DataTable();
    }
  }
  if (transactionTable && transactionTable.ajax) {
    transactionTable.ajax.reload(null, true);
  } else if (transactionTable) {
    var val = $(this).val();
    transactionTable.column(4).search(val ? val : "").draw();
  }
});

// Hızlı Genel Arama Inputu
var searchTimer = null;
$(document).on("input", "#transactions-fast-search", function () {
  var val = this.value;
  $("#transactions-search-clear").toggleClass("d-none", val.length === 0);
  clearTimeout(searchTimer);
  searchTimer = setTimeout(function () {
    if (!transactionTable) {
      if ($.fn.DataTable && $.fn.DataTable.isDataTable("#transactionTable")) {
        transactionTable = $("#transactionTable").DataTable();
      }
    }
    if (transactionTable) {
      transactionTable.search(val).draw();
    }
  }, 300);
});

$(document).on("click", "#transactions-search-clear", function () {
  clearTimeout(searchTimer);
  $("#transactions-fast-search").val("").trigger("focus");
  $(this).addClass("d-none");
  if (!transactionTable) {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable("#transactionTable")) {
      transactionTable = $("#transactionTable").DataTable();
    }
  }
  if (transactionTable) {
    transactionTable.search("").draw();
  }
});

// Özet Kartları Daraltma/Genişletme Butonu
function syncSummaryToggle() {
  var $summaryToggle = $("#toggleTransactionSummary");
  if (!$summaryToggle.length) return;
  var isCollapsed = document.documentElement.classList.contains("transactions-summary-collapsed");
  $summaryToggle
    .attr("aria-expanded", String(!isCollapsed))
    .attr("aria-label", isCollapsed ? "Özet kartlarını göster" : "Özet kartlarını gizle")
    .attr("title", isCollapsed ? "Özet kartlarını göster" : "Özet kartlarını gizle");
  $summaryToggle.find("i")
    .toggleClass("ti-chevron-up", !isCollapsed)
    .toggleClass("ti-chevron-down", isCollapsed);
}

$(document).ready(function () {
  syncSummaryToggle();
});

$(document).on("click", "#toggleTransactionSummary", function () {
  var isCollapsed = document.documentElement.classList.toggle("transactions-summary-collapsed");
  try {
    localStorage.setItem("transactions_summary_collapsed", isCollapsed ? "1" : "0");
  } catch (e) {}
  syncSummaryToggle();
});

// Tabloda Sağ Tık (Custom Context Menu)
$(document).on("contextmenu", "#transactionTable tbody tr", function (e) {
  var $tr = $(this);
  var $editBtn = $tr.find(".edit-transactions");
  var $deleteBtn = $tr.find(".delete-transaction");

  if (!$editBtn.length && !$deleteBtn.length) return;

  e.preventDefault();
  $("#transactionTable tbody tr").removeClass("context-menu-active");
  $tr.addClass("context-menu-active");

  var rowTitle = $tr.find("td:eq(5)").text().trim() || $tr.find("td:eq(2)").text().trim() || "Kasa Hareketi";
  var editId = $editBtn.attr("data-id") || "";
  var deleteId = $deleteBtn.attr("data-id") || "";

  var $contextMenu = $("#customContextMenu");
  if (!$contextMenu.length) {
    $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo("body");
  }

  var menuHtml = `
    <div class="cm-header"><i class="ti ti-arrows-diff me-1"></i> ${$("<div>").text(rowTitle).html()}</div>
    ${editId ? `<a href="javascript:void(0);" class="cm-edit"><i class="ti ti-edit"></i> Güncelle / Detay</a>` : ""}
    ${deleteId ? `<div class="cm-divider"></div><a href="javascript:void(0);" class="cm-danger cm-delete"><i class="ti ti-trash"></i> Hareketi Sil</a>` : ""}
  `;

  $contextMenu.html(menuHtml);
  $contextMenu.css({ display: "block", opacity: 0 });

  $contextMenu.find(".cm-edit").off("click").on("click", function () {
    $editBtn.trigger("click");
    $contextMenu.hide();
  });

  $contextMenu.find(".cm-delete").off("click").on("click", function () {
    $deleteBtn.trigger("click");
    $contextMenu.hide();
  });

  var menuWidth = $contextMenu.outerWidth();
  var menuHeight = $contextMenu.outerHeight();
  var clickX = e.clientX;
  var clickY = e.clientY;
  var windowWidth = $(window).width();
  var windowHeight = $(window).height();

  var posX = (clickX + menuWidth > windowWidth) ? windowWidth - menuWidth - 10 : clickX;
  var posY = (clickY + menuHeight > windowHeight) ? windowHeight - menuHeight - 10 : clickY;

  $contextMenu.css({
    top: posY + "px",
    left: posX + "px",
    opacity: 1
  });
});

$(document).on("click", function (e) {
  if (!$(e.target).closest("#customContextMenu").length) {
    $("#customContextMenu").hide();
    $("#transactionTable tbody tr").removeClass("context-menu-active");
  }
});

$(window).on("scroll resize blur", function () {
  $("#customContextMenu").hide();
  $("#transactionTable tbody tr").removeClass("context-menu-active");
});

/**
 * Excel Dışa Aktarma Butonu
 */
$(document).on("click", "#btnExportTransactionExcel", function (e) {
  e.preventDefault();
  if (transactionTable && transactionTable.button) {
    var dtBtn = transactionTable.button(".buttons-excel");
    if (dtBtn && dtBtn.length) {
      dtBtn.trigger();
      return;
    }
  }
  var $dtBtn = $(".buttons-excel");
  if ($dtBtn.length) {
    $dtBtn.trigger("click");
  } else {
    Swal.fire({
      title: "Bilgi",
      text: "Dışa aktarma işlemi hazırlanıyor...",
      icon: "info",
      timer: 1500,
      showConfirmButton: false
    });
  }
});

/**
 * Modal ve Select2 / Flatpickr Hazırlıkları
 */
function initTransactionModals() {
  if (typeof flatpickr === "function") {
    $(".flatpickr").flatpickr({
      dateFormat: "d.m.Y",
      allowInput: true,
      locale: {
        firstDayOfWeek: 1
      }
    });
  }
}

//Genel modal kaydet butonuna basınca
$(document).on("click", "#saveTransaction", function () {
  var form = $("#transactionModalForm");
  //Eğer tüm kontroller doğru ise
  if (form.valid()) {
    let formData = new FormData(form[0]);
    let id = $("#transaction_id").val();
    formData.append("transaction_id", id);
    formData.append("action", "saveTransaction");
    // for (var pair of formData.entries()) {
    //   console.log(pair[0] + ", " + pair[1]);
    // }

    fetch("/api/financial/transaction.php", {
      method: "POST",
      body: formData
    })
      .then((response) => response.json())
      .then((data) => {
        console.log(data);

        if (data.status == "success") {
          title = "Başarılı!";
        } else {
          title = "Hata!";
        }
        Swal.fire({
          title: title,
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        }).then((result) => {
          if (result.isConfirmed) {
            //$("#amount").val("");
            hasProcess = true;
          }
        });
      })
      .catch((error) => {
        console.error("Error:", error);
      });
  }
});

//general-modal veya diğer modallar kapatıldığında ID'yi sıfırla
$(".modal").on("hidden.bs.modal", function () {
  $("#transaction_id").val(0);
  //console.log(hasProcess);

  if (hasProcess === true) {
    window.location.reload();
  }
});

$(document).on("click", ".delete-transaction", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteTransaction";
  let confirmMessage = "Kasa hareketi silinecektir!";
  let type = $(this).data("type") || "";
  let table = $(this).data("table") || "";
  let url = "/api/financial/transaction.php?type=" + encodeURIComponent(type) + "&table=" + encodeURIComponent(table);

  deleteRecord(this, action, confirmMessage, url);
});

$('input[name="amount"]').keypress(function (e) {
  if ((e.which < 48 || e.which > 57) && e.which != 46) {
    return false;
  }
});

// Alt türleri yükle
function loadSubTypes(type, selectedId = null) {
  var formData = new FormData();
  formData.append("action", "getSubTypes");
  formData.append("type", type);

  return fetch("api/financial/transaction.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      $("#gm_incexp_type").html("");
      var options = "<option value=''>Tür Seçiniz</option>";
      data = data.subTypes;
      data.forEach((element) => {
        options += `<option value="${element.id}">${element.name}</option>`;
      });
      $("#gm_incexp_type").html(options);
      if (selectedId) {
        $("#gm_incexp_type").val(selectedId).trigger("change");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

$(document).on("click", ".transaction_type", function () {
  loadSubTypes($(this).val());
});

$(document).on("change", "#firm_cases", function () {
  if (transactionTable && transactionTable.ajax) {
    transactionTable.ajax.reload(null, true);
  } else {
    var case_id = $(this).val();
    var form = $("#caseForm");
    form.append(`<input type="hidden" name="case_id" value="${case_id}">`);
    form.submit();
  }
});
let isTriggeringChange = false;

function clearAndTrigger(selectors) {
  if (!isTriggeringChange) {
    isTriggeringChange = true;
    $(selectors).val(0).trigger("change");
    isTriggeringChange = false;
  }
}

$(document).on("change", "#gm_project_id", function () {
  clearAndTrigger("#gm_person_name, #gm_company");
});

$(document).on("change", "#gm_person_name", function () {
  clearAndTrigger("#gm_company, #gm_project_id");
});

$(document).on("change", "#gm_company", function () {
  clearAndTrigger("#gm_project_id, #gm_person_name");
});

// select2 elemanlarında seçim yapıldığında validator'ı tekrar çalıştır
$(".select2").on("change", function () {
  $(this).valid();
});
//projeden ödeme al
$(document).on("click", "#savePaymentFromProject", function () {
  var id = $("#transaction_id").val();

  addCustomValidationMethods(); //app.js içerisinde tanımlı(validNumber metodu)
  addCustomValidationValidValue(); //app.js içerisinde tanımlı(validValue metodu)
  var form = $("#paymentFromProjectForm");
  form.validate({
    rules: {
      fp_project_name: {
        required: true,
        validValue: true
      },
      fp_amount: {
        required: true,
        validNumber: true
      },
      fp_cases: {
        required: true,
        validValue: true
      }
    },
    messages: {
      fp_project_name: {
        required: "Lütfen proje seçin",
        validValue: "Lütfen proje seçin"
      },
      fp_amount: {
        required: "Lütfen miktarı girin",
        validNumber: "Geçerli bir miktar girin"
      },
      fp_cases: {
        required: "Lütfen kasa seçin",
        validValue: "Lütfen kasa seçin"
      }
    },
    errorPlacement: function (error, element) {
      customErrorPlacement(error, element);
    }
  });
  if (!form.valid()) {
    return;
  }
  let formData = new FormData(form[0]);
  formData.append("action", "getPaymentFromProject");
  formData.append("id", id);

  fetch("api/financial/transaction.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      // console.log(data);

      if (data.status == "success") {
        Swal.fire({
          title: "Başarılı!",
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        }).then((result) => {
          if (result.isConfirmed) {
            location.reload();
          }
        });
      } else {
        Swal.fire({
          title: "Hata!",
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        });
      }
    });
});

// Toplu Personel Ödemesi Yap
$(document).ready(function () {
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

    // Modal açıldığında canlı / güncel verileri sunucudan yükleme (Dönemden bağımsız genel bakiyeler)
    function loadPayToPersonsLiveBalances() {
      var $tbody = $("#payToPersonsTableBody");
      $tbody.html(`
        <tr id="bulkPayLoadingRow">
          <td colspan="5" class="text-center py-4 text-muted small">
            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
            Güncel personel hakediş ve toplam bakiye bilgileri yükleniyor...
          </td>
        </tr>
      `);

      $.ajax({
        url: "api/bordro/get-bulk-pay-data.php",
        type: "POST",
        data: {
          is_overall: 1
        },
        dataType: "json",
        success: function (res) {
          if (res.status === "success" && Array.isArray(res.persons)) {
            if (res.period_title) {
              $("#payPeriodBadgeText").text(res.period_title);
            }
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
              if (typeof transactionTable !== "undefined" && transactionTable && transactionTable.ajax) {
                transactionTable.ajax.reload(null, false);
              } else {
                location.reload();
              }
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

///// GENEL MODALDA BİŞRLEŞTİRİLDİ//////////////////////

//Personele ödeme yap
$(document).ready(function () {
  addCustomValidationMethods();
  addCustomValidationValidValue();

  $("#payToPersonForm").validate({
    rules: {
      tp_person_name: {
        required: true
      },
      tp_amount: {
        required: true,
        validNumber: true,
        validValue: true
      },
      tp_action_date: {
        required: true
      },
      tp_cases: {
        required: true
      }
    },
    messages: {
      tp_person_name: {
        required: "Lütfen personel seçin"
      },
      tp_amount: {
        required: "Lütfen ödeme tutarını girin",
        validNumber: "Lütfen geçerli bir sayı girin",
        validValue: "Lütfen geçerli bir değer girin"
      },
      tp_action_date: {
        required: "Lütfen ödeme tarihini girin"
      },
      tp_cases: {
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

  $("#savePayToPerson").on("click", function () {
    if ($("#payToPersonForm").valid()) {
      // Form geçerliyse işlemleri yap
      // Örneğin formu submit edebilirsiniz
      var form = $("#payToPersonForm");
      let formData = new FormData(form[0]);
      let id = $("#transaction_id").val();
      formData.append("action", "payToPerson");
      formData.append("id", id);

      fetch("api/financial/transaction.php", {
        method: "POST",
        body: formData
      })
        .then((response) => response.json())
        .then((data) => {
          console.log(data);

          if (data.status == "success") {
            Swal.fire({
              title: "Başarılı!",
              text: data.message,
              icon: data.status,
              confirmButtonText: "Tamam"
            }).then((result) => {
              if (result.isConfirmed) {
                location.reload();
              }
            });
          } else {
            Swal.fire({
              title: "Hata!",
              text: data.message,
              icon: data.status,
              confirmButtonText: "Tamam"
            });
          }
        });
    }
  });
});

//Firma Ödemesi yap
$(document).on("click", "#savePayToCompany", function () {
  let id = $("#transaction_id").val();
  var form = $("#payToCompanyForm");

  addCustomValidationMethods(); //app.js içerisinde tanımlı(validNumber metodu)
  addCustomValidationValidValue(); //app.js içerisinde tanımlı(validValue metodu)
  form.validate({
    rules: {
      tc_company_name: {
        required: true,
        validValue: true
      },
      tc_amount: {
        required: true,
        validNumber: true
      },
      tc_cases: {
        required: true,
        validValue: true
      }
    },
    messages: {
      tc_company_name: {
        required: "Lütfen bir firma seçin",
        validValue: "Lütfen bir firma seçin"
      },
      tc_amount: {
        required: "Lütfen miktarı girin",
        validNumber: "Geçerli bir miktar girin"
      },
      tc_cases: {
        required: "Lütfen bir kasa seçin",
        validValue: "Lütfen bir kasa seçin"
      }
    },
    errorPlacement: function (error, element) {
      customErrorPlacement(error, element);
    }
  });
  if (!form.valid()) {
    return;
  }

  let formData = new FormData(form[0]);

  formData.append("action", "payToCompany");
  formData.append("id", id);

  // for (var pair of formData.entries()) {
  //   console.log(pair[0] + ", " + pair[1]);
  // }

  fetch("api/financial/transaction.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      // console.log(data);

      if (data.status == "success") {
        Swal.fire({
          title: "Başarılı!",
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        }).then((result) => {
          if (result.isConfirmed) {
            location.reload();
          }
        });
      } else {
        Swal.fire({
          title: "Hata!",
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        });
      }
    });
});

//Alınan Proje Masraf Ekle
$(document).on("click", "#saveAddExpenseReceivedProject", function () {
  let id = $("#transaction_id").val();
  var form = $("#addExpenseReceivedProjectForm");

  addCustomValidationMethods(); //app.js içerisinde tanımlı(validNumber metodu)
  addCustomValidationValidValue(); //app.js içerisinde tanımlı(validValue metodu)
  form.validate({
    rules: {
      rp_project_name: {
        required: true,
        validValue: true
      },
      rp_amount: {
        required: true,
        validNumber: true
      },
      rp_cases: {
        required: true,
        validValue: true
      }
    },
    messages: {
      rp_project_name: {
        required: "Lütfen bir proje seçin",
        validValue: "Lütfen bir proje seçin"
      },
      rp_amount: {
        required: "Lütfen miktarı girin",
        validNumber: "Geçerli bir miktar girin"
      },
      rp_cases: {
        required: "Lütfen bir kasa seçin",
        validValue: "Lütfen bir kasa seçin"
      }
    },
    errorPlacement: function (error, element) {
      customErrorPlacement(error, element);
    }
  });
  if (!form.valid()) {
    return;
  }

  let formData = new FormData(form[0]);

  formData.append("action", "addExpenseReceivedProject");
  formData.append("id", id);

  // for (var pair of formData.entries()) {
  //   console.log(pair[0] + ", " + pair[1]);
  // }

  fetch("api/financial/transaction.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      // console.log(data);

      if (data.status == "success") {
        Swal.fire({
          title: "Başarılı!",
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        }).then((result) => {
          if (result.isConfirmed) {
            location.reload();
          }
        });
      } else {
        Swal.fire({
          title: "Hata!",
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        });
      }
    });
});

///// GENEL MODALDA BİŞRLEŞTİRİLDİ//////////////////////

//Güncelleme işlemi
$(document).on("click", ".edit-transactions", function () {
  let id = $(this).data("id");
  $("#transaction_id").val(id);

  var modal, case_select, project_select, person_select, companies_select;
  var amount_input, date_input, description_input;

  //preloader göster
  $(".preloader").show();

  // Alt tür bilgisini data attribute, class veya tablodan güvenli şekilde al
  let type = ($(this).data("sub-type-name") || $(this).closest("tr").find(".sub-type-name").text() || $(this).closest("tr").find("td:eq(3)").text()).trim();


  switch (type) {
    case "Proje(Alınan Ödeme)":
      modal = $("#get_payment_from_project-modal");
      case_select = $("#fp_cases");
      project_select = $("#fp_project_name");
      amount_input = "fp_amount";
      date_input = "fp_action_date";
      description_input = "fp_description";
      break;

    case "Personel Ödemesi":
      modal = $("#pay_to_person-modal");
      case_select = $("#tp_cases");
      person_select = $("#tp_person_name");
      amount_input = "tp_amount";
      date_input = "tp_action_date";
      description_input = "tp_description";
      break;

    case "Firma Ödemesi":
      modal = $("#pay_to_company-modal");
      case_select = $("#tc_cases");
      companies_select = $("#tc_company_name");
      amount_input = "tc_amount";
      date_input = "tc_action_date";
      description_input = "tc_description";
      break;

    case "Alınan Proje Masrafı":
      modal = $("#add_expense_received_project-modal");
      case_select = $("#rp_cases");
      project_select = $("#rp_project_name");
      amount_input = "rp_amount";
      date_input = "rp_action_date";
      description_input = "rp_description";
      break;

    case "Virman":
      swal.fire({
        title: "Uyarı!",
        text: "Virman işlemi buradan güncellenemez!",
        icon: "error",
        confirmButtonText: "Tamam"
      });
      $(".preloader").hide();
      return;

    default:
      modal = $("#general-modal");
      case_select = $("#gm_case_id");
      project_select = $("#gm_project_id");
      person_select = $("#gm_person_name");
      companies_select = $("#gm_company");
      amount_input = "amount";
      date_input = "transaction_date";
      description_input = "description";
      break;
  }

  // Seçenekleri toplayan yardımcı fonksiyon
  const getOptionsString = (select) => {
    if (!select || !select.length) return "";
    let vals = [];
    select.find("option").each(function () {
      let v = $(this).val();
      if (v && v != "0") vals.push(v);
    });
    return vals.join(",");
  };

  var formData = new FormData();
  formData.append("id", id);
  formData.append("action", "getTransaction");
  formData.append("cases", getOptionsString(case_select));
  formData.append("projects", getOptionsString(project_select));
  formData.append("persons", getOptionsString(person_select));
  formData.append("companies", getOptionsString(companies_select));

  fetch("api/financial/transaction.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        var t = data.transaction;

        if (project_select && t.project_id != "0") project_select.val(t.project_id).trigger("change");
        else if (project_select) project_select.val(0).trigger("change.select2");

        if (person_select && t.person_id != "0") person_select.val(t.person_id).trigger("change");
        else if (person_select) person_select.val(0).trigger("change.select2");

        if (companies_select && t.company_id != "0") companies_select.val(t.company_id).trigger("change");
        else if (companies_select) companies_select.val(0).trigger("change.select2");

        if (case_select) case_select.val(t.case_id).trigger("change");

        $("input[name='" + amount_input + "']").val(t.amount);
        $("input[name='" + date_input + "']").val(t.date);

        var $desc = $("textarea[name='" + description_input + "']");
        if ($desc.length) $desc.val(t.description);
        else $("#" + description_input).val(t.description); // id ile de kontrol et

        // Genel modal ise tür ve alt türü de ayarla
        if (modal.attr("id") === "general-modal") {
          $("input[name='transaction_type'][value='" + t.type_id + "']").prop("checked", true);
          loadSubTypes(t.type_id, t.users_type_id);

          // Tabları ayarla
          if (t.project_id && t.project_id != "0") {
            modal.find('a[href="#tabs-home-7"]').tab("show");
          } else if (t.person_id && t.person_id != "0") {
            modal.find('a[href="#tabs-profile-7"]').tab("show");
          } else if (t.company_id && t.company_id != "0") {
            modal.find('a[href="#tabs-activity-7"]').tab("show");
          }
        }

        modal.modal("show");
        $(".preloader").hide();
      }
    });
});

// Fetch isteğinden dönen veriyi kullanarak işlemler yapan fonksiyon
function processTransactionData() {}

function customErrorPlacement(error, element) {
  if (element.hasClass("select2")) {
    error.insertAfter(element.next("span"));
  } else {
    error.insertAfter(element);
  }
}

//Virman yaparken çıkış yapılacak kasa seçilince hedef kasaları getirmekiçin
$(document).on("change", "#it_from_cases", function () {
  let from_case_id = $(this).val();
  var formData = new FormData();
  formData.append("from_case_id", from_case_id);
  formData.append("action", "getCaseTransfer");

  fetch("api/financial/transaction.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      if (data.status == "success") {
        // Başarılı yanıt alındığında kasa seçenekleri oluşturuluyor
        select = "<option value=''>Kasa Seçiniz!!</option>";
        $.each(data.cases, function (index, value) {
          select +=
            "<option value='" + value.id + "'>" + value.case_name + "</option>";
        });

        // Kasa seçenekleri HTML'e ekleniyor
        $("#it_to_case").html(select);
      }
    });
});

//Virman modalindaki kaydet butonuna basınca
$(document).on("click", "#add-case-transfer", function () {
  var form = $("#caseTransferForm");
  var formData = new FormData(form[0]);
  formData.append("action", "intercashTransfer");

  fetch("/api/financial/case.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        Swal.fire({
          title: "Başarılı!",
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        }).then((result) => {
          if (result.isConfirmed) {
            location.reload();
          }
        });
      } else {
        Swal.fire({
          title: "Hata!",
          html: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        });
      }
    });
});
