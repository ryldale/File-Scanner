jQuery(document).ready(function ($) {
  var spam = false;

  $("#scan").on("click", function () {
    if (spam) {
      return;
    }

    spam = true;

    $("#scan").prop("disabled", true);

    $(".list-con").empty();

    $.ajax({
      url: myAjax.ajaxurl,
      type: "post",
      data: { action: "file_scanner_action" },
      success: function (response) {
        $(".list-con").append(response);
        $("table").show();
        initializePagination(); // Initialize pagination after content is added
      },
      complete: function () {
        // Enable the Scan button
        $("#scan").prop("disabled", false);
        spam = false;
      },
    });
  });


  function initializePagination() {
    var items = $("#fileScannerTable .all-images");
    // console.log(items);
    var numItems = items.length;
    var perPage = 20;

    items.slice(perPage).hide();

    $("#pagination-container").pagination({
      items: numItems,
      itemsOnPage: perPage,
      prevText: "&laquo;",
      nextText: "&raquo;",
      onPageClick: function (pageNumber) {
        var showFrom = perPage * (pageNumber - 1);
        var showTo = showFrom + perPage;
        items.hide().slice(showFrom, showTo).show();
      },
    });
  }

  $(document).on('click', '.delete-image', function () {
    console.log('delete');
  });


});
