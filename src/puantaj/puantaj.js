const listgun = $(".gun"); //Puantaj tablosundaki seçim yapılacak günler
const listItems = $(".modal .nav-item"); // Açılan Modaldeki puantaj türü seçenekleri
const project_id = $("#projects option:selected").val(); //Proje id'si

//********************************************** */
listItems.click(function () {
  const clickedCells = $("table td.clicked");
  const current_project_id = $("#projects option:selected").val();

  const avatar = $(this).find(".avatar");
  const avatarText = avatar.text();
  const avatarColor = avatar.css("color");
  const avatarBgColor = avatar.css("background-color");
  const avatarDataid = avatar.attr("data-id");
  const calismaTuru = avatar.attr("data-tooltip");

  clickedCells.each(function () {
    let cell = $(this);
    cell.text(avatarText);
    cell.css("color", avatarColor);
    cell.attr("data-id", avatarDataid);
    cell.attr("data-change", "true");
    cell.attr("data-tooltip", calismaTuru);
    cell.attr("data-project", current_project_id);
    cell.css("background-color", avatarBgColor);
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
      $(this).attr("data-change", "true");
      $(this).css("background-color", "white");
      $(this).removeAttr("data-tooltip");
      $(this).removeClass("clicked");
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

function puantaj_olustur() {
  var project_id = $("#projects option:selected").val();
  var year = $("#year").val();
  var month = $("#months").val().padStart(2, "0");
  
  // Kaydet butonunu bul ve kilitle
  var saveBtn = $('button[onclick="puantaj_olustur()"]');
  var originalBtnHtml = saveBtn.html();
  saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Kaydediliyor...');

  var jsonData = {};

  // Gün numaralarını al (2. satırdaki th'ler)
  var headDates = [];
  $("#puantajTable thead tr").eq(1).find("th.head-date").each(function() {
    var day = $(this).text().trim();
    if(day) headDates.push(day.padStart(2, "0"));
  });

  // Tablodaki her satırı döngü ile işle
  $("#puantajTable tbody tr").each(function (rowIndex) {
    var row = $(this);
    var person_id = row.find("td[data-id]").first().attr("data-id");
    
    if (!person_id) return;

    row.find("td").each(function(tdIdx) {
        var td = $(this);
        if (!td.hasClass("gun") && !td.hasClass("noselect")) return;
        if (td.text().trim() === "---") return;

        var dateIdx = tdIdx - 3; 
        if (dateIdx < 0 || dateIdx >= headDates.length) return;

        var date = year + month + headDates[dateIdx];
        var puantajId = td.attr("data-id") || "";

        if (!jsonData[person_id]) jsonData[person_id] = {};

        if (td.attr("data-change") === "true") {
            jsonData[person_id][date] = {
                puantajId: puantajId,
                project_id: project_id
            };
            td.attr("data-change", "false");
        } else if (puantajId !== "") {
            jsonData[person_id][date] = {
                puantajId: puantajId,
                project_id: td.attr("data-project") || project_id
            };
        }
    });
  });

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
      // Butonu eski haline getir
      saveBtn.prop('disabled', false).html(originalBtnHtml);

      Swal.fire({
        title: data.status == "success" ? "Başarılı" : (data.status == "info" ? "Bilgi" : "Hata"),
        html: data.message,
        icon: data.status == "success" ? "success" : (data.status == "info" ? "info" : "error")
      });
    })
    .catch((error) => {
      saveBtn.prop('disabled', false).html(originalBtnHtml);
      Swal.fire("Hata", "Sistem hatası oluştu: " + error, "error");
    });
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
