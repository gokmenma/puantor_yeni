// API Path tespiti
const getApiPath = (endpoint) => {
  const isMobile = window.location.pathname.includes('/mobile/');
  const base = isMobile ? '../api/' : 'api/';
  return base + endpoint;
};

// Modal gösterim fonksiyonu
const showProjectModal = () => {
  const modalEl = document.getElementById('projectModal');
  if (!modalEl) return;
  
  if (window.bootstrap && window.bootstrap.Modal) {
    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  } else if (typeof $ !== 'undefined' && $.fn.modal) {
    $(modalEl).modal("show");
  }
};

$(document).on("click", "#addNewProject", function (e) {
  if (e) e.preventDefault();
  const form = $("#projectForm");
  if (form.length > 0) {
    form[0].reset();
  }
  $("#modal_project_id").val(0);
  $("#modal_is_home_gantt").prop("checked", false);
  $("#projectModalTitle").text("Yeni Proje Ekle");
  $("#modal_project_town").html('<option value="">İlçe seçiniz</option>').val('').trigger('change');
  $("select[name='project_company']").val('0').trigger('change');
  $("select[name='project_status']").val('').trigger('change');
  $("select[name='project_city']").val('').trigger('change');
  $("input[name='project_type'][value='1']").prop("checked", true);
  
  // İlk sekmeye dön
  const firstTabBtn = document.getElementById('tab-project-general-btn');
  if (firstTabBtn && window.bootstrap && window.bootstrap.Tab) {
    window.bootstrap.Tab.getOrCreateInstance(firstTabBtn).show();
  }
  
  // Re-init flatpickr if needed
  if (typeof flatpickr !== 'undefined') {
    flatpickr(".flatpickr", { dateFormat: "d.m.Y", locale: "tr" });
  }
  
  showProjectModal();
});

$(document).on("click", ".update-project", function (e) {
  e.preventDefault();
  var id = $(this).data("id");
  var formData = new FormData();
  formData.append("action", "getProject");
  formData.append("id", id);

  // İlk sekmeye dön
  const firstTabBtn = document.getElementById('tab-project-general-btn');
  if (firstTabBtn && window.bootstrap && window.bootstrap.Tab) {
    window.bootstrap.Tab.getOrCreateInstance(firstTabBtn).show();
  }

  fetch(getApiPath("projects/projects.php"), {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        var p = data.data;
        $("#modal_project_id").val(p.id);
        $("#projectModalTitle").text("Proje Düzenle: " + p.project_name);
        $("input[name='project_name']").val(p.project_name);
        $("input[name='project_type'][value='" + p.type + "']").prop("checked", true);
        $("select[name='project_company']").val(p.company_id).trigger("change");
        $("select[name='project_status']").val(p.status).trigger("change");
        $("input[name='start_date']").val(p.start_date);
        $("input[name='end_date']").val(p.end_date);
        $("input[name='budget']").val(p.budget);
        $("select[name='project_city']").val(p.city).trigger("change");
        $("input[name='email']").val(p.email);
        $("input[name='phone']").val(p.phone);
        $("input[name='account_number']").val(p.account_number);
        $("textarea[name='address']").val(p.address);
        $("textarea[name='project']").val(p.notes);
        $("#modal_is_home_gantt").prop("checked", p.is_home_gantt == 1);

        // Set town
        var townOption = new Option(p.town_name, p.town, true, true);
        $("#modal_project_town").empty().append(townOption).trigger("change");

        showProjectModal();
      }
    });
});

$(document).on('shown.bs.modal', '#projectModal', function () {
  if (typeof flatpickr !== 'undefined') {
    flatpickr("#projectModal .flatpickr", { dateFormat: "d.m.Y", locale: "tr" });
  }
  if (typeof $.fn.select2 !== 'undefined') {
    $('#projectModal .select2').each(function () {
      $(this).select2({
        dropdownParent: $('#projectModal'),
        width: '100%'
      });
    });
  }
});

