let urlParams = new URLSearchParams(window.location.search);
let myParam = urlParams.get("tab");

function activateTab(tabName) {
  $("#tabs-home").removeClass("active");
  $("#tabs-home-7").removeClass("active show");
  $(`#tabs-${tabName}`).addClass("active");
  $(`#tabs-${tabName}-7`).addClass("active show");
}

if (myParam == "edit-profile") {
  activateTab("profile");
}

if (myParam == "edit-account") {
  activateTab("account");
}
$(document).on("click", "#userSave", function () {
  var form = $("#userForm");

  form.validate({
    rules: {
      name: {
        required: true
      },
      password: {
        required: true
      },
      user_roles: { required: true }
    },
    messages: {
      name: {
        required: "Lütfen adınızı giriniz"
      },
      password: {
        required: "Lütfen şifrenizi giriniz"
      },
      user_roles: {
        required: "Lütfen kullanıcı rolünü seçiniz"
      }
    },
    errorElement: "em",
    errorPlacement: function (error, element) {
      if (element.hasClass("select2")) {
        error.insertAfter(element.next("span"));
      } else {
        error.insertAfter(element);
      }
    }
    // highlight: function (element, errorClass, validClass) {
    //   $(element).addClass("is-invalid").removeClass("is-valid");
    // },
  });

  if (!form.valid()) {
    return;
  }

  var formData = new FormData(form[0]);
  formData.append("action", "userSave");

  for (var pair of formData.entries()) {
    console.log(pair[0] + ", " + pair[1]);
  }
  fetch("/api/settings/settings.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Ok"
      });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).on("change", "#send_email_on_login", function () {
  var form = $("#notificationsForm");
  var formData = new FormData(form[0]);
  formData.append("action", "send_email_on_login");

  for (var pair of formData.entries()) {
    console.log(pair[0] + ", " + pair[1]);
  }
  fetch("/api/settings/settings.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      title = data.status == "success" ? "Başarılı!" : "Hata!";
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).on("click", "#home_save", function () {
  var form = $("#settingsHomeForm");
  let formData = new FormData(form[0]);
  formData.append("action", "homeSettings");

  fetch("api/settings/settings.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      //console.log(data);

      title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Ok"
      });
    });
});

$(document).on("click", "#financial_save", function () {
  var form = $("#settingsFinancialForm");
  let formData = new FormData(form[0]);
  formData.append("action", "financialSettings");

  fetch("api/settings/settings.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      //console.log(data);

      title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Ok"
      });
    });
});
