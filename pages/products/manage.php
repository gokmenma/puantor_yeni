<?php
require_once "App/Helper/helper.php";
require_once "Model/Products.php";

use App\Helper\Helper;

$id = $_GET["id"] ?? 0;
$productObj = new Product();
$product = $productObj->find($id);

?>
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Yeni Ürün
                    </h2>
                </div>

                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-outline-secondary route-link" data-page="products/list">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="urun_kaydet">
                        <i class="ti ti-device-floppy icon me-2"></i>
                        Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form action="" id="productForm">
                            <input type="text" id="id" name="id" class="form-control" value="<?php echo $product->id ?? '' ?>">

                            <div class="row mb-3 mt-3">
                                <div class="col-md-2">
                                    <label class="form-label">Ürün/Hizmet Adı</label>

                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="urun_adi" id="urun_adi" value="<?php echo $product->urun_adi ?? '' ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Stok Kodu</label>

                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="stok_kodu" id="stok_kodu" value="<?php echo $product->stok_kodu ?? '' ?>">
                                </div>

                            </div>
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="form-label">Birimi</label>

                                </div>
                                <div class="col-md-4">
                                    <?php echo Helper::unitSelect("birimi" ,$product->birimi ?? ''); ?>
                                </div>
                            </div>


                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label class="form-label">Alış Fiyatı</label>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control" name="alis_fiyati" id="alis_fiyati" value="<?php echo $product->alis_fiyati ?? '' ?>">
                                </div>

                                <div class="col-md-2">
                                    <?php echo Helper::moneySelect("alis_para_birimi", $product->alis_para_birimi ?? ''); ?>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Satış Fiyatı</label>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control" name="satis_fiyati" id="satis_fiyati" value="<?php echo $product->satis_fiyati ?? '' ?>">
                                </div>

                                <div class="col-md-2">
                                    <?php echo Helper::moneySelect("satis_para_birimi", $product->satis_para_birimi ?? ''); ?>
                                </div>

                            </div>
                            <div class="row mb-">
                                <div class="col-md-2">
                                    <label class="form-label">Açıklama</label>

                                </div>
                                <div class="col-md-10">
                                    <textarea class="form-control" name="aciklama" id="aciklama"><?php echo $product->aciklama ?? '' ?></textarea>

                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>