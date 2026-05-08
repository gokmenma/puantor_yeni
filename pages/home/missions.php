<?php
require_once ROOT . "/Model/Missions.php";
require_once ROOT . "/Model/MissionHeaders.php";
require_once ROOT . "/Model/SettingsModel.php";
require_once ROOT . "/Model/UserModel.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/users.php";

use App\Helper\Helper;
use App\Helper\Security;

$userHelper = new UserHelper();


$userObj = new UserModel();
$settingsObj = new SettingsModel();

$missionObj = new Missions();
$headerObj = new MissionHeaders();

$is_done_visible = $settingsObj->getSettings("completed_tasks_visible")->set_value ?? 0;
$visible_button_text = $is_done_visible == 1 ? "Gizle" : "Göster";
$visible_button_icon = $is_done_visible == 1 ? "eye-off" : "eye";
$missionHeaders = $missionObj->getHeaderFromMissionsFirm($firm_id);



// Helper::dd($m_process);

if (!$Auths->Authorize("home_page_mission_view")) {
    Helper::authorizePage();
    return;
}

//Giriş kayıtlarını getir
$loginRecords = $userObj->getLoginLogs($_SESSION["user"]->id);

?>



<div class="table-responsive">

    <div class="page-wrapper">
        <!-- Page header -->
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <h2 class="page-title">
                            Görevler
                        </h2>
                    </div>
                    <!-- Page title actions -->
                    <div class="col-auto ms-auto d-print-none">
                        <a href="#" class="btn btn-primary" id="done-show">
                            <i class="ti ti-<?php echo $visible_button_icon ?> icon me-1"></i>
                            <?php echo $visible_button_text; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .responsive {
                overflow: auto;
                white-space: nowrap;
            }

            .card-container {
                display: inline-block;
                vertical-align: top;
                margin-right: 10px;
            }

            .card {
                overflow: auto;
                white-space: wrap;
                border-radius: 6px;
                box-shadow: 0 0 4px rgba(0, 0, 0, 0.1);
                transition: all 0.3s;

            }

            .pointer {
                cursor: pointer;
            }

            .card-title {
                display: flex;
                align-items: center;
            }

            .form-colorinput {
                margin-right: 10px;
            }

            .avatar-xs {
                padding: 0 0.9rem;
            }

            .done {
                color: #28a745
            }

            .no-done {
                color: #EF5A6F
            }
        </style>
        <!-- Page body -->
        <div class="page-body">
            <div class="container-xl d-flex">
                <div class="col-8">
                    <div class="responsive d-flex" id="sortable">
                        <?php foreach ($missionHeaders as $item) { ?>
                            <?php
                            $mission_header_name = $headerObj->getMissionHeader($item->header_id)->header_name;
                            //Eğer bu başlığın tamamlanmamış görevi yoksa başlığı ve is_done_visible = 1 ise gizle, değilse göster
                            if ($is_done_visible == 0 && $missionObj->getUncompletedMissions($item->header_id)->count == 0)
                                $display = "none";
                            else
                                $display = "block";

                            if ($missionObj->getUncompletedMissions($item->header_id)->count == 0)
                                $color = "done";
                            else
                                $color = "no-done";

                            ?>

                            <div class="col-md-2 col-lg-2 me-3 header-item" style="display:<?php echo $display ?>"
                                id="<?php echo $item->header_id; ?>">
                                <div class="d-flex pointer">
                                    <i class="ti ti-drag-drop icon me-1 "></i>
                                    <h3 class="mb-3"> <?php echo $mission_header_name; ?></h3>
                                </div>
                                <?php
                                $missions = $missionObj->getMissionsByHeader($item->header_id);

                                ?>
                                <?php foreach ($missions as $mission) {
                                    $id = Security::encrypt($mission->id);
                                    $checked = $mission->status == 1 ? "checked" : "";
                                    $color = $mission->status == 1 ? "done" : "no-done";
                                    if ($is_done_visible == 0 && $mission->status == 1)
                                        $display = "none";
                                    else
                                        $display = "block";

                                    if ($mission->priority == 1) {
                                        $card_status = "bg-green";
                                    } elseif ($mission->priority == 2) {
                                        $card_status = "bg-yellow";

                                    } elseif ($mission->priority == 3) {
                                        $card_status = "bg-red";
                                    }

                                    ?>


                                    <div class="mb-2 mission-items" style="display:<?php echo $display; ?>"
                                        id="<?php echo $mission->id; ?>">
                                        <div class="row row-cards">
                                            <div class="col-12">
                                                <div class="card card-sm">
                                                    <div class="card-status-top <?php echo $card_status ?>"></div>
                                                    <div class="card-body">

                                                        <h3 class="card-title <?php echo $color ?>">
                                                            <label class="form-colorinput form-colorinput-light"
                                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                                data-bs-custom-class="custom-tooltip"
                                                                data-bs-title="Tamamlandı Yap">

                                                                <input name="color" type="checkbox" value="white"
                                                                    data-mission-id="<?php echo $mission->id; ?>"
                                                                    class="form-colorinput-input done-mission" <?php echo $checked ?>>
                                                                <span class="form-colorinput-color bg-white"></span>
                                                            </label>
                                                            <?php echo $mission->name; ?>
                                                        </h3>

                                                        <div class="card-subtitle text-muted">
                                                            <span><?php echo $mission->start_date . "-" . $mission->end_date; ?></span>
                                                        </div>
                                                        <div class="card-subtitle text-muted">
                                                            <span><?php echo $mission->description; ?></span>
                                                        </div>
                                                        <div class="mt-4">
                                                            <div class="row">
                                                                <div class="col">
                                                                    <div class="avatar-list avatar-list-stacked">
                                                                        <?php
                                                                        $user_ids = $mission->user_ids ?? [];
                                                                        $user_ids = explode(",", $user_ids);
                                                                        $user_names = $userHelper->getUsersName($mission->user_ids);


                                                                        foreach ($user_ids as $user_id) {
                                                                            $user = $userObj->getUser($user_id);
                                                                            ?>
                                                                            <span class="avatar avatar-xs rounded"
                                                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                                                data-bs-custom-class="custom-tooltip"
                                                                                data-bs-title="<?php echo $user->full_name ?? '' ?>">
                                                                                <?php echo Helper::getInitials($user->full_name ?? 0); ?>
                                                                            </span>


                                                                        <?php } ?>
                                                                    </div>


                                                                </div>
                                                                <div class="col-auto text-secondary">
                                                                    <a href="#" class=" route-link"
                                                                        data-page="missions/manage&id=<?php echo $id ?>">
                                                                        <span class="switch-icon-a text-muted">
                                                                            <i class="ti ti-edit icon"></i>
                                                                        </span>

                                                                    </a>

                                                                </div>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                        </div>
                                    </div>

                                <?php } ?> <!-- end of foreach missions -->
                            </div>
                        <?php } ?> <!-- end of foreach m_process -->
                        <?php
                        if (count($missionHeaders) == 0) { ?>
                            <div class="col">
                                <!-- static içindeki add-mission.svg'yi burada göster, Görev ekle yazısı ile beraber -->
                                <img src="./static/illustrations/to-do.avif" height="300" width="300" class="d-block mx-auto" alt="">
                                <h3 class="text-muted text-center">Firma için herhangi bir görev tanımlanmamış</h3>
                            </div>
                        <?php } ?>

                    </div>
                </div>
                <div class="col-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Giriş Kayıtları</h3>
                        </div>
                        <div class="list-group list-group-flush overflow-auto" style="max-height: 35rem">
                            
                            <div class="list-group-item">

                                <?php foreach ($loginRecords as $login) { ?>
                                    <div class="row mb-3">
                                        <div class="col-auto">
                                            <a href="#">
                                                <span class="avatar"
                                                    style="background-image: url(./static/avatars/023f.jpg)"></span>
                                            </a>
                                        </div>
                                        <div class="col text-truncate">
                                            <a href="#" class="text-body d-block">Giriş zamanı :<?php echo $login->login_time; ?></a>
                                            <div class="text-secondary text-truncate mt-n1">İp Adresi : <?php echo $login->ip_address; ?></div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>