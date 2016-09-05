(function($){
	var mobile_paymill = {
        submitPayment : function () {
            alert("1");
			var view = this;
			var page_template	=	et_globals.page_template;
			var action	=	'et_payment_process'
			if(page_template == 'page-upgrade-account.php') {
				action	=	'resume_view_setup_payment';
			}
			var packageID	= $('input[name="et_payment_package"]').val();
			console.log(packageID);
			$.ajax ({
				type : 'post',
				url  : et_globals.ajaxURL,
				data : {
				    action         : action,
                    billplz_firstname : $form.find('#billplz_firstname').val(),
                    billplz_lastname  : $form.find('#billplz_lastname').val(),
                    billplz_email     : $form.find('#billplz_email').val(),
                    billplz_phone     : $form.find('#billplz_phone').val(),
                    paymentType	   : 'billplz',
                   	jobID		   : this.job.id,
					authorID	   : this.job.get('author_id'),
					packageID	   : this.job.get('job_package'),
                    coupon_code	   : $('#coupon_code').val()
				},
				beforeSend : function () {
					
					$.mobile.showPageLoadingMsg();
				},
				success : function (res) {
					
					if(res.success) {
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
	$(document).on('pageinit' , function () {
		$("#button_billplz").click(function(){
			$("#button_billplz").attr('disabled','disabled');
			$("form#billplz_form").trigger("submit");
			return false;
		});

		$("form#billplz_form").submit(function(event){	
            alert("a");
            var $form = $(event.currentTarget);
            var page_template	=	et_globals.page_template;
		    var action	=	'et_payment_process'
            if(page_template == 'page-upgrade-account.php') {
                action	=	'resume_view_setup_payment';
            }
    		var packageID	= $('input[name="et_payment_package"]').val();
    		console.log(packageID);
    		$.ajax ({
    			type : 'post',
    			url  : et_globals.ajaxURL,
    			data : {
    			    action         : action,
                    billplz_firstname : $form.find('#billplz_firstname').val(),
                    billplz_lastname  : $form.find('#billplz_lastname').val(),
                    billplz_email     : $form.find('#billplz_email').val(),
                    billplz_phone     : $form.find('#billplz_phone').val(),
                    paymentType	   : 'billplz',
                  	jobID		: $('input[name="ad_id"]').val(),
					authorID	: $('input[name="post_author"]').val(),
					packageID	: $('input[name="et_payment_package"]').val(),
                    coupon_code	   : $('#coupon_code').val()
    			},
    			beforeSend : function () {
    				
    				$.mobile.showPageLoadingMsg();
    			},
    			success : function (res) {
    				
    				if(res.success) {
    					console.log(res);
                        $('#billplz_hash').val(res.data.value.hash);
                        $('#billplz_txnid').val(res.data.value.txnid);
                        $('#billplz_key').val(res.data.value.key);
                        $("#billplz_amount").val(res.data.value.amount);
                        $("#billplz_firstname_h").val(res.data.value.firstname);
                        $("#billplz_email_h").val(res.data.value.email);
                        $("#billplz_phone_h").val(res.data.value.phone);
                        $("#billplz_productinfo").val(res.data.value.productinfo);
                        $("#billplz_hidden_form").attr("action", res.data.url);
                        $('#billplz_surl').val(res.data.surl);
                        $('#billplz_furl').val(res.data.furl);
                        $('#billplz_curl').val(res.data.furl);
                        $('#button_billplz_h').trigger("click");
    					//window.location = res.data.url;
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