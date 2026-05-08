$(document).on("click", "#urun_kaydet", function () {
  var form = $("#productForm");

  let formData = new FormData(form[0]);
  formData.append("action", "saveProduct");

  // for (data of formData.entries()) {
  //   console.log(data);
  // }
  fetch("api/products.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        title = "Başarılı!";
        $("#id").val(data.lastid);
      } else {
        title = "Hata!";
      }
      swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam",
      });
    });
});

$(document).on("click", ".delete-product", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteProduct";
  let confirmMessage = "Ürün silinecektir!";
  let url = "/api/products.php";

  deleteRecord(this, action, confirmMessage, url);
});