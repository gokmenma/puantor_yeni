$(document).on('click', '#saveIncExpType', function() {
    var form = $('#incExpForm');

    form.validate({
        rules: {
            incexp_name: {
                required: true
            }
        },
        messages: {
            incexp_name: {
                required: "Gelir/Gider adı boş bırakılamaz."
            }
        }
    });

    if (!form.valid()) {
        return;
    }

    let formData = new FormData(form[0]);

    for(var pair of formData.entries()) {
        console.log(pair[0]+ ', '+ pair[1]);
    }

    fetch('/api/defines/incexp.php', {
        method: 'POST',
        body: formData
    }).then(response =>response.json())
    .then(data => {
        if(data.status=="success"){
            title="Başarılı";
        }else{
            title="Hata";
        }
        Swal.fire({
            title: title,
            text: data.message,
            icon: data.status
        });
    });
    

});

$(document).on("click", ".delete-incexp", function () {
    //Tablo adı butonun içinde bulunduğu tablo
    let action = "deleteIncExpType";
    let confirmMessage = "Gelir/Gider Tanımlaması silinecektir!";
    let url = "/api/defines/incexp.php";
  
    deleteRecord(this, action, confirmMessage, url);
  });