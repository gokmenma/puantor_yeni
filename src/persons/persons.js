$(document).on("click", "#savePerson", function () {
  var form = $("#personForm");
  jQuery.validator.addMethod(
    "money",
    function (value, element) {
      var isValidMoney = /^\d{1,3}(?:\.\d{3})*(?:,\d{2})?$/.test(value);
      return this.optional(element) || isValidMoney;
    },
    "Lütfen geçerli bir para birimi giriniz"
  );
  form.validate({
    rules: {
      full_name: {
        required: true,
        minlength: 3,
        maxlength: 50,
        number: false,
      },
      kimlik_no: {
        required: true,
            number: true,
      },
      job_start_date: {
        required: true,
      },
      daily_wages: {
        required: true,
        money: true,
      },
    },
    messages: {
      full_name: {
        required: "Lütfen personel adını giriniz",
        minlength: "Ad-Soyad en az 3 karakter olmalıdır",
        maxlength: "Ad-Soyad en fazla 50 karakter olmalıdır",
        number: "Ad-Soyadda sayısal değer bulunamaz",
      },
      kimlik_no: {
        required: "Lütfen kimlik numarasını giriniz",
        number: "Kimlik numarası sayısal değer olmalıdır",
      },
      job_start_date: {
        required: "Lütfen işe başlama tarihini giriniz",
        date: "Lütfen geçerli bir tarih giriniz",
      },
      daily_wages: {
        required: "Ücret alanı zorunludur",
      },
    },
  });
  if (!form.valid()) return false;

  var formData = new FormData(form[0]);
  formData.append("action", "savePerson");

  // for(var pair of formData.entries()) {
  //   console.log(pair[0]+ ', '+ pair[1]); 
  // }

  fetch("api/persons/person.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      if (data.status == "success") {
        title = "Başarılı!";
        $("#person_id").val(data.lastid);
      } else {
        title = "Hata!";
      }
      swal.fire({
        title: title,
        html: data.message + (data.status == 'success' ? '<br><br><span class="text-danger"><strong>Not:</strong> Ücret veya tarih değişikliği yaptıysanız, lütfen bordro sayfasından yeniden hesaplama yapınız.</span>' : ''),
        icon: data.status,
      });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).on("click", ".delete-person", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deletePerson";
  let confirmMessage = "Personel silinecektir!";
  let url = "/api/persons/person.php";

  deleteRecord(this, action, confirmMessage, url);
  // deleteRecord(this, action, confirmMessage, url);
});

$('input[name="kimlik_no"]').keypress(function (e) {
  if (this.value.length >= 11) {
    return false;
  }
  if (e.which < 48 || e.which > 57) {
    return false;
  }
});


$(document).on('click', '.wage_type', function () {
  if ($(this).attr('id') === 'blue_collar') {
    $('#wage_type_label').text('Günlük Ücreti');

  } else if ($(this).attr('id') === 'white_collar') {
    $('#wage_type_label').text('Aylık Maaş');
  }

});


$(document).on("click", ".delete-payment", async function () {
  let type_name = $(this).closest("tr").find("td:eq(2)").text();
  let type = $(this).closest("tr").find("td:eq(5)").text();
  let person_id = $("#person_id").val();
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deletePayment";
  let confirmMessage = type_name + " silinecektir!";
  let url = "/api/persons/person.php?person_id=" + person_id  + "&type=" + type;

  const result = await deleteRecordByReturn(this, action, confirmMessage, url);

  let income_expense = result.income_expense;

  let total_income = income_expense.total_income;
  let total_expense = income_expense.total_expense;
  let total_payment = income_expense.total_payment;
  let balance = income_expense.balance;

  // console.log(total_income + " " + total_payment + " " + balance);
  

  $("#total_payment").text(total_payment);
  $("#total_income").text(total_income);
  $("#total_expense").text(total_expense);
  $("#balance").text(balance);
  

});

$(document).on("click", "#btnSaveBulkWages", function () {
  const form = document.getElementById("bulkWageForm");
  const formData = new FormData(form);

  fetch("api/persons/bulk-update-wages.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        Swal.fire({
          title: "Başarılı!",
          html: data.message + '<br><br><span class="text-danger"><strong>Önemli:</strong> Ücret değişikliklerinin yansıması için lütfen bordro sayfasından <strong>"Hesapla"</strong> butonuna basarak yeniden hesaplama yapınız.</span>',
          icon: "success",
        }).then(() => {
          location.reload();
        });
      } else {
        Swal.fire({
          title: "Hata!",
          text: data.message,
          icon: "error",
        });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Hata!", "Bir hata oluştu.", "error");
    });
});

$(document).on("click", "#btnSaveJobGroup", function () {
  const groupName = $("#new_job_group_name").val();
  const description = $("#new_job_group_description").val();

  if (!groupName) {
    Swal.fire("Hata!", "Grup adı boş olamaz.", "error");
    return;
  }

  const btn = $(this);
  btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Kaydediliyor...');

  const formData = new FormData();
  formData.append("action", "saveJobGroups");
  formData.append("id", 0);
  formData.append("job_group_name", groupName);
  formData.append("description", description);

  fetch("api/defines/job-groups.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      btn.prop("disabled", false).text("Kaydet");
      if (data.status === "success") {
        Swal.fire("Başarılı!", data.message, "success");
        $("#modal-job-group").modal("hide");
        
        // Update the select box
        const newOption = new Option(groupName, data.id, true, true);
        $("#job_groups").append(newOption).trigger("change");
        
        // Clear inputs
        $("#new_job_group_name").val("");
        $("#new_job_group_description").val("");
      } else {
        Swal.fire("Hata!", data.message, "error");
      }
    })
    .catch((error) => {
      btn.prop("disabled", false).text("Kaydet");
      console.error("Error:", error);
      Swal.fire("Hata!", "Bir hata oluştu.", "error");
    });
});
