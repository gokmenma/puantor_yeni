$(document).on("click", "#saveProjectStatus", function () {
  var form = $("#projectStatusForm");
  let formData = new FormData(form[0]);

  form.validate({
    rules: {
      statu_name: {
        required: true
      }
    },
    messages: {
      statu_name: {
        required: "Durum adı boş bırakılamaz."
      }
    }
  });
  if (!form.valid()) {
    return;
  }

  fetch("/api/defines/project-status.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      $("#id").val(data.id);
      //   console.log(data);
      title = data.status == "success" ? "Başarılı!" : "Hata!";
      swal.fire({ title: title, text: data.message, icon: data.status });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).on("click", ".delete-project-status", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteProjectStatus";
  let confirmMessage = "Proje Durumu Tanımlaması silinecektir!";
  let url = "/api/defines/project-status.php";

  deleteRecord(this, action, confirmMessage, url);
});
