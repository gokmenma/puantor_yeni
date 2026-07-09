var homeGanttInstance = null;

$(document).ready(function () {
    if (!$('#home-gantt-project').length) return;

    $('#home-gantt-project').select2({
        placeholder: 'Proje seçiniz...',
        allowClear: true,
    });

    $('#home-gantt-project').on('change', function () {
        var val = $(this).val();
        loadHomeGantt(val);
        if (val) {
            $('#btn-home-add-task').removeClass('d-none');
        } else {
            $('#btn-home-add-task').addClass('d-none');
        }
    });

    var preselected = $('#home-gantt-project').val();
    if (preselected) {
        loadHomeGantt(preselected);
        $('#btn-home-add-task').removeClass('d-none');
    }
});

$(document).on('click', '#home-gantt-view-modes .btn', function () {
    $(this).addClass('active').siblings().removeClass('active');
    if (homeGanttInstance) homeGanttInstance.change_view_mode($(this).data('mode'));
});

function loadHomeGantt(projectId) {
    $('#home-gantt-placeholder, #home-gantt-empty').hide();
    $('#home-gantt-container').hide().empty();
    homeGanttInstance = null;

    if (!projectId) {
        $('#home-gantt-placeholder').show();
        return;
    }

    $('#home-gantt-loading').show();

    $.post('/api/projects/tasks.php', { action: 'get_tasks', project_id: parseInt(projectId) }, function (res) {
        $('#home-gantt-loading').hide();

        var tasks       = res.data || [];
        var statusCls   = { 0: '', 1: 'bar-in-progress', 2: 'bar-done' };
        var statusColor = { 0: '#206bc4', 1: '#f59f00', 2: '#2fb344' };
        var statusLabel = { 0: 'Bekliyor', 1: 'Devam Ediyor', 2: 'Tamamlandı' };

        var ganttTasks = [];
        tasks.forEach(function (row) {
            if (!row.start_date_raw || !row.end_date_raw) return;
            ganttTasks.push({
                id:           String(row.id),
                name:         row.task_name_raw,
                start:        row.start_date_raw,
                end:          row.end_date_raw,
                progress:     row.completion_percent,
                custom_class: statusCls[row.status] || '',
                _status:      row.status,
                _responsible: row.responsible_raw,
                _start_disp:  row.start_date,
                _end_disp:    row.end_date,
            });
        });

        if (!ganttTasks.length) {
            $('#home-gantt-empty').show();
            return;
        }

        $('#home-gantt-container').show();

        homeGanttInstance = new Gantt('#home-gantt-container', ganttTasks, {
            view_mode:         'Week',
            date_format:       'YYYY-MM-DD',
            language:          'tr',
            header_height:     52,
            column_width:      38,
            bar_height:        28,
            bar_corner_radius: 5,
            padding:           14,
            custom_popup_html: function (task) {
                var color = statusColor[task._status] || '#206bc4';
                var label = statusLabel[task._status] || '';
                return '<div style="padding:14px 16px;font-family:inherit">'
                    + '<div style="font-weight:600;font-size:13px;margin-bottom:10px;line-height:1.4;color:#111">' + hEsc(task.name) + '</div>'
                    + '<div style="display:grid;grid-template-columns:auto 1fr;gap:3px 10px;font-size:12px;margin-bottom:10px">'
                    + '<span style="color:#999">Sorumlu</span><span style="color:#333">' + hEsc(task._responsible || '—') + '</span>'
                    + '<span style="color:#999">Başlangıç</span><span style="color:#333">' + hEsc(task._start_disp || '—') + '</span>'
                    + '<span style="color:#999">Bitiş</span><span style="color:#333">' + hEsc(task._end_disp || '—') + '</span>'
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

        var activeMode = $('#home-gantt-view-modes .btn.active').data('mode') || 'Week';
        if (activeMode !== 'Week') homeGanttInstance.change_view_mode(activeMode);

    }, 'json').fail(function () {
        $('#home-gantt-loading').hide();
        $('#home-gantt-empty').show();
    });
}

function hEsc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// Yeni Görev Modali ve Yönetimi
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

$(document).on('click', '#btn-home-add-task', function (e) {
    e.preventDefault();
    var projectId = $('#home-gantt-project').val();
    if (projectId) {
        openTaskModal({ project_id: projectId });
    }
});

$(document).on('submit', '#task-modal-form', function (e) {
    e.preventDefault();
    var formData = $(this).serialize();

    $.post('/api/projects/tasks.php', formData, function (res) {
        if (res.status === 'success') {
            $('#task-modal').modal('hide');
            Swal.fire({ title: 'Başarılı!', text: res.message, icon: 'success', timer: 1500, showConfirmButton: false });
            var currentProj = $('#home-gantt-project').val();
            if (currentProj) {
                loadHomeGantt(currentProj);
            }
        } else {
            Swal.fire('Hata!', res.message, 'error');
        }
    }, 'json');
});

function openTaskModal(data) {
    var isEdit = !!data.id;
    $('#task-modal-title').text(isEdit ? 'Görevi Düzenle' : 'Yeni Görev');
    $('#task_id').val(data.id || 0);
    $('#task_project_id').val(data.project_id || $('#home-gantt-project').val());
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
