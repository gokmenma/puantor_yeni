const listItems = $(".modal .nav-item"); // Açılan Modaldeki puantaj türü seçenekleri

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

  const rowsToRecalculate = new Set();
  clickedCells.each(function () {
    let cell = $(this);
    cell.text(avatarText);
    cell.css("color", avatarColor);
    cell.attr("data-id", avatarDataid);
    cell.attr("data-change", "true");
    cell.attr("data-tooltip", calismaTuru);
    cell.attr("data-project", current_project_id);
    cell.css("background-color", avatarBgColor);

    let parentTr = cell.closest("tr");
    if (parentTr.length > 0) {
      rowsToRecalculate.add(parentTr[0]);
    }
  });
  clickedCells.removeClass("clicked");
  $("#modal-default").modal("hide");

  rowsToRecalculate.forEach(function(tr) {
    calculateRowTotals($(tr));
  });
});

//********************************************** */
// Sayfalama desteği için event delegation kullanıyoruz
$(document).on("click", ".gun", function (e) {
  var background = $(this).css("background-color");
  //Başka projeden gelen veri varsa değişiklik yapma
  if (background === "rgb(187, 187, 187)") {
    return; // Exit the function and prevent further code execution
  }

  // $(this).addClass("clicked");
  const autoOpen = localStorage.getItem('autoOpenPuantajTypes') === 'true';
  if (e.which === 1 && (e.ctrlKey || autoOpen)) {
    if ($(this).hasClass("clicked")) {
      $("#modal-default").modal("show");
    }
  }
});

//Tablonun 1. satırdaki gunadi classına sahip td elemanına basınca tüm kolona click classını ekler
$(".head-date, .gunadi").on("click", function () {
  var index = $(this).index();
  
  // DataTable nesnesini alıp sadece FILTRELENMIS satırları geziyoruz
  var table = $("#puantajTable").DataTable();
  var rows = table.rows({ search: 'applied' }).nodes(); 
  
  $(rows).each(function () {
    //eğer td --- farklı ise
    var td = $(this).find("td").eq(index);

    //td'nin değeri --- ise seçme
    if (td.text().trim() != "---") {
      td.toggleClass("clicked");
    }
  });
});

//********************************************** */
//Delete tuşuna basıldığı zaman içeriği temizler
$(document).keydown(function (event) {
  // 'Delete' tuşunun keycode'u 46'dır
  if (event.keyCode === 46) {
    const rowsToRecalculate = new Set();
    // .clicked sınıfına sahip tüm td elemanlarını seç ve içeriğini temizle
    $("td.clicked").each(function () {
      let cell = $(this);
      cell.attr("data-id", 0);
      cell.empty();
      cell.attr("data-change", "true");
      cell.css("background-color", "white");
      cell.removeAttr("data-tooltip");
      cell.removeClass("clicked");

      let parentTr = cell.closest("tr");
      if (parentTr.length > 0) {
        rowsToRecalculate.add(parentTr[0]);
      }
    });

    rowsToRecalculate.forEach(function(tr) {
      calculateRowTotals($(tr));
    });
  }
});

//mouse basılı tutulduğu zaman
let isMouseDown = false;
// Dinamik yüklenen satırlar için event delegation
$(document).on("mouseover", ".gun:not(.selected)", function (event) {
  if (isMouseDown) {
    $(this).addClass("clicked");
  }
});

$(document).on("mousedown", ".gun:not(.selected)", function (event) {
  if (event.which === 1) {
    isMouseDown = true;
    $(this).toggleClass("clicked");
  }
});

