$(document).on("click", "#saveFeedBack", function () {
  var form = $("#FeedBackForm");
  var formData = new FormData(form[0]);
  formData.append("action", "saveFeedBack");

  for (var pair of formData.entries()) {
    console.log(pair[0] + ", " + pair[1]);
  }
  fetch("/api/FeedBackController.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);

      if (data.status == "success") {
        title = "Başarılı!";
        form[0].reset();
        $('.summernote').summernote('reset');
      } else {
        title = "Hata!";
      }
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam"
      });
    });
});
