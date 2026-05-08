$(document).on("click", "#saveMyFirm", function () {
  var form = $("#myFirmForm");

  let formData = new FormData(form[0]);
  // for (data of formData.entries()) {
  //   console.log(data);
  // }

  fetch("/api/companies/mycompanies.php", {
    method: "POST",
    body: formData,
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
        confirmButtonText: "Tamam",
      });
    });
});

$(document).on("click", ".delete-mycompany", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteMyCompany";
  let confirmMessage = "Firma silinecektir!";
  let url = "/api/companies/mycompanies.php";

  deleteRecord(this, action, confirmMessage, url);
});
