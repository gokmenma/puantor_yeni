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
        $output = '<ul class="nav grid">';

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
            <li class="nav-item" style="min-width:200px">
                <div class="user-block" >
                    <span class="avatar" data-tooltip="' . $puantaj_saati . ' Saat"  data-id="' . $result["id"] . '" style="background-color:' . htmlspecialchars($result["ArkaPlanRengi"])
                . ';color:' . $result["FontRengi"] . '">' . htmlspecialchars($result["PuantajKod"]) . '</span>
                    <span class="head-title">' . htmlspecialchars($result["PuantajAdi"]) . '</span>
                    <p class="description">' . htmlspecialchars($result["Turu"]) . '</p>
                </div>
            </li>';
        }

        // Kapanış HTML
        $output .= '</ul>';

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