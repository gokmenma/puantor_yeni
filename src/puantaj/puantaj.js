var activePuantajType = null;
var isShortcutActive = true;

function updateActiveTypeUI() {
  if (activePuantajType) {
    $("#selected-type-code")
      .text(activePuantajType.PuantajKod)
      .css({
        "background-color": activePuantajType.ArkaPlanRengi,
        "color": activePuantajType.FontRengi
      })
      .attr("title", activePuantajType.PuantajAdi + " (" + activePuantajType.Turu + ")");
      
    if (isShortcutActive) {
      $("#selected-type-toggle").removeClass("inactive");
      $("#selected-type-status-dot")
        .css({
          "background-color": "#2fb344",
          "box-shadow": "0 0 0 2px rgba(47, 179, 68, 0.2)"
        })
        .attr("title", "Kısayol Aktif (Devredışı bırakmak için tıklayın)");
    } else {
      $("#selected-type-toggle").addClass("inactive");
      $("#selected-type-status-dot")
        .css({
          "background-color": "#aeaeae",
          "box-shadow": "none"
        })
        .attr("title", "Kısayol Devredışı (Etkinleştirmek için tıklayın)");
    }
    
    $("#selected-type-container").removeClass("d-none").addClass("d-flex");
  } else {
    $("#selected-type-container").removeClass("d-flex").addClass("d-none");
  }
}

function applyTypeToClickedCells(typeInfo) {
  const clickedCells = $("table td.clicked");
  if (clickedCells.length === 0) return;

  const current_projects = $("#projects").val() || [];
  const rowsToRecalculate = new Set();

  clickedCells.each(function () {
    let cell = $(this);
    let parentTr = cell.closest("tr");
    let rowDefaultProject = parentTr.attr("data-default-project") || "0";

    // Determine target project ID based on selection count
    let cellProj = "0";
    if (current_projects.length === 1) {
      cellProj = current_projects[0];
    } else {
      cellProj = "0";
    }

    // Resolve project name for tooltip
    let cellProjName = "Proje Yok";
    if (cellProj && cellProj !== "0" && cellProj !== "") {
      let opt = $("#projects option[value='" + cellProj + "']");
      if (opt.length > 0) {
        cellProjName = opt.text().trim();
      }
    }

    cell.text(typeInfo.PuantajKod);
    cell.css("color", typeInfo.FontRengi);
    cell.attr("data-id", typeInfo.id);
    cell.attr("data-change", "true");
    cell.attr("data-tooltip", cellProjName);
    cell.attr("title", cellProjName);
    cell.attr("data-project", cellProj);
    cell.css("background-color", typeInfo.ArkaPlanRengi);

    if (parentTr.length > 0) {
      rowsToRecalculate.add(parentTr[0]);
    }
  });

  clickedCells.removeClass("clicked");

  rowsToRecalculate.forEach(function(tr) {
    calculateRowTotals($(tr));
  });
}

