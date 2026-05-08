const listgun = $(".gun"); //Puantaj tablosundaki seçim yapılacak günler
const listItems = $(".modal .nav-item"); // Açılan Modaldeki puantaj türü seçenekleri
const project_id = $("#projects option:selected").val(); //Proje id'si

//********************************************** */
listItems.click(function () {
  const clickedCells = $("table td.clicked");

  const avatar = $(this).find(".avatar");
  const avatarText = avatar.text();
  const avatarColor = avatar.css("color");
  const avatarBgColor = avatar.css("background-color");
  const avatarDataid = avatar.attr("data-id");
  const calismaTuru = avatar.attr("data-tooltip");

  clickedCells.each(function () {
    clickedCells.text(avatarText);
    clickedCells.css("color", avatarColor);
    clickedCells.attr("data-id", avatarDataid);
    clickedCells.attr("data-change", true);
    clickedCells.attr("data-tooltip", calismaTuru);
    clickedCells.attr("data-project", project_id);

    clickedCells.css("background-color", avatarBgColor);
  });
  clickedCells.removeClass("clicked");
  $("#modal-default").modal("hide");
});

//********************************************** */
listgun.on("click", function (e) {
  var background = $(this).css("background-color");
  //Başka projeden gelen veri varsa değişiklik yapma
  if (background === "rgb(187, 187, 187)") {
    return; // Exit the function and prevent further code execution
  }

  // $(this).addClass("clicked");
  if (e.which === 1 && e.ctrlKey) {
    if ($(this).hasClass("clicked")) {
      $("#modal-default").modal("show");
    }
  }
});

//Tablonun 1. satırdaki gunadi classına sahip td elemanına basınca tüm kolona click classını ekler
$(".head-date, .gunadi").on("click", function () {
  var index = $(this).index();
  $("table tbody tr").each(function () {
    //eğer td --- farklı ise
    var td = $(this).find("td").eq(index);

    //td'nin değeri --- ise seçme
    if (td.text() != "---") {
      td.toggleClass("clicked");
    }
  });
});

//********************************************** */
//Delete tuşuna basıldığı zaman içeriği temizler
$(document).keydown(function (event) {
  // 'Delete' tuşunun keycode'u 46'dır
  if (event.keyCode === 46) {
    // .clicked sınıfına sahip tüm td elemanlarını seç ve içeriğini temizle
    $("td.clicked").each(function () {
      $(this).attr("data-id", 0);

      $(this).empty();
      $(this).toggleClass("clicked");
      $(this).css("background-color", "white");
      $(this).removeAttr("data-tooltip", "");
    });
  }
});

//mouse basılı tutulduğu zaman
let isMouseDown = false;
$(".gun:not(.selected)").mouseover(function (event) {
  if (isMouseDown) {
    $(this).addClass("clicked");
  }
});

$(".gun:not(.selected)")
  .mousedown(function (event) {
    if (event.which === 1) {
      // Only execute the code if left mouse button is clicked
      isMouseDown = true;
      $(this).toggleClass("clicked");
    }
  })
  .mouseup(function () {
    isMouseDown = false;

    if ($(".gun.clicked").length > 0 && event.ctrlKey) {
      $("#modal-default").modal("show");
    }
  });

//Ecs tuşuna basıldığında seçili hücrelerdeki seçimleri iptal eder
$(document).keydown(function (event) {
  // 'Escape' tuşunun keycode'u 27'dir
  if (event.keyCode === 27) {
    $("#modal-default").modal("hide");
    // .clicked sınıfına sahip tüm td elemanlarından clicked sınıfını kaldır
    $("td.clicked").removeClass("clicked");
  }
});

//********************************************** */
function puantaj_olustur() {
  var project_id = $("#projects option:selected").val();
  var year = $("#year").val();
  var month = $("#months").val().padStart(2, "0");

  //preloader göster
  $(".preloader").fadeIn();
  // JSON verisini saklamak için bir nesne oluştur
  var jsonData = {};
  // Tablodaki her satırı döngü ile işle
  $("table tbody tr").each(function (index) {
    var row = $(this);
    var employeeData = {}; // Her çalışan için bir nesne oluştur

    // Ad, soyad ve ünvan bilgisini al
    var person_id = row.find("td:first").data("id");
    var position = project_id;

    // Tarihler için döngü yap
    //gt = greater then
    row.find("td:gt(2)").each(function (index, td) {
      var date =
        year +
        month +
        $("table thead tr:eq(1) th")
          .eq(index + 3)
          .text(); // İndeks + 2, 2. indeksten başlamasını sağlar
      var puantajId = $(this).attr("data-id") ? $(this).attr("data-id") : ""; // Durum bilgisini al
      //console.log(person_id + "--" + date + "--" + puantajId); //

      // var key = person_id + " : " + position;
      var key = person_id;
      if (jsonData[key] && jsonData[key][date]) {
        jsonData[key][date].puantajId = puantajId;
        jsonData[key][date].project = project_id;
      } else {
        // Anahtar veya tarih yoksa, yeni bir tarih nesnesi oluşturun
        if (!jsonData[key]) jsonData[key] = {};
        //puantaj değeri varsa puantaj ve proje id'sini ekle
        if (puantajId != "") {
          //var olan kayıt değiştirilmişse, puantaj id ve proje id'sini ekle
          if ($(this).attr("data-change") == "true") {
            //  $(this).attr("data-project", project_id);
            projeAdi = $("#projects option:selected").text().trim();
            // $(this).css("background-color", "#bbb");
            // $(this).css("color", "#666");
            if (projeAdi == "Proje Seçiniz") {
              projeAdi = $("#myFirm option:selected").text().trim();
            }
            $(this).attr("data-tooltip", projeAdi);
            jsonData[key][date] = {
              puantajId: puantajId,
              project_id: project_id
            };
          } else {
            // değişiklik yapılmamışsa sadece kendi kayıtlarını al
            jsonData[key][date] = {
              puantajId: puantajId,
              project_id: $(this).attr("data-project")
            };
            //console.log(person_id + "--" + date + "--" + puantajId); //
          }
        } else {
          jsonData[key][date] = { puantajId: puantajId, project_id: "" };
        }
      }

      $(this).attr("data-change", "false");
    });
  });

  // JSON verisini konsolda göster
  // console.log(JSON.stringify(jsonData, null, 2));

  var data = {
    action: "puantaj",
    project_id: project_id,
    data: JSON.stringify(jsonData)
  };

  let formData = new FormData();
  formData.append("action", "savePuantaj");
  formData.append("project_id", project_id);
  formData.append("data", JSON.stringify(jsonData));

  

  fetch("api/puantaj.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      $(".preloader").fadeOut();
      // console.log(data);
      if (data.status == "success") {
        //console.log(data.error_wages);

        title = "Başarılı";
      } else {
        title = "Hata";
      }
      //preloader gizle

      Swal.fire({
        title: "Başarılı",
        html: data.message,
        icon: "success"
      });
    });

  //preloader gizle
  $(".preloader").fadeOut();
}

$(document).ready(function () {
  $("#projects, #year, #months, #job_groups, #team_id").on("change select2:select", function () {
    Route();
  });
});

function Route() {
  var form = $("#puantajInfoForm");
  form.submit();
}
