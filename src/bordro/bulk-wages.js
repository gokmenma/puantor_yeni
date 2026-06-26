(function () {
  var bwState = {
    page: 1,
    perPage: 100,
    total: 0,
    search: '',
    individualMode: false,
    searchTimer: null,
  };

  var $modal = $('#bulk-wages-modal');

  function initFlatpickr() {
    $modal.find('.bw-flatpickr').each(function () {
      if (!this._flatpickr) {
        flatpickr(this, { dateFormat: 'd.m.Y', locale: 'tr' });
      }
    });
  }

  function initMoneyInputs($scope) {
    $scope.find('.bw-money').inputmask('decimal', {
      radixPoint: ',',
      groupSeparator: '.',
      digits: 2,
      autoGroup: true,
      rightAlign: false,
      removeMaskOnSubmit: true,
    });
  }

  function updateSelectedCount() {
    var count = $modal.find('#bw-persons-tbody input.bw-check:checked').length;
    $('#bw-selected-count').text(count);
  }

  function buildRow(p) {
    var inputCol = bwState.individualMode
      ? '<td class="bw-col-individual"><input type="text" class="form-control form-control-sm bw-money bw-ind-wage" data-id="' + p.id + '" placeholder="0,00" style="min-width:110px;"></td>'
      : '<td class="bw-col-individual" style="display:none;"></td>';

    return '<tr data-person-id="' + p.id + '">' +
      '<td><input type="checkbox" class="form-check-input bw-check" value="' + p.id + '"></td>' +
      '<td>' + escHtml(p.full_name) + '</td>' +
      '<td>' + escHtml(p.job) + '</td>' +
      '<td>' + escHtml(p.job_start_date) + '</td>' +
      '<td class="text-end fw-bold">' + escHtml(p.current_wage_fmt) + '</td>' +
      inputCol +
      '</tr>';
  }

  function escHtml(s) {
    if (!s) return '-';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function loadPersons() {
    var $tbody = $('#bw-persons-tbody');
    $tbody.html('<tr><td colspan="6" class="text-center py-4 text-secondary"><div class="spinner-border spinner-border-sm"></div> Yükleniyor...</td></tr>');

    $.ajax({
      url: 'api/bordro/bulk-wages.php',
      method: 'POST',
      data: {
        action: 'getPersons',
        search: bwState.search,
        page: bwState.page,
        per_page: bwState.perPage,
      },
      success: function (res) {
        bwState.total = res.total || 0;
        var persons = res.data || [];

        if (persons.length === 0) {
          $tbody.html('<tr><td colspan="6" class="text-center py-4 text-secondary">Kayıt bulunamadı.</td></tr>');
        } else {
          var html = '';
          $.each(persons, function (_, p) { html += buildRow(p); });
          $tbody.html(html);
          initMoneyInputs($tbody);
        }

        var totalPages = Math.max(1, Math.ceil(bwState.total / bwState.perPage));
        var start = (bwState.page - 1) * bwState.perPage + 1;
        var end = Math.min(bwState.page * bwState.perPage, bwState.total);
        $('#bw-total-label').text('Toplam ' + bwState.total + ' kayıttan ' + start + '-' + end + ' arası gösteriliyor');
        $('#bw-page-label').text('Sayfa ' + bwState.page + ' / ' + totalPages);

        $('#bw-page-first, #bw-page-prev').prop('disabled', bwState.page <= 1);
        $('#bw-page-next, #bw-page-last').prop('disabled', bwState.page >= totalPages);

        updateSelectedCount();

        if (bwState.individualMode) {
          $modal.find('th.bw-col-individual').show();
        }
      },
      error: function (xhr) {
        var msg = xhr.responseText ? xhr.responseText.substring(0, 200) : 'Bilinmeyen hata';
        $tbody.html('<tr><td colspan="6" class="text-center py-4 text-danger">Veriler yüklenirken hata oluştu.<br><small class="text-muted">' + msg + '</small></td></tr>');
      },
    });
  }

  function getSelectedIds() {
    var ids = [];
    $modal.find('#bw-persons-tbody input.bw-check:checked').each(function () {
      ids.push($(this).val());
    });
    return ids;
  }

  function showResult(res) {
    Swal.fire({
      title: res.status === 'success' ? 'Başarılı!' : 'Hata!',
      text: res.message,
      icon: res.status,
      confirmButtonText: 'Tamam',
    }).then(function () {
      if (res.status === 'success') {
        loadPersons();
      }
    });
  }

  function dmyToYmd(dmy) {
    if (!dmy) return '';
    var parts = dmy.split('.');
    if (parts.length !== 3) return dmy;
    return parts[2] + '-' + parts[1] + '-' + parts[0];
  }

  $modal.on('shown.bs.modal', function () {
    initFlatpickr();
    bwState.page = 1;
    bwState.search = '';
    $('#bw-search').val('');
    loadPersons();
  });

  $('#bw-search').on('input', function () {
    clearTimeout(bwState.searchTimer);
    var val = $(this).val();
    bwState.searchTimer = setTimeout(function () {
      bwState.search = val;
      bwState.page = 1;
      loadPersons();
    }, 400);
  });

  $('#bw-per-page').on('change', function () {
    bwState.perPage = parseInt($(this).val(), 10);
    bwState.page = 1;
    loadPersons();
  });

  $('#bw-page-first').on('click', function () { bwState.page = 1; loadPersons(); });
  $('#bw-page-prev').on('click', function () { if (bwState.page > 1) { bwState.page--; loadPersons(); } });
  $('#bw-page-next').on('click', function () {
    var totalPages = Math.ceil(bwState.total / bwState.perPage);
    if (bwState.page < totalPages) { bwState.page++; loadPersons(); }
  });
  $('#bw-page-last').on('click', function () {
    bwState.page = Math.ceil(bwState.total / bwState.perPage);
    loadPersons();
  });

  $('#bw-select-all').on('click', function () {
    $modal.find('#bw-persons-tbody input.bw-check').prop('checked', true);
    updateSelectedCount();
  });

  $('#bw-clear-all').on('click', function () {
    $modal.find('#bw-persons-tbody input.bw-check').prop('checked', false);
    updateSelectedCount();
  });

  $(document).on('change', '#bw-persons-tbody input.bw-check', function () {
    updateSelectedCount();
  });

  $('#bw-btn-raise').on('click', function () {
    var ids = getSelectedIds();
    if (ids.length === 0) {
      Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Lütfen en az bir personel seçin.' });
      return;
    }
    var pct = parseFloat(String($('#bw-raise-pct').val()).replace(',', '.'));
    if (!pct || pct <= 0) {
      Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Lütfen geçerli bir zam oranı girin.' });
      return;
    }
    var start = $('#bw-raise-start').val();
    var end   = $('#bw-raise-end').val();
    if (!start || !end) {
      Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Lütfen başlangıç ve bitiş tarihlerini girin.' });
      return;
    }

    Swal.fire({
      title: 'Zam Uygula',
      html: '<b>' + ids.length + '</b> personele <b>%' + pct + '</b> zam uygulanacak. Onaylıyor musunuz?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Uygula',
      cancelButtonText: 'Vazgeç',
    }).then(function (result) {
      if (!result.isConfirmed) return;

      var formData = new FormData();
      formData.append('action', 'applyRaise');
      formData.append('raise_percent', pct);
      formData.append('start_date', dmyToYmd(start));
      formData.append('end_date', dmyToYmd(end));
      formData.append('description', $('#bw-raise-desc').val());
      $.each(ids, function (_, id) { formData.append('person_ids[]', id); });

      $.ajax({
        url: 'api/bordro/bulk-wages.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: showResult,
        error: function () { Swal.fire({ icon: 'error', title: 'Hata', text: 'Sunucu hatası oluştu.' }); },
      });
    });
  });

  $('#bw-btn-fixed').on('click', function () {
    var ids = getSelectedIds();
    if (ids.length === 0) {
      Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Lütfen en az bir personel seçin.' });
      return;
    }
    var amount = $('#bw-fixed-amount').val();
    if (!amount || amount.trim() === '') {
      Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Lütfen ücret tutarını girin.' });
      return;
    }
    var start = $('#bw-fixed-start').val();
    var end   = $('#bw-fixed-end').val();
    if (!start || !end) {
      Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Lütfen başlangıç ve bitiş tarihlerini girin.' });
      return;
    }

    Swal.fire({
      title: 'Ücret Güncelle',
      html: '<b>' + ids.length + '</b> personelin ücreti güncellenecek. Onaylıyor musunuz?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Güncelle',
      cancelButtonText: 'Vazgeç',
    }).then(function (result) {
      if (!result.isConfirmed) return;

      var formData = new FormData();
      formData.append('action', 'setFixed');
      formData.append('amount', amount);
      formData.append('start_date', dmyToYmd(start));
      formData.append('end_date', dmyToYmd(end));
      formData.append('description', $('#bw-fixed-desc').val());
      $.each(ids, function (_, id) { formData.append('person_ids[]', id); });

      $.ajax({
        url: 'api/bordro/bulk-wages.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: showResult,
        error: function () { Swal.fire({ icon: 'error', title: 'Hata', text: 'Sunucu hatası oluştu.' }); },
      });
    });
  });

  $('#bw-btn-individual-toggle').on('click', function () {
    bwState.individualMode = !bwState.individualMode;

    if (bwState.individualMode) {
      $(this).html('<i class="ti ti-x icon me-1"></i> Bireysel Modu Kapat').removeClass('btn-outline-success').addClass('btn-outline-danger');
      $('#bw-btn-individual-save').show();
      $('#bw-individual-badge').css('display', '');
      $modal.find('th.bw-col-individual').show();
    } else {
      $(this).html('<i class="ti ti-table-column icon me-1"></i> Bireysel Giriş Modunu Aç').removeClass('btn-outline-danger').addClass('btn-outline-success');
      $('#bw-btn-individual-save').hide();
      $('#bw-individual-badge').css('display', 'none!important');
      $modal.find('th.bw-col-individual').hide();
    }

    loadPersons();
  });

  $('#bw-btn-individual-save').on('click', function () {
    var start = $('#bw-ind-start').val();
    var end   = $('#bw-ind-end').val();
    if (!start || !end) {
      Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Lütfen başlangıç ve bitiş tarihlerini girin.' });
      return;
    }

    var wages = {};
    var count = 0;
    $modal.find('.bw-ind-wage').each(function () {
      var val = $(this).val();
      var id  = $(this).data('id');
      if (val && val.trim() !== '' && val.trim() !== '0,00') {
        wages[id] = val;
        count++;
      }
    });

    if (count === 0) {
      Swal.fire({ icon: 'warning', title: 'Uyarı', text: 'Lütfen en az bir personel için ücret girin.' });
      return;
    }

    Swal.fire({
      title: 'Bireysel Ücret Kaydet',
      html: '<b>' + count + '</b> personel için bireysel ücretler kaydedilecek. Onaylıyor musunuz?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Kaydet',
      cancelButtonText: 'Vazgeç',
    }).then(function (result) {
      if (!result.isConfirmed) return;

      var formData = new FormData();
      formData.append('action', 'setIndividual');
      formData.append('start_date', dmyToYmd(start));
      formData.append('end_date', dmyToYmd(end));
      formData.append('description', $('#bw-ind-desc').val());
      $.each(wages, function (id, amount) {
        formData.append('wages[' + id + ']', amount);
      });

      $.ajax({
        url: 'api/bordro/bulk-wages.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: showResult,
        error: function () { Swal.fire({ icon: 'error', title: 'Hata', text: 'Sunucu hatası oluştu.' }); },
      });
    });
  });
})();