//********************************************** */
$(document).on("click", "#modal-default .nav-item", function () {
  const avatar = $(this).find(".avatar");
  const avatarDataid = avatar.attr("data-id");

  if (typeof allPuantajTurleri !== 'undefined' && allPuantajTurleri[avatarDataid]) {
    const setAsShortcut = $("#modal-set-as-shortcut").is(":checked");
    if (setAsShortcut) {
      activePuantajType = allPuantajTurleri[avatarDataid];
      isShortcutActive = true;
      localStorage.setItem('activePuantajTypeId', avatarDataid);
      localStorage.setItem('isShortcutActive', 'true');
      updateActiveTypeUI();
    }
    applyTypeToClickedCells(allPuantajTurleri[avatarDataid]);
  }
  
  $("#modal-default").modal("hide");
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

$(document).on("click", ".head-date, .gunadi", function () {
  var index = $(this).index();
  
  // DataTable nesnesini alıp sadece FILTRELENMIS satırları geziyoruz
  var table = $("#puantajTable").DataTable();
  var rows = table.rows({ search: 'applied' }).nodes(); 
  
  if (activePuantajType && isShortcutActive) {
    $(rows).each(function () {
      var td = $(this).find("td").eq(index);
      if (td.text().trim() != "---") {
        td.addClass("clicked");
      }
    });
    applyTypeToClickedCells(activePuantajType);
  } else {
    $(rows).each(function () {
      var td = $(this).find("td").eq(index);
      if (td.text().trim() != "---") {
        td.toggleClass("clicked");
      }
    });
  }
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
      cell.removeAttr("title");
      // Keep the original project ID so the backend knows which record to delete.
      // If it doesn't have one, we default it to "0".
      if (!cell.attr("data-project")) {
        cell.attr("data-project", "0");
      }
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
  if ($(".gun.clicked").length > 0) {
    if (activePuantajType && isShortcutActive) {
      applyTypeToClickedCells(activePuantajType);
    } else if (event.ctrlKey || autoOpen) {
      $("#modal-default").modal("show");
    }
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
  var project_ids = $("#projects").val() || [];
  var project_id = project_ids.length === 1 ? project_ids[0] : "0";
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

function bindFilters() {
  // Bind standard filters (excluding projects)
  $("#year, #months, #job_groups, #team_id").on("change select2:select select2:unselect", function () {
    setCookie('p_year', $("#year").val(), 30);
    setCookie('p_months', $("#months").val(), 30);
    setCookie('p_job_groups', $("#job_groups").val(), 30);
    setCookie('p_team_id', $("#team_id").val(), 30);
    Route();
  });

  // Bind projects filter to save cookie on change, but do NOT reload immediately
  $("#projects").on("change select2:select select2:unselect", function () {
    setCookie('p_projects', $(this).val(), 30);
    checkProjectsWarning();
  });

  // Bind projects close event to trigger Route if values changed
  $("#projects").on("select2:close", function () {
    const currentValue = $(this).val() || [];
    const lastValue = lastValues.projects || [];
    
    // Order-independent array comparison
    const isSame = currentValue.length === lastValue.length && 
                   [...currentValue].sort().every((val, idx) => val === [...lastValue].sort()[idx]);
                   
    if (!isSame) {
      Route();
    }
  });

  $("input[name='person_status']").on("change", function() {
    setCookie('p_person_status', $(this).val(), 30);
    Route();
  });
}

function unbindFilters() {
  $("#projects, #year, #months, #job_groups, #team_id").off("change select2:select select2:unselect select2:close");
  $("input[name='person_status']").off("change");
}

$(document).ready(function () {
  // Mevcut değerleri kaydet
  storeLastValues();

  // Filtreleri bağla
  bindFilters();

  // Proje uyarı barını ilk yüklemede de kontrol et
  checkProjectsWarning();

  // Ayarların yüklenmesi (only active project)
  const onlyActiveSetting = localStorage.getItem('onlyActiveProjectDays') === 'true';
  $('#setting-only-active-project').prop('checked', onlyActiveSetting);

  $('#setting-only-active-project').on('change', function() {
    const isChecked = $(this).is(':checked');
    localStorage.setItem('onlyActiveProjectDays', isChecked);
    setCookie('p_only_active_project', isChecked ? '1' : '0', 30);
    Route();
  });

  // Restore active type
  const storedActiveTypeId = localStorage.getItem('activePuantajTypeId');
  if (storedActiveTypeId && typeof allPuantajTurleri !== 'undefined' && allPuantajTurleri[storedActiveTypeId]) {
    activePuantajType = allPuantajTurleri[storedActiveTypeId];
  }

  // Restore shortcut active state
  const storedIsShortcutActive = localStorage.getItem('isShortcutActive');
  if (storedIsShortcutActive !== null) {
    isShortcutActive = (storedIsShortcutActive === 'true');
  } else {
    isShortcutActive = true;
  }

  // Restore checkbox state
  const storedSetAsShortcut = localStorage.getItem('setAsShortcut');
  if (storedSetAsShortcut !== null) {
    $("#modal-set-as-shortcut").prop('checked', storedSetAsShortcut === 'true');
  } else {
    $("#modal-set-as-shortcut").prop('checked', true);
  }

  // Bind modal checkbox change event
  $(document).on("change", "#modal-set-as-shortcut", function() {
    localStorage.setItem('setAsShortcut', $(this).is(':checked') ? 'true' : 'false');
  });

  // Update UI if we have an active type
  if (activePuantajType) {
    updateActiveTypeUI();
  }

  // Bind selected type events
  $(document).on("click", "#clear-selected-type", function(e) {
    e.preventDefault();
    e.stopPropagation();
    activePuantajType = null;
    localStorage.removeItem('activePuantajTypeId');
    updateActiveTypeUI();
  });

  $(document).on("click", "#btn-clear-projects", function(e) {
    e.preventDefault();
    $("#projects").val([]).trigger("change");
    Route();
  });

  $(document).on("click", "#selected-type-toggle", function(e) {
    if ($(e.target).closest("#clear-selected-type").length > 0) {
      return;
    }
    e.preventDefault();
    if (activePuantajType) {
      isShortcutActive = !isShortcutActive;
      localStorage.setItem('isShortcutActive', isShortcutActive ? 'true' : 'false');
      updateActiveTypeUI();

      if (isShortcutActive && $("table td.clicked").length > 0) {
        applyTypeToClickedCells(activePuantajType);
      }
    }
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
        loadPuantajTable();
      } else {
        // Kullanıcı vazgeçti. Dropdown'u eski haline çekmeliyiz ki kafa karışıklığı olmasın
        unbindFilters();
        
        $("#projects").val(lastValues.projects).trigger("change");
        $("#year").val(lastValues.year).trigger("change");
        $("#months").val(lastValues.months).trigger("change");
        $("#job_groups").val(lastValues.job_groups).trigger("change");
        $("#team_id").val(lastValues.team_id).trigger("change");
        
        // Radio button'u eski haline getir
        $("input[name='person_status'][value='" + lastValues.person_status + "']").prop('checked', true);
        
        // Dinleyicileri geri yükle
        bindFilters();
      }
    });
  } else {
    loadPuantajTable();
  }
}

function loadPuantajTable() {
  // Ensure loading bar element exists
  if ($("#puantaj-top-loading-bar").length === 0) {
    $("body").append('<div id="puantaj-top-loading-bar"></div>');
  }

  // Show and reset progress bar
  var $loadingBar = $("#puantaj-top-loading-bar");
  $loadingBar.css({ "width": "0%", "opacity": "1" });
  
  var width = 10;
  $loadingBar.css("width", width + "%");

  // Animate progress bar progressively
  var progressInterval = setInterval(function() {
    if (width < 85) {
      width += Math.floor(Math.random() * 6) + 3;
      $loadingBar.css("width", width + "%");
    }
  }, 180);

  // Serialize filter values
  var formData = $("#puantajInfoForm").serialize();

  // Send AJAX request
  $.ajax({
    url: window.location.href,
    type: 'POST',
    data: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    },
    success: function (response) {
      // Update URL query parameters in the address bar without page reload
      var queryParams = new URLSearchParams(formData);
      queryParams.set('p', 'puantaj/list');
      window.history.pushState({}, '', 'index.php?' + queryParams.toString());

      // 1. Destroy existing DataTable
      if ($.fn.DataTable.isDataTable('#puantajTable')) {
        $('#puantajTable').DataTable().destroy();
      }

      // 2. Parse response and swap elements
      var $response = $(response);
      
      // Swap the main card body contents (table and stats)
      var newCard = $response.find("#puantajTable").closest('.card').html();
      if (newCard) {
        $("#puantajTable").closest('.card').html(newCard);
      }

      // Swap statistics modal
      var newStatsModal = $response.find("#modal-statistics").html();
      if (newStatsModal) {
        $("#modal-statistics").html(newStatsModal);
      }

      // Re-initialize DataTable on the new table
      if (typeof window.initializePuantajDataTable === 'function') {
        window.initializePuantajDataTable();
      }

      // Apply column visibility states
      if (typeof window.applyColumnVisibility === 'function') {
        window.applyColumnVisibility();
      }

      // Re-bind shortcut badge UI
      updateActiveTypeUI();

      // Check projects warning bar
      checkProjectsWarning();

      // Save the last values for unsaved changes detection
      storeLastValues();

      // Complete progress bar
      clearInterval(progressInterval);
      $loadingBar.css("width", "100%");
      setTimeout(function() {
        $loadingBar.css("opacity", "0");
        setTimeout(function() {
          $loadingBar.css("width", "0%");
        }, 300);
      }, 300);
    },
    error: function (xhr, status, error) {
      console.error("Puantaj yüklenirken hata oluştu:", error);
      Swal.fire({
        title: 'Hata!',
        text: 'Veriler yüklenirken bir hata oluştu.',
        icon: 'error'
      });
      
      // Fail progress bar
      clearInterval(progressInterval);
      $loadingBar.css("width", "100%");
      setTimeout(function() {
        $loadingBar.css("opacity", "0");
        setTimeout(function() {
          $loadingBar.css("width", "0%");
        }, 300);
      }, 300);
    }
  });
}

// Sekmeyi kapatma vb. durumlarda native uyarı hala geçerli koruma sağlar
$(window).on('beforeunload', function() {
    var table = $("#puantajTable").DataTable();
    var rows = table.rows().nodes();
    if ($(rows).find("td[data-change='true']").length > 0) {
        return "Sayfada kaydedilmemiş değişiklikleriniz var.";
    }
});

// Sütun göster/gizle fonksiyonu
window.applyColumnVisibility = function() {
    $(".column-toggle-check").each(function() {
        var columnClass = $(this).data("column");
        if ($(this).is(":checked")) {
            $("." + columnClass).show();
        } else {
            $("." + columnClass).hide();
        }
    });
};

// Sütun göster/gizle (Bağımsız seçim)
$(document).on("change", ".column-toggle-check", function() {
    applyColumnVisibility();

    // Seçilen sütunların durumunu localStorage'a kaydet
    var columnStates = {};
    $(".column-toggle-check").each(function() {
        columnStates[$(this).data("column")] = $(this).is(":checked");
    });
    localStorage.setItem("puantajColumnStates", JSON.stringify(columnStates));

    // DataTable yerleşimini yeniden hesapla
    if ($.fn.DataTable.isDataTable('#puantajTable')) {
        $("#puantajTable").DataTable().columns.adjust().draw();
    }
});

// Sayfa yüklendiğinde kaydedilmiş sütun görünürlük ayarlarını uygula
$(document).ready(function() {
    // Tablo her çizildiğinde (sayfalama, sıralama vb.) sütun görünürlük durumlarını uygula
    $('#puantajTable').on('draw.dt', function() {
        applyColumnVisibility();
    });

    var storedStates = localStorage.getItem("puantajColumnStates");
    if (storedStates) {
        try {
            var columnStates = JSON.parse(storedStates);
            $(".column-toggle-check").each(function() {
                var columnClass = $(this).data("column");
                if (columnClass in columnStates) {
                    var isVisible = columnStates[columnClass];
                    $(this).prop("checked", isVisible);
                }
            });
            applyColumnVisibility();
            
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
$(document).on("click", ".dropdown-menu-column-selector, .dropdown-menu-settings", function (e) {
    e.stopPropagation();
});

// Satır toplamlarını (Toplam Gün ve Toplam Fazla Mesai) dinamik olarak yeniden hesaplar
function calculateRowTotals(row) {
  let totalDays = 0;
  let totalOvertime = 0;

  // Let's get the settings for only active project calculation
  const onlyActiveProject = localStorage.getItem('onlyActiveProjectDays') === 'true';
  const activeProjects = $("#projects").val() || []; // array of selected projects (as strings)

  row.find("td.gun").each(function() {
    let td = $(this);
    let id = td.attr("data-id");
    let text = td.text().trim();
    let cellProj = td.attr("data-project") || "0";

    // Sadece işe başlama/ayrılma tarihleri dışındaki hücreleri geç (--- olanlar)
    if (text === "---") {
      return;
    }

    if (id && id !== "0" && id !== "") {
      let shouldCount = true;
      if (onlyActiveProject && activeProjects.length > 0) {
        if (!activeProjects.includes(cellProj)) {
          shouldCount = false;
        }
      }

      if (shouldCount) {
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
      }
    } else {
      // Eğer hücre boşsa/temizlendiyse ve Pazar günü ise varsayılan HT (53) 'Ücretsiz' olduğu için gün toplamına eklemiyoruz
    }
  });

  row.find(".td-toplam-gun").text(totalDays);
  let otText = totalOvertime > 0 ? totalOvertime.toFixed(1).replace('.0', '') : '0';
  row.find(".td-toplam-fazla-mesai").text(otText);
}

// Excel download preview modal functionality
function generateExcelPreview() {
  var viewFormat = $('input[name="excel_view_format"]:checked').val() || 'code';
  window.excelExportType = viewFormat;
  
  var previewContainer = $("#excel-preview-container");
  previewContainer.empty();
  
  var previewTable = $('<table class="table table-bordered m-0"></table>');
  
  // Build header
  var thead = $('<thead></thead>');
  $('#puantajTable thead tr').each(function() {
    var headerRow = $('<tr></tr>');
    $(this).find('th:visible:not(.no-export)').each(function() {
      var th = $(this);
      var clonedTh = $('<th></th>');
      clonedTh.text(th.text().trim());
      
      // Copy background color, color and classes
      var bg = th.css('background-color');
      var color = th.css('color');
      if (bg) clonedTh.css('background-color', bg);
      if (color) clonedTh.css('color', color);
      
      // Copy classes except search or sorting ones
      var classes = th.attr('class');
      if (classes) {
        var cleanClasses = classes.replace(/\bsorting\S*/g, '').replace(/\bsearch\S*/g, '');
        clonedTh.addClass(cleanClasses);
      }
      
      headerRow.append(clonedTh);
    });
    thead.append(headerRow);
  });
  previewTable.append(thead);
  
  // Build body
  var tbody = $('<tbody></tbody>');
  var table = $("#puantajTable").DataTable();
  var rows = table.rows({ search: 'applied' }).nodes();
  
  $(rows).each(function() {
    var tr = $('<tr></tr>');
    $(this).find('td:visible:not(.no-export)').each(function() {
      var td = $(this);
      var clonedTd = $('<td></td>');
      
      // Copy alignment classes
      if (td.hasClass('text-start')) {
        clonedTd.addClass('text-start');
      } else if (td.hasClass('text-end')) {
        clonedTd.addClass('text-end');
      }
      
      if (td.hasClass('gun') || td.hasClass('noselect')) {
        var text = td.text().trim();
        if (text === "---") {
          clonedTd.text("---");
        } else {
          if (viewFormat === 'hour') {
            var id = td.attr('data-id');
            if (id && id !== '0' && id !== '') {
              var hr = window.getCellHour(id);
              clonedTd.text(hr !== "" ? hr : "");
            } else {
              clonedTd.text("");
            }
          } else {
            clonedTd.text(text);
          }
        }
      } else {
        clonedTd.text(td.text().trim());
      }
      
      // Copy background and text color for all cells (including names and totals)
      var bg = td.css('background-color');
      var color = td.css('color');
      if (bg) clonedTd.css('background-color', bg);
      if (color) clonedTd.css('color', color);
      
      tr.append(clonedTd);
    });
    tbody.append(tr);
  });
  
  previewTable.append(tbody);
  previewContainer.append(previewTable);
  
  // Update row count
  $('#excel-preview-row-count').text(rows.length + ' Satır');
}

$(document).ready(function() {
  // Override default Excel export button to open the preview modal instead
  $("#export_excel_puantaj").off("click").on("click", function (e) {
    e.preventDefault();
    // Generate preview
    generateExcelPreview();
    // Show modal
    $("#modal-excel-preview").modal("show");
  });
  
  // Listen for radio button change inside the modal
  $('input[name="excel_view_format"]').on('change', function() {
    generateExcelPreview();
  });
  
  // Listen for confirm button click
  $("#btn-confirm-excel-export").on("click", function() {
    var viewFormat = $('input[name="excel_view_format"]:checked').val() || 'code';
    window.excelExportType = viewFormat;
    
    // Hide modal
    $("#modal-excel-preview").modal("hide");
    
    // Trigger actual DataTables Excel export
    var table = $("#puantajTable").DataTable();
    table.button(".buttons-excel").trigger();
  });
});

function checkProjectsWarning() {
  const current_projects = $("#projects").val() || [];
  if (current_projects.length > 1) {
    $("#project-warning-bar").removeClass("d-none");
  } else {
    $("#project-warning-bar").addClass("d-none");
  }
}

