$(document).ready(function () {
  // Category-based selection (Main checkbox in accordion header)
  $(document).on("change", ".main-category-check", function () {
    var isChecked = $(this).is(":checked");
    var groupClass = $(this).data("group");
    var $group = $("." + groupClass);
    
    $group.find(".sub-auth-check").prop("checked", isChecked);
    
    // Update counter
    updateCounter($(this).closest(".accordion-item").find(".selection-counter"));
  });

  // Sub-permission selection
  $(document).on("change", ".sub-auth-check", function () {
    var parentCounterId = $(this).data("parent-counter");
    var parentMainId = $(this).data("parent-main");
    var $counter = $("#" + parentCounterId);
    var $main = $("#" + parentMainId);
    
    updateCounter($counter);
    
    // Update parent main checkbox based on children
    var $group = $(this).closest(".row");
    var allChecked = $group.find(".sub-auth-check:checked").length === $group.find(".sub-auth-check").length;
    var anyChecked = $group.find(".sub-auth-check:checked").length > 0;
    
    // User requirement: if at least one is selected, the parent (accordion) must be selected
    $main.prop("checked", anyChecked);
    $main.prop("indeterminate", anyChecked && !allChecked);
  });

  function updateCounter($counter) {
    if (!$counter.length) return;
    var total = $counter.data("total");
    var checked = $counter.closest(".accordion-item").find(".sub-auth-check:checked").length;
    $counter.text(checked + " / " + total);
    
    if (checked > 0) {
      $counter.removeClass("bg-primary-lt").addClass("bg-primary text-white");
    } else {
      $counter.addClass("bg-primary-lt").removeClass("bg-primary text-white");
    }
  }

  // Initial counter and indeterminate state setup
  $(".selection-counter").each(function() {
    updateCounter($(this));
    
    // Initial indeterminate/checked state for parents
    var $item = $(this).closest(".accordion-item");
    var $subChecks = $item.find(".sub-auth-check");
    var $main = $item.find(".main-category-check");
    var checkedCount = $subChecks.filter(":checked").length;
    var totalCount = $subChecks.length;
    
    if (checkedCount > 0) {
      $main.prop("checked", true);
      $main.prop("indeterminate", checkedCount < totalCount);
    }
  });

  // Expand All / Collapse All
  $(document).on("click", "#expandAll", function() {
    $(".accordion-collapse").addClass("show");
    $(".accordion-button").removeClass("collapsed").attr("aria-expanded", "true");
  });

  $(document).on("click", "#collapseAll", function() {
    $(".accordion-collapse").removeClass("show");
    $(".accordion-button").addClass("collapsed").attr("aria-expanded", "false");
  });
});

$(document).on("click", "#authsSave", function () {
  var form = $("#authsForm");
  var formData = new FormData(form[0]);

  fetch("api/users/auths.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      title = data.status == "success" ? "Başarılı" : "Hata";
      swal.fire(title, data.message, data.status);
      $("#auth_id").val(data.id);
    });
});

// global checkAll
$(document).on("click", "#checkAll", function () {
  var isChecked = $(this).is(":checked");
  $(".form-check-input").prop("checked", isChecked);
  $(".sub-auth-check").prop("checked", isChecked);
  $(".main-category-check").prop("checked", isChecked).prop("indeterminate", false);
  
  $(".selection-counter").each(function() {
    updateCounter($(this));
  });
});