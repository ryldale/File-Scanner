jQuery(function ($) {

    var spam = false;

    $('#scan').on('click', function () {
        
        if (spam) {
            return;
        }

        spam = true;

        $('#scan').prop('disabled', true);

        $('.list-con').empty();

        $.ajax({
            url: myAjax.ajaxurl,
            type: "post",
            data: { action: "file_scanner_action" },
            success: function (response) {

                $('.list-con').append(response);
                $('table').show();

            },
            complete: function () {
                // Enable the Scan button 
                $('#scan').prop('disabled', false);
                spam = false;
            }
        });
    });
});
