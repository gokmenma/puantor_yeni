$(document).on("click", "#saveButton", function () {
  var form = $("#serviceHeadForm");

  form.validate({
    rules: {
      service_head: {
        required: true,
      },
    },
    messages: {
      service_head: {
        required: "Servis Konusu boş bırakılamaz!",
      },
    },
  });
  if(!form.valid()) return false;

  var formData = new FormData(form[0]);
  formData.append("action", "saveServiceHead");

  fetch("api/defines.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        title = "Başarılı!";
      } else {
        title = "Hata!";
      }
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam",
      });
    });
});

$(document).on("click", ".delete-defines", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteServiceHead";
  let confirmMessage = "Servis Konusu silinecektir!";
  let url = "/api/defines.php";

  deleteRecord(this, action, confirmMessage, url);
});
