<?php
$enc_id = isset($_GET['id']) ? $_GET['id'] : 0;
$project_int_id = $id > 0 ? $id : 0;
?>

<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title">İş Kalemleri</h3>
                    <div class="ms-auto">
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
        </div>
    </div>
</div>
