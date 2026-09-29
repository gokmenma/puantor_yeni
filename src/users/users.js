/**
 * Users Management JS
 * Modal and Full Page User Operations
 */

function initUserModalSelect2() {
  if ($.fn.select2) {
    if ($('#modal_user_roles').hasClass('select2-hidden-accessible')) {
      $('#modal_user_roles').select2('destroy');
    }
    $('#modal_user_roles').select2({
      dropdownParent: $('#userModal'),
      placeholder: 'Rol seçiniz',
      width: '100%',
      allowClear: true
    });

    if ($('#modal_responsible_projects').hasClass('select2-hidden-accessible')) {
      $('#modal_responsible_projects').select2('destroy');
    }
    $('#modal_responsible_projects').select2({
      dropdownParent: $('#userModal'),
      placeholder: 'Tüm projeler için boş bırakınız',
      width: '100%',
      allowClear: true
    });
  }
}

// Modal Açılış ve Select2 Yönetimi
$(document).ready(function () {
  // Modal gösterildiğinde Select2 ve Focus
  $('#userModal').on('show.bs.modal shown.bs.modal', function () {
    initUserModalSelect2();
    setTimeout(function () {
      $('#modal_full_name').focus();
    }, 120);
  });

  // Modal kapandığında formu sıfırla
  $('#userModal').on('hidden.bs.modal', function () {
    var form = $('#modalUserForm')[0];
    if (form) {
      form.reset();
    }
    if ($.fn.select2) {
      $('#modal_user_roles').val(null).trigger('change');
      $('#modal_responsible_projects').val(null).trigger('change');
    }
    $('#modal_password').attr('type', 'password');
    $('.toggle-password-visibility i').removeClass('ti-eye-off').addClass('ti-eye');
  });

  // Parola Göster / Gizle Toggle
  $(document).on('click', '.toggle-password-visibility', function (e) {
    e.preventDefault();
    var target = $(this).data('target');
    var $input = $(target);
    var $icon = $(this).find('i');

    if ($input.attr('type') === 'password') {
      $input.attr('type', 'text');
      $icon.removeClass('ti-eye').addClass('ti-eye-off');
    } else {
      $input.attr('type', 'password');
      $icon.removeClass('ti-eye-off').addClass('ti-eye');
    }
  });

  // Yeni Kullanıcı Ekle Butonuna Tıklandığında Rol Kontrolü
  $(document).on('click', '.btn-new-user-trigger', function (e) {
    var $rolesSelect = $('#modal_user_roles');
    if ($rolesSelect.length && $rolesSelect.find('option').length === 0) {
      e.preventDefault();
      e.stopPropagation();
      Swal.fire({
        title: 'Rol Tanımı Gerekli!',
        text: 'Yeni kullanıcı ekleyebilmek için önce en az bir Kullanıcı Rolü oluşturmalısınız.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yeni Rol Ekle',
        cancelButtonText: 'Vazgeç'
      }).then(function (result) {
        if (result.isConfirmed) {
          window.location.href = 'index.php?p=users/roles/manage';
        }
      });
      return false;
    }
  });

  // Modal Form Gönderimi (AJAX)
  $(document).on('submit', '#modalUserForm', function (e) {
    e.preventDefault();
    var form = $(this);
    var fullName = ($('#modal_full_name').val() || '').trim();
    var username = ($('#modal_username').val() || '').trim();
    var email = ($('#modal_email').val() || '').trim();
    var password = $('#modal_password').val() || '';
    var roles = $('#modal_user_roles').val();

    if (!fullName) {
      Swal.fire({
        icon: 'warning',
        title: 'Eksik Bilgi',
        text: 'Lütfen kullanıcının Adı ve Soyadını giriniz.',
        confirmButtonText: 'Tamam'
      });
      $('#modal_full_name').focus();
      return;
    }

    if (!username) {
      Swal.fire({
        icon: 'warning',
        title: 'Eksik Bilgi',
        text: 'Lütfen kullanıcı adını giriniz.',
        confirmButtonText: 'Tamam'
      });
      $('#modal_username').focus();
      return;
    }

    if (!email) {
      Swal.fire({
        icon: 'warning',
        title: 'Eksik Bilgi',
        text: 'Lütfen geçerli bir e-posta adresi giriniz.',
        confirmButtonText: 'Tamam'
      });
      $('#modal_email').focus();
      return;
    }

    if (!password) {
      Swal.fire({
        icon: 'warning',
        title: 'Eksik Bilgi',
        text: 'Lütfen kullanıcı için bir parola belirleyiniz.',
        confirmButtonText: 'Tamam'
      });
      $('#modal_password').focus();
      return;
    }

    if (!roles || roles.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Eksik Bilgi',
        text: 'Lütfen kullanıcıya en az bir rol atayınız.',
        confirmButtonText: 'Tamam'
      });
      if ($.fn.select2) {
        $('#modal_user_roles').select2('open');
      }
      return;
    }

    var $btn = $('#btnSaveUserModal');
    var origHtml = $btn.html();
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Kaydediliyor...');

    var formData = new FormData(form[0]);
    formData.append('id', '0');
    formData.append('action', 'userSave');

    fetch('/api/users/users.php', {
      method: 'POST',
      body: formData
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        $btn.prop('disabled', false).html(origHtml);
        if (data.status === 'success') {
          Swal.fire({
            title: 'Başarılı!',
            text: data.message || 'Kullanıcı başarıyla kaydedildi.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
          }).then(function () {
            $('#userModal').modal('hide');
            window.location.reload();
          });
        } else {
          Swal.fire({
            title: 'Hata!',
            text: data.message || 'Kullanıcı kaydedilemedi.',
            icon: 'error',
            confirmButtonText: 'Tamam'
          });
        }
      })
      .catch(function (err) {
        $btn.prop('disabled', false).html(origHtml);
        Swal.fire({
          title: 'Hata!',
          text: 'Sunucuya bağlanırken bir sorun oluştu.',
          icon: 'error',
          confirmButtonText: 'Tamam'
        });
      });
  });
});

