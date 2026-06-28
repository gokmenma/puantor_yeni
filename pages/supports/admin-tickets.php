<?php

require_once 'Model/SupportsModel.php';
require_once 'App/Helper/date.php';

use App\Helper\Date;
use App\Helper\Security;

// Yetki kontrolü (Sadece superadmin girebilir)
if (($_SESSION['user']->superadmin ?? 0) != 1) {
    header("Location: index.php?p=authorize");
    exit();
}

$Supports = new SupportsModel();
$supports = $Supports->getAllSupportsForAdmin();

// İstatistikler
$total_tickets = count($supports);
$open_tickets = 0;
$closed_tickets = 0;

foreach ($supports as $s) {
    if ($s->status == 0) {
        $open_tickets++;
    } else {
        $closed_tickets++;
    }
}

?>
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center mb-3">
                <div class="col">
                    <h2 class="page-title">
                        Destek Talepleri Yönetimi
                    </h2>
                </div>
            </div>

            <!-- Özet Kartlar -->
            <div class="row row-cards mb-4">
                <!-- Toplam Talep Card -->
                <div class="col-sm-6 col-lg-4">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-primary text-white avatar">
                                        <i class="ti ti-headset" style="font-size: 1.25rem;"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">
                                        <?php echo $total_tickets; ?> Toplam Talep
                                    </div>
                                    <div class="text-secondary small">
                                        Sistemdeki tüm bildirimler
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Bekleyen (Açık) Card -->
                <div class="col-sm-6 col-lg-4">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-red text-white avatar">
                                        <i class="ti ti-clock" style="font-size: 1.25rem;"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">
                                        <?php echo $open_tickets; ?> Bekleyen (Açık)
                                    </div>
                                    <div class="text-secondary small">
                                        Cevaplanması gereken talepler
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Tamamlanan (Kapalı) Card -->
                <div class="col-sm-6 col-lg-4">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-success text-white avatar">
                                        <i class="ti ti-circle-check" style="font-size: 1.25rem;"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">
                                        <?php echo $closed_tickets; ?> Tamamlanan (Kapalı)
                                    </div>
                                    <div class="text-secondary small">
                                        Çözülmüş destek bildirimleri
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DataTable Kartı -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Destek Talebi Listesi</h3>
                </div>
                <div class="table-responsive p-3">
                    <table id="admin-tickets-table" class="table card-table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th>Talep No</th>
                                <th>Kullanıcı Adı</th>
                                <th>E-posta</th>
                                <th>Konu</th>
                                <th>Oluşturma Tarihi</th>
                                <th>Durum</th>
                                <th class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supports as $support): ?>
                                <tr>
                                    <td>#<?php echo $support->id; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($support->user_name ?? 'Bilinmeyen Kullanıcı'); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($support->user_email ?? '-'); ?></td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;" title="<?php echo htmlspecialchars($support->subject); ?>">
                                            <?php echo htmlspecialchars($support->subject); ?>
                                        </div>
                                    </td>
                                    <td><?php echo Date::dmY($support->created_at); ?></td>
                                    <td>
                                        <?php if ($support->status == 0): ?>
                                            <span class="badge bg-red-lt">Açık</span>
                                        <?php else: ?>
                                            <span class="badge bg-green-lt">Kapalı</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary route-link" data-page="supports/admin-ticket-view&id=<?php echo Security::encrypt($support->id); ?>">
                                            <i class="ti ti-message-dots me-1"></i> Görüntüle / Cevapla
                                        </button>
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
