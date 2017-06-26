(function ($) {
    var mobile_paymill = {
        submitPayment: function () {
            alert("1");
            var view = this;
            var page_template = et_globals.page_template;
            var action = 'et_payment_process'
            if (page_template == 'page-upgrade-account.php') {
                action = 'resume_view_setup_payment';
            }
            var packageID = $('input[name="et_payment_package"]').val();
            console.log(packageID);
            $.ajax({
                type: 'post',
                url: et_globals.ajaxURL,
                data: {
                    action: action,
                    billplz_firstname: $form.find('#billplz_firstname').val(),
                    billplz_email: $form.find('#billplz_email').val(),
                    billplz_phone: $form.find('#billplz_phone').val(),
                    paymentType: 'billplz',
                    jobID: this.job.id,
                    authorID: this.job.get('author_id'),
                    packageID: this.job.get('job_package'),
                    coupon_code: $('#coupon_code').val()

                },
                beforeSend: function () {
                    $('#button_billplz').attr("disabled", true);
                    $("#button_billplz").html('Processing...');
                    $.mobile.showPageLoadingMsg();
                },
                success: function (res) {

                    if (res.success) {
                        console.log(res);
                        window.location = res.data.url;
                    } else {
                        alert(res.msg);
                    }
                    $.mobile.hidePageLoadingMsg();
                    $("#submit_paymill").removeAttr('disabled');
                }
            });
        }

    };
    $(document).on('pageinit', function () {
        $("#button_billplz").click(function () {
            $("#button_billplz").attr('disabled', 'disabled');
            $("form#billplz_form").trigger("submit");
            return false;
        });

        $("form#billplz_form").submit(function (event) {
            var $form = $(event.currentTarget);
            var page_template = et_globals.page_template;
            var action = 'et_payment_process';
            if (page_template == 'page-upgrade-account.php') {
                action = 'resume_view_setup_payment';
            }
            $.ajax({
                type: 'post',
                url: et_globals.ajaxURL,
                data: {
                    action: action,
                    billplz_firstname: $form.find('#billplz_firstname').val(),
                    billplz_email: $form.find('#billplz_email').val(),
                    billplz_phone: $form.find('#billplz_phone').val(),
                    paymentType: 'billplz',
                    jobID: $('input[name="ad_id"]').val(),
                    authorID: $('input[name="post_author"]').val(),
                    packageID: $('input[name="et_payment_package"]').val(),
                    coupon_code: $('#coupon_code').val()
                },
                beforeSend: function () {
                    $('#button_billplz').attr("disabled", true);
                    $("#button_billplz").html('Processing...');
                    $.mobile.showPageLoadingMsg();
                },
                success: function (res) {

                    if (res.success) {
                        $("#billplz_hidden_form").attr("action", res.data.url);
                        $('#button_billplz_h').trigger("click");
                    } else {
                        alert(res.msg);
                    }
                    $.mobile.hidePageLoadingMsg();
                    $("#submit_paymill").removeAttr('disabled');
                }
            });
        });
    });
})(jQuery);