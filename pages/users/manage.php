<?php
require_once "Model/UserModel.php";
require_once "Model/Projects.php";
require_once "Model/Persons.php";
require_once "App/Helper/security.php";

use App\Helper\Security;

$userObj = new UserModel();
$projectsObj = new Projects();
$personsObj = new Persons();

//Sayfa başlarında eklenecek alanlar
$perm->checkAuthorize("user_add_update");
$id = isset($_GET["id"]) ? Security::decrypt($_GET['id']) : 0;
$new_id = isset($_GET["id"]) ? $_GET['id'] : 0;

//Eğer url'den id yazılmışsa veya id boş ise projeler sayfasına gider
if($id == null && isset($_GET['id'])) {
    header("Location: /index.php?p=users/list");
    exit;
}

$user = $userObj->find($id);
$projects = $projectsObj->getProjectsByFirm($_SESSION["firm_id"]);
$persons = $personsObj->getPersonsByFirm($_SESSION["firm_id"]);

$Auths->checkFirm();

require_once "App/Helper/teams.php";
$teamsHelper = new Teams();

// Load job groups for quick lookup using secure database access method
$all_job_groups = [];
try {
    if (isset($personsObj) && method_exists($personsObj, 'getDb')) {
        $q_jg = $personsObj->getDb()->prepare("SELECT id, group_name FROM job_groups WHERE firm_id = ?");
        $q_jg->execute([$_SESSION['firm_id']]);
        foreach ($q_jg->fetchAll(PDO::FETCH_OBJ) as $jg) {
            $all_job_groups[$jg->id] = $jg->group_name;
        }
    }
} catch (Exception $e) {}


