var wage_id = $("#wage_id");
// Değişkenleri daha geniş bir kapsamda tanımlayın
var wage_name, start_date, end_date, amount, description, created_at;

$(document).on("click", "#add_wage_row", function () {
  var table = $("#personWageTable").DataTable();
  var personel_id = $("#person_id").val();
  if (!checkPersonId(personel_id)) {
    return;
  }

  let rowCount = table.rows().count() + 1;
  wage_id.val(0);
  //tabloya yeni bir satır ekliyoruz
  table.order([0, "desc"]).draw(false);
  table.row
    .add([
      rowCount,
      `<input type='text' class='form-control' name='wage_name' required placeholder='Ücret tanımı girin'>`,
      `<input type='text' class='form-control flatpickr' name='wage_start_date' id="wage_start_date" required placeholder='Başlama Tarihi girin'>`,
      `<input type='text' class='form-control flatpickr' name='wage_end_date' id="wage_end_date" required placeholder='Bitiş Tarihi girin'>`,
      `<input type='text' class='form-control money' name='wage_amount' required placeholder='Tutar giriniz'>`,
      `<input type='text' class='form-control' name='wage_description'>`,
      ``,
      `<button type='button' class='btn me-1 remove_wage_row'><i class='ti ti-trash icon m-0'></i></button>
        <button type='button' class='btn save_wage_row'><i class='ti ti-device-floppy icon m-0'></i><div id="spinner" class="spinner" style="display: none;"></div></button>`
    ])
    .draw(false);

  table.column(0).nodes().to$().addClass("text-center");

  if ($(".flatpickr").length > 0) {
    $(".flatpickr").flatpickr({
      dateFormat: "d.m.Y",
      locale: "tr" // locale for this instance only
    });
  }

  //Bastığım butonu disabled yap
  $(this).attr("disabled", true);
});

$(document).on("click", ".remove_wage_row", function () {
  var table = $("#personWageTable").DataTable();
  table.rows($(this).closest("tr")).remove().draw();
  $("#add_wage_row").removeAttr("disabled");
});

$(document).ready(function () {
  $.validator.addMethod(
    "greaterThan",
    function (value, element, param) {
      var $otherElement = $(param);
      var startDate = $otherElement.val();
      var endDate = value;

      //console.log("başlama tarihi " + startDate, "Bitiş Tarihi" + endDate);

      return startDate <= endDate;
    },
    "This value must be greater than the other value."
  );
});

// yeni bir satır ekledikten veya güncellemeye bastıktan sonraki kaydet butonu
$(document).on("click", ".save_wage_row", function () {
  var form = $("#personWageForm");
  let rowCount = table.rows().count();
  let row = $(this).closest("tr");
  //let inputArray = {};

  //Satırdaki inputları objeye ekler
  //rowInputAddArray(row, inputArray);

  form.validate({
    rules: {
      wage_name: {
        required: true
      },
      wage_start_date: {
        required: true
      },
      wage_end_date: {
        required: true,
        greaterThan: "#wage_start_date"
      },
      wage_amount: {
        required: true
      }
    },
    messages: {
      wage_name: {
        required: "Bu alan zorunludur"
      },
      wage_start_date: {
        required: "Bu alan zorunludur"
      },
      wage_end_date: {
        required: "Bu alan zorunludur",
        greaterThan: "Bitiş tarihi başlama tarihinden büyük olmalıdır"
      },
      wage_amount: {
        required: "Bu alan zorunludur",
        inputPattern: "Tutar sayısal değer olmalıdır"
      }
    }
  });

  if (!form.valid()) {
    return;
  }

  let formData = new FormData(form[0]);
  //formData.append("data", JSON.stringify(inputArray));
  formData.append("action", "saveWage");

  for (var pair of formData.entries()) {
    console.log(pair[0] + ", " + pair[1]);
  }

  showSpinner();
  fetch("api/persons/wages.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      //console.log(data);

      if (data.status == "success") {
        title = "Başarılı!";
        $("#add_wage_row").removeAttr("disabled");
      } else {
        title = "Hata!";
      }
      swal.fire({
        title: title,
        html:
          data.message +
          "<span style='color: red;'><br><br> Ücreti değiştirdiğiniz takdirde ilgili ayları yeniden hesaplamalısınız.</span>",
        icon: data.status,
        confirmButtonText: "Tamam"
      });
      var table = $("#personWageTable").DataTable();
      var data = data.data;
      console.log(data.data);
      table
        .row(row)
        .data([
          rowCount,
          data.wage_name,
          data.start_date,
          data.end_date,
          data.amount,
          data.description,
          data.created_at,
          `<div class="dropdown">
              <button class="btn dropdown-toggle align-text-top"
                  data-bs-toggle="dropdown">İşlem</button>
              <div class="dropdown-menu dropdown-menu-end">
                  <a class="dropdown-item update-wage"
                      data-id="${data.id}" href="#">
                      <i class="ti ti-edit icon me-3"></i> Güncelle
                  </a>
                  <a class="dropdown-item delete-wage" href="#" data-id="${data.id}">
                      <i class="ti ti-trash icon me-3"></i> Sil
                  </a>
              </div>
          </div>`
        ])
        .draw(false);
      var columns = [3, 4];
      columns.forEach(function (index) {
        table.column(index).nodes().to$().addClass("text-start");
      });

      // table.column(3).nodes().to$().addClass("text-start");
      // table.column(4).nodes().to$().addClass("text-start");
      table.order([1, "desc"]).draw(false);
    });
  hideSpinner();
});

