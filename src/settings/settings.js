$(document).ready(function () {
  var urlParams = new URLSearchParams(window.location.search);
  var myParam = urlParams.get("tab");

  // Activate tab programmatically if specified in URL
  if (myParam == "edit-profile") {
    $('#settings-tabs a[href="#tabs-profile"]').tab('show');
  } else if (myParam == "edit-account") {
    $('#settings-tabs a[href="#tabs-account"]').tab('show');
  }

  // Profile Form submit validation and handler
  $(document).on("submit", "#profileForm", function (e) {
    e.preventDefault();
    var form = $(this);

    form.validate({
      rules: {
        full_name: {
          required: true
        }
      },
      messages: {
        full_name: {
          required: "Lütfen adınızı soyadınızı giriniz"
        }
      },
      errorElement: "em",
      errorPlacement: function (error, element) {
        error.insertAfter(element);
      }
    });

    if (!form.valid()) {
      return;
    }

    var formData = new FormData(form[0]);
    formData.append("action", "userSave");

    fetch("/api/settings/settings.php", {
      method: "POST",
      body: formData
    })
      .then((response) => response.json())
      .then((data) => {
        var title = data.status == "success" ? "Başarılı!" : "Hata!";
        Swal.fire({
          title: title,
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        }).then(() => {
          if (data.status == "success") {
            window.location.reload();
          }
        });
      })
      .catch((error) => {
        console.error("Error:", error);
        Swal.fire("Hata!", "Profil bilgileri güncellenirken bir sorun oluştu.", "error");
      });
  });

  // Password Form submit validation and handler
  $(document).on("submit", "#passwordForm", function (e) {
    e.preventDefault();
    var form = $(this);

    form.validate({
      rules: {
        password: {
          required: true,
          minlength: 6
        },
        password_confirm: {
          required: true,
          equalTo: "#new_password"
        }
      },
      messages: {
        password: {
          required: "Lütfen yeni şifrenizi giriniz",
          minlength: "Şifreniz en az 6 karakter olmalıdır"
        },
        password_confirm: {
          required: "Lütfen şifrenizi tekrar giriniz",
          equalTo: "Şifreler birbiriyle eşleşmiyor"
        }
      },
      errorElement: "em",
      errorPlacement: function (error, element) {
        error.insertAfter(element);
      }
    });

    if (!form.valid()) {
      return;
    }

    var formData = new FormData(form[0]);
    formData.append("action", "userSave");

    fetch("/api/settings/settings.php", {
      method: "POST",
      body: formData
    })
      .then((response) => response.json())
      .then((data) => {
        var title = data.status == "success" ? "Başarılı!" : "Hata!";
        Swal.fire({
          title: title,
          text: data.message,
          icon: data.status,
          confirmButtonText: "Tamam"
        }).then(() => {
          if (data.status == "success") {
            form[0].reset();
          }
        });
      })
      .catch((error) => {
        console.error("Error:", error);
        Swal.fire("Hata!", "Şifre güncellenirken bir sorun oluştu.", "error");
      });
  });
});

// Original Notification checkboxes (retained for backward compatibility)
$(document).on("change", "#send_email_on_login", function () {
  var form = $("#notificationsForm");
  var formData = new FormData(form[0]);
  formData.append("action", "send_email_on_login");

  fetch("/api/settings/settings.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      // Swallowed status change
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

// Original Home page settings save (retained for backward compatibility)
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
      title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Ok"
      });
    });
});

// Original Financial settings save (retained for backward compatibility)
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
      title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Ok"
      });
    });
});

// Account deletion handler
$(document).on("click", "#btn-delete-account", function () {
  Swal.fire({
    title: "Hesabınızı Silmek İstediğinize Emin misiniz?",
    text: "Hesabınızı sildiğinizde tüm kayıtlarınız silinecektir. Bu işlem geri alınamaz!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Evet, Hesabımı Sil",
    cancelButtonText: "İptal",
  }).then((result) => {
    if (result.isConfirmed) {
      let formData = new FormData();
      formData.append("action", "deleteAccount");

      fetch("api/settings/settings.php", {
        method: "POST",
        body: formData
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            Swal.fire({
              title: "Başarılı!",
              text: data.message,
              icon: "success",
              confirmButtonText: "Tamam"
            }).then(() => {
              window.location.href = "logout.php";
            });
          } else {
            Swal.fire("Hata!", data.message, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Hata!", "Hesap silinirken bir sorun oluştu.", "error");
        });
    }
  });
});

// System General Settings Save
$(document).on("submit", "#systemGeneralForm", function (e) {
  e.preventDefault();
  var form = $(this);

  var formData = new FormData(form[0]);
  formData.append("action", "systemGeneralSave");

  fetch("/api/settings/settings.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      var title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam"
      }).then(() => {
        if (data.status == "success") {
          window.location.reload();
        }
      });
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Hata!", "Genel ayarlar güncellenirken bir sorun oluştu.", "error");
    });
});

// System SMTP Settings Save
$(document).on("submit", "#systemSmtpForm", function (e) {
  e.preventDefault();
  var form = $(this);

  var formData = new FormData(form[0]);
  formData.append("action", "systemSmtpSave");

  fetch("/api/settings/settings.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      var title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam"
      }).then(() => {
        if (data.status == "success") {
          window.location.reload();
        }
      });
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Hata!", "SMTP ayarları güncellenirken bir sorun oluştu.", "error");
    });
});

// System SMTP Connection Test
$(document).on("click", "#btn-test-smtp", function () {
  var emailInput = $("#smtp_test_email");
  var testEmail = emailInput.val().trim();
  
  if (testEmail === "") {
    Swal.fire("Hata!", "Lütfen test e-postasının gönderileceği bir adres giriniz.", "warning");
    return;
  }

  // Gather SMTP form data
  var smtpForm = $("#systemSmtpForm")[0];
  var formData = new FormData(smtpForm);
  formData.append("action", "systemSmtpTest");
  formData.append("test_email", testEmail);
  formData.append("test_account", $("#smtp_test_account").val());

  // Disable button and show loading state
  var btn = $(this);
  var originalHtml = btn.html();
  btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Test ediliyor...');

  fetch("/api/settings/settings.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      btn.prop("disabled", false).html(originalHtml);
      var title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam"
      });
    })
    .catch((error) => {
      btn.prop("disabled", false).html(originalHtml);
      console.error("Error:", error);
      Swal.fire("Hata!", "E-posta gönderim testi başarısız oldu.", "error");
    });
});
