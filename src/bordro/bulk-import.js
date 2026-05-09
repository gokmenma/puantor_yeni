$(document).ready(function () {
  // --- TEMPLATE DOWNLOAD HANDLING ---
  function getFilterParams(type) {
    let projectId = $("#projects").val() || 0;
    let month = $("#months").val() || "";
    let year = $("#year").val() || "";
    return `pages/payroll/xls/bulk-template.php?type=${type}&project_id=${projectId}&month=${month}&year=${year}`;
  }

  $(document).on("click", "#download-income-template", function (e) {
    e.preventDefault();
    window.location.href = getFilterParams("income");
  });

  $(document).on("click", "#download-wage-cut-template", function (e) {
    e.preventDefault();
    window.location.href = getFilterParams("wage_cut");
  });

  // --- DROPZONE INTERACTION ENGINE ---
  setupDragAndDrop("income");
  setupDragAndDrop("wage-cut");

  function setupDragAndDrop(type) {
    const zone = $(`#dropzone-${type}`);
    const input = $(`#bulk-${type}-file`);
    const preview = $(`#preview-${type}`);
    const nameSpan = $(`#preview-name-${type}`);
    const sizeSpan = $(`#preview-size-${type}`);
    const removeBtn = $(`#remove-${type}`);
    const uploadBtn = $(`#btn-upload-${type}`);

    // Click to select
    zone.on("click", function () {
      input.click();
    });

    // Drag-over styling
    zone.on("dragover dragenter", function (e) {
      e.preventDefault();
      e.stopPropagation();
      zone.addClass("dragover");
    });

    zone.on("dragleave drop", function (e) {
      e.preventDefault();
      e.stopPropagation();
      zone.removeClass("dragover");
    });

    // Handle Drop
    zone.on("drop", function (e) {
      const files = e.originalEvent.dataTransfer.files;
      if (files.length) {
        input[0].files = files;
        handleFileSelection(files[0], type);
      }
    });

    // Handle standard change
    input.on("change", function () {
      if (this.files.length) {
        handleFileSelection(this.files[0], type);
      }
    });

    // Handle Remove
    removeBtn.on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      input.val("");
      preview.hide();
      zone.show();
      uploadBtn.prop("disabled", true);
    });
  }

  function handleFileSelection(file, type) {
    const zone = $(`#dropzone-${type}`);
    const preview = $(`#preview-${type}`);
    const nameSpan = $(`#preview-name-${type}`);
    const sizeSpan = $(`#preview-size-${type}`);
    const uploadBtn = $(`#btn-upload-${type}`);

    // Validations
    const allowedExtensions = ["xls", "xlsx"];
    const fileExtension = file.name.split(".").pop().toLowerCase();

    if (!allowedExtensions.includes(fileExtension)) {
      Swal.fire({
        icon: "warning",
        title: "Hata!",
        text: "Sadece Excel (.xls, .xlsx) dosyaları yüklenebilir.",
        confirmButtonText: "Tamam"
      });
      $(`#bulk-${type}-file`).val("");
      return;
    }

    const maxSizeBytes = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSizeBytes) {
      Swal.fire({
        icon: "warning",
        title: "Hata!",
        text: "Dosya boyutu 5MB'dan büyük olamaz.",
        confirmButtonText: "Tamam"
      });
      $(`#bulk-${type}-file`).val("");
      return;
    }

    // Format size
    let formattedSize = (file.size / 1024).toFixed(1) + " KB";
    if (file.size > 1024 * 1024) {
      formattedSize = (file.size / (1024 * 1024)).toFixed(1) + " MB";
    }

    nameSpan.text(file.name);
    sizeSpan.text(formattedSize);

    zone.hide();
    preview.css("display", "flex");
    uploadBtn.prop("disabled", false);
  }

  // --- AJAX FILE UPLOADS ---
  $(document).on("click", "#btn-upload-income", function () {
    performUpload("income");
  });

  $(document).on("click", "#btn-upload-wage-cut", function () {
    performUpload("wage-cut");
  });

  function performUpload(type) {
    const input = $(`#bulk-${type}-file`)[0];
    const file = input.files[0];
    if (!file) {
      Swal.fire({ icon: "warning", title: "Hata!", text: "Lütfen bir dosya seçin." });
      return;
    }

    const btn = $(`#btn-upload-${type}`);
    btn.prop("disabled", true).html(`<span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...`);

    const month = $("#months").val();
    const year = $("#year").val();

    const formData = new FormData();
    formData.append("action", "bulk-import");
    formData.append("type", type === "income" ? "income" : "wage_cut");
    formData.append("month", month);
    formData.append("year", year);
    formData.append("file", file);

    fetch("api/bordro/bulk-import.php", {
      method: "POST",
      body: formData
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Başarılı!",
            text: data.message,
            confirmButtonText: "Tamam"
          }).then(() => {
            $(`#bulk-${type}-modal`).modal("hide");
            location.reload();
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Hata!",
            text: data.message,
            confirmButtonText: "Tamam"
          });
          resetUploadButton(type);
        }
      })
      .catch((err) => {
        console.error(err);
        Swal.fire({
          icon: "error",
          title: "Hata!",
          text: "Dosya yüklenirken teknik bir sorun oluştu.",
          confirmButtonText: "Tamam"
        });
        resetUploadButton(type);
      });
  }

  function resetUploadButton(type) {
    const btn = $(`#btn-upload-${type}`);
    const label = type === "income" ? "Yükle" : "Yükle";
    btn.prop("disabled", false).html(label);
  }
});
