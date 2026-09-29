/**
 * Kasa Yönetimi (Financial Cases) JS Modülü
 */
var caseTable = null;

$(document).ready(function () {
  initCaseDataTable();
  initCaseModals();
  syncCaseSummaryToggle();
});

// SPA veya dinamik sayfa geçişleri için
$(document).on("page:loaded content:loaded", function () {
  initCaseDataTable();
  initCaseModals();
  syncCaseSummaryToggle();
});

/**
 * DataTable Başlatma ve Sütun Yönetimi
 */
function initCaseDataTable() {
  var $table = $("#caseTable");
  if (!$table.length) return;

  $table.find("thead .search-input-row").remove();

  if (typeof $ !== "undefined" && $.fn && $.fn.DataTable && $.fn.DataTable.isDataTable("#caseTable")) {
    caseTable = $("#caseTable").DataTable();
    return;
  }

  var options = {
    order: [[0, "asc"]],
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    disableSearchRow: true,
    noSearchRow: true,
    columnDefs: [
      { targets: [0, 4, 5, 6], className: "text-center" },
      { targets: [7], className: "text-end" },
      { targets: [9], orderable: false, searchable: false, className: "text-end no-export actions-column" }
    ],
    language: {
      url: "src/tr.json"
    },
    initComplete: function () {
      var api = this.api();
      buildCaseColvisMenu(api);
      if (typeof window.initDataTableColumnFilters === "function") {
        window.initDataTableColumnFilters($("#caseTable"), api);
      }
    }
  };

  if (typeof window.createDataTable === "function") {
    caseTable = window.createDataTable("#caseTable", options);
  } else if (typeof $ !== "undefined" && $.fn && $.fn.DataTable) {
    caseTable = $("#caseTable").DataTable(options);
  }
}

/**
 * Sütun Göster / Gizle Menüsü
 */
