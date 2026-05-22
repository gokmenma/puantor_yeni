<?php
require_once "App/Helper/helper.php";
require_once "Model/AbonelerModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;

// Yetki Kontrolü
$perm->checkAuthorize("aboneler_sayfasi");

$abonelerModel = new AbonelerModel();
$subscribers = $abonelerModel->getSubscribers();
?>
<div class="container-xl">
    <!-- Alert component'i dahil et -->
    <?php
    $title = "Aboneler Listesi!";
    $text = "Sistemdeki tüm ana aboneleri (parent_id = 0), aktif paketlerini, başlangıç/bitiş tarihlerini ve kalan gün sürelerini buradan takip edebilirsiniz.";
    require_once 'pages/components/alert.php';
    ?>
    
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aboneler Listesi</h3>
                </div>

                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap datatable" id="aboneTable">
                        <thead>
                            <tr>
                                <th style="width:7%" class="text-center">Sıra</th>
                                <th>Adı Soyadı</th>
                                <th>Email</th>
                                <th>Telefon</th>
                                <th>Aktif Paket</th>
                                <th>Başlangıç Tarihi</th>
                                <th>Bitiş Tarihi</th>
                                <th>Kalan Gün</th>
                                <th style="width:10%">Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($subscribers as $sub):
                                // Initials for Avatar
                                $words = explode(" ", trim($sub->full_name));
                                $initials = "";
                                foreach ($words as $w) {
                                    $initials .= mb_substr($w, 0, 1, 'UTF-8');
                                }
                                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'));
                                if (empty($initials)) {
                                    $initials = "AB";
                                }

                                // Status and package formatting
                                $status = $sub->abonelik_durumu;
                                $paket_adi = $sub->paket_adi;
                                
                                // Format dates
                                $baslangic = $sub->baslangic_tarihi ? date('d.m.Y', strtotime($sub->baslangic_tarihi)) : '-';
                                $bitis = $sub->bitis_tarihi ? date('d.m.Y', strtotime($sub->bitis_tarihi)) : '-';

                                // Calculate remaining days if active and bitis_tarihi exists
                                $kalan_gun_str = '-';
                                $kalan_gun_class = 'text-secondary';
                                if ($status == 'aktif' && $sub->bitis_tarihi) {
                                    $today = new DateTime(date('Y-m-d'));
                                    $end_date = new DateTime($sub->bitis_tarihi);
                                    if ($today <= $end_date) {
                                        $interval = $today->diff($end_date);
                                        $days = (int)$interval->format('%r%a');
                                        if ($days == 0) {
                                            $kalan_gun_str = 'Bugün son gün';
                                            $kalan_gun_class = 'badge bg-warning text-warning-fg';
                                        } else {
                                            $kalan_gun_str = $days . ' gün kaldı';
                                            $kalan_gun_class = $days <= 5 ? 'badge bg-warning text-warning-fg' : 'badge bg-success text-success-fg';
                                        }
                                    } else {
                                        $kalan_gun_str = 'Süresi doldu';
                                        $kalan_gun_class = 'badge bg-danger text-danger-fg';
                                    }
                                }

                                // Status Badge
                                $status_badge = '';
                                if ($status == 'aktif') {
                                    $status_badge = '<span class="badge bg-success text-success-fg">Aktif</span>';
                                } elseif ($status == 'sona_erdi') {
                                    $status_badge = '<span class="badge bg-secondary text-secondary-fg">Sona Erdi</span>';
                                } elseif ($status == 'iptal') {
                                    $status_badge = '<span class="badge bg-danger text-danger-fg">İptal Edildi</span>';
                                } elseif ($status == 'onay_bekliyor') {
                                    $status_badge = '<span class="badge bg-warning text-warning-fg">Onay Bekliyor</span>';
                                } elseif ($status == 'beklemede') {
                                    $status_badge = '<span class="badge bg-info text-info-fg">Beklemede</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-light text-secondary">Abonelik Yok</span>';
                                }
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-sm me-2 bg-blue-lt text-blue font-weight-bold"><?php echo htmlspecialchars($initials); ?></span>
                                            <div class="font-weight-medium"><?php echo htmlspecialchars($sub->full_name); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($sub->email); ?></td>
                                    <td><?php echo htmlspecialchars($sub->phone ?? '-'); ?></td>
                                    <td>
                                        <?php if ($paket_adi): ?>
                                            <span class="font-weight-medium text-primary"><?php echo htmlspecialchars($paket_adi); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Aktif Paket Yok</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $baslangic; ?></td>
                                    <td><?php echo $bitis; ?></td>
                                    <td>
                                        <span class="<?php echo $kalan_gun_class; ?>">
                                            <?php echo $kalan_gun_str; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $status_badge; ?></td>
                                </tr>
                                <?php
                                $i++;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