$(document).on("submit", "#projectForm", function (e) {
  e.preventDefault();
  var form = $(this);
  let formData = new FormData(form[0]);

  fetch(getApiPath("projects/projects.php"), {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: "Başarılı!",
            text: data.message,
            icon: "success"
          }).then(() => {
            location.reload();
          });
        } else {
          alert(data.message);
          location.reload();
        }
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire("Hata!", data.message, "error");
        } else {
          alert(data.message);
        }
      }
    });
});

$(document).on("change", "select[name='project_city']", function () {
  var cityId = $(this).val();
  var target = $(this).closest(".modal-body").length > 0 ? "#modal_project_town" : "#project_town";
  if (typeof getTowns === "function") {
    getTowns(cityId, target);
  }
});

$(document).on("click", "#savePersontoProject", function () {
  var checkedItems = [];
  $("#addPersontoProject tbody tr").each(function () {
    var checkbox = $(this).find("input[type='checkbox']");
    if (checkbox.prop("checked")) {
      checkedItems.push(checkbox.val());
    }
  });

  let formData = new FormData();
  formData.append("project_id", $("#project_id").val());
  formData.append("person_id", checkedItems);
  formData.append("action", "addPersonToProject");

  fetch(getApiPath("projects/project-person.php"), {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        title = "Başarılı!";
      } else {
        title = "Hata!";
      }
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: title,
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        });
      } else {
        alert(data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).ready(function () {
  // "Tümünü Seç" checkbox'ının durumunu kontrol edin
  $("#allPersonCheck").change(function () {
    // "Tümünü Seç" checkbox'ının durumu true ise tüm personel checkbox'larını işaretleyin, değilse işaretlerini kaldırın
    var isChecked = $(this).is(":checked");
    $("#addPersontoProject .form-check-input").prop("checked", isChecked);
  });
});

$(document).on("change", "#project_city", function () {
  //İl id'si alınır ilce selectine ilceler yüklenir

  getTowns($(this).val(), "#project_town");
});

$(document).on("click", ".delete-project", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteProject";
  let confirmMessage = "Proje silinecektir!";
  let url = "/api/projects/projects.php";

  deleteRecord(this, action, confirmMessage, url);
});

$(document).on("click", ".delete-project-action", async function () {
  //işlem türünü al,tablonun 2. sütununda bulunan veriyi al
  let type = $(this).closest("tr").find("td:eq(2)").text().trim();

  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteProjectAction";
  let confirmMessage = (type ? type + " silinecektir!" : "Bu kayıt silinecektir!");
  let project_id = $(this).attr("data-project");
  let table = $(this).attr("data-table") || "project_gelir_gider";
  let url = "/api/projects/projects.php?project_id=" + project_id + "&table=" + table;

  const result = await deleteRecordByReturn(this, action, confirmMessage, url);

  console.log(result);

  if (result && result.status == "success") {
    if (result.summary) {
      $("#total_income").text(result.summary.hakedis || "0,00 TRY");
      $("#total_payment").text(result.summary.gelir || "0,00 TRY");
      $("#total_expense").text(result.summary.kesinti || "0,00 TRY");
      $("#balance").text(result.summary.balance || "0,00 TRY");
    }
    if (result.progress !== undefined) {
      $("#progress-bar").text(result.progress + "%");
      $(".progress-bar").css("width", result.progress + "%");
    }
  }
});


// Proje manage sayfasında aktif sekmeyi localStorage'a kaydet ve geri yükle
$(document).ready(function () {
  if ($('[data-bs-toggle="tab"]').length === 0) return;

  var tabKey = 'project_manage_tab_' + (new URLSearchParams(window.location.search).get('id') || 'new');
  var savedTab = localStorage.getItem(tabKey);

  if (savedTab) {
    var $target = $('[href="' + savedTab + '"]');
    if ($target.length) {
      $('[data-bs-toggle="tab"].active').removeClass('active').attr('aria-selected', 'false');
      $('.tab-pane.active').removeClass('active show');
      $target.addClass('active').attr('aria-selected', 'true');
      $(savedTab).addClass('active show');
    }
  }

  $(document).on('shown.bs.tab', '[data-bs-toggle="tab"]', function () {
    localStorage.setItem(tabKey, $(this).attr('href'));
  });
});