$(document).on("mouseup", ".gun:not(.selected)", function (event) {
  isMouseDown = false;

  const autoOpen = localStorage.getItem('autoOpenPuantajTypes') === 'true';
  if ($(".gun.clicked").length > 0 && (event.ctrlKey || autoOpen)) {
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

//Ctrl + S tuşuna basıldığında kaydet butonunu çalıştırır
$(document).keydown(function (event) {
  // Ctrl + S kontrolü
  if ((event.ctrlKey || event.metaKey) && (event.keyCode === 83)) {
    event.preventDefault(); // Tarayıcı kaydet penceresini engelle
    puantaj_olustur();
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
  // Sayfalama desteği: Tüm satırları DataTable cache'inden çekiyoruz
  var table = $("#puantajTable").DataTable();
  var rows = table.rows().nodes();

  $(rows).each(function (rowIndex) {
    var row = $(this);
    var person_id = row.find("td[data-id]").first().attr("data-id");
    
    if (!person_id) return;

    row.find("td").each(function(tdIdx) {
        var td = $(this);
        if (!td.hasClass("gun") && !td.hasClass("noselect")) return;
        if (td.text().trim() === "---") return;

        var dateIdx = tdIdx - 4; 
        if (dateIdx < 0 || dateIdx >= headDates.length) return;

        var date = year + month + headDates[dateIdx];
        var puantajId = td.attr("data-id") || "";
        // Eğer hücrede tanımlı bir proje varsa onu kullan, yoksa global filtreyi kullan
        var cell_project_id = td.attr("data-project") || project_id;

        if (td.attr("data-change") === "true") {
            if (!jsonData[person_id]) jsonData[person_id] = {};
            jsonData[person_id][date] = {
                puantajId: puantajId,
                project_id: cell_project_id
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

      if (data.status == "success") {
        // Tüm sayfalardaki (görünür veya gizli) bayrakları sıfırla
        var table = $("#puantajTable").DataTable();
        var rows = table.rows().nodes();
        $(rows).find("td[data-change='true']").attr("data-change", "false");
      }

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

var lastValues = {};

function storeLastValues() {
  $("#projects, #year, #months, #job_groups, #team_id").each(function() {
    lastValues[this.id] = $(this).val();
  });
  lastValues['person_status'] = $("input[name='person_status']:checked").val();
}

function setCookie(name, value, days) {
  var expires = "";
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

$(document).ready(function () {
  // Mevcut değerleri kaydet
  storeLastValues();

  $("#projects, #year, #months, #job_groups, #team_id").on("change select2:select", function () {
    // Save current values to Cookies for PHP to read on next fresh load
    setCookie('p_projects', $("#projects").val(), 30);
    setCookie('p_year', $("#year").val(), 30);
    setCookie('p_months', $("#months").val(), 30);
    setCookie('p_job_groups', $("#job_groups").val(), 30);
    setCookie('p_team_id', $("#team_id").val(), 30);
    
    Route();
  });

  $("input[name='person_status']").on("change", function() {
    setCookie('p_person_status', $(this).val(), 30);
    Route();
  });
});

function Route() {
  // DataTable cache'indeki tüm satırlardan değişmiş veri var mı diye bak
  var table = $("#puantajTable").DataTable();
  var rows = table.rows().nodes();
  var hasUnsavedChanges = $(rows).find("td[data-change='true']").length > 0;

  if (hasUnsavedChanges) {
    Swal.fire({
      title: 'Kaydedilmemiş Değişiklikler!',
      text: "Yaptığınız değişiklikler kaydedilmedi. Farklı bir sayfaya/projeye geçerseniz bu verileriniz kaybolur. Devam etmek istediğinize emin misiniz?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Evet, Çık',
      cancelButtonText: 'Hayır, Sayfada Kal'
    }).then((result) => {
      if (result.isConfirmed) {
        // Kullanıcı devam etmek istedi. Bayrakları temizleyerek native uyarının çıkmasını engelle.
        $(rows).find("td[data-change='true']").attr("data-change", "false");
        $("#puantajInfoForm").submit();
      } else {
        // Kullanıcı vazgeçti. Dropdown'u eski haline çekmeliyiz ki kafa karışıklığı olmasın
        $("#projects, #year, #months, #job_groups, #team_id").off("change select2:select");
        $("input[name='person_status']").off("change");
        
        $("#projects").val(lastValues.projects).trigger("change");
        $("#year").val(lastValues.year).trigger("change");
        $("#months").val(lastValues.months).trigger("change");
        $("#job_groups").val(lastValues.job_groups).trigger("change");
        $("#team_id").val(lastValues.team_id).trigger("change");
        
        // Radio button'u eski haline getir
        $("input[name='person_status'][value='" + lastValues.person_status + "']").prop('checked', true);
        
        // Dinleyicileri geri yükle
        $("#projects, #year, #months, #job_groups, #team_id").on("change select2:select", function () {
          Route();
        });
        $("input[name='person_status']").on("change", function() {
          Route();
        });
      }
    });
  } else {
    $("#puantajInfoForm").submit();
  }
}

// Sekmeyi kapatma vb. durumlarda native uyarı hala geçerli koruma sağlar
$(window).on('beforeunload', function() {
    var table = $("#puantajTable").DataTable();
    var rows = table.rows().nodes();
    if ($(rows).find("td[data-change='true']").length > 0) {
        return "Sayfada kaydedilmemiş değişiklikleriniz var.";
    }
});

// Sütun göster/gizle (Bağımsız seçim)
$(document).on("change", ".column-toggle-check", function() {
    var columnClass = $(this).data("column");
    if ($(this).is(":checked")) {
        $("." + columnClass).show();
    } else {
        $("." + columnClass).hide();
    }

    // Seçilen sütunların durumunu localStorage'a kaydet
    var columnStates = {};
    $(".column-toggle-check").each(function() {
        columnStates[$(this).data("column")] = $(this).is(":checked");
    });
    localStorage.setItem("puantajColumnStates", JSON.stringify(columnStates));

    // DataTable yerleşimini yeniden hesapla
    $("#puantajTable").DataTable().columns.adjust().draw();
});

// Sayfa yüklendiğinde kaydedilmiş sütun görünürlük ayarlarını uygula
$(document).ready(function() {
    var storedStates = localStorage.getItem("puantajColumnStates");
    if (storedStates) {
        try {
            var columnStates = JSON.parse(storedStates);
            $(".column-toggle-check").each(function() {
                var columnClass = $(this).data("column");
                if (columnClass in columnStates) {
                    var isVisible = columnStates[columnClass];
                    $(this).prop("checked", isVisible);
                    if (isVisible) {
                        $("." + columnClass).show();
                    } else {
                        $("." + columnClass).hide();
                    }
                }
            });
            // DataTable yerleşimini yeniden hesapla
            setTimeout(function() {
                if ($.fn.DataTable.isDataTable('#puantajTable')) {
                    $('#puantajTable').DataTable().columns.adjust().draw();
                }
            }, 100);
        } catch (e) {
            console.error("Sütun görünürlük ayarları yüklenemedi:", e);
        }
    }
});

// Dropdown içindeki tıklamalarda menünün kapanmasını engelle
$(document).on("click", ".dropdown-menu-column-selector", function (e) {
    e.stopPropagation();
});

// Satır toplamlarını (Toplam Gün ve Toplam Fazla Mesai) dinamik olarak yeniden hesaplar
function calculateRowTotals(row) {
  let totalDays = 0;
  let totalOvertime = 0;

  row.find("td.gun").each(function() {
    let td = $(this);
    let id = td.attr("data-id");
    let text = td.text().trim();

    // Sadece işe başlama/ayrılma tarihleri dışındaki hücreleri geç (--- olanlar)
    if (text === "---") {
      return;
    }

    if (id && id !== "0" && id !== "") {
      if (typeof allPuantajTurleri !== 'undefined' && allPuantajTurleri[id]) {
        let type = allPuantajTurleri[id];
        if (type.Turu !== "Ücretsiz") {
          totalDays++;
        }
        if (type.Turu === "Fazla Çalışma") {
          let hours = parseFloat(type.EklenecekSaat) || 0;
          totalOvertime += hours;
        }
      }
    } else {
      // Eğer hücre boşsa/temizlendiyse ve Pazar günü ise varsayılan HT (53) 'Ücretsiz' olduğu için gün toplamına eklemiyoruz
    }
  });

  row.find(".td-toplam-gun").text(totalDays);
  let otText = totalOvertime > 0 ? totalOvertime.toFixed(1).replace('.0', '') : '0';
  row.find(".td-toplam-fazla-mesai").text(otText);
}
