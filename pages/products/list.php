<?php

require_once "App/Helper/helper.php";
require_once "App/Helper/auths.php";
require_once "Model/Products.php";

use App\Helper\Helper;

$productObj = new Product();
$products = $productObj->all();

$auths = new Authorize();
$auths->getAuth('productcategory')

?>
<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
    
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ürün Listesi</h3>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-primary route-link" data-page="products/manage">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table card-table text-nowrap datatable">
                        <thead>
                            <tr>
                                <th>id</th>
                                <th>Stok Kodu</th>
                                <th>Ürün Adı</th>
                                <th>Birimi</th>
                                <th style="width:5%">Alış Fiyatı</th>
                                <th>Satış Fiyatı</th>
                                <th>Açıklama</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php foreach ($products as $product) :
                            ?>
                                <tr>
                                    <td><?php echo $product->id; ?></td>
                                    <td><?php echo $product->stok_kodu; ?></td>
                                    <td><?php echo Helper::short($product->urun_adi, 35); ?></td>
                                    <td><?php echo Helper::unit($product->birimi); ?></td>
                                    <td class="text-center" style="max-width:1%">
                                        <?php
                                        if ($product->alis_fiyati > 0) {
                                            echo $product->alis_fiyati  . " " . Helper::money($product->alis_para_birimi);
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        if ($product->satis_fiyati > 0) {
                                            echo $product->satis_fiyati . " " . Helper::money($product->satis_para_birimi);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $product->aciklama; ?></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link" data-page="products/manage&id=<?php echo $product->id ?>">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item delete-product" href="#">
                                                    <i class="ti ti-trash icon me-3"></i> Sil
                                                </a>
                                            </div>
                                        </div>

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