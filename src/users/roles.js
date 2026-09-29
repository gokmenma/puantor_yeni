//Rol Kaydet butonuna basınca Rol Grubunu kaydedecek
$(document).on("click", "#rol_kaydet", function () {
  var form = $("#roleForm");

  let formData = new FormData(form[0]);
  formData.append("id", $("#role_id").val());
  formData.append("action", "saveRoles");

  // for (data of formData.entries()) {
  //   console.log(data);
  // }

  fetch("api/users/roles.php", {
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
        icon: data.status,
        title: title,
        text: data.message
      });
    });
});

//Yetki grubunu silme butonu
$(document).on("click", ".delete_role", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteRole";
  let confirmMessage = "Rol silinecektir!";
  let url = "/api/users/roles.php";

  deleteRecord(this, action, confirmMessage, url);
});


//Yetkileri kopyala butonuna basınca modal açılacak ve yetkileri kopyalayacağı rol seçilecek
$(document).on("click", ".copy-roles", function () {
  var id = $(this).data("id");
  $("#copy_role_id").val(id);
  $("#role_name").text($(this).data("name"));
  var formData = new FormData();
  formData.append("id", id);
  formData.append("action", "copyRoles");

  fetch("api/users/roles.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      data = data.roles || [];
      var options = '<option value="">Kaynak Rol Seçin...</option>';
      data.forEach((element) => {
        options += `<option value="${element.id}">${element.roleName}</option>`;
      });
      $("#role_to_copy").html(options).trigger('change');
    });
});

//Modaldaki Yetkileri kopyala butonu
$(document).on("click", "#copy_roles", function () {
  var roleToCopy = $("#role_to_copy").val();
  if (!roleToCopy) {
    Swal.fire({
      icon: "warning",
      title: "Uyarı",
      text: "Lütfen yetkileri kopyalanacak kaynak rolü seçin!"
    });
    return;
  }

  var form = $("#copyRoleForm");
  let formData = new FormData(form[0]);

  fetch("api/users/auths.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      var isSuccess = (data.status == "success");
      var title = isSuccess ? "Başarılı!" : "Hata!";
      
      Swal.fire({
        icon: data.status,
        title: title,
        text: data.message
      }).then(() => {
        if (isSuccess) {
          var modalEl = document.getElementById('modal-small');
          if (modalEl) {
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
              modalInstance.hide();
            } else {
              $('#modal-small').modal('hide');
            }
          }
        }
      });
    });
});
