// Modal gösterildiğinde eğer yeni ekleme ise form temizlenir
$(document).on("click", "#btnNewHoliday", function () {
  $("#holidayModalTitle").html('<i class="ti ti-calendar-event text-primary me-2"></i>Yeni Resmi Tatil');
  $("#id").val("");
  $("#holiday_name").val("");
  $("#description").val("");
  $("#holiday_type").val("national");
  $("#day_ratio").val("1.00");
  $("#holiday_is_active").prop("checked", true);
  
  const fp = document.querySelector('#holiday_date')._flatpickr;
  if (fp) {
    fp.clear();
  } else {
    $("#holiday_date").val("");
  }
});

// Güncelle butonuna tıklandığında veriler forma aktarılır ve modal açılır
$(document).on("click", ".btn-edit-holiday", function (e) {
  e.preventDefault();
  const btn = $(this);
  
  $("#holidayModalTitle").html('<i class="ti ti-edit text-primary me-2"></i>Resmi Tatil Düzenle');
  $("#id").val(btn.data("id"));
  $("#holiday_name").val(btn.data("name"));
  $("#description").val(btn.data("desc"));
  $("#holiday_type").val(btn.data("type") || "national");
  $("#day_ratio").val(parseFloat(btn.data("ratio")) === 0.5 ? "0.50" : "1.00");
  $("#holiday_is_active").prop("checked", btn.data("active") == 1);
  
  const dateVal = btn.data("date");
  const fp = document.querySelector('#holiday_date')._flatpickr;
  if (fp) {
    fp.setDate(dateVal);
  } else {
    $("#holiday_date").val(dateVal);
  }
  
  $("#nationalHolidayModal").modal("show");
});

$(document).on("click", "#saveNationalHoliday", function () {
  var form = $("#nationalHolidayForm");
  let formData = new FormData(form[0]);

  form.validate({
    rules: {
      holiday_name: {
        required: true
      },
      holiday_date: {
        required: true
      }
    },
    messages: {
      holiday_name: {
        required: "Resmi tatil adı boş bırakılamaz."
      },
      holiday_date: {
        required: "Tarih boş bırakılamaz."
      }
    }
  });

  if (!form.valid()) {
    return;
  }

  fetch("/api/defines/national-holidays.php", {
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
          $("#nationalHolidayModal").modal("hide");
          location.reload();
        }
      });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).on("click", ".delete-national-holiday", function () {
  let action = "deleteNationalHoliday";
  let confirmMessage = "Resmi tatil tanımlaması silinecektir!";
  let url = "/api/defines/national-holidays.php";

  deleteRecord(this, action, confirmMessage, url);
});
