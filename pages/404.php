<div class="container-xl">
  <div class="page page-center">
    <div class="container-tight py-4">
      <div class="empty">
        <div>
          <img style="width: 400px; height: 400px;" src="static/illustrations/not-found.svg" alt="">
        </div>
        <div class="empty-header">404</div>
        <p class="empty-title">Oops… Bir hata oluştu!</p>
        <p class="empty-subtitle text-secondary">
          Üzgünüz, aradığınız sayfa mevcut değil.
        </p>
        <div class="empty-action">
          <a onclick="goBack()" class="btn btn-primary">
           <i class="ti ti-arrow-left icon"></i>
            Geri Dön
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function goBack() {
  window.history.back();
}
</script>