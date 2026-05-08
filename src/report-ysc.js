$(document).on("click", "#ysc_rapor_kaydet", function () {
  var form = $("#yscForm");
  form.validate({
    rules: {
      report_number: {
        required: true,
      },
      customers: {
        required: true,
      },
    },
    messages: {
      report_number: {
        required: "Rapor numarası zorunludur",
      },
      customers: {
        required: "Müşteri seçimi zorunludur",
      },
    },
    ignore: ".hidden",
    // Doğrulama hatası olduğunda odaklanılacak elemanı belirlemek için errorPlacement fonksiyonunu özelleştirebilirsiniz
    errorPlacement: function (error, element) {
      if (element.hasClass("select2")) {
        // select2 konteynerini bul
        var container = element.next(".select2-container");
        // Hata mesajını, select2 konteynerinin sonuna ekler
        error.insertAfter(container);
      } else {
        // Diğer tüm durumlar için varsayılan davranış
        error.insertAfter(element);
      }
    },
  });

  if (!form.valid()) {
    swal.fire({
      icon: "error",
      title: "Hata",
      text: "Sekmelerdeki tüm alanları eksiksiz doldurunuz",
    });
    return;
  }
  var formData = new FormData(form[0]);
  formData.append("action", "save_ysc_report");

  for (data of formData.entries()) {
    console.log(data);
  }
  fetch("api/reports/ysc.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        Swal.fire({
          icon: "success",
          title: "Başarılı",
          text: data.message,
        }).then((result) => {
          window.location = "index.php?p=reports/ysc&id=" + data.lastid;
        });
        // $("#report_id").val(data.lastid);
      } else {
        Swal.fire({
          icon: "error",
          title: "Hata",
          text: data.message,
        });
      }
      console.log(data);
    });
});

$(document).on("click", ".urun_sil", function () {
  let row = $("#yscTable .product-row").length;
  if (row == 1) {
    swal.fire({
      icon: "warning",
      title: "Uyarı!",
      text: "En az bir ürün olmalıdır",
    });
    return;
  }

  var id = $(this).data("id");
  var formData = new FormData();
  formData.append("id", id);
  formData.append("action", "delete_ysc_product");

  fetch("api/reports/ysc.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status == "success") {
        Swal.fire({
          icon: "success",
          title: "Başarılı",
          text: data.message,
        }).then((result) => {
          $(this).closest("tr").remove();
          $("#yscTable .product-row").each(function () {
            $(this)
              .find(".satir_no")
              .val($(this).index() + 1);
          });
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Hata",
          text: data.message,
        });
      }
    });
});

$(document).on("click", "#add-product-row", function () {
  var $select2 = $(".select2").select2();
  $select2.each(function (i, item) {
    $(item).select2("destroy");
  });
  addyscRow();

  $(".select2").select2();
  $(".select2.islem").select2({
    tags: true,
  });
});

$(document).on("click", "#add-product-multi-row", function () {
  var $select2 = $(".select2").select2();
  $select2.each(function (i, item) {
    $(item).select2("destroy");
  });
  let rowCount = $("#multi-row-count").val();
  for (let i = 0; i < rowCount; i++) {
    addyscRow();
  }
  $(".select2").select2();
  $(".select2.islem").select2({
    tags: true,
  });
});
function addyscRow() {
  // Find the element with class 'invoice-row'
  const productRow = $(".product-row").last();

  // Clone the invoiceRow element along with its children
  const clone = productRow.clone(true);

  // Append the cloned element to the parent of the original element
  productRow.parent().append(clone);
  clone.find("input").val("");
  clone.find(".urun_id").val(0);

  $("#yscTable .product-row").each(function () {
    $(this)
      .find(".select2")
      .each(function () {
        var id = $(this).attr("id");
        var uniqVal = Math.random().toString(36).substr(2, 9);
        var newName = id.replace(/\[\]/g, "") + uniqVal;
        $(this).attr("id", newName);
      });

    $(this)
      .find(".satir_no")
      .val($(this).index() + 1);
  });
}
