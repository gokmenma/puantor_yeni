
$(document).on("click", "#saveMissionHeader", function () {
  var form = $("#missionHeadersForm");

  form.validate({
    rules: {
      header_name: {
        required: true,
      },
      header_order: {
        required: true,
        number: true,
      },
    },
    messages: {
      header_name: {
        required: "Görev başlığı boş bırakılamaz.",
      },
      header_order: {
        required: "Görev başlık sırası boş bırakılamaz.",
        number: "Görev başlık sırası sayı olmalı",
      },
    },
  });

  if (!form.valid()) {
    return;
  }

  let formData = new FormData(form[0]);

  for (var pair of formData.entries()) {
    console.log(pair[0] + ", " + pair[1]);
  }

  fetch("/api/missions/headers.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      title = data.status == "success" ? "Başarılı" : "Hata";
      Swal.fire({ title: title, text: data.message, icon: data.status });
    });
});

//Süreç sırası inputuna sadece sayı girilmesini sağlar
$(document).on("keypress", "#header_order", function (e) {
  if ((e.which != 8 && e.which != 0 && e.which < 48) || e.which > 57) {
    return false;
  }
});
// console.log($.fn.jquery); // jQuery sürümünü kontrol eder
// console.log($.ui);

$("#sortable").sortable({
  
  items: ".header-item",
  update: function (event, ui) {
    var order = $(this).sortable("toArray");
    var form = $("#missionHeadersForm");
    let formData = new FormData(form[0]);
    formData.append("order", JSON.stringify(order));
    formData.append("action", "updateOrder");

    for (var pair of formData.entries()) {
            console.log(pair[0] + ', ' + pair[1]);
          }

    fetch("/api/missions/headers.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success") {
          // Sıralama güncellendi, DOM'u güncelle
          updateOrderInDOM(order);
        } else {
          console.error("Error updating order:", data.message);
        }
      });
  },
});

$("#sortable").disableSelection();

function updateOrderInDOM(order) {
  order.forEach((id, index) => {
    $("#" + id)
      .find(".header-order")
      .text(index + 1);
  });
}
