<?php
require_once "Model/FeedBackModel.php";
$id = $_GET['id'] ?? 0;

$pageTitle = $id == 0 ? "Görüş ve Öneri" : "Görüş ve Öneri";

?>
<div class="page-wrapper">
    <div class="container-xl">
        <div class="alert alert-info bg-white alert-dismissible mt-3" role="alert">
            <div class="d-flex">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon alert-icon">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                        <path d="M12 9h.01"></path>
                        <path d="M11 12h1v4h1"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="alert-title">Görüş ve Öneri!</h4>
                    <div class="text-secondary">Sizlerin görüş ve önerileri, hizmetlerimizi geliştirmek ve deneyiminizi
                        daha iyi hale getirmek için bizim için son derece önemlidir. Bu form aracılığıyla bize
                        düşüncelerinizi, önerilerinizi veya geri bildirimlerinizi iletebilirsiniz.</div>
                </div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    </div>
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <?php echo $pageTitle; ?>
                    </h2>
                </div>

                <!-- Page title actions -->

                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="saveFeedBack">
                        <i class="ti ti-send icon me-2"></i>
                        Gönder
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
                        <!-- **************FORM**************** -->
                        <form action="" id="FeedBackForm">
                            <!--********** HIDDEN ROW************** -->
                            <div class="row d-none">
                                <div class="col-md-4">
                                    <input type="text" name="id" class="form-control" value="<?php echo $id ?? '' ?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="action" value="saveFeedBack" class="form-control">
                                </div>
                            </div>
                            <!--********** HIDDEN ROW************** -->
                            <div class="container p-5">


                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="">Adı</label>
                                        <input type="text" name="user_name" disabled readonly class="form-control"
                                            value="<?php echo $user->full_name ?? '' ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="">Email</label>
                                        <input type="text" name="email" disabled readonly class="form-control"
                                            value="<?php echo $user->email ?? '' ?>">
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label for="">Konu</label>
                                        <input type="text" name="subject" class="form-control" required>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label for="">Mesaj</label>
                                        <textarea name="message" class="form-control"
                                            style="min-height: 200px;" required></textarea>
                                    </div>
                                </div>
                            </div>

                        </form>
                        <!-- **************FORM**************** -->

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>