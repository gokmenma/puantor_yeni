$(document).on("click", "#saveJobGroups", function () {
  var form = $("#jobGroupsForm");
  let formData = new FormData(form[0]);

  form.validate({
    rules: {
        job_group_name: {
        required: true
      }
    },
    messages: {
      job_group_name: {
        required: "İş Grubu adı boş bırakılamaz."
      }
    }
  });
  if (!form.valid()) {
    return;
  }

  fetch("/api/defines/job-groups.php", {
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

$(document).on("click", ".delete-job-groups", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteJobGroups";
  let confirmMessage = "İş Grubu Tanımlaması silinecektir!";
  let url = "/api/defines/job-groups.php";

  deleteRecord(this, action, confirmMessage, url);
});
