$(document).on("click", "#saveCompany", function () {
  var form = $("#companyForm");
  let formData = new FormData(form[0]);

  fetch("/api/companies/companies.php", {
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

$(document).on("click", ".delete-company", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteCompany";
  let confirmMessage = "Firma silinecektir!";
  let url = "/api/companies/companies.php";

  deleteRecord(this, action, confirmMessage, url);
});


$(document).on("change", "#firm_cities", function () {
  //İl id'si alınır ilce selectine ilceler yüklenir
  getTowns($(this).val(),"#firm_towns");
});