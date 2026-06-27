var homeGanttInstance = null;

$(document).ready(function () {
    if (!$('#home-gantt-project').length) return;

    $('#home-gantt-project').select2({
        placeholder: 'Proje seçiniz...',
        allowClear: true,
    });

    $('#home-gantt-project').on('change', function () {
        loadHomeGantt($(this).val());
    });

    var preselected = $('#home-gantt-project').val();
    if (preselected) loadHomeGantt(preselected);
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