// Global Modal Kapatma Yardimcisi
function closeProjectModalSafely(modalId) {
  const modalEl = document.getElementById(modalId);
  if (modalEl) {
    if (window.bootstrap && window.bootstrap.Modal) {
      const bsModal = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
      if (bsModal) {
        bsModal.hide();
      }
    }
    $(modalEl).modal("hide");
  }
  $(".modal-backdrop").remove();
  $("body").removeClass("modal-open").css({ overflow: "", paddingRight: "" });
}

// Proje Hareketi Guncelleme Handler
$(document).on("click", ".edit-project-action", function (e) {
  e.preventDefault();
  let id = $(this).attr("data-id");
  let project_id = $(this).attr("data-project");
  let type = parseInt($(this).attr("data-type"), 10);
  let amount = $(this).attr("data-amount");
  let date = $(this).attr("data-date");
  let caseId = $(this).attr("data-case");
  let description = $(this).attr("data-description");
  let projectName = $(".page-title").text().trim();

  function setProjectModalCaseValue(form, selectName, caseId) {
    let selectEl = form.find('[name="' + selectName + '"]');
    if (caseId && caseId !== '0' && caseId !== 0) {
      let option = selectEl.find('option[data-case-id="' + caseId + '"]');
      if (option.length) {
        selectEl.val(option.val()).trigger('change');
      } else {
        selectEl.val(caseId).trigger('change');
      }
    } else {
      selectEl.val('0').trigger('change');
    }
  }

  // 10: Hakedis
  if (type === 10) {
    let modal = $("#progress-payment-modal");
    let form = $("#progress_payment_modalForm");
    $("#progress_payment_project_name").text(projectName);
    form.find('[name="progress_payment_id"]').val(id);
    form.find('[name="progress_payment_project_id"]').val(project_id);
    form.find('[name="progress_payment_amount"]').val(amount);
    form.find('[name="progress_payment_date"]').val(date);
    setProjectModalCaseValue(form, 'progress_payment_cases', caseId);
    form.find('[name="progress_payment_description"]').val(description);
    $("#progress_payment_addButton").text("Hakediş Güncelle");
    modal.modal("show");
  } 
  // 5: Odeme (Alinan Odeme / Odeme)
  else if (type === 5 || type === 1) {
    let modal = $("#payment-modal");
    let form = $("#payment_modalForm");
    $("#payment_project_name").text(projectName);
    form.find('[name="payment_id"]').val(id);
    form.find('[name="payment_project_id"]').val(project_id);
    form.find('[name="payment_amount"]').val(amount);
    form.find('[name="payment_date"]').val(date);
    setProjectModalCaseValue(form, 'payment_cases', caseId);
    form.find('[name="payment_description"]').val(description);
    $("#payment_addButton").text("Ödeme Güncelle");
    modal.modal("show");
  } 
  // 12: Kesinti
  else if (type === 12) {
    let modal = $("#deduction-modal");
    let form = $("#deduction_modalForm");
    $("#deduction_project_name").text(projectName);
    form.find('[name="deduction_id"]').val(id);
    form.find('[name="deduction_project_id"]').val(project_id);
    form.find('[name="deduction_amount"]').val(amount);
    form.find('[name="deduction_date"]').val(date);
    setProjectModalCaseValue(form, 'deduction_cases', caseId);
    form.find('[name="deduction_description"]').val(description);
    $("#deduction_addButton").text("Kesinti Güncelle");
    modal.modal("show");
  } 
  // 11: Masraf
  else if (type === 11 || type === 2) {
    let modal = $("#expense-modal");
    let form = $("#expense_modalForm");
    $("#expense_project_name").text(projectName);
    form.find('[name="expense_id"]').val(id);
    form.find('[name="expense_project_id"]').val(project_id);
    form.find('[name="expense_amount"]').val(amount);
    form.find('[name="expense_date"]').val(date);
    setProjectModalCaseValue(form, 'expense_cases', caseId);
    form.find('[name="expense_description"]').val(description);
    $("#expense_addButton").text("Masraf Güncelle");
    modal.modal("show");
  }
});

