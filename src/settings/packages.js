$(document).on("click", ".choose_package", function () {
  var id = $(this).data("id");
  $("#package_id").val(id);

  // reset checkbox states
  $("#monthly_price").prop("checked", true);
  $("#yearly_price").prop("checked", false);

  //paket bilgilerini getir
  fetch("/api/settings/packages.php?id=" + id + "&action=getPackage")
    .then((response) => response.json())
    .then((data) => {
      let package = data.data;

      if (package) {
        $("#package_name").text(package.name);
        
        let isUnlimited = (package.name.toLowerCase() === 'sınırsız');
        let isTrial = (parseFloat(package.price) <= 0 && !isUnlimited);

        if (isUnlimited) {
          $("#package_price").text("Bize Ulaşın");
          $("#package_days").text(package.days + " Gün");
          $("#duration_selector_container").hide();
        } else if (isTrial) {
          $("#package_price").text("Ücretsiz");
          $("#package_days").text(package.days + " Gün");
          $("#duration_selector_container").hide();
        } else {
          $("#package_price").text(
            new Intl.NumberFormat("tr-TR", {
              style: "currency",
              currency: package.money_unit
            }).format(package.price)
          );
          $("#package_days").text(package.days + " Gün");
          $("#monthly_price").attr("data-price", package.price);
          $("#yearly_price").attr("data-price", package.price);
          $("#duration_selector_container").show();
        }
      } else {
        $("#package_name").text('');
        $("#package_price").text('');
        $("#package_days").text('');
        $("#monthly_price").attr("data-price", '');
        $("#yearly_price").attr("data-price", '');
        $("#duration_selector_container").show();
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
});

$(document).on("click", "#yearly_price", function () {
  var price = $(this).attr("data-price");
  var package_price = price * 12;
  var package_price_discount = price * 10;

  $("#package_price").html(
    `<s>${new Intl.NumberFormat("tr-TR", {
      style: "currency",
      currency: "TRY"
    }).format(package_price)}</s> yerine  ${new Intl.NumberFormat("tr-TR", {
      style: "currency",
      currency: "TRY"
    }).format(package_price_discount)}`
  );
  $("#package_days").text(365 + " Gün");
});

$(document).on("click", "#monthly_price", function () {
  var price = $(this).data("price");
  var gun = $(this).data("gun");
  var package_price = price;

  $("#package_price").html(
    `${new Intl.NumberFormat("tr-TR", {
      style: "currency",
      currency: "TRY"
    }).format(package_price)}`
  );
  $("#package_days").text(gun + " Gün");
});

$("#modal-team").on("hidden.bs.modal", function () {
  $("#yearly_price").prop("checked", false);
  $("#monthly_price").prop("checked", true);
});

$(document).on("click", ".buy_package", function (e) {
  e.preventDefault();
  var package_id = $("#package_id").val();
  var duration_type = $("#monthly_price").prop("checked") ? "monthly" : "yearly";

  if (!package_id) {
    Swal.fire("Hata!", "Lütfen geçerli bir paket seçin.", "error");
    return;
  }

  var formData = new FormData();
  formData.append("action", "buyPackage");
  formData.append("package_id", package_id);
  formData.append("duration_type", duration_type);

  fetch("/api/settings/packages.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      var title = data.status == "success" ? "Başarılı!" : "Hata!";
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: "Tamam"
      }).then(() => {
        if (data.status == "success") {
          window.location.reload();
        }
      });
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Hata!", "Abonelik talebi gönderilirken bir sorun oluştu.", "error");
    });
});

$(document).on("click", ".btn-new-package-pending", function (e) {
  e.preventDefault();
  Swal.fire({
    title: "Onay Bekleyen Talep!",
    text: "Zaten onay bekleyen bir paket satın alma talebiniz bulunmaktadır. Mevcut talebiniz yönetici tarafından onaylanana kadar yeni bir talep oluşturamazsınız.",
    icon: "warning",
    confirmButtonText: "Tamam"
  });
});

function isNumeric(value) {
    return !isNaN(value - parseFloat(value));
}