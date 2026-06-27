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

// View toggle
$(document).on('click', '#btn-view-table', function () {
    $('#btn-view-table').addClass('active');
    $('#btn-view-gantt').removeClass('active');
    $('#tasks-gantt-wrap').hide();
    $('#tasks-table-wrap').show();
});

$(document).on('click', '#btn-view-gantt', function () {
    $('#btn-view-gantt').addClass('active');
    $('#btn-view-table').removeClass('active');
    $('#tasks-table-wrap').hide();
    $('#tasks-gantt-wrap').show();
    renderGanttChart();
});

$(document).on('click', '#btn-tasks-print', function () { printTasks(); });
$(document).on('click', '#btn-tasks-excel', function () { exportTasksExcel(); });

function getTaskRows() {
    if (!tasksTable) return [];
    return tasksTable.rows().data().toArray();
}

function escHtml(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function printTasks() {
    var rows = getTaskRows();
    var projectName = $('h2.page-title').text().trim();
    var html = '<!doctype html><html><head><meta charset="utf-8"><title>' + escHtml(projectName) + ' - İş Takibi</title>'
        + '<style>body{font-family:Arial,sans-serif;font-size:12px;padding:20px}'
        + 'h2{font-size:16px;margin-bottom:12px}'
        + 'table{border-collapse:collapse;width:100%}'
        + 'th,td{border:1px solid #ccc;padding:5px 8px;text-align:left}'
        + 'th{background:#f0f0f0;font-weight:bold}'
        + '</style></head><body>';
    html += '<h2>' + escHtml(projectName) + ' - İş Takibi</h2>';
    html += '<table><thead><tr><th>#</th><th>Görev Adı</th><th>Sorumlu</th><th>Başlangıç</th><th>Bitiş</th><th>Durum</th><th>Tamamlanma %</th></tr></thead><tbody>';
    rows.forEach(function (row, i) {
        html += '<tr>'
            + '<td>' + (i + 1) + '</td>'
            + '<td>' + escHtml(row.task_name_raw) + (row.description ? '<br><small style="color:#666">' + escHtml(row.description) + '</small>' : '') + '</td>'
            + '<td>' + escHtml(row.responsible_raw) + '</td>'
            + '<td>' + escHtml(row.start_date) + '</td>'
            + '<td>' + escHtml(row.end_date) + '</td>'
            + '<td>' + escHtml(row.status_label) + '</td>'
            + '<td>%' + row.completion_percent + '</td>'
            + '</tr>';
    });
    html += '</tbody></table></body></html>';
    var win = window.open('', '_blank');
    win.document.write(html);
    win.document.close();
    win.print();
}

function exportTasksExcel() {
    if (typeof XLSX === 'undefined') {
        Swal.fire('Hata!', 'Excel kütüphanesi yüklenemedi.', 'error');
        return;
    }
    var rows = getTaskRows();
    var projectName = $('h2.page-title').text().trim() || 'Proje';
    var data = [['#', 'Görev Adı', 'Açıklama', 'Sorumlu', 'Başlangıç', 'Bitiş', 'Durum', 'Tamamlanma %']];
    rows.forEach(function (row, i) {
        data.push([
            i + 1,
            row.task_name_raw || '',
            row.description || '',
            row.responsible_raw || '',
            row.start_date || '',
            row.end_date || '',
            row.status_label || '',
            row.completion_percent || 0,
        ]);
    });
    var ws = XLSX.utils.aoa_to_sheet(data);
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'İş Takibi');
    XLSX.writeFile(wb, projectName + ' - İş Takibi.xlsx');
}

var ganttInstance = null;

function dateToDisplay(d) {
    return String(d.getDate()).padStart(2, '0') + '.'
         + String(d.getMonth() + 1).padStart(2, '0') + '.'
         + d.getFullYear();
}

function rawToDisplay(raw) {
    if (!raw) return '';
    var p = raw.split('-');
    return p[2] + '.' + p[1] + '.' + p[0];
}

