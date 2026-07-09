// Renk seçici senkronizasyonları
$(document).on("input", "#background_color_picker", function() {
  $("#background_color").val($(this).val());
});
$(document).on("input", "#background_color", function() {
  let val = $(this).val();
  if(val.match(/^#[0-9A-F]{6}$/i)) {
    $("#background_color_picker").val(val);
  }
});

$(document).on("input", "#font_color_picker", function() {
  $("#font_color").val($(this).val());
});
$(document).on("input", "#font_color", function() {
  let val = $(this).val();
  if(val.match(/^#[0-9A-F]{6}$/i)) {
    $("#font_color_picker").val(val);
  }
});

// Modal Select2 initialize
$(document).ready(function() {
  if ($.fn.select2 && $('#timesheetTypeModal').length) {
    $('.select2-modal').select2({
      dropdownParent: $('#timesheetTypeModal'),
      width: '100%'
    });
  }
});

// Modal gösterildiğinde eğer yeni ekleme ise form temizlenir
$(document).on("click", "#btnNewTimesheetType", function () {
  $("#timesheetModalTitle").html('<i class="ti ti-calendar-time text-primary me-2"></i>Yeni Puantaj Türü');
  $("#id").val("");
  $("#puantaj_name").val("");
  $("#puantaj_code").val("");
  $("#puantaj_hours").val("");
  
  $("#operant").val("").trigger("change");
  $("#added_hours").val("");
  $("#type").val("").trigger("change");
  
  $("#background_color").val("#ffffff");
  $("#background_color_picker").val("#ffffff");
  $("#font_color").val("#000000");
  $("#font_color_picker").val("#000000");
  
  $("#isActive").prop("checked", true);
  $("#IzinRapor").prop("checked", false);
  $("#is_deductable").prop("checked", false);
  $("#beyaz_yaka_kesinti").prop("checked", false);
  $("#personel_gorsun").prop("checked", false);
});

// Güncelle butonuna tıklandığında veriler forma aktarılır ve modal açılır
$(document).on("click", ".btn-edit-timesheet-type", function (e) {
  e.preventDefault();
  const btn = $(this);
  
  $("#timesheetModalTitle").html('<i class="ti ti-edit text-primary me-2"></i>Puantaj Türü Düzenle');
  $("#id").val(btn.data("id"));
  $("#puantaj_name").val(btn.data("name"));
  $("#puantaj_code").val(btn.data("code"));
  $("#puantaj_hours").val(btn.data("hours"));
  
  $("#operant").val(btn.data("operant")).trigger("change");
  $("#added_hours").val(btn.data("added-hours"));
  $("#type").val(btn.data("type")).trigger("change");
  
  const bg = btn.data("bg") || "#ffffff";
  const font = btn.data("font") || "#000000";
  $("#background_color").val(bg);
  $("#background_color_picker").val(bg);
  $("#font_color").val(font);
  $("#font_color_picker").val(font);
  
  $("#isActive").prop("checked", btn.data("active") == 1);
  $("#IzinRapor").prop("checked", btn.data("izin") == 1);
  $("#is_deductable").prop("checked", btn.data("deductable") == 1);
  $("#beyaz_yaka_kesinti").prop("checked", btn.data("beyaz-yaka-kesinti") == 1);
  $("#personel_gorsun").prop("checked", btn.data("personel-gorsun") == 1);
  
  $("#timesheetTypeModal").modal("show");
});

$(document).on("click", "#saveTimesheetType", function () {
  var form = $("#timesheetTypeForm");
  let formData = new FormData(form[0]);

  form.validate({
    rules: {
      puantaj_name: {
        required: true
      },
      puantaj_code: {
        required: true
      },
      type: {
        required: true
      }
    },
    messages: {
      puantaj_name: {
        required: "Puantaj türü adı boş bırakılamaz."
      },
      puantaj_code: {
        required: "Puantaj kodu boş bırakılamaz."
      },
      type: {
        required: "Türü boş bırakılamaz."
      }
    }
  });

  if (!form.valid()) {
    return;
  }

  fetch("/api/defines/timesheet-types.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({ 
        title: title, 
        text: data.message, 
        icon: data.status 
      }).then((result) => {
        if (data.status == "success") {
          $("#timesheetTypeModal").modal("hide");
          location.reload();
        }
      });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).on("click", ".delete-timesheet-type", function () {
  let action = "deleteTimesheetType";
  let confirmMessage = "Puantaj türü tanımlaması silinecektir!";
  let url = "/api/defines/timesheet-types.php";

  deleteRecord(this, action, confirmMessage, url);
});