// Sayfa Üzerinden Kayıt (users/manage)
$(document).on('click', '#kullanici_kaydet', function () {
  let id = $('#user_id').val();
  var form = $('#userForm');

  if ($.fn.validate) {
    form.validate({
      rules: {
        full_name: { required: true },
        username: { required: true },
        email: { required: true, email: true },
        password: {
          required: function () {
            return $('#user_id').val() == '0';
          }
        },
        'user_roles[]': { required: true }
      },
      messages: {
        full_name: { required: 'Lütfen ad soyad giriniz' },
        username: { required: 'Lütfen kullanıcı adını giriniz' },
        email: {
          required: 'Lütfen email adresini giriniz',
          email: 'Lütfen geçerli bir email adresi giriniz'
        },
        password: { required: 'Lütfen şifreyi giriniz' },
        'user_roles[]': { required: 'Lütfen en az bir kullanıcı rolü seçiniz' }
      },
      errorPlacement: function (error, element) {
        if (element.hasClass('select2')) {
          var container = element.next('.select2-container');
          error.insertAfter(container);
        } else {
          error.insertAfter(element);
        }
      }
    });
    if (!form.valid()) {
      return;
    }
  }

  var formData = new FormData(form[0]);

  // Sorumlu Personeller tablosundaki sayfalar arası seçimleri ekle
  if ($.fn.DataTable && $.fn.DataTable.isDataTable('#responsible-persons-table')) {
    var dt = $('#responsible-persons-table').DataTable();
    dt.$('input[type="checkbox"]:checked').each(function () {
      if (!$.contains(document, this)) {
        formData.append(this.name, this.value);
      }
    });
  }

  formData.append('id', id);
  formData.append('action', 'userSave');

  fetch('/api/users/users.php', {
    method: 'POST',
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      var title = data.status === 'success' ? 'Başarılı!' : 'Hata!';
      if (data.status === 'success') {
        $('#user_id').val(data.lastid);
      }
      Swal.fire({
        title: title,
        text: data.message,
        icon: data.status,
        confirmButtonText: 'Tamam'
      }).then(() => {
        if (data.status === 'success' && id == '0') {
          window.location.href = 'index.php?p=users/manage&id=' + data.lastid;
        }
      });
    })
    .catch(() => {
      Swal.fire({
        title: 'Hata!',
        text: 'Sunucuya bağlanırken bir sorun oluştu.',
        icon: 'error',
        confirmButtonText: 'Tamam'
      });
    });
});

// Silme İşlemi
$(document).on('click', '.delete_user', function (e) {
  e.preventDefault();
  let action = 'deleteUser';
  let confirmMessage = 'Kullanıcı kalıcı olarak silinecektir. Devam etmek istiyor musunuz?';
  let url = '/api/users/users.php';

  if (typeof deleteRecord === 'function') {
    deleteRecord(this, action, confirmMessage, url);
  } else {
    let id = $(this).data('id');
    let $row = $(this).closest('tr');
    Swal.fire({
      title: 'Emin misiniz?',
      text: confirmMessage,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Evet, Sil',
      cancelButtonText: 'Vazgeç',
      confirmButtonColor: '#d33'
    }).then((result) => {
      if (result.isConfirmed) {
        var formData = new FormData();
        formData.append('action', action);
        formData.append('id', id);
        formData.append('csrf_token', $('meta[name="csrf-token"]').attr('content') || '');

        fetch(url, {
          method: 'POST',
          body: formData
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.status === 'success') {
              Swal.fire('Silindi!', data.message, 'success');
              if ($.fn.DataTable && $.fn.DataTable.isDataTable('#userTable')) {
                $('#userTable').DataTable().row($row).remove().draw(false);
              } else {
                $row.remove();
              }
            } else {
              Swal.fire('Hata!', data.message, 'error');
            }
          });
      }
    });
  }
});

// Paket Limit Aşımı Uyarısı
$(document).on('click', '.btn-new-user-limit', function (e) {
  e.preventDefault();
  let limit = $(this).data('limit');
  Swal.fire({
    title: 'Limit Aşımı!',
    text: 'Paketinizin alt kullanıcı limiti (' + limit + ') dolmuştur. Yeni kullanıcı eklemek için lütfen paketinizi yükseltiniz.',
    icon: 'warning',
    confirmButtonText: 'Tamam'
  });
});
