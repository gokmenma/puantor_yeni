<?php
$enc_id = isset($_GET['id']) ? $_GET['id'] : 0;
$project_int_id = $id > 0 ? $id : 0;
?>

<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <h3 class="card-title">İş Kalemleri</h3>
                    <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary active" id="btn-view-table">
                                <i class="ti ti-list me-1"></i>Tablo
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-view-gantt">
                                <i class="ti ti-chart-bar me-1"></i>Gantt
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-tasks-print" title="Yazdır">
                            <i class="ti ti-printer"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="btn-tasks-excel" title="Excel'e Aktar">
                            <i class="ti ti-file-spreadsheet"></i>
                        </button>
                        <button type="button" class="btn btn-primary btn-sm add-task-btn"
                            data-project-id="<?php echo $project_int_id; ?>">
                            <i class="ti ti-plus icon me-1"></i> Yeni Görev
                        </button>
                    </div>
                </div>

                <div class="card-header border-top-0 pt-0" id="tasks-progress-bar-wrap" style="display:none;">
                    <div class="w-100">
                        <div class="progress mb-1" style="height:8px;">
                            <div class="progress-bar bg-primary" id="tasks-progress-bar" style="width:0%" role="progressbar"></div>
                        </div>
                        <small class="text-muted" id="tasks-progress-label"></small>
                    </div>
                </div>

                <div id="tasks-table-wrap">
                    <div class="table-responsive">
                        <table class="table card-table" id="tasks-table" data-project-id="<?php echo $project_int_id; ?>">
                            <thead>
                                <tr>
                                    <th style="width:3%">#</th>
                                    <th>Görev Adı</th>
                                    <th>Sorumlu</th>
                                    <th>Başlangıç</th>
                                    <th>Bitiş</th>
                                    <th>Durum</th>
                                    <th style="width:15%">İlerleme</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div id="tasks-gantt-wrap" style="display:none; padding:1rem 1.25rem;">
                    <div class="d-flex align-items-center flex-wrap gap-3 mb-3">
                        <div class="btn-group btn-group-sm" id="gantt-view-modes">
                            <button type="button" class="btn btn-outline-secondary" data-mode="Day">Gün</button>
                            <button type="button" class="btn btn-outline-secondary active" data-mode="Week">Hafta</button>
                            <button type="button" class="btn btn-outline-secondary" data-mode="Month">Ay</button>
                        </div>
                        <div class="d-flex align-items-center gap-3 ms-auto">
                            <span class="d-flex align-items-center gap-1">
                                <span style="width:13px;height:13px;border-radius:3px;background:#206bc4;display:inline-block;flex-shrink:0"></span>
                                <small class="text-muted">Bekliyor</small>
                            </span>
                            <span class="d-flex align-items-center gap-1">
                                <span style="width:13px;height:13px;border-radius:3px;background:#f59f00;display:inline-block;flex-shrink:0"></span>
                                <small class="text-muted">Devam Ediyor</small>
                            </span>
                            <span class="d-flex align-items-center gap-1">
                                <span style="width:13px;height:13px;border-radius:3px;background:#2fb344;display:inline-block;flex-shrink:0"></span>
                                <small class="text-muted">Tamamlandı</small>
                            </span>
                        </div>
                    </div>
                    <div id="tasks-gantt-empty" class="text-muted text-center py-5" style="display:none;">
                        <i class="ti ti-calendar-off" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                        Gantt şeması için görevlerin başlangıç ve bitiş tarihleri girilmelidir.
                    </div>
                    <div id="tasks-gantt-container" style="overflow-x:auto;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
