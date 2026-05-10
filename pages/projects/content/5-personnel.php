<?php
$persons = $projectObj->getPersontoProject($firm_id, $id);
?>
<div class="row">
    <input type="hidden" id="project_id" value="<?php echo $id ?>">
    <div class="col-12">
        <div class="card border-0 shadow-none">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title">Proje Personel Listesi</h3>
                <div class="card-actions">
                    <button type="button" class="btn btn-primary btn-sm" id="savePersontoProject">
                        <i class="ti ti-device-floppy icon me-2"></i> Personelleri Kaydet
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="addPersontoProject" class="table card-table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th style="width:1%" class="no-sorting">
                                    <input class="form-check-input" type="checkbox" id="allPersonCheck">
                                </th>
                                <th style="width:1%">ID</th>
                                <th>Adı Soyadı</th>
                                <th>Ücret Türü</th>
                                <th>Görevi</th>
                                <th style="width:1%">Durumu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($persons as $person) :
                                $checked = ($person->is_added ?? 0) == 1 ? "checked" : "";
                            ?>
                                <tr>
                                    <td>
                                        <input class="form-check-input" name="person_checked[<?php echo $person->id ?>]" <?php echo $checked; ?> type="checkbox" value="<?php echo $person->id ?>">
                                    </td>
                                    <td><?php echo $person->id; ?></td>
                                    <td><?php echo $person->full_name; ?></td>
                                    <td><?php echo ($person->wage_type ?? 0) == 1 ? "Beyaz Yaka" : "Mavi Yaka"; ?></td>
                                    <td><?php echo $person->job ?? '-'; ?></td>
                                    <td>
                                        <?php if (!empty($person->job_end_date)): ?>
                                            <span class="badge bg-danger">Pasif</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
