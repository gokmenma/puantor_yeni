$(document).ready(function () {
  $("#payment-load-file").on("change", function () {
    var file = $("#payment-load-file")[0].files[0];
    if (!file) {
      console.error("No file selected");
      return;
    }
    // Güvenlik kontrolü: Dosya türünü kontrol et
    var allowedTypes = [
      "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      "application/vnd.ms-excel"
    ];
    if (!allowedTypes.includes(file.type)) {
      $("#payment-load-file").val(""); // Inputu temizle
      swalAlert("Uyarı!", "Lütfen bir Excel dosyası yükleyin.");
      return;
    }

    // Güvenlik kontrolü: Dosya boyutunu kontrol et (örneğin, 5MB'den büyük olamaz)
    var maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
      $("#payment-load-file").val(""); // Inputu temizle
      swalAlert(
        "Uyarı!",
        "Dosya boyutu çok büyük. Lütfen daha küçük bir dosya yükleyin."
      );
      return;
    }

    var reader = new FileReader();
    reader.onload = function (e) {
      var data = new Uint8Array(e.target.result);
      var workbook = XLSX.read(data, { type: "array" });
      var sheet = workbook.Sheets[workbook.SheetNames[0]];
      var result = XLSX.utils.sheet_to_html(sheet);

      // #result içindeki tablonun body'sine aktar
      var tableBody = $(result)
        .find("tbody tr")
        .slice(1)
        .map(function () {
          return $(this).prop("outerHTML");
        })
        .get()
        .join("");
      if (tableBody === "") {
        tableBody = "<tr><td colspan='100%'>Dosyada veri bulunamadı.</td></tr>";
      } else {
        $("#result tbody").html(tableBody);
        $("#result table").addClass("table table-hover table-bordered");
      }
    };

    reader.onerror = function (error) {
      console.error("Error reading file:", error);
    };

    reader.readAsArrayBuffer(file);
  });
});

function swalAlert(title = "Uyarı!", text, icon = "warning") {
  Swal.fire({
    title: title,
    text: text,
    icon: icon,
    confirmButtonText: "Tamam"
  });
}

//Yüklenen dosya veritabanına kaydedilecek
$(document).on("click", "#paymentLoadButton", function () {
  var file = $("#payment-load-file")[0].files[0];
  if (!file) {
    swalAlert("Uyarı!", "Lütfen bir dosya seçin.", "warning");
    return;
  }

  // tabloda 'Dosyada veri bulunamadı' uyarısı var mı kontrol et
  //tabloda 'Dosyada veri bulunamadı.' uyarısından başka veri var mı kontrol et
  if ($("#result tbody tr td").text() === "Dosyada veri bulunamadı.") {
    swalAlert("Uyarı!", "Seçtiğiniz dosyada veri yok!", "warning");
    $("#persons-load-file").val(""); // Inputu temizle
    return;
  }  

  // tablonun body'sinde veri var mı kontrol et
  if ($("#result tbody td").length === 0) {
    swalAlert("Uyarı!", "Seçtiğiniz dosyada veri yok!", "warning");
    $("#payment-load-file").val(""); // Inputu temizle
    return;
  }

  //butona spinner ekleyerek kullanıcının işlem yapıldığını anlamasını sağla
  var spinner = $(
    "<span class='spinner-border spinner-border-sm me-2' role='status' aria-hidden='true'></span>"
  );
  $("#paymentLoadButton").prepend(spinner);
  $("#paymentLoadButton").prop("disabled", true);

  var form = $("#paymentLoadForm");
  var formData = new FormData(form[0]);
  formData.append("action", "payment-load-from-xls");

  //   for (var pair of formData.entries()) {
  //     console.log(pair[0] + ", " + pair[1]);
  //   }

  fetch("api/bordro/payment-load.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      //console.log(data);
      if (data.status === "success") {
        swalAlert("Başarılı!", "Ödemeler başarıyla yüklendi.", "success");
        $("#payment-load-file").val("");
        $("#result tbody").html("");
        $("#paymentLoadButton").prop("disabled", false);
        $("#paymentLoadButton .spinner-border").remove();
      } else {
        $("#paymentLoadButton").prop("disabled", false);
        $("#paymentLoadButton .spinner-border").remove();
        swalAlert("Hata!", data.message, "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      swalAlert("Hata!", "Bir hata oluştu. Lütfen tekrar deneyin.", "error");
    });
});
$(document).on('click', '.clear', function() {
  $("#payment-load-file").val("");
  $("#result tbody").html("");
  $("#paymentLoadButton").prop("disabled", false);
  $("#paymentLoadButton .spinner-border").remove();


});