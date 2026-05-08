<?php
require_once "App/Helper/date.php";
require_once "Model/PackageModel.php";
require_once "Model/UsersPackagesModel.php";

use App\Helper\Date;
use App\Helper\Security;

$Packages = new PackageModel();
$UsersPackages = new UsersPackageModel();
$user_package = $UsersPackages->getSelectUserPackage($_SESSION["user"]->id);
$package = $Packages->getPackage($user_package->package_id ?? 0) ?? null;

$packages = $Packages->getPackages();
?>

<style>


</style>
<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards pricing-container">
            <?php
            foreach ($packages as $package):
                $package_id = Security::encrypt($package->id);
                if($package->person >100){
                    $package->person = "Sınırsız";
                    $package->firm = "Sınırsız";
                    $package->project = "Sınırsız";
                    $package->case = "Sınırsız";
                    
                    $free_month = "";
                }else{
                   
                    $free_month = "Yıllık ödeme yaparsanız 2 ay ücretsiz!";
                }
                if($package->mission_manage == 1 ){
                    $mission_manage = "Görev Yönetimi";
                }else{
                    $mission_manage = "<del>Görev Yönetimi</del>";
                }
                
                ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-md pricing-card">
                        <div class="card-body text-center">
                            <div class="text-uppercase text-secondary font-weight-medium"><?php echo $package->name; ?>
                            </div>
                            <div class="d-flex justify-content-center">
                                <?php
                                //fiyat numeric değilse gösterme
                                if (is_numeric($package->price)) { ?>
                                    <div class="display-5 fw-bold my-3"> ₺ <?php echo number_format($package->price, 0, ',', '.'); ?> </div>
                                    <div class="display-7 vertical-center ms-1"> /ay </div>
                                    <?php
                                } else { ?>
                                    <div class="display-6 fw-bold my-3"> <?php echo $package->price; ?> </div>
                                <?php } ?>
                            </div>


                            <ul class="list-unstyled lh-lg">

                                <li><strong><?php echo $package->person; ?></strong> Personel</li>
                                <li><?php echo $package->firm; ?> Firma</li>
                                <li><?php echo $package->project; ?> Proje</li>
                                <li><?php echo $package->case; ?> Kasa</li>
                                <li>
                                    <?php echo $mission_manage ; ?>
                                  
                                </li>
                                <li></li>
                            </ul>


                            <div class="text-center mt-4">
                                <a href="#" class="btn w-100 choose_package" data-id="<?php echo $package_id; ?>"
                                    data-bs-toggle="modal" data-bs-target="#modal-team">Seç</a>

                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
        <div class="col-12 mt-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Paket Bilgileriniz
                    </h3>
                    <div class="card-actions">
                        <a href="#">
                            Edit configuration<!-- Download SVG icon from http://tabler-icons.io/i/edit -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon ms-1">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                                <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                                <path d="M16 5l3 3"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-2">Paket Adı:</dt>
                        <dd class="col-10"><?php echo $package->name ?? ''; ?></dd>
                        <dt class="col-2">Başlangıç Tarihi:</dt>
                        <dd class="col-10"><?php echo Date::dmY($user_package->start_date ?? ''); ?></dd>
                        <dt class="col-2">Bitiş Tarihi:</dt>
                        <dd class="col-10"><?php echo Date::dmY($user_package->end_date ?? ''); ?></dd>
                        <dt class="col-2">Ödeme Tarihi:</dt>
                        <dd class="col-10"><?php echo ($user_package->created_at ?? ''); ?></dd>
                    </dl>
                </div>
            </div>
        </div>


        <div class="row mt-3">

            <div class="accordion" id="accordion-example">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-1">

                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapse-1" aria-expanded="false">
                            Hesabımı Sil!
                        </button>
                    </h2>
                    <div id="collapse-1" class="accordion-collapse collapse" data-bs-parent="#accordion-example">
                        <div class="accordion-body pt-0">
                            <ul>
                                <li> Puantaj tablosu verileri</li>
                                <li> Kullanıcı Tablosu verileri</li>
                                <li>Projeler- Hareketleri ile birlikte(Ödeme-personel vb..)</li>
                                <li>Personeller (Tüm işlemleri ile birlikte)</li>
                                <li>Finansal İşlemler</li>
                            </ul>
                            <button class="btn btn-danger">Hesabımı Sil</button>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapse-2" aria-expanded="false">
                            Hesabımı Dondur!
                        </button>
                    </h2>
                    <div id="collapse-2" class="accordion-collapse collapse" data-bs-parent="#accordion-example">
                        <div class="accordion-body pt-0">
                            <ul>
                                <li>
                                    Geri dönüş yapmak üzere bir süreliğine hesabınızı dondurmak isterseniz bu butonu
                                    kullanın

                                </li>
                            </ul>
                            <button class="btn btn-warning">Hesabımı Dondur</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<?php include_once "pricing-modal.php" ?>