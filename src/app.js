if ($(".datatable").length > 0) {
  var table = $(".datatable:not(#puantajTable)").DataTable({
    autoWidth: false,
    order: false,
    language: {
      url: "src/tr.json"
    },
    //dom: "Bfrtip",
    buttons: [
      {
        extend: "excelHtml5",
        className: "d-none",
        title: "Personel Listesi",
        messageTop: "Tarih: " + new Date().toLocaleDateString("tr-TR"),
        exportOptions: {
          columns: ":visible:not(.no-export)"
        }
      },
      {
        extend: "pdfHtml5",
        className: "d-none",
        title: "Personel Listesi",
        messageTop: "Tarih: " + new Date().toLocaleDateString("tr-TR"),
        orientation: "landscape",
        pageSize: "A4",
        exportOptions: {
          columns: ":visible:not(.no-export)"
        },
        customize: function (doc) {
          doc.styles.tableHeader.fillColor = '#206bc4';
          doc.styles.tableHeader.color = 'white';
          doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
        }
      }
    ],
    layout: {
      bottomStart: "pageLength",
      bottom2Start: "info",
      topStart: null,
      topEnd: null
    },
    initComplete: function (settings, json) {
      var api = this.api();
      var tableId = settings.sTableId;
      $("#" + tableId + " thead").append('<tr class="search-input-row"></tr>');

      api.columns().every(function () {
        let column = this;
        let title = column.header().textContent;

        //0. ve 1. kolonun index numarasına göre arama kutusu ekle
        //kolon başlığında checkbox varsa arama kutusu ekleme

        if (
          title != "İşlem" &&
          title != "Seç" &&
          $(column.header()).find('input[type="checkbox"]').length === 0
        ) {
          // Create input element
          let input = document.createElement("input");
          input.placeholder = title;
          input.classList.add("form-control");
          input.classList.add("form-control-sm");
          input.setAttribute("autocomplete", "off");

          // Append input element to the new row
          $("#" + tableId + " .search-input-row").append(
            $('<th class="search">').append(input)
          );

          // Event listener for user input
          $(input).on("keyup change", function () {
            if (column.search() !== this.value) {
              column.search(this.value).draw();
            }
          });
        } else {
          // Eğer "İşlem" sütunuysa, boş bir th ekleyin
          $("#" + tableId + " .search-input-row").append("<th></th>");
        }
      });
    }
  });
  //Tüm tablolar için excel dışa aktarım butonu
  $("#export_excel").on("click", function (e) {
    e.preventDefault();
    table.button(".buttons-excel").trigger();
  });

  $("#export_pdf").on("click", function (e) {
    e.preventDefault();
    table.button(".buttons-pdf").trigger();
  });

  //Personelin çalışma bilgileri tablosu için
  $("#export_excel_puantaj_info").on("click", function () {
    var table_puantaj_info = $("#puantaj_info_table").DataTable();
    table_puantaj_info.button(".buttons-excel").trigger();
  });

  //Puantaj tablosu için
  var puantaj_table = $("#puantajTable").DataTable({
    ordering: false,

    layout: {
      bottomStart: "pageLength",
      bottom2Start: "info",
      topStart: null,
      topEnd: "search"
    },
    language: {
      url: "src/tr.json"
    },
    buttons: [
      {
        extend: "excelHtml5",
        className: "d-none", // Butonu gizliyoruz
        exportOptions: {
          columns: ":visible:not(.no-export)" // .no-export sınıfına sahip sütunları dışa aktarma
        }
      }
    ],

    initComplete: function (settings, json) {
      var api = this.api();
      var tableId = settings.sTableId;
      $("#" + tableId + " thead").append('<tr class="search-input-row"></tr>');

      api.columns().every(function () {
        let column = this;
        let title = api.column(0).header().textContent;
        //0. kolonun title bilgisini al

        //0. ve 1. kolonun index numarasına göre arama kutusu ekle
        if (column.index() == 0 || column.index() == 1) {
          // Create input element
          let input = document.createElement("input");
          // Set placeholder based on column index
          input.placeholder = column.index() === 0 ? "Adı Soyadı" : "Unvanı";
          input.classList.add("form-control");
          input.classList.add("form-control-sm");
          input.setAttribute("autocomplete", "off");

          // Append input element to the existing row
          $(
            "#" + tableId + " thead tr:eq(1) th:eq(" + column.index() + ")"
          ).append(input);

          // Event listener for user input
          $(input).on("keyup change", function () {
            if (column.search() !== this.value) {
              column.search(this.value).draw();
            }
          });
        }
      });
    }
  });

  $("#export_excel_puantaj").on("click", function () {
    puantaj_table.button(".buttons-excel").trigger();
  });
}