$(document).on("keypress", 'input[name="wage_amount"]', function (e) {
  if ((e.which < 48 || e.which > 57) && e.which != 46 && e.which != 44) {
    return false;
  }
});

function showSpinner() {
  $(".spinner").css("display", "inline-block");
  $(".ti-device-floppy").css("display", "none");
}

function hideSpinner() {
  $(".ti-device-floppy").css("display", "inline-block");
  $(".spinner").css("display", "none");
}

//Array'deki verileri ekrana yazdırma
function displayInputArrayData(inputArray) {
  for (const key in inputArray) {
    if (Object.hasOwnProperty.call(inputArray, key)) {
      const element = inputArray[key];
      console.log(key, element);
    }
  }
}

//Satırdaki inputları objeye ekler
function rowInputAddArray(row, inputArray) {
  row.find("input").each(function () {
    let input = $(this);
    inputArray[input.attr("name").replace("[]", "")] = input.val();
  });
}

$(document).ready(function () {
  //Ücret tanımı güncelleme
  $(document).on("click", ".update-wage", function () {
    let id = $(this).data("id");


    //preloader göster
    $(".preloader").fadeIn();
    let row = $(this).closest("tr");
    $("#wage_id").val(id);
    $("#add_wage_row").attr("disabled", true);
    let formData = new FormData();
    formData.append("action", "getWage");
    formData.append("id", id);

    // for (var pair of formData.entries()) {
    //   console.log(pair[0] + ", " + pair[1]);
    // }

    fetch("api/persons/wages.php", {
      method: "POST",
      body: formData
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status == "success") {
          let wage = data.data;
          wage_name = wage.wage_name;
          start_date = wage.start_date;
          end_date = wage.end_date;
          amount = wage.amount;
          description = wage.description;
          created_at = wage.created_at;

          row
            .find("td:eq(1)")
            .html(
              `<input type='text' class='form-control' name='wage_name' value='${wage_name}' required placeholder='Ücret tanımı girin'>`
            );
          row
            .find("td:eq(2)")
            .html(
              `<input type='text' class='form-control flatpickr' name='wage_start_date' id="wage_start_date" value='${start_date}' required placeholder='Başlama Tarihi girin'>`
            );
          row
            .find("td:eq(3)")
            .html(
              `<input type='text' class='form-control flatpickr' name='wage_end_date'  value='${end_date}' required placeholder='Bitiş Tarihi girin'>`
            );
          row
            .find("td:eq(4)")
            .html(
              `<input type='text' class='form-control money' name='wage_amount' value='${amount}' required placeholder='Tutar giriniz'>`
            );
          row
            .find("td:eq(5)")
            .html(
              `<input type='text' class='form-control' name='wage_description' value='${description}'>`
            );

          row.find("td:eq(7)").html("");
          row.find("td:eq(7)")
            .html(`<button type='button' class='btn me-1 cancel_wage_row'><i class='ti ti-x icon m-0'></i></button>
        <button type='button' class='btn save_wage_row'><i class='ti ti-device-floppy icon m-0'></i><div id="spinner" class="spinner" style="display: none;"></div></button>`);
      //preloader gizle
      $(".preloader").fadeOut();  
      }

      });

    if ($(".flatpickr").length > 0) {
      $(".flatpickr").flatpickr({
        dateFormat: "d.m.Y",
        locale: "tr" // locale for this instance only
      });
    }
  });

  //Ücret tanımı güncelleme iptal
  $(document).on("click", ".cancel_wage_row", function () {
    let row = $(this).closest("tr");
    let id = $("#wage_id").val();
    let inputs = row.find("input");

    $("#add_wage_row").removeAttr("disabled");
    wage_id.val(0);

    let values = [wage_name, start_date, end_date, amount, description,created_at];

    values.forEach((value, index) => {
      row.find(`td:eq(${index + 1})`).html(value);
    });

    row.find("td:eq(7)").html(`
    <div class="dropdown">
        <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">İşlem</button>
        <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item update-wage" data-id="${id}" href="#">
                <i class="ti ti-edit icon me-3"></i> Güncelle
            </a>
            <a class="dropdown-item delete-wage" href="#" data-id="${id}">
                <i class="ti ti-trash icon me-3"></i> Sil
            </a>
        </div>
    </div>
`);
  });
});

$(document).on("click", ".delete-wage", function () {
  //Tablo adı butonun içinde bulunduğu tablo
  let action = "deleteWage";
  let confirmMessage =
    "<span style='color: red;'>Ücreti sildiğiniz takdirde ilgili dönemleri yeniden hesaplamalısınız!</span>";
  let url = "/api/persons/wages.php";

  deleteRecord(this, action, confirmMessage, url);
});
