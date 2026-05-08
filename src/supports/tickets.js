// Summernote elementini seç
$(document).ready(function () {
  var summernoteElement = $(".summernote");

  summernoteElement.summernote({
    height: 200,
    callbacks: {
      onChange: function (contents, $editable) {
        summernoteElement.val(
          summernoteElement.summernote("isEmpty") ? "" : contents
        );
        validator.element(summernoteElement);
      }
    }
  });

  // Form doğrulama
  var validator = $("#supportTicketForm").validate({
    errorElement: "em",
    ignore: ":hidden:not(.summernote),.note-editable.card-block",
    errorPlacement: function (error, element) {
      if (element.prop("type") === "checkbox") {
        error.insertAfter(element.siblings("label"));
      } else if (element.hasClass("summernote")) {
        error.insertAfter(element.siblings(".note-editor"));
      } else {
        error.insertAfter(element);
      }
    },
    rules: {
      subject: {
        required: true
      },
      message: {
        required: true
        // required: function () {
        //   // Summernote içeriğini kontrol et
        //   var summernoteContent = $(".summernote").summernote("isEmpty");
        //   return summernoteContent;
        // }
      }
    },
    messages: {
      subject: {
        required: "Bu alan zorunludur."
      },
      message: {
        required: "Bu alan zorunludur."
      }
    }
  });

  // Form gönderme işlemi
  $("#send-ticket").on("click", function (e) {
    //preloader göster

    // Formu doğrula
    if ($("#supportTicketForm").valid()) {
      $(".preloader").fadeIn(200);

      e.preventDefault(); // Formun varsayılan gönderimini engelle
      // Form geçerliyse gönder
      var form = $("#supportTicketForm");
      let formData = new FormData(form[0]);

      fetch("api/supports/tickets.php", {
        method: "POST",
        body: formData
      })
        .then((response) => response.json())
        .then((data) => {
          //preloader gizle
          $(".preloader").fadeOut(200);
          let title = data.status == "success" ? "Başarılı!" : "Hata!";
          swal
            .fire({
              title: title,
              text: data.message,
              icon: data.status
            })
            .then(() => {
              if (data.status == "success") {
                window.location.reload();
              }
            });
        });
    }
  });
});

$(document).on("click", "#send_new_ticket_message", function () {
  var form = $("#newTicketMessageForm");
  let formData = new FormData(form[0]);
  formData.append("action", "newTicketMessage");

  form.validate({
    rules: {
      message: {
        required: true
      }
    },
    messages: {
      message: {
        required: "Lütfen mesajınızı giriniz."
      }
    }
  });
  if (!form.valid()) {
    return false;
  }

  fetch("api/supports/tickets.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      // console.log(data);
      title = data.status == "success" ? "Başarılı!" : "Hata!";
      swal
        .fire({
          title: title,
          text: data.message,
          icon: data.status
        })
        .then(() => {
          if (data.status == "success") {
            window.location.reload();
          }
        });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).on("click", "#close_ticket", function () {
  swal
    .fire({
      title: "Emin misiniz?",
      text: "Destek talebi kapatılacaktır!",
      icon: "warning",
      showCancelButton: true,
      cancelButtonColor: "#d33",
      confirmButtonText: "Evet,Kapat!"
    })
    .then((result) => {
      if (result.isConfirmed) {
        closeTicket();
      }
    });
});

function closeTicket() {
  let id = $("#support_id").val();
  let formData = new FormData();
  formData.append("action", "closeTicket");
  formData.append("id", id);

  fetch("api/supports/tickets.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      // console.log(data);
      let title = data.status == "success" ? "Başarılı!" : "Hata!";
      swal
        .fire({
          title: title,
          text: data.message,
          icon: data.status
        })
        .then(() => {
          if (data.status == "success") {
            window.location.reload();
          }
        });
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}
