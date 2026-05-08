$(document).on("click", "#saveMission", function () {
  var form = $("#missionForm");
  let formData = new FormData(form[0]);

  // for (var pair of formData.entries()) {
  //   console.log(pair[0] + ", " + pair[1]);
  // }
  form.validate({
    rules: {
      header_id: {
        required: true
      }
    },
    messages: {
      header_id: {
        required: "Görev Başlığını seçiniz"
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
  //preloader göster
  $(".preloader").show();

  fetch("api/missions/missions.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({ title: title, text: data.message, icon: data.status });
      $("#mission_id").val(data.lastInsertId);
      //preloader gizle
      $(".preloader").hide();
    })
    .catch((error) => {
      console.error("Error:", error);
    });
    $(".preloader").hide();
});

$(document).on("click", ".delete-mission", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteMission";
  let confirmMessage = "Görev silinecektir!";
  let url = "/api/missions/missions.php";

  deleteRecord(this, action, confirmMessage, url);
});