if ($(".select2").length > 0) {
  $(".select2").select2();

  // $("#products").select2({
  //   dropdownParent: $(".modal")
  // });
  // $(".modal .select2").select2({
  //   dropdownParent: $(".modal")
  // });
  // $("#amount_money").select2({
  //   dropdownParent: $(".modal")
  // });
  // // $("#firm_cases").select2({
  // //   dropdownParent: $(".modal")
  // // });
  // $(
  //   "#wage_cut_month, #wage_cut_year,#income_month, #income_year, #payment_month, #payment_year"
  // ).select2({
  //   dropdownParent: $(".modal")
  // });

  //Modal'daki select2'lerin dropdown parent'ını modal yap
  $(".modal .select2").each(function () {
    $(this).select2({ dropdownParent: $(this).parent() });
  });
}
$(document).ready(function () {
  if ($(".summernote").length > 0) {
    var summernoteHeight = $(window).height() * 0.24; // Set height to 30% of window height
    $(".summernote").summernote({
      height: summernoteHeight,
      fontNames: [
        "inter",
        "Arial",
        "Arial Black",
        "Comic Sans MS",
        "Courier New"
      ],
      addDefaultFonts: "inter",
      callbacks: {
        onInit: function () {
          $(".summernote").summernote("height", summernoteHeight);
          $(".summernote").summernote("fontName", "inter");
        }
      }
    });
  }
});

if ($(".flatpickr").length > 0) {
  $(".flatpickr").flatpickr({
    dateFormat: "d.m.Y",
    locale: "tr" // locale for this instance only
  });
}

function formatNumber(num) {
  return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
}

$(document).on("click", ".route-link", function () {
  var page = $(this).data("page");
  var link = "index.php?p=" + page;

  window.location = link;
});
if ($(".select2").length > 0) {
  $(".select2.islem").select2({
    tags: true
  });
}

function dtSearchInput(tableId, column, value) {}

//Geri dönüş yapmadan kayıt silme işlemi
function deleteRecord(
  button = this,
  action = null,
  confirmMessage = "Kayıt silinecektir!",
  url = "/api/ajax.php"
) {
  // Butonun bulunduğu satırın referansını al
  var row = $(button).closest("tr");

  //Tablo adı butonun içinde bulunduğu tablo
  var tableName = $(button).closest("table")[0].id;
  var table = $("#" + tableName).DataTable();

  var tableRow = table.row(row);

  var id = $(button).data("id");

  //formData objesi oluştur
  const formData = new FormData();
  //formData objesine action ve id elemanlarını ekle
  formData.append("action", action);
  formData.append("id", id);
  // formData.append("csrf_token", csrf_token);

  // console.log(url);

  AlertConfirm(confirmMessage).then((result) => {
    fetch(url, {
      method: "POST",
      body: formData
    })
      //Gelen yanıtı json'a çevir
      .then((response) => response.json())

      //Sonuc olumlu ise success toast mesajı göster
      .then((data) => {
        // console.log(data);

        if (data.status == "success") {
          title = "Başarılı!";
          icon = "success";
        } else {
          title = "Hata!";
          icon = "error";
        }
        Swal.fire({
          title: title,
          html: data.message,
          icon: icon
        }).then((result) => {
          if (result.isConfirmed) {
            if (data.status == "success") tableRow.remove().draw(false);
            return data;
          }
        });
        // createToast("success", data.message);
      })

      //Sonuc olumsuz ise error toast mesajı göster
      .catch((error) => alert("Error deleting : " + error));
  });
}