function saveGanttTask(taskId, startDisp, endDisp, progress) {
    var rowData = null;
    getTaskRows().forEach(function (row) {
        if (parseInt(row.id) === parseInt(taskId)) rowData = row;
    });
    if (!rowData) return;

    $.post('/api/projects/tasks.php', {
        action: 'save_task',
        task_id: parseInt(taskId),
        project_id: $('#tasks-table').data('project-id'),
        task_name: rowData.task_name_raw,
        description: rowData.description || '',
        responsible: rowData.responsible_raw || '',
        status: rowData.status,
        completion_percent: progress != null ? progress : rowData.completion_percent,
        start_date: startDisp,
        end_date: endDisp,
    }, function (res) {
        if (res.status === 'success') {
            if (tasksTable) tasksTable.ajax.reload(null, false);
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Güncellendi', timer: 1200, showConfirmButton: false });
        } else {
            Swal.fire('Hata!', res.message, 'error');
            renderGanttChart();
        }
    }, 'json');
}

function renderGanttChart() {
    if (typeof Gantt === 'undefined') {
        $('#tasks-gantt-empty').text('Gantt kütüphanesi yüklenemedi.').show();
        return;
    }
    var rows = getTaskRows();
    var ganttTasks = [];
    var statusClasses = { 0: '', 1: 'bar-in-progress', 2: 'bar-done' };
    rows.forEach(function (row) {
        if (!row.start_date_raw || !row.end_date_raw) return;
        ganttTasks.push({
            id: String(row.id),
            name: row.task_name_raw,
            start: row.start_date_raw,
            end: row.end_date_raw,
            progress: row.completion_percent,
            custom_class: statusClasses[row.status] || '',
        });
    });

    $('#tasks-gantt-container').empty();
    ganttInstance = null;

    if (ganttTasks.length === 0) {
        $('#tasks-gantt-empty').show();
        return;
    }
    $('#tasks-gantt-empty').hide();

    ganttInstance = new Gantt('#tasks-gantt-container', ganttTasks, {
        view_mode: 'Week',
        date_format: 'YYYY-MM-DD',
        language: 'tr',
        header_height: 52,
        column_width: 38,
        bar_height: 28,
        bar_corner_radius: 5,
        padding: 14,
        on_date_change: function (task, start, end) {
            saveGanttTask(task.id, dateToDisplay(start), dateToDisplay(end), null);
        },
        on_progress_change: function (task, progress) {
            var rowData = null;
            getTaskRows().forEach(function (row) {
                if (parseInt(row.id) === parseInt(task.id)) rowData = row;
            });
            if (!rowData) return;
            saveGanttTask(task.id, rawToDisplay(rowData.start_date_raw), rawToDisplay(rowData.end_date_raw), progress);
        },
        custom_popup_html: function (task) {
            var statusColors = { 0: '#206bc4', 1: '#f59f00', 2: '#2fb344' };
            var statusLabels = { 0: 'Bekliyor', 1: 'Devam Ediyor', 2: 'Tamamlandı' };
            var rowData = null;
            getTaskRows().forEach(function (row) {
                if (String(row.id) === String(task.id)) rowData = row;
            });
            var status   = rowData ? parseInt(rowData.status) : 0;
            var responsible = rowData ? (rowData.responsible_raw || '—') : '—';
            var startDisp = task.start ? rawToDisplay(task.start) : '—';
            var endDisp   = task.end   ? rawToDisplay(task.end)   : '—';
            var color = statusColors[status] || '#206bc4';
            var label = statusLabels[status] || '';
            return '<div style="padding:14px 16px;font-family:inherit">'
                + '<div style="font-weight:600;font-size:13px;margin-bottom:10px;line-height:1.4;color:#111">' + escHtml(task.name) + '</div>'
                + '<div style="display:grid;grid-template-columns:auto 1fr;gap:3px 10px;font-size:12px;margin-bottom:10px">'
                + '<span style="color:#999">Sorumlu</span><span style="color:#333">' + escHtml(responsible) + '</span>'
                + '<span style="color:#999">Başlangıç</span><span style="color:#333">' + startDisp + '</span>'
                + '<span style="color:#999">Bitiş</span><span style="color:#333">' + endDisp + '</span>'
                + '</div>'
                + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">'
                + '<span style="background:' + color + ';color:#fff;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:500">' + label + '</span>'
                + '<span style="font-size:12px;color:#555;font-weight:600">%' + task.progress + '</span>'
                + '</div>'
                + '<div style="background:#e9ecef;border-radius:10px;height:4px;overflow:hidden">'
                + '<div style="width:' + task.progress + '%;background:' + color + ';height:4px;border-radius:10px"></div>'
                + '</div>'
                + '</div>';
        },
    });
}

$(document).on('click', '#gantt-view-modes .btn', function () {
    $(this).addClass('active').siblings().removeClass('active');
    if (ganttInstance) ganttInstance.change_view_mode($(this).data('mode'));
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
