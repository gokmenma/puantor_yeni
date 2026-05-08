$(document).on("click", ".add-wage-cut", function () {
    let personel_id = $(this).data("id");
    let personel_name = $(this).closest("tr").find("td:eq(1)").text();
    let balance = $(this).closest("tr").find("td:eq(9)").text();
    $("#person_id_wage_cut").val(personel_id);
    $("#person_name_wage_cut").text(personel_name);
    $("#wage_cut_modal").modal("show");
    
  
    $("#person_wage_cut_balance").text("Bakiye :" + balance);
});

$(document).on('click', '#wage_cut_addButton', function () {
    var form = $('#wage_cut_modalForm');
    let urlParams = new URLSearchParams(window.location.search);
    let page = urlParams.get("p");

    addCustomValidationMethods(); // custom validation methodlarını çalıştır
    form.validate({
        rules: {
            wage_cut_amount: {
                required: true,
                validNumber: true
            },
            wage_cut_type: {
                required: true
            },
        },
        messages: {
            wage_cut_amount: {
                required: "Lütfen bir miktar giriniz.",
                validNumber: "Lütfen geçerli bir miktar giriniz."
            },
            wage_cut_type: {
                required: "Lütfen Kesinti adını giriniz."
            }
        }
    });

    if (!form.valid()) {
        return;
    }

    var formData = new FormData(form[0]);

    formData.append("action", "saveWageCut");
    formData.append("page", page);

    // for (var pair of formData.entries()) {
    //     console.log(pair[0] + ', ' + pair[1]);
    // }

    fetch("api/persons/wage_cut.php", {
        method: "POST",
        body: formData
    }).then(response => response.json())
        .then(data => {
            if (data.status == "success") {
                console.log(data);
                
                form.trigger("reset");
                Swal.fire({
                    icon: 'success',
                    title: 'Başarılı!',
                    text: data.message
                }).then(() => {
                    $('#wage_cut-modal').modal('hide');
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: data.message
                });
            }
        })
});