//Geri dönüş yaparak kayıt silme işlemi
async function deleteRecordByReturn(
  button,
  action = null,
  confirmMessage = "Kayıt silinecektir!",
  url = "/api/ajax.php"
) {
  // Butonun bulunduğu satırın referansını al
  var row = $(button).closest("tr");

  //Tablo adı butonun içinde bulunduğu tablo
  var tableName = $(button).closest("table")[0].id;
  var table = $("#" + tableName).DataTable();

  var tableRow = table.row(row);

  var id = $(button).data("id");

  //formData objesi oluştur
  const formData = new FormData();
  //formData objesine action ve id elemanlarını ekle
  formData.append("action", action);
  formData.append("id", id);

  const result = await AlertConfirm(confirmMessage);
  if (result) {
    try {
      const response = await fetch(url, {
        method: "POST",
        body: formData
      });
      const data = await response.json();

      let title, icon;
      if (data.status == "success") {
        title = "Başarılı!";
        icon = "success";
      } else {
        title = "Hata!";
        icon = "error";
      }

      await Swal.fire({
        title: title,
        text: data.message,
        icon: icon
      });

      if (data.status == "success") {
        tableRow.remove().draw(false);
      }

      return data;
    } catch (error) {
      console.error("Error deleting:", error);
      return { status: "error", message: "Bir hata oluştu." };
    }
  }
}

function AlertConfirm(confirmMessage = "Emin misiniz?") {
  return new Promise((resolve, reject) => {
    Swal.fire({
      title: "Emin misiniz?",
      html: confirmMessage,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Evet, Sil!"
    }).then((result) => {
      if (result.isConfirmed) {
        resolve(true); // Kullanıcı onayladı, işlemi devam ettir
      } else {
        reject(false); // Kullanıcı onaylamadı, işlemi durdur
      }
    });
  });
}

$(document).on("change", "#myFirm", function () {
  var page = new URLSearchParams(window.location.search).get("p");
  window.location = "set-session.php?p=" + page + "&firm_id=" + $(this).val();
});

// function fadeOut(element, duration) {
//   var op = 1; // Opaklık başlangıç değeri
//   var interval = 50; // Milisaniye cinsinden aralık
//   var delta = interval / duration; // Her adımda azaltılacak opaklık miktarı

//   function reduceOpacity() {
//     op -= delta;
//     if (op <= 0) {
//       op = 0;
//       element.style.display = "none"; // Elementi gizle
//       clearInterval(fading); // Animasyonu durdur
//     }
//     element.style.opacity = op;
//   }

//   var fading = setInterval(reduceOpacity, interval);
// }

