$(document).on("click", ".choose_package", function () {
  var id = $(this).data("id");
  $("#package_id").val(id);

  //paket bilgilerini getir
  fetch("/api/settings/packages.php?id=" + id + "&action=getPackage")
    .then((response) => response.json())
    .then((data) => {
      let package = data.data;

      if (isNumeric(package.price)) {
        $("#package_name").text(package.name);
        $("#package_price").text(
          new Intl.NumberFormat("tr-TR", {
            style: "currency",
            currency: package.money_unit
          }).format(package.price)
        );
        $("#package_days").text(package.days + " Gün");
        $("#monthly_price").attr("data-price", package.price);
        $("#yearly_price").attr("data-price", package.price);
      }
      else{
        $("#package_name").text('');
        $("#package_price").text('');
        $("#package_days").text('');
        $("#monthly_price").attr("data-price", '');
        $("#yearly_price").attr("data-price", '');
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

function isNumeric(value) {
    return !isNaN(value - parseFloat(value));
}