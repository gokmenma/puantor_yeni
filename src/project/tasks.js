var tasksTable = null;

$(document).ready(function () {
    var $table = $('#tasks-table');
    if (!$table.length || !window.createDataTable) return;

    var projectId = parseInt($table.data('project-id'), 10);
    if (!projectId) return;

    tasksTable = window.createDataTable('#tasks-table', {
        ajax: {
            url: '/api/projects/tasks.php',
            type: 'POST',
            data: { action: 'get_tasks', project_id: projectId },
            dataSrc: function (json) {
                updateProgressBar(json.summary);
                return json.data || [];
            },
        },
        columns: [
            { data: 'rownum',        searchable: false, orderable: false },
            { data: 'task_name' },
            { data: 'responsible' },
            { data: 'start_date' },
            { data: 'end_date' },
            { data: 'status_html',   searchable: false, orderable: false },
            { data: 'progress_html', searchable: false, orderable: false },
            { data: 'actions_html',  searchable: false, orderable: false },
        ],
    });
});

function updateProgressBar(summary) {
    if (!summary || summary.total === 0) {
        $('#tasks-progress-bar-wrap').hide();
        return;
    }
    $('#tasks-progress-bar-wrap').show();
    $('#tasks-progress-bar').css('width', summary.overall + '%');
    $('#tasks-progress-label').text(
        'Genel ilerleme: %' + summary.overall + ' — ' + summary.completed + '/' + summary.total + ' görev tamamlandı'
    );
}

$(document).on('shown.bs.modal', '#task-modal', function () {
    if (!$('#task_status').hasClass('select2-hidden-accessible')) {
        $('#task_status').select2({
            dropdownParent: $('#task-modal'),
            minimumResultsForSearch: Infinity,
        });
    }
    if ($('#task_start_date').length && !document.getElementById('task_start_date')._flatpickr) {
        flatpickr('#task_start_date, #task_end_date', { dateFormat: 'd.m.Y', locale: 'tr' });
    }
});

$(document).on('input', '#task_completion_percent', function () {
    $('#task_percent_label').text($(this).val());
});

$(document).on('click', '.add-task-btn', function (e) {
    e.preventDefault();
    var projectId = $(this).data('project-id');
    openTaskModal({ project_id: projectId });
});

$(document).on('click', '.edit-task-btn', function (e) {
    e.preventDefault();
    var taskId = $(this).data('id');
    if (!tasksTable) return;

    var rowData = null;
    tasksTable.rows().every(function () {
        if (this.data().id == taskId) {
            rowData = this.data();
        }
    });

    if (!rowData) return;

    openTaskModal({
        id:          rowData.id,
        project_id:  $('#tasks-table').data('project-id'),
        task_name:   rowData.task_name_raw,
        responsible: rowData.responsible_raw,
        description: rowData.description,
        start_date:  rowData.start_date !== '-' ? rowData.start_date : '',
        end_date:    rowData.end_date   !== '-' ? rowData.end_date   : '',
        status:      rowData.status,
        percent:     rowData.completion_percent,
    });
});

$(document).on('click', '.delete-task-btn', function (e) {
    e.preventDefault();
    var taskId = $(this).data('id');

    Swal.fire({
        title: 'Emin misiniz?',
        text: 'Bu görev silinecektir!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, Sil!',
        cancelButtonText: 'Vazgeç',
    }).then(function (result) {
        if (!result.isConfirmed) return;

        $.post('/api/projects/tasks.php', { action: 'delete_task', task_id: taskId }, function (res) {
            if (res.status === 'success') {
                if (tasksTable) tasksTable.ajax.reload(null, false);
                Swal.fire('Silindi!', res.message, 'success');
            } else {
                Swal.fire('Hata!', res.message, 'error');
            }
        }, 'json');
    });
});

$(document).on('submit', '#task-modal-form', function (e) {
    e.preventDefault();
    var formData = $(this).serialize();

    $.post('/api/projects/tasks.php', formData, function (res) {
        if (res.status === 'success') {
            $('#task-modal').modal('hide');
            if (tasksTable) tasksTable.ajax.reload(null, false);
            Swal.fire({ title: 'Başarılı!', text: res.message, icon: 'success', timer: 1500, showConfirmButton: false });
        } else {
            Swal.fire('Hata!', res.message, 'error');
        }
    }, 'json');
});

function openTaskModal(data) {
    var isEdit = !!data.id;
    $('#task-modal-title').text(isEdit ? 'Görevi Düzenle' : 'Yeni Görev');
    $('#task_id').val(data.id || 0);
    $('#task_project_id').val(data.project_id || $('#tasks-table').data('project-id'));
    $('#task_name').val(data.task_name || '');
    $('#task_responsible').val(data.responsible_raw || data.responsible || '');
    $('#task_description').val(data.description || '');

    if ($('#task_status').hasClass('select2-hidden-accessible')) {
        $('#task_status').val(data.status != null ? data.status : 0).trigger('change');
    } else {
        $('#task_status').val(data.status != null ? data.status : 0);
    }

    var pct = data.percent != null ? data.percent : 0;
    $('#task_completion_percent').val(pct);
    $('#task_percent_label').text(pct);

    var startFp = document.getElementById('task_start_date') && document.getElementById('task_start_date')._flatpickr;
    var endFp   = document.getElementById('task_end_date')   && document.getElementById('task_end_date')._flatpickr;

    if (startFp) startFp.setDate(data.start_date || '');
    else $('#task_start_date').val(data.start_date || '');

    if (endFp) endFp.setDate(data.end_date || '');
    else $('#task_end_date').val(data.end_date || '');

    $('#task-modal').modal('show');
}
