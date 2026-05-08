<div class="modal modal-blur fade" id="modal-team" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Paket Seç</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="col-12">
                    <form action="">
                        <input type="hidden" class="form-control mb-2" name="package_id" id="package_id">
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="btn-radio-basic" id="monthly_price"
                                autocomplete="off" checked="" data-price="" data-gun="30">
                            <label for="monthly_price" type="button" class="btn">Ay</label>
                            <input type="radio" class="btn-check" name="btn-radio-basic" id="yearly_price"
                                autocomplete="off" data-price="">
                            <label for="yearly_price" type="button" class="btn">Yıl</label>

                        </div>

                        <div class="card-body">
                            <h3 class="card-title">Paket Bilgileri</h3>
                            <dl class="row">
                                <dt class="col-4" >Paket Adı:</dt>
                                <dd class="col-8"><label for="" class="text-uppercase" id="package_name"></label></dd>
                                <dt class="col-4">Paket Süresi:</dt>
                                <dd class="col-8"><label for="" class="text-uppercase" id="package_days"></label></dd>
                                <dt class="col-4">Paket Fiyatı:</dt>
                                <dd class="col-8"><label for="" class="text-uppercase fw-bold" id="package_price"></label></dd>
                            </dl>

                            <hr>
                            <h3 class="card-title">Ödeme Bilgileri</h3>
                            <dl class="row">
                                <dt class="col-4">Banka Adı:</dt>
                                <dd class="col-8">Ziraat Bankası</dd>
                                <dt class="col-4">Hesap Adı :</dt>
                                <dd class="col-8">Mbe Yazılım</dd>
                                <dt class="col-4">İban No :</dt>
                                <dd class="col-8">TR00 0000 0000 0000 0000 0000 00</dd>

                            </dl>
                            <h3 class="card-title">Not:</h3>
                            <p class="card-subtitle">Paketi Seç butonuna bastığınızda talebiniz tarafımıza iletilecek ve
                                yukarıdaki hesap numarasına ödemeyi yaptığınızda paket işlemleriniz onaylanacaktır.</p>
                        </div>
                    </form>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn me-auto close_package_modal" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary buy_package" data-bs-dismiss="modal">Paketi Seç</button>
            </div>
        </div>
    </div>
</div>