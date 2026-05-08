$("#sortable").sortable({
  items: ".header-item",
  update: function (event, ui) {
    var order = $(this).sortable("toArray");
    var form = $("#missionHeadersForm");
    let formData = new FormData();
    formData.append("order", JSON.stringify(order));
    formData.append("action", "updateOrder");

    for (var pair of formData.entries()) {
      console.log(pair[0] + ", " + pair[1]);
    }

    fetch("/api/missions/headers.php", {
      method: "POST",
      body: formData
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success") {
          console.log("Sıralama güncellendi");
        } else {
          console.error("Error updating order:", data.message);
        }
      });
  }
});

$("#sortable").disableSelection();
$("#sortable").on("sortupdate", function (event, ui) {
  var order = $(this).sortable("toArray");
  //console.log(order); // Sıralanan öğelerin id'lerini konsola yazdır
});

$(".header-item").sortable({
  connectWith: ".header-item",
  items: ".mission-items",
  update: function (event, ui) {
    if (this === ui.item.parent()[0]) {
      var movedItemId = ui.item.attr("id");
      console.log("Taşınan öğenin ID'si: " + movedItemId); // Taşınan öğenin ID'sini konsola yazdır
      var newHeaderId = $(this).attr("id");
      console.log("Yeni başlık ID'si: " + newHeaderId); // Yeni başlık ID'sini konsola yazdır

      var formData = new FormData();
      formData.append("mission_id", movedItemId);
      formData.append("header_id", newHeaderId);
      formData.append("action", "updateMissionHeader");

      for (var pair of formData.entries()) {
        console.log(pair[0] + ", " + pair[1]);
      }

      fetch("/api/missions/missions.php", {
        method: "POST",
        body: formData
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            console.log(data);

            console.log("Başlık güncellendi");
          } else {
            console.error("Başlık güncellenirken hata oluştu:", data.message);
          }
        });
    }
  }
});

$(".done-mission").on("change", function () {
  var missionId = $(this).data("mission-id");
  var isDone = $(this).is(":checked") ? 1 : 0;
  var header = $(this).closest(".card-title");

  var formData = new FormData();
  formData.append("missionId", missionId);
  formData.append("isDone", isDone);
  formData.append("action", "updateIsDone");

  fetch("/api/missions/missions.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        if (isDone) {
          header.addClass("done").removeClass("no-done");
        } else {
          header.addClass("no-done").removeClass("done");
        }
      } else {
        console.error("Güncelleme başarısız:", data.message);
      }
    });
});

$(document).on("click", "#done-show", function () {
  var main = $("#sortable");
  var toggle = main.find(".done");

  // sortable div içindeki done classına sahip divlerin en yakın mission-items divini bulup toggle işlemi yapar
  toggle.closest(".mission-items").toggle();

  // header-item içindeki mission-items elemanlarının görünürlüğünü kontrol et ve toggle işlemi yap
  $(".header-item").each(function () {
    var hasVisibleMissionItems = false;

    $(this)
      .find(".mission-items")
      .each(function () {
        if ($(this).is(":visible")) {
          hasVisibleMissionItems = true;
          return false; // Döngüyü kır
        }
      });

    if (!hasVisibleMissionItems) {
      $(this).toggle();
    } else {
      $(this).show();
    }
  });

  var button = $(this);
  if (button.text().trim() === "Göster") {
    button.html('<i class="ti ti-eye-off icon me-1"></i> Gizle');
    var visible = 1;
  } else {
    button.html('<i class="ti ti-eye-check icon me-1"></i> Göster');
    var visible = 0;
  }
  updateIsDoneVisibility(visible);
});

function updateIsDoneVisibility(visible) {
  var formData = new FormData();
  formData.append("visible", visible);
  formData.append("action", "updateIsDoneVisibility");

  for (var pair of formData.entries()) {
    console.log(pair[0] + ", " + pair[1]);
  }

  fetch("/api/missions/missions.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        console.log(data.message);
      } else {
        swal.fire({
          title: "Hata!",
          text: data.message,
          icon: "error"
        });
      }
    });
}
