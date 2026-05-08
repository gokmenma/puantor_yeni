$(document).ready(function () {
  $(".form-check-input.main").on("click", function () {
    if ($(this).is(":checked")) {
      $(this)
        .closest(".datagrid-item")
        .find(".form-selectgroup-input")
        .prop("checked", true);
    } else {
      $(this)
        .closest(".datagrid-item")
        .find(".form-selectgroup-input")
        .prop("checked", false);
    }
  });
});

$(document).on("click", "#authsSave", function () {
  var form = $("#authsForm");
  var formData = new FormData(form[0]);

//   for (var pair of formData.entries()) {
//     console.log(pair[0] + ", " + pair[1]);
//   }

  fetch("api/users/auths.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      title = data.status == "success" ? "Başarılı" : "Hata";
      swal.fire(title, data.message, data.status);
      //console.log(data);
      $("#auth_id").val(data.id);
    });
});


//checkAll
$(document).on("click", "#checkAll", function () {
  if ($(this).is(":checked")) {
    $(".form-selectgroup-input").prop("checked", true);
    $(".form-check-input").prop("checked", true);
  } else {
    $(".form-selectgroup-input").prop("checked", false);
    $(".form-check-input").prop("checked", false);
  }
});