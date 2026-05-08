<?php
require_once "App/Helper/date.php";
require_once "App/Helper/financial.php";
use App\Helper\Date;

$financialHelper = new Financial();
?>


<div class="modal modal-blur fade" id="wage_cut_modal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center pb-0">
                <!-- Download SVG icon from http://tabler-icons.io/i/alert-triangle -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon mb-2 text-danger icon-lg">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M12 9v4"></path>
                    <path
                        d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z">
                    </path>
                    <path d="M12 16h.01"></path>
                </svg>
                <h3 id="person_name_wage_cut">
                </h3>
                <p>
                    <small class="text-danger">Kesinti eklemek için aşağıdaki bilgileri doldurunuz</small>
                </p>
            </div>
            <div class="container ps-4 pe-4 py-4">
                <form action="" id="wage_cut_modalForm">
                    <input type="hidden" class="form-control" name="wage_cut_id" value="0">
                    <input type="hidden" class="form-control" name="person_id_wage_cut" id="person_id_wage_cut"
                        value="0">

                    <div class="text-secondary mt-3">
                        <label for="">Kesinti Adı</label>
                        <input type="text" name="wage_cut_type" class="form-control mt-1">
                    </div>
                    <div class="row">


                        <div class="text-secondary mt-3">
                            <label for="">Kesinti Miktarı</label>
                            <input type="text" name="wage_cut_amount" class="form-control mt-1 money">
                        </div>


                    </div>

                    <div class="text-secondary mt-3">
                        <label for="">Kesinti Yapılacak Dönem</label>
                        <div class="row d-flex">
                            <div class="col-6">

                                <?php echo Date::getMonthsSelect("wage_cut_month"); ?>
                            </div>
                            <div class="col-6">

                                <?php echo Date::getYearsSelect("wage_cut_year"); ?>

                            </div>
                        </div>
                    </div>

                    <div class="text-secondary mt-3">
                        <label for="">Açıklama</label>
                        <textarea name="wage_cut_description" class="form-control mt-1"
                            placeholder="Kesinti hakkında açıklama yazınız"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col"><a href="#" class="btn w-100" data-bs-dismiss="modal">
                                Vazgeç
                            </a></div>
                        <div class="col">
                            <a href="#" class="btn btn-danger w-100" id="wage_cut_addButton">
                                Kesinti Ekle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>