//İl seçildiğinde ilçeleri getir
function getTowns(cityId, targetElement) {
  var formData = new FormData();
  formData.append("city_id", cityId);
  formData.append("action", "getTowns");

  fetch("/api/il-ilce.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      let towns = data.towns;
      $(targetElement).html(towns);
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

//Personeli kaydedip kaydetmediğimize bakarız
function checkPersonId(id) {
  if (id == 0) {
    swal.fire({
      title: "Hata",
      icon: "warning",
      text: "Öncelikle personeli kaydetmeniz gerekir!"
    });
    return false;
  }
  return true;
}
//Personeli kaydedip kaydetmediğimize bakarız
function checkId(id, item) {
  if (id == 0) {
    swal.fire({
      title: "Hata",
      icon: "warning",
      text: "Öncelikle " + item + " kaydetmeniz gerekir!"
    });
    return false;
  }
  return true;
}

// Sayfanın herhangi bir yerine tıklandığında fab menüyü kapat
document.addEventListener("click", function (event) {
  if (event.target.closest(".fab-menu") === null) {
    const fabOptions = document.getElementById("fab-options");
    const mainIcon = document.getElementById("main-icon");
    const closeIcon = document.getElementById("close-icon");

    if (fabOptions.style.display === "block") {
      fabOptions.classList.remove("show");
      setTimeout(() => {
        fabOptions.style.display = "none";
      }, 300);
      mainIcon.style.opacity = 1;
      closeIcon.style.opacity = 0;
    }
  }
});

function toggleFabMenu() {
  const fabOptions = document.getElementById("fab-options");
  const mainIcon = document.getElementById("main-icon");
  const closeIcon = document.getElementById("close-icon");

  if (fabOptions.style.display === "none" || fabOptions.style.display === "") {
    fabOptions.style.display = "block";
    setTimeout(() => {
      fabOptions.classList.add("show");
    }, 10);
    mainIcon.style.opacity = 0;
    closeIcon.style.opacity = 1;
  } else {
    fabOptions.classList.remove("show");
    setTimeout(() => {
      fabOptions.style.display = "none";
    }, 300);
    mainIcon.style.opacity = 1;
    closeIcon.style.opacity = 0;
  }
}

function goWhatsApp() {
  const phoneNumber = "905079432723";
  const message = encodeURIComponent("Merhaba, Teknik desteğe ihtiyacım var");
  const url = `https://wa.me/send?phone=${phoneNumber}&text=${message}`;
  window.open(url, "_blank");
}

function previewImage(event) {
  var reader = new FileReader();
  reader.onload = function () {
    var output = document.querySelector(".brand-img img");
    output.src = reader.result;
  };
  reader.readAsDataURL(event.target.files[0]);
}

//para birimi mask
if ($(".money").length > 0) {
  //1.234,52 şeklinden regex yaz
  //$(".money").inputmask("9-a{1,3}9{1,3}"); //mask with dynamic syntax

  $(".money").inputmask("decimal", {
    radixPoint: ",",
    groupSeparator: ".",
    digits: 2,
    autoGroup: true,
    rightAlign: false,
    removeMaskOnSubmit: true
  });

  $(document).on("focus", ".money", function () {
    $(this).inputmask("decimal", {
      radixPoint: ",",
      groupSeparator: ".",
      digits: 2,
      autoGroup: true,
      rightAlign: false,
      removeMaskOnSubmit: true
    });
  });
  //Para birimi olan alanlarda virgülü noktaya çevir
  // $('.money').on('keyup', function () {
  //   var value = $(this).val();
  //   var value = value.replace(/,/g, '.');
  //   $(this).val(value);
  // });
}

//Jquery validate ile yapılan doğrulamalarda para birimi formatı için
function addCustomValidationMethods() {
  $.validator.addMethod(
    "validNumber",
    function (value, element) {
      return this.optional(element) || (/^[0-9.,]+$/.test(value) && parseFloat(value.replace(",", ".")) > 0);
    },
    "Lütfen geçerli bir sayı girin ve 0'dan büyük bir değer girin"
  );
}

//Jquery validate ile yapılan doğrulamalarda 0 olan değeri kabul etmemek için
function addCustomValidationValidValue() {
  $.validator.addMethod(
    "validValue",
    function (value, element) {
      return (
        this.optional(element) || parseFloat(value.replace(",", ".")) !== 0
      );
    },
    "Lütfen geçerli bir değer girin"
  );
}


    // let ec = new EventCalendar(document.getElementById('ec'), {
    //     view: 'dayGridMonth',
    //     selectable:true,
    //     events: [
    //         event = {
    //             title: 'Yapılacak İş',
    //             start: '2025-01-03',
    //             end: '2025-01-07',
    //             color: 'red'
    //         },
    //     ]
    // });

