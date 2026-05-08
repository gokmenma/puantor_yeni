function addProject() {
  $("#offerForm").submit();
}

$(document).on("click", "#teklif_kaydet", function () {
  addProject();
});
// Özel 'atLeastOneFilled' doğrulama yöntemini tanımla
$.validator.addMethod(
  "atLeastOneFilled",
  function (value, element) {
    var allEmpty = true;
    $('input[name="urun_adi[]"]').each(function () {
      if ($(this).val().trim() !== "") {
        allEmpty = false;
      }
    });
    return !allEmpty; // Eğer tüm alanlar boş değilse true döndür
  },
  "En az bir ürün adı girilmelidir."
);

var validForm = $("#offerForm").validate({
  rules: {
    customers: {
      required: true,
    },
    offerNumber: {
      required: true,
      minlength: 3,
      maxlength: 50,
    },
    offerDate: {
      required: true,
      date: true,
    },
    "urun_adi[]": {
      required: true,
      atLeastOneFilled: true,
    },
  },
  messages: {
    offerNumber: {
      required: "Teklif numarası boş bırakılamaz",
      minlength: "Teklif numarası en az 3 karakter olmalıdır",
      maxlength: "Teklif numarası en fazla 50 karakter olmalıdır",
    },
    offerDate: {
      required: "Teklif tarihi boş bırakılamaz",
      Date: "Lütfen geçerli bir tarih giriniz",
    },
    "urun_adi[]": {
      required: "Ürün adı boş bırakılamaz",
    },
  },
  // Doğrulama hatası olduğunda odaklanılacak elemanı belirlemek için errorPlacement fonksiyonunu özelleştirebilirsiniz
  errorPlacement: function (error, element) {
    if (element.attr("name") == "urun_adi[]" || element.hasClass("select2")) {
      error.insertAfter(element.parent()); // Hata mesajını, input elementinin ebeveyn elementinin hemen sonrasına yerleştirir
    } else {
      error.insertAfter(element); // Diğer tüm durumlar için varsayılan davranış
    }
  },

  submitHandler: function (form) {
    saveOffer();
  },
});

function saveOffer() {
  var id = $("#offer_id").val();

  var form = $("#offerForm")[0];
  var formData = new FormData(form);
  formData.append("id", id);
  formData.append("action", "offerSave");

  fetch("api/offer.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      Swal.fire({
        title: "Başarılı!",
        text: data.message,
        icon: data.status,
        confirmButtonText: "Kapat",
      });
    });
}

$(document).on("click", "#add-invoice-item", function () {
  var $select2 = $(".select2").select2();
  $select2.each(function (i, item) {
    $(item).select2("destroy");
  });
  // Find the element with class 'invoice-row'
  const invoiceRow = $(".invoice-row").last();

  // Clone the invoiceRow element along with its children
  const clone = invoiceRow.clone(true);

  // Append the cloned element to the parent of the original element
  invoiceRow.parent().append(clone);
  clone.find("input").val("");

  let order_number = 1;
  $("#invoice-item-list .invoice-row").each(function () {
    var selectUnits = $(this).find("[id*='urun_birim']").select2();
    selectUnits.attr("id", `urun_birim${order_number}`);

    var select2Id = $(this).find("[id*='satis_para_birimi']").select2();
    select2Id.attr("id", `satis_para_birimi${order_number}`);

    var selectAlisParaBirimi = $(this)
      .find("[id*='alis_para_birimi']")
      .select2();
    selectAlisParaBirimi.attr("id", `alis_para_birimi${order_number}`);
    order_number++;
  });

  $(".select2").select2();
  $("#products").select2({
    dropdownParent: $(".modal"),
  });

  order_number = 1;
  $(".invoice-row").each(function () {
    $(this).find(".order-number").text(order_number);
    order_number++;
  });
});

$(document).on("click", ".remove-item", function () {
  let orderElement = $(".order-number");
  if (orderElement.text() > 1) {
    $(this).closest(".invoice-row").remove();
  }

  let order_number = 1;
  $(".invoice-row").each(function () {
    $(this).find(".order-number").text(order_number);
    order_number++;
  });
  calculateTotal();
});
$(".satis_fiyati").on("input", function () {
  calculateTotal();
});
$(".quantity").on("input", function () {
  calculateTotal();
});
$("#kdv_orani").on("change", function () {
  calculateTotal();
});

function calculateTotal() {
  let total = 0;
  $(".invoice-row").each(function () {
    let quantity = $(this).find(".quantity").val();
    let price = parseFloat($(this).find(".satis_fiyati").val());
    const totalRow = $(this).find(".total-row");

    if (isNaN(quantity)) {
      quantity = 0;
    }

    if (isNaN(price)) {
      price = 0;
    }

    const totalValue = quantity * price;

    totalRow.val(totalValue);
    total += totalValue;
    // $(".total").val(parseFloat(totalValue));
  });

  // total değişkenini string'e çevir ve başındaki sıfırları kaldır
  var formattedTotal = formatNumber(total).toString().replace(/^0+/, "");

  // Düzeltilmiş total değerini ve "TL" birimini HTML elemanına ata
  $("#alt_toplam").text(formattedTotal + " TL");

  //KDV Hesaplama alanı
  let kdv_orani = $("#kdv_orani").val();
  let kdv_tutari = (total * kdv_orani) / 100;
  $("#kdv_tutari").text(formatNumber(kdv_tutari) + " TL");
  //KDV Hesaplama alanı

  let genel_toplam = total + kdv_tutari;
  $("#genel_toplam").text(formatNumber(genel_toplam) + " TL");
  $("#genel_toplam_input").val(genel_toplam);
}

let currentRow; // Seçilen satırı saklamak için global değişken

$(document).on("click", ".urun_sec_icon", function () {
  // 'urun_sec_icon' butonuna tıklandığında, o butonun bulunduğu satırı veya alanı 'currentRow' değişkeninde sakla
  currentRow = $(this).closest(".invoice-row"); // Veya '.row' yerine tıklanan butonun bulunduğu satırı temsil eden sınıfı kullanın.
});

$(document).on("click", "#urun_sec", function (e) {
  const id = $("#products").val();
  e.preventDefault();
  var formData = new FormData();
  formData.append("id", id);
  formData.append("action", "getProductInfo");

  fetch("api/products.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      currentRow.find("#urun_adi").val(data.urun_adi);
      currentRow.find(".alis_fiyati").val(data.AlisFiyati);
      currentRow.find(".satis_fiyati").val(data.SatisFiyati);
      calculateTotal();
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});