function buildCaseColvisMenu(api) {
  var $menu = $("#caseColvisMenu");
  if (!$menu.length || !api) return;

  var columnConfig = {
    1: { label: "Firması", default: true },
    3: { label: "Banka / Şube", default: true },
    4: { label: "Kasa Türü", default: true },
    5: { label: "Para Birimi", default: true },
    6: { label: "Varsayılan", default: true },
    7: { label: "Güncel Bakiye", default: true },
    8: { label: "Açıklama", default: true }
  };

  var savedVisibility = localStorage.getItem("case_column_visibility");
  var visibilityState = savedVisibility ? JSON.parse(savedVisibility) : {};

  var menuHtml = "";
  $.each(columnConfig, function (idx, conf) {
    var isVisible = visibilityState.hasOwnProperty(idx) ? visibilityState[idx] : conf.default;
    api.column(idx).visible(isVisible, false);

    menuHtml += `
      <label class="dropdown-item d-flex align-items-center cursor-pointer py-1.5 px-3 rounded-2" style="font-size: 0.85rem;">
        <div class="form-check mb-0 w-100">
          <input class="form-check-input case-col-trigger" type="checkbox" id="colCheck_${idx}" data-column="${idx}" ${isVisible ? "checked" : ""}>
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
$(document).on("change", ".case-col-trigger", function () {
  if (!caseTable) {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable("#caseTable")) {
      caseTable = $("#caseTable").DataTable();
    }
  }
  if (!caseTable) return;

  var colIdx = parseInt($(this).data("column"));
  var isChecked = this.checked;
  caseTable.column(colIdx).visible(isChecked);

  var savedVisibility = localStorage.getItem("case_column_visibility");
  var visibilityState = savedVisibility ? JSON.parse(savedVisibility) : {};
  visibilityState[colIdx] = isChecked;
  localStorage.setItem("case_column_visibility", JSON.stringify(visibilityState));
});

$(document).on("click", "#caseColvisMenu", function (e) {
  e.stopPropagation();
});

// Tür Filtresi (Tümü / Banka / Nakit)
$(document).on("change", ".case-type-filter", function () {
  if (!caseTable) {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable("#caseTable")) {
      caseTable = $("#caseTable").DataTable();
    }
  }
  if (!caseTable) return;
  var val = $(this).val();
  caseTable.column(4).search(val ? val : "").draw();
});

// Hızlı Genel Arama Inputu
var caseSearchTimer = null;
$(document).on("input", "#case-fast-search", function () {
  var val = this.value;
  $("#case-search-clear").toggleClass("d-none", val.length === 0);
  clearTimeout(caseSearchTimer);
  caseSearchTimer = setTimeout(function () {
    if (!caseTable) {
      if ($.fn.DataTable && $.fn.DataTable.isDataTable("#caseTable")) {
        caseTable = $("#caseTable").DataTable();
      }
    }
    if (caseTable) {
      caseTable.search(val).draw();
    }
  }, 300);
});

$(document).on("click", "#case-search-clear", function () {
  clearTimeout(caseSearchTimer);
  $("#case-fast-search").val("").trigger("focus");
  $(this).addClass("d-none");
  if (!caseTable) {
    if ($.fn.DataTable && $.fn.DataTable.isDataTable("#caseTable")) {
      caseTable = $("#caseTable").DataTable();
    }
  }
  if (caseTable) {
    caseTable.search("").draw();
  }
});

// Özet Kartları Daraltma/Genişletme Butonu
function syncCaseSummaryToggle() {
  var $summaryToggle = $("#toggleCaseSummary");
  if (!$summaryToggle.length) return;
  var isCollapsed = document.documentElement.classList.contains("case-summary-collapsed");
  $summaryToggle
    .attr("aria-expanded", String(!isCollapsed))
    .attr("aria-label", isCollapsed ? "Özet kartlarını göster" : "Özet kartlarını gizle")
    .attr("title", isCollapsed ? "Özet kartlarını göster" : "Özet kartlarını gizle");
  $summaryToggle.find("i")
    .toggleClass("ti-chevron-up", !isCollapsed)
    .toggleClass("ti-chevron-down", isCollapsed);
}

$(document).on("click", "#toggleCaseSummary", function () {
  var isCollapsed = document.documentElement.classList.toggle("case-summary-collapsed");
  try {
    localStorage.setItem("case_summary_collapsed", isCollapsed ? "1" : "0");
  } catch (e) {}
  syncCaseSummaryToggle();
});

/**
 * Excel Dışa Aktarma Butonu
 */
$(document).on("click", "#btnExportCaseExcel", function (e) {
  e.preventDefault();
  if (caseTable && typeof caseTable.button === "function") {
    var dtBtn = caseTable.button(".buttons-excel");
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
function initCaseModals() {
  if ($.fn.select2) {
    $("#case_money_unit").select2({
      dropdownParent: $("#case-modal"),
      width: "100%"
    });

    $('#modal-user-ids-container select[name="user_ids[]"]').select2({
      dropdownParent: $("#case-modal"),
      width: "100%",
      placeholder: "Kullanıcı seçiniz..."
    });

    $("#it_to_case").select2({
      dropdownParent: $("#intercash_transfer-modal"),
      width: "100%"
    });
  }

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

/**
 * Yeni Kasa Butonu
 */
$(document).on("click", "#btn-new-case, #btn-new-case-icon", function () {
  var form = $("#caseForm");
  form[0].reset();

  if (form.data("validator")) {
    form.data("validator").resetForm();
  }
  form.find(".is-invalid").removeClass("is-invalid");
  form.find(".error").removeClass("error");

  $("#case_id_input").val("0");
  $("#case_money_unit").val("1").trigger("change");
  $('#modal-user-ids-container select[name="user_ids[]"]').val([]).trigger("change");

  $("#default_case").prop("checked", false).prop("disabled", false);
  $("#caseModalTitle span").text("Yeni Kasa");
  $("#caseModalTitle i").attr("class", "ti ti-wallet text-primary");
  
  $("#case-modal").modal("show");
  setTimeout(initCaseModals, 200);
});

/**
 * Kasa Düzenleme (Edit)
 */
$(document).on("click", ".edit-case", function (e) {
  e.preventDefault();
  var case_id = $(this).data("id");
  var form = $("#caseForm");

  form[0].reset();
  if (form.data("validator")) {
    form.data("validator").resetForm();
  }
  form.find(".is-invalid").removeClass("is-invalid");
  form.find(".error").removeClass("error");

  var formData = new FormData();
  formData.append("id", case_id);
  formData.append("action", "getCase");

  fetch("/api/financial/case.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        var c = data.case;
        $("#case_id_input").val(case_id);
        $("#case_name").val(c.case_name || "");
        $("#bank_name").val(c.bank_name || "");
        $("#branch_name").val(c.branch_name || "");
        $("#description").val(c.description || "");

        $("#case_money_unit").val(c.case_money_unit || "1").trigger("change");
        $('#modal-user-ids-container select[name="user_ids[]"]').val(c.user_ids || []).trigger("change");

        if (c.isDefault == 1) {
          $("#default_case").prop("checked", true).prop("disabled", true);
        } else {
          $("#default_case").prop("checked", false).prop("disabled", false);
        }

        $("#caseModalTitle span").text("Kasa Güncelle");
        $("#caseModalTitle i").attr("class", "ti ti-edit text-azure");
        $("#case-modal").modal("show");
        setTimeout(initCaseModals, 200);
      } else {
        Swal.fire({
          title: "Hata!",
          text: data.message || "Kasa bilgileri alınamadı.",
          icon: "error",
          confirmButtonText: "Tamam"
        });
      }
    })
    .catch((err) => {
      console.error("Kasa bilgisi alma hatası:", err);
      Swal.fire({
        title: "Hata!",
        text: "Sunucu ile iletişim kurulurken bir hata oluştu.",
        icon: "error",
        confirmButtonText: "Tamam"
      });
    });
});

/**
 * Kasa Kaydet / Güncelle
 */
$(document).on("click", "#saveCase", function () {
  var form = $("#caseForm");

  form.validate({
    rules: {
      case_name: {
        required: true
      }
    },
    messages: {
      case_name: {
        required: "Kasa Adı boş bırakılamaz!"
      }
    },
    errorPlacement: function (error, element) {
      if (element.parent(".input-icon").length) {
        error.insertAfter(element.parent());
      } else {
        error.insertAfter(element);
      }
    }
  });

  if (!form.valid()) return false;

  var formData = new FormData(form[0]);

  var $btn = $(this);
  var origHtml = $btn.html();
  $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Kaydediliyor...');

  fetch("/api/financial/case.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      $btn.prop("disabled", false).html(origHtml);
      var isSuccess = data.status === "success";
      Swal.fire({
        title: isSuccess ? "Başarılı!" : "Hata!",
        text: data.message,
        icon: isSuccess ? "success" : "error",
        confirmButtonText: "Tamam"
      }).then((result) => {
        if (isSuccess) {
          $("#case-modal").modal("hide");
          location.reload();
        }
      });
    })
    .catch((err) => {
      $btn.prop("disabled", false).html(origHtml);
      console.error("Kasa kaydetme hatası:", err);
      Swal.fire({
        title: "Hata!",
        text: "Sunucu hatası oluştu.",
        icon: "error",
        confirmButtonText: "Tamam"
      });
    });
});

/**
 * Kasa Silme
 */
$(document).on("click", ".delete-case", function () {
  let action = "deleteCase";
  let confirmMessage = "Kasa, tüm hareketleri ile birlikte silinecektir!";
  let url = "/api/financial/case.php";

  if (typeof deleteRecord === "function") {
    deleteRecord(this, action, confirmMessage, url);
  } else {
    var id = $(this).data("id");
    Swal.fire({
      title: "Emin misiniz?",
      text: confirmMessage,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Evet, Sil!",
      cancelButtonText: "Vazgeç"
    }).then((result) => {
      if (result.isConfirmed) {
        var fd = new FormData();
        fd.append("id", id);
        fd.append("action", action);
        fetch(url, { method: "POST", body: fd })
          .then((r) => r.json())
          .then((data) => {
            if (data.status === "success") {
              Swal.fire("Başarılı!", data.message, "success").then(() => location.reload());
            } else {
              Swal.fire("Hata!", data.message, "error");
            }
          });
      }
    });
  }
});

/**
 * Varsayılan Kasa Yapma
 */
$(document).on("click", ".default-case", function (e) {
  e.preventDefault();
  let case_id = $(this).data("id");

  Swal.fire({
    title: "Varsayılan Kasa",
    text: "Bu kasayı firmanızın varsayılan kasası yapmak istiyor musunuz?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Evet, Varsayılan Yap",
    cancelButtonText: "Vazgeç"
  }).then((result) => {
    if (result.isConfirmed) {
      var formData = new FormData();
      formData.append("case_id", case_id);
      formData.append("action", "defaultCase");

      fetch("/api/financial/case.php", {
        method: "POST",
        body: formData
      })
        .then((response) => response.json())
        .then((data) => {
          var isSuccess = data.status === "success";
          Swal.fire({
            title: isSuccess ? "Başarılı!" : "Hata!",
            text: data.message,
            icon: isSuccess ? "success" : "error",
            confirmButtonText: "Tamam"
          }).then((res) => {
            if (isSuccess) {
              location.reload();
            }
          });
        })
        .catch((err) => {
          console.error("Varsayılan kasa hatası:", err);
          Swal.fire({
            title: "Hata!",
            text: "Sunucu hatası oluştu.",
            icon: "error",
            confirmButtonText: "Tamam"
          });
        });
    }
  });
});

/**
 * Kasalar Arası Virman / Transfer Modal Açma (Tablo Satırından veya Header İşlemler Menüsünden)
 */
$(document).on("click", ".intercash-transfer, .intercash-transfer-header", function (e) {
  e.preventDefault();
  let modal = $("#intercash_transfer-modal");
  let case_id = $(this).data("id");

  // Eğer header'dan tıklandıysa ilk satırdaki kasanın ID'sini al
  if (!case_id) {
    var firstRow = $("#caseTable tbody tr").first();
    case_id = firstRow.data("id");
  }

  if (!case_id) {
    Swal.fire({
      title: "Uyarı",
      text: "Virman yapmak için en az iki kasanızın olması gerekmektedir.",
      icon: "warning",
      confirmButtonText: "Tamam"
    });
    return;
  }

  var formData = new FormData();
  formData.append("case_id", case_id);
  formData.append("action", "getCases");

  fetch("/api/financial/case.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        var select = "<option value=''>Hedef Kasa Seçiniz...</option>";
        $.each(data.cases, function (index, value) {
          select +=
            "<option value='" + value.id + "'>" + value.case_name + (value.bank_name ? " (" + value.bank_name + ")" : "") + "</option>";
        });

        $("#it_from_cases").val(case_id);
        $("#it_to_case").html(select);

        modal.modal("show");
        setTimeout(initCaseModals, 200);
      } else {
        Swal.fire({
          title: "Hata!",
          text: data.message,
          icon: "error",
          confirmButtonText: "Tamam"
        });
      }
    })
    .catch((err) => {
      console.error("Kasa transfer listesi hatası:", err);
      Swal.fire({
        title: "Hata!",
        text: "Kasa listesi alınırken hata oluştu.",
        icon: "error",
        confirmButtonText: "Tamam"
      });
    });
});

/**
 * Kasalar Arası Transfer İşlemini Kaydetme
 */
$(document).on("click", "#add-case-transfer", function () {
  var form = $("#caseTransferForm");
  var toCase = $("#it_to_case").val();
  var amount = $("#it_amount").val();

  if (!toCase || toCase == "0" || toCase === "") {
    Swal.fire({
      title: "Uyarı",
      text: "Lütfen hedef kasayı seçiniz.",
      icon: "warning",
      confirmButtonText: "Tamam"
    });
    return false;
  }

  if (!amount || amount.trim() === "") {
    Swal.fire({
      title: "Uyarı",
      text: "Lütfen aktarılacak tutarı giriniz.",
      icon: "warning",
      confirmButtonText: "Tamam"
    });
    return false;
  }

  var formData = new FormData(form[0]);
  formData.append("action", "intercashTransfer");

  var $btn = $(this);
  var origHtml = $btn.html();
  $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Transfer Yapılıyor...');

  fetch("/api/financial/case.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      $btn.prop("disabled", false).html(origHtml);
      var isSuccess = data.status === "success";
      Swal.fire({
        title: isSuccess ? "Başarılı!" : "Hata!",
        text: data.message,
        icon: isSuccess ? "success" : "error",
        confirmButtonText: "Tamam"
      }).then((result) => {
        if (isSuccess) {
          $("#intercash_transfer-modal").modal("hide");
          location.reload();
        }
      });
    })
    .catch((err) => {
      $btn.prop("disabled", false).html(origHtml);
      console.error("Virman transfer hatası:", err);
      Swal.fire({
        title: "Hata!",
        text: "Transfer işlemi sırasında sunucu hatası oluştu.",
        icon: "error",
        confirmButtonText: "Tamam"
      });
    });
});

/**
 * Tabloda Sağ Tık (Custom Context Menu)
 */
$(document).on("contextmenu", "#caseTable tbody tr", function (e) {
  var $tr = $(this);
  var caseId = $tr.data("id") || $tr.find(".edit-case").data("id");
  var caseName = $tr.data("name") || $tr.find("td").eq(2).text().trim() || "Kasa İşlemleri";
  var isDefault = $tr.find(".badge.bg-success-lt").length > 0;

  if (!caseId) return;

  e.preventDefault();
  $("#caseTable tbody tr").removeClass("context-menu-active");
  $tr.addClass("context-menu-active");

  var $contextMenu = $("#customContextMenu");
  if (!$contextMenu.length) {
    $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo("body");
  }

  var menuHtml = `
    <div class="cm-header"><i class="ti ti-wallet me-1"></i> ${$("<div>").text(caseName).html()}</div>
    <a href="javascript:void(0);" class="route-link" data-page="financial/case/manage&id=${caseId}"><i class="ti ti-receipt-2 text-info"></i> Kasa Hareketleri</a>
    <a href="javascript:void(0);" class="edit-case" data-id="${caseId}"><i class="ti ti-pencil text-primary"></i> Düzenle / Detay</a>
    <a href="javascript:void(0);" class="intercash-transfer" data-id="${caseId}"><i class="ti ti-arrows-left-right text-warning"></i> Kasalararası Virman</a>
    ${!isDefault ? `<a href="javascript:void(0);" class="default-case" data-id="${caseId}"><i class="ti ti-star text-yellow"></i> Varsayılan Yap</a>` : ""}
    ${!isDefault ? `<div class="cm-divider"></div><a href="javascript:void(0);" class="cm-danger delete-case" data-id="${caseId}"><i class="ti ti-trash"></i> Kasayı Sil</a>` : ""}
  `;

  $contextMenu.html(menuHtml);
  $contextMenu.css({ display: "block", opacity: 0 });

  var menuWidth = $contextMenu.outerWidth();
  var menuHeight = $contextMenu.outerHeight();
  var clickX = e.clientX;
  var clickY = e.clientY;
  var windowWidth = $(window).width();
  var windowHeight = $(window).height();

  var posX = clickX + menuWidth > windowWidth ? windowWidth - menuWidth - 10 : clickX;
  var posY = clickY + menuHeight > windowHeight ? windowHeight - menuHeight - 10 : clickY;

  $contextMenu.css({
    top: posY + "px",
    left: posX + "px",
    opacity: 1
  });
});

$(document).on("click", function (e) {
  if (!$(e.target).closest("#customContextMenu").length) {
    $("#customContextMenu").hide();
    $("#caseTable tbody tr").removeClass("context-menu-active");
  }
});

$(document).on("click", "#customContextMenu a", function () {
  $("#customContextMenu").hide();
  $("#caseTable tbody tr").removeClass("context-menu-active");
});

$(window).on("scroll resize blur", function () {
  $("#customContextMenu").hide();
  $("#caseTable tbody tr").removeClass("context-menu-active");
});
