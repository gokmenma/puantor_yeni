var hasProcess = false;

//Genel Gelir-Gider Ekle
$(document).ready(function () {
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
  let type = $(this).data("type");
  let url = "/api/financial/transaction.php?type=" + type;

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
  //case_id'yi al sayfayı post ile yenile
  var case_id = $(this).val();
  var form = $("#caseForm");
  //case_id'yi form'a ekle
  form.append(`<input type="hidden" name="case_id" value="${case_id}">`);
  form.submit();
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

//Personellere ödeme yap
$(document).ready(function () {
  addCustomValidationMethods();
  addCustomValidationValidValue();

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

  $("#savePayToPersons").on("click", function () {
    if ($("#payToPersonsForm").valid()) {
      //tablodaki satırlardaki değerleri al
      var person_ids = [];
      var amounts = [];
      var person_id = "";
      var amount = "";

      var form = $("#payToPersonsForm");
      var formData = new FormData(form[0]);
      //preloader göster
      $(".preloader").fadeIn();
      //tablodaki satırlardaki değerleri al
      $("#payToPersons tbody tr").each(function () {
        //ilk td elemanının data-id attribute'undaki değeri al
        person_id = $(this).find("td:eq(0)").data("id");
        amount = $(this).find("td:eq(1) input").val();
        //eğer amount 0'dan büyükse veya boş değilse veya numeric ise işlem yap
        if (amount > 0 && amount != "" && $.isNumeric(amount)) {
          person_ids.push(person_id);
          amounts.push(amount);
        }
      });

      formData.append("person_ids", person_ids);
      formData.append("amounts", amounts);

      for (var pair of formData.entries()) {
        console.log(pair[0] + ", " + pair[1]);
      }

      formData.append("action", "payToPersons");
      fetch("api/financial/transaction.php", {
        method: "POST",
        body: formData
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status == "success") {
            title = "Başarılı!";
          } else {
            title = "Hata";
          }
          swal
            .fire({
              title: title,
              text: data.message,
              icon: data.status
            })
            .then((result) => {
              if (result.isConfirmed) {
                location.reload();
              }
            });
        });
      //preloader gizle
      $(".preloader").fadeOut();
    }
  });
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

  //tablonun 4. sütunundaki (indeks 3) veriyi al
  let type = $(this).closest("tr").find("td:eq(3)").text().trim();

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
