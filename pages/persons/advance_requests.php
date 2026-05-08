<?php
require_once "Database/require.php";
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Personel Avans Talepleri
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="advance-requests-table" class="table card-table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th>Personel</th>
                                <th>Tutar</th>
                                <th>Hedef Dönem</th>
                                <th>Açıklama</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Veriler AJAX ile gelecek -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const table = $('#advance-requests-table').DataTable({
        ajax: {
            url: 'api/admin/advances.php?action=list',
            dataSrc: 'list'
        },
        columns: [
            { data: 'full_name' },
            { 
                data: 'tutar',
                render: function(data) {
                    return '₺' + parseFloat(data).toLocaleString('tr-TR', {minimumFractionDigits: 2});
                }
            },
            { 
                data: null,
                render: function(data) {
                    return data.hedef_ay + '/' + data.hedef_yil;
                }
            },
            { data: 'aciklama' },
            { data: 'created_at' },
            { 
                data: 'durum',
                render: function(data) {
                    if (data == 0) return '<span class="badge bg-warning">Bekliyor</span>';
                    if (data == 1) return '<span class="badge bg-success">Onaylandı</span>';
                    if (data == 2) return '<span class="badge bg-danger">Reddedildi</span>';
                    return '';
                }
            },
            {
                data: null,
                render: function(data) {
                    if (data.durum == 0) {
                        return `
                            <button class="btn btn-sm btn-success btn-approve" data-id="${data.id}">Onayla</button>
                            <button class="btn btn-sm btn-danger btn-reject" data-id="${data.id}">Reddet</button>
                        `;
                    }
                    return '-';
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json'
        }
    });

    $(document).on('click', '.btn-approve', function() {
        const id = $(this).data('id');
        if (confirm('Bu avans talebini onaylamak istediğinize emin misiniz?')) {
            updateStatus(id, 1);
        }
    });

    $(document).on('click', '.btn-reject', function() {
        const id = $(this).data('id');
        if (confirm('Bu avans talebini reddetmek istediğinize emin misiniz?')) {
            updateStatus(id, 2);
        }
    });

    function updateStatus(id, status) {
        $.post('api/admin/advances.php', {
            action: 'update_status',
            id: id,
            status: status
        }, function(response) {
            if (response.status === 'success') {
                table.ajax.reload();
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        }, 'json');
    }
});
</script>
