var form = $("#missionProcessForm");
$(document).on("click", "#saveMissionProcess", function () {
  //var form = $("#missionProcessForm");

  form.validate({
    rules: {
      process_name: {
        required: true,
      },
      process_order: {
        required: true,
        number: true,
      },
    },
    messages: {
      process_name: {
        required: "Süreç adı boş bırakılamaz.",
      },
      process_order: {
        required: "Süreç sırası boş bırakılamaz.",
        number: "Süreç sırası sayı olmalı",
      },
    },
  });

  if (!form.valid()) {
    return;
  }

  let formData = new FormData(form[0]);

  // for (var pair of formData.entries()) {
  //   console.log(pair[0] + ", " + pair[1]);
  // }

  fetch("/api/missions/process.php", {
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
$(document).on("keypress", "#process_order", function (e) {
  if ((e.which != 8 && e.which != 0 && e.which < 48) || e.which > 57) {
    return false;
  }
});

$("#sortable").sortable({
  items: ".process-item",
  update: function (event, ui) {
    var order = $(this).sortable("toArray");
    let formData = new FormData(form[0]);
    formData.append("order", JSON.stringify(order));
    formData.append("action", "updateOrder");

    // for (var pair of formData.entries()) {
    //         console.log(pair[0] + ', ' + pair[1]);
    //       }

    fetch("/api/missions/process.php", {
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
      .find(".process-order")
      .text(index + 1);
  });
}