// Load person-projects mappings for quick lookup
$all_person_projects = [];
try {
    $q_pp = $personsObj->getDb()->prepare("
        SELECT pp.person_id, p.project_name 
        FROM project_person pp 
        JOIN projects p ON pp.project_id = p.id 
        WHERE p.firm_id = ?
    ");
    $q_pp->execute([$_SESSION['firm_id']]);
    foreach ($q_pp->fetchAll(PDO::FETCH_OBJ) as $row) {
        $ids = explode(',', $row->person_id);
        foreach ($ids as $pid) {
            $trimmed = trim($pid);
            if (!empty($trimmed)) {
                $all_person_projects[$trimmed][] = $row->project_name;
            }
        }
    }
} catch (Exception $e) {}
?>
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <?php echo ($id > 0) ? "Güncelle" : "Yeni Kullanıcı"; ?>
                    </h2>
                </div>

                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-outline-secondary route-link" data-page="users/list">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="kullanici_kaydet">
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
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#tabs-home-3" class="nav-link active" data-bs-toggle="tab" aria-selected="true"
                                    role="tab"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
                                    <i class="ti ti-home icon me-2"></i>
                                    Genel Bilgiler
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tabs-profile-3" class="nav-link" data-bs-toggle="tab" aria-selected="false"
                                    tabindex="-1"
                                    role="tab">
                                    <i class="ti ti-users icon me-2"></i>
                                    Sorumlu Olduğu Personeller
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <form action="" id="userForm">
                            <input type="hidden" id="user_id" value="<?php echo $new_id ?>">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="tabs-home-3" role="tabpanel">
                                    <?php include_once "content/0-home.php"; ?>
                                </div>
                                <div class="tab-pane" id="tabs-profile-3" role="tabpanel">
                                    <div class="row mb-3 mt-2">
                                        <div class="col-md-12">
                                            <div class="card">
                                                <div class="card-header bg-light py-2">
                                                    <h4 class="card-title mb-0">Sorumlu Olduğu Personeller ve Yetkili Modüller</h4>
                                                </div>
                                                <div class="table-responsive">
                                                    <table id="responsible-persons-table" class="table card-table table-vcenter table-hover text-nowrap datatable">
                                                        <thead class="sticky-top bg-white" style="z-index: 10;">
                                                            <tr>
                                                                <th style="width: 40px;" class="text-center no-export"><input type="checkbox" class="form-check-input select-all-persons-modules" data-tooltip="Tümünü Seç / Kaldır"></th>
                                                                <th>Personel Adı Soyadı</th>
                                                                <th>Görevi / Meslek</th>
                                                                <th>Ekibi</th>
                                                                <th>Projeleri</th>
                                                                <th class="text-center" style="width: 100px;">
                                                                    <label class="form-check mb-0 d-inline-flex align-items-center cursor-pointer" data-tooltip="Tüm personeller için Puantaj modülünü seç/kaldır">
                                                                        <input type="checkbox" class="form-check-input me-2 check-all-module" data-target="puantaj-checkbox" checked>
                                                                        <span class="font-weight-bold">Puantaj</span>
                                                                    </label>
                                                                </th>
                                                                <th class="text-center" style="width: 100px;">
                                                                    <label class="form-check mb-0 d-inline-flex align-items-center cursor-pointer" data-tooltip="Tüm personeller için Bordro modülünü seç/kaldır">
                                                                        <input type="checkbox" class="form-check-input me-2 check-all-module" data-target="bordro-checkbox" checked>
                                                                        <span class="font-weight-bold">Bordro</span>
                                                                    </label>
                                                                </th>
                                                                <th class="text-center" style="width: 100px;">
                                                                    <label class="form-check mb-0 d-inline-flex align-items-center cursor-pointer" data-tooltip="Tüm personeller için Personel Listesi modülünü seç/kaldır">
                                                                        <input type="checkbox" class="form-check-input me-2 check-all-module" data-target="personel-checkbox" checked>
                                                                        <span class="font-weight-bold">Personel</span>
                                                                    </label>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php 
                                                            // Parse existing mapping
                                                            $saved_map = [];
                                                            if (!empty($user->responsible_persons)) {
                                                                $saved_map = json_decode($user->responsible_persons, true);
                                                                if (!is_array($saved_map)) {
                                                                    $saved_map = [];
                                                                }
                                                            }
                                                            
                                                            foreach ($persons as $person) {
                                                                // If mapping is empty (new user or first time), check all by default
                                                                if (empty($user->responsible_persons)) {
                                                                    $has_puantaj = true;
                                                                    $has_bordro = true;
                                                                    $has_personel = true;
                                                                } else {
                                                                    $has_puantaj = isset($saved_map[$person->id]) && in_array('puantaj', $saved_map[$person->id]);
                                                                    $has_bordro = isset($saved_map[$person->id]) && in_array('bordro', $saved_map[$person->id]);
                                                                    $has_personel = isset($saved_map[$person->id]) && in_array('personel', $saved_map[$person->id]);
                                                                }
                                                                ?>
                                                                <tr>
                                                                    <td class="text-center">
                                                                        <input type="checkbox" class="form-check-input person-all-modules-checkbox" data-tooltip="Bu personel için tüm modülleri seç/kaldır" <?php echo ($has_puantaj && $has_bordro && $has_personel) ? 'checked' : ''; ?>>
                                                                    </td>
                                                                    <td>
                                                                        <div class="font-weight-bold text-dark"><?php echo $person->full_name; ?></div>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (!empty($person->job)): ?>
                                                                            <span class="badge bg-blue-lt py-1 px-2"><i class="ti ti-briefcase me-1"></i><?php echo $person->job; ?></span>
                                                                        <?php else: ?>
                                                                            <span class="text-muted small">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php 
                                                                        $team_name = !empty($person->ekip) ? $person->ekip : (!empty($person->team_id) ? $teamsHelper->getTeamName($person->team_id) : '');
                                                                        if (!empty($team_name)): ?>
                                                                            <span class="badge bg-orange-lt py-1 px-2"><i class="ti ti-binary me-1"></i><?php echo $team_name; ?></span>
                                                                        <?php else: ?>
                                                                            <span class="text-muted small">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex flex-wrap gap-1">
                                                                            <?php 
                                                                            $p_list = isset($all_person_projects[$person->id]) ? $all_person_projects[$person->id] : [];
                                                                            if (!empty($p_list)): 
                                                                                foreach ($p_list as $p_name): ?>
                                                                                    <span class="badge bg-purple-lt py-1 px-2"><i class="ti ti-building me-1"></i><?php echo $p_name; ?></span>
                                                                                <?php endforeach; 
                                                                            else: ?>
                                                                                <span class="text-muted small">-</span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <input type="checkbox" name="responsible_map[<?php echo $person->id; ?>][]" value="puantaj" class="form-check-input puantaj-checkbox" <?php echo $has_puantaj ? 'checked' : ''; ?>>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <input type="checkbox" name="responsible_map[<?php echo $person->id; ?>][]" value="bordro" class="form-check-input bordro-checkbox" <?php echo $has_bordro ? 'checked' : ''; ?>>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <input type="checkbox" name="responsible_map[<?php echo $person->id; ?>][]" value="personel" class="form-check-input personel-checkbox" <?php echo $has_personel ? 'checked' : ''; ?>>
                                                                    </td>
                                                                </tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <script>
                                        $(window).on('load', function() {
                                            // DataTable is initialized by the global app.js, we just get the API instance
                                            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#responsible-persons-table')) {
                                                const respTable = $('#responsible-persons-table').DataTable();

                                                // 1. Master Toggle for specific individual module columns
                                                $(document).on('change', '.check-all-module', function() {
                                                    var targetClass = $(this).data('target');
                                                    respTable.$('.' + targetClass).prop('checked', this.checked);
                                                    
                                                    // Refresh all persons grouped checkbox state
                                                    respTable.$('tr').each(function(){
                                                        const $r = $(this);
                                                        const all = $r.find('.puantaj-checkbox').prop('checked') && 
                                                                    $r.find('.bordro-checkbox').prop('checked') && 
                                                                    $r.find('.personel-checkbox').prop('checked');
                                                        $r.find('.person-all-modules-checkbox').prop('checked', all);
                                                    });
                                                });

                                                // 2. Global Master Toggle for everything
                                                $(document).on('change', '.select-all-persons-modules', function() {
                                                    respTable.$('input[type="checkbox"]').prop('checked', this.checked);
                                                });

                                                // 3. Individual Person Row Multi-Select toggle
                                                $(document).on('change', '.person-all-modules-checkbox', function() {
                                                    const $row = $(this).closest('tr');
                                                    $row.find('.puantaj-checkbox, .bordro-checkbox, .personel-checkbox').prop('checked', this.checked);
                                                });

                                                // 4. Auto update individual row master when subcheckboxes toggle
                                                $(document).on('change', '.puantaj-checkbox, .bordro-checkbox, .personel-checkbox', function() {
                                                    const $row = $(this).closest('tr');
                                                    const all = $row.find('.puantaj-checkbox').prop('checked') && 
                                                                $row.find('.bordro-checkbox').prop('checked') && 
                                                                $row.find('.personel-checkbox').prop('checked');
                                                    $row.find('.person-all-modules-checkbox').prop('checked', all);
                                                });
                                            }
                                        });
                                    </script>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>