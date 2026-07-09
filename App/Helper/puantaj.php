<?php
!defined('ROOT') ? define('ROOT', $_SERVER['DOCUMENT_ROOT']) : null;
require_once ROOT ."/Database/db.php";
require_once ROOT ."/Model/Projects.php";
require_once ROOT ."/Model/SettingsModel.php";

require_once ROOT. "/App/Helper/projects.php";



use Database\Db;
class puantajHelper extends Db
{
    protected $table = 'puantajturu';
    protected $Settings;
    public function __construct()
    {
        parent::__construct();
        $this->Settings = new SettingsModel();
    }
    public function getPuantajTuruList($turu)
    {

        //$Settings = new SettingsModel();
        $work_hour = $this->Settings->getSettings("work_hour")->set_value ?? 8;

        // SQL sorgusu ve verilerin alınması
        $sql = $this->db->prepare("SELECT * FROM puantajturu WHERE Turu = ? ORDER BY PuantajSaati ");
        $sql->execute(array($turu));

        // Başlangıç HTML
        $output = '<div class="row g-2 w-100 m-0" style="max-width: 580px;">';

        // Veritabanından gelen verilerle liste öğeleri oluşturma
        while ($result = $sql->fetch(PDO::FETCH_ASSOC)) {
            //if ($result["Turu"] != "Saatlik") {
                $saat = $result["EklenecekSaat"];
                $operant = $result["operant"];
                $puantaj_saati = $this->calculatePuantajSaati($saat, $work_hour, $operant);
            // } else {
            //     $puantaj_saati = $result["PuantajSaati"];
            // }

            $output .= '
            <div class="col-6 nav-item cursor-pointer p-1" style="list-style: none;">
                <div class="user-block d-flex align-items-center justify-content-between w-100 p-2 border rounded" style="background: var(--tblr-bg-surface); min-height: 54px;">
                    <div class="d-flex align-items-center flex-grow-1">
                        <span class="avatar" data-tooltip="' . $puantaj_saati . ' Saat"  data-id="' . $result["id"] . '" style="background-color:' . htmlspecialchars($result["ArkaPlanRengi"])
                . ';color:' . $result["FontRengi"] . '; width: 34px; height: 34px; line-height: 34px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">' . htmlspecialchars($result["PuantajKod"]) . '</span>
                        <div class="ms-2" style="line-height: 1.3;">
                            <span class="head-title d-block fw-semibold" style="font-size: 12.5px; color: var(--tblr-body-color);">' . htmlspecialchars($result["PuantajAdi"]) . '</span>
                            <span class="description text-muted" style="font-size: 10.5px;">' . htmlspecialchars($result["Turu"]) . '</span>
                        </div>
                    </div>
                    <span class="favorite-star-btn cursor-pointer p-1 ms-2" data-id="' . $result["id"] . '" style="line-height: 1; display: inline-flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="star-svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="#ccc" fill="none" stroke-linecap="round" stroke-linejoin="round" style="transition: fill 0.2s, stroke 0.2s; pointer-events: none;">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
                        </svg>
                    </span>
                </div>
            </div>';
        }

        // Kapanış HTML
        $output .= '</div>';

        return $output;
    }

    function puantajClass($turu, $project = 0, $puantaj_project = "")
    {
        $projectObj = new ProjectHelper();

        $pcq = $this->db->prepare("SELECT * FROM puantajturu WHERE id = ?");
        $pcq->execute(array($turu));
        $result = $pcq->fetch(PDO::FETCH_ASSOC);

        $tooltip = $projectObj->getProjectName($puantaj_project);

        if ($result) {
            if ($result["PuantajKod"] == "HT") {
                $backcolor = $result["ArkaPlanRengi"];
                $color = $result["FontRengi"];
                $selected = "";
            } else {
                if ($puantaj_project != $project) {
                    $backcolor = "#bbb";
                    $color = "#666";
                    $selected = "selected";
                } else {
                    $backcolor = $result["ArkaPlanRengi"];
                    $color = $result["FontRengi"];
                    $selected = "";
                }
            }
            echo "<td class='gun noselect $selected' data-tooltip ='$tooltip' data-change='false'  data-project='" . $puantaj_project . "' data-id=" . $result["id"] . " style='background:" . $backcolor . ";color:" . $color . "'>" . $result["PuantajKod"] . "</td>";
        } else {
            echo "<td class='gun noselect' data-change='false' data-project='0'></td>";
        }
    }

    // gelen operanta göre işlem yap
 
    function calculatePuantajSaati($saat, $work_hour, $operant)
    {
        // Sayısal olmayan değerleri kontrol et
        // if (!is_numeric($saat) || !is_numeric($work_hour)) {
        //     return 'Non-numeric value encountered';
        // }
        $work_hour = str_replace(',', '.', $work_hour);
        $saat = str_replace(',', '.', $saat);
    
        $puantaj_saati = 0;
    
        switch ($operant) {
            case '+':
                $puantaj_saati = $saat + $work_hour;
                break;
            case '-':
                $puantaj_saati = $saat - $work_hour;
                break;
            case '*':
                $puantaj_saati = $saat * $work_hour;
                break;
            case '/':
                if ($work_hour != 0) {
                    $puantaj_saati = $saat / $work_hour;
                } else {
                    return 'Division by zero error';
                }
                break;
            default:
                return 'Invalid operant';
        }
    
        return $puantaj_saati;
    }




}