//Project manage sayfasında Ödeme, hakediş gibi verileri ekledikten sonra tabloya eklemek için
function addDataToTable(data) {
  var table = $("#project_paymentTable").DataTable();
  table.row
    .add([
      table.rows().count() + 1, // Sıra numarası
      data.tarih,
      data.turu,
      data.ay || '',
      data.yil || '',
      data.tutar,
      data.aciklama || '',
      data.created_at || '',
      `<div class="dropdown">
                <button class="btn dropdown-toggle align-text-top"
                    data-bs-toggle="dropdown">İşlem</button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item edit-project-action" href="#"
                        data-id='${data.id}'
                        data-project='${data.project_id}'
                        data-type='10'
                        data-amount='${data.tutar}'
                        data-date='${data.tarih}'
                        data-table='project_gelir_gider'
                        data-description='${data.aciklama || ""}'>
                        <i class="ti ti-edit icon me-3"></i> Güncelle
                    </a>
                    <a class="dropdown-item delete-project-action" href="#" data-id='${data.id}' data-project='${data.project_id}' data-table='project_gelir_gider'>
                        <i class="ti ti-trash icon me-3"></i> Sil
                    </a>
                </div>
            </div>`
    ])
    .order([7, "desc"])
    .draw(false);
}

function updateSaveButtonVisibility() {
  var activeTabHref = $('.nav-tabs .nav-link.active').attr("href");
  if (activeTabHref === "#tabs-personnel-3") {
    $("#saveProject").show();
  } else {
    $("#saveProject").hide();
  }
}

$(document).ready(function () {
  // URL'deki hash değerine göre ilgili tabı aktif et
  var hash = window.location.hash;
  if (hash) {
    var tabTrigger = $('.nav-tabs a[href="' + hash + '"]');
    if (tabTrigger.length) {
      if (window.bootstrap && window.bootstrap.Tab) {
        var tab = window.bootstrap.Tab.getOrCreateInstance(tabTrigger[0]);
        tab.show();
      } else if (typeof $.fn.tab !== 'undefined') {
        tabTrigger.tab('show');
      }
    }
  }
  updateSaveButtonVisibility();
});

$(document).on('shown.bs.tab', 'a[data-bs-toggle="tab"]', function () {
  updateSaveButtonVisibility();
});

$(document).on("click", "#saveProject", function () {
  $("#savePersontoProject").trigger("click");
});

$(document).on("click", "#btn_toggle_puantaj_grouping", function(e) {
  e.preventDefault();
  var isGrouped = $(this).attr("data-grouped") === "true";
  if (isGrouped) {
    $("#puantaj_grouped_wrapper").hide();
    $("#puantaj_detailed_wrapper").show();
    $(this).attr("data-grouped", "false");
    $(this).html('<i class="ti ti-users icon me-1"></i> Personel Bazlı Grupla');
    
    var table = $("#puantaj_info_table").DataTable();
    table.columns.adjust().draw();
  } else {
    $("#puantaj_detailed_wrapper").hide();
    $("#puantaj_grouped_wrapper").show();
    $(this).attr("data-grouped", "true");
    $(this).html('<i class="ti ti-list icon me-1"></i> Detaylı Göster');
    
    var table = $("#puantaj_info_grouped_table").DataTable();
    table.columns.adjust().draw();
  }
});

$(document).on("click", "#export_excel_puantaj_info_custom", function (e) {
  e.preventDefault();
  var isGrouped = $("#btn_toggle_puantaj_grouping").attr("data-grouped") === "true";
  var tableSelector = isGrouped ? "#puantaj_info_grouped_table" : "#puantaj_info_table";
  var table = $(tableSelector).DataTable();
  table.button(".buttons-excel").trigger();
});
