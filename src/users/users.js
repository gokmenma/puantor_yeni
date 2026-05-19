$(document).on("click", "#kullanici_kaydet", function () {
  let id = $("#user_id").val();
  var form = $("#userForm");

  form.validate({
    rules: {
      full_name: {
        required: true
      },
      username: {
        required: true
      },
      email: {
        required: true,
        email: true
      },
      password: {
        required: function() {
          return $("#user_id").val() == "0";
        }
      },
      user_roles: {
        required: true
      }
    },
    messages: {
      full_name: {
        required: "Lütfen ad soyad giriniz"
      },
      username: {
        required: "Lütfen kullanıcı adını giriniz"
      },
      email: {
        required: "Lütfen email adresini giriniz",
        email: "Lütfen geçerli bir email adresi giriniz"
      },
      password: {
        required: "Lütfen şifreyi giriniz"
      },
      user_roles: {
        required: "Lütfen kullanıcı rolünü seçiniz"
      }
    },
    errorPlacement: function (error, element) {
      if (element.hasClass("select2")) {
        // select2 konteynerini bul
        var container = element.next(".select2-container");
        // Hata mesajını, select2 konteynerinin sonuna ekler
        error.insertAfter(container);
      } else {
        // Diğer tüm durumlar için varsayılan davranış
        error.insertAfter(element);
      }
    }
  });
  if (!form.valid()) {
    return;
  }

  var formData = new FormData(form[0]);

  // If DataTable is present, collect checked checkboxes from non-visible pages
  if ($.fn.DataTable && $.fn.DataTable.isDataTable('#responsible-persons-table')) {
      var dt = $('#responsible-persons-table').DataTable();
      dt.$('input[type="checkbox"]:checked').each(function() {
          // Only append if it is NOT currently in the DOM (already caught by FormData)
          if (!$.contains(document, this)) {
              formData.append(this.name, this.value);
          }
      });
  }

  formData.append("id", id);
  formData.append("action", "userSave");

  // for (data of formData.entries()) {
  //   console.log(data);
  // }

  fetch("/api/users/users.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        title = "Başarılı!";
        $("#user_id").val(data.lastid);
      } else {
        title = "Hata!";
      }
      swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam"
      }).then(() => {
        if (data.status == "success" && id == "0") {
            window.location.href = "index.php?p=users/manage&id=" + data.lastid;
        }
      });
    });
});

$(document).on("click", ".add-user", function () {
  var firm_id = $("#myFirm").val();

  var formData = new FormData();
  formData.append("firm_id", firm_id);
  formData.append("action", "isThereUserRoleGroup");

  fetch("/api/users/users.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      //console.log(data);
      if (data.roles == 0) {
        swal
          .fire({
            title: "Hata!",
            text: "Lütfen önce kullanıcı rolü ekleyiniz.",
            icon: "error",
            confirmButtonText: "Tamam"
          })
          .then(() => {
            window.location.href = "index.php?p=users/roles/manage";
          });
      }
    });
    window.location.href = "index.php?p=users/roles/manage";
});

$(document).on("click", ".delete_user", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteUser";
  let confirmMessage = "Kullanıcı silinecektir!";
  let url = "/api/users/users.php";

  deleteRecord(this, action, confirmMessage, url);
});
