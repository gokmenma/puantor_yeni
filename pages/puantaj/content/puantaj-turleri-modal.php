<div class="modal modal-blur fade" id="modal-default" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-list-details me-2 text-primary"></i> Puantaj Türü Seçimi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <div class="d-flex align-items-start">

                    <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active" id="v-pills-0-tab" data-bs-toggle="pill" data-bs-target="#v-pills-0" type="button" role="tab" aria-controls="v-pills-0" aria-selected="true">Normal Çalışma</button>
                        <button class="nav-link" id="v-pills-1-tab" data-bs-toggle="pill" data-bs-target="#v-pills-1" type="button" role="tab" aria-controls="v-pills-1" aria-selected="false">Fazla Çalışma</button>
                        <button class="nav-link" id="v-pills-2-tab" data-bs-toggle="pill" data-bs-target="#v-pills-2" type="button" role="tab" aria-controls="v-pills-2" aria-selected="false">Saatlik Çalışma</button>
                        <button class="nav-link" id="v-pills-3-tab" data-bs-toggle="pill" data-bs-target="#v-pills-3" type="button" role="tab" aria-controls="v-pills-3" aria-selected="false">Ücretli</button>
                        <button class="nav-link" id="v-pills-4-tab" data-bs-toggle="pill" data-bs-target="#v-pills-4" type="button" role="tab" aria-controls="v-pills-4" aria-selected="false">Ücretiz/Rapor</button>
                    </div>
                    <div class="tab-content hover-menu" id="v-pills-tabContent">
                        
                        <div class="tab-pane fade show active" id="v-pills-0" role="tabpanel" aria-labelledby="v-pills-0-tab" tabindex="0">
                            <?php echo $puantajHelper->getPuantajTuruList("Normal Çalışma"); ?>
                        </div>
                        
                        <div class="tab-pane fade" id="v-pills-1" role="tabpanel" aria-labelledby="v-pills-1-tab" tabindex="0">
                            <?php echo $puantajHelper->getPuantajTuruList("Fazla Çalışma"); ?>
                        </div>
                       
                        <div class="tab-pane fade" id="v-pills-2" role="tabpanel" aria-labelledby="v-pills-2-tab" tabindex="0">
                            <?php echo $puantajHelper->getPuantajTuruList("Saatlik"); ?>
                        </div>

                        <div class="tab-pane fade" id="v-pills-3" role="tabpanel" aria-labelledby="v-pills-3-tab" tabindex="0">
                            <?php echo $puantajHelper->getPuantajTuruList("Ücretli İzin"); ?>
                        </div>
                        <div class="tab-pane fade" id="v-pills-4" role="tabpanel" aria-labelledby="v-pills-4-tab" tabindex="0">
                            <?php echo $puantajHelper->getPuantajTuruList("Ücretsiz"); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex align-items-center justify-content-between">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="modal-set-as-shortcut" checked>
                    <label class="form-check-label cursor-pointer" for="modal-set-as-shortcut" style="font-size: 13px; font-weight: 500;">Seçili Tür'e Ata</label>
                </div>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-link link-secondary mb-0" data-bs-dismiss="modal">
                        Vazgeç
                    </a>
                    <a href="#" class="btn btn-primary mb-0" data-bs-dismiss="modal">
                        <i class="ti ti-checks icon me-2"></i>
                        Seç
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>