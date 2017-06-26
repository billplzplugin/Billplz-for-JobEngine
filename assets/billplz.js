(function ($) {
    JobEngine.Views.BillplzForm = JobEngine.Views.Modal_Box.extend({
        el: $('div#billplz_modal'),
        job: [],
        events: {
            'submit form#billplz_form': 'submitBillplz',
            'click .modal-close': 'close'
        },
        initialize: function (options) {
            JobEngine.Views.Modal_Box.prototype.initialize.apply(this, arguments);
            // bind event to modal
            //this.InitValidate();
            this.blockUi = new JobEngine.Views.BlockUi();
            // catch event select extend gateway
            //AE.pubsub.on('ae:submitPost:extendGateway', this.setupData);
            this.openpaymillModal();
            this.job = JobEngine.post_job.job;

        },
        // callback when user select Paymill, set data and open modal
        openpaymillModal: function () {
            var job = JobEngine.post_job.job,
                    packageID = job.get('job_package'),
                    plans = JSON.parse($('#package_plans').html()),
                    job_package = plans[packageID];
            var currency = je_billplz.currency.icon;
            //console.log(job);
            var price = job_package.price + currency;
            if ($('.coupon-price').html() !== '' && $('#coupon_code').val() != '')
                price = $('.coupon-price').html();
            this.$el.find('span.plan_name').html(job_package.title + ' (' + price + ')');
            this.$el.find('span.plan_desc').html(job_package.description);

            this.openModal();
            this.InitValidate();

        },
        close: function (event) {
            event.preventDefault();
            this.closeModal();
        },
        InitValidate: function () {
            if (typeof this.validator_je_billplz === "undefined") {
                this.validator_je_billplz = $('form#billplz_form').validate({
                    rules: {
                        billplz_firstname: {
                            required: true,
                        },
                        billplz_email: {
                            required: true,
                            email: true
                        },
                    },
                });
            }
        },
        // catch user event click on pay
        submitBillplz: function (event) {
            event.preventDefault();
            if (this.validator_je_billplz.form()) {
                var page_template = et_globals.page_template;
                var $form = $(event.currentTarget);
                var action = 'et_payment_process'
                if (page_template == 'page-upgrade-account.php') {
                    action = 'resume_view_setup_payment';
                }

                var view = this;

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
                        view.blockUi.block('#button_billplz');
                    },
                    success: function (res) {
                        // view.blockUi.unblock();
                        if (res.success) {
                            $("#button_billplz").html('Redirecting......');
                            $("#billplz_hidden_form").attr("action", res.data.url);
                            $('#button_billplz_h').trigger("click");
                        } else {
                            pubsub.trigger('je:notification', {
                                msg: res.msg,
                                notice_type: 'error'
                            });
                            return false
                        }
                    }
                });
            }
        },
    });
    // init Billplz form
    /*  $(document).ready(function() {
     new JobEngine.Views.BillplzForm();
     });*/
    $(document).ready(function () {
        $('#btn_billplz').click(function (e) {
            e.preventDefault();
            var payment_form = new JobEngine.Views.BillplzForm();
            return false;
        });
    });
})(jQuery);
