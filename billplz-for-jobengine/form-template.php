<?php 
	$options = ET_GeneralOptions::get_instance();
    // save this setting to theme options
    $website_logo = $options->site_logo;
?>
<style type="text/css">
	.modal-billplz .button{
		overflow: hidden;
		max-width: 100%;
		display: block;
		position: relative;
		clear: both;
	}
    .plan_desc{
        color:#428BCA;
    }
    #billplz_email{
        height:41px;
        width:100%;
    }
    #billplz_form input{
        width:100%;
    }
    
</style>
<div class="modal modal-job modal-login modal-billplz form_modal_style" id="billplz_modal" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<?php if( !function_exists('et_is_mobile') || !et_is_mobile() ) { ?>
			<div class="billplz-header bg-main-header">
				<div class="logo"></div>
				<div class="info slogan">
      				<span class="plan_name">{$plan_name}</span> <br/>
      				<span class="plan_desc">{$plan_description}</span>      
    			</div>
			</div>
			<?php } ?>
			<div class="modal-body">
				
				<form class="modal-form" id="billplz_form" action="#" method="GET" autocomplete="on">
                
					<div class="content clearfix">		
						<div class="form-group">
							<div class="controls">
								<div class="form-field">
									<label>
										<?php _e('First name:', ET_DOMAIN);?>
									</label>
									<div class="controls fld-wrap" id="">
										<input  tabindex="20" id="billplz_firstname" name="billplz_firstname" type="text" size="20"  class="bg-default-input not_empty required" placeholder="Your first name" />
									</div>
								</div>
                                <div class="form-field">
									<label>
										<?php _e('Last name:', ET_DOMAIN);?>
									</label>
									<div class="controls fld-wrap" id="">
										<input  tabindex="20" id="billplz_lastname" name="billplz_lastname" type="text" size="20"  class="bg-default-input not_empty" placeholder="Your last name" />
									</div>
								</div>
                                <div class="form-field">
									<label>
										<?php _e('Email:', ET_DOMAIN);?>
									</label>
									<div class="controls fld-wrap" id="">
										<input  tabindex="20" id="billplz_email" type="email" name="billplz_email" size="20" required  class="bg-default-input not_empty" placeholder="e.g exemple@enginethemes.com" />
									</div>
								</div>
                                <div class="form-field">
									<label>
										<?php _e('Phone:', ET_DOMAIN);?>
									</label>
									<div class="controls fld-wrap" id="">
										<input  tabindex="20" id="billplz_phone" type="text" size="20" class="bg-default-input not_empty" placeholder="0123456789" />
									</div>
								</div>
							</div>
						</div>	
					</div>
					<div class="footer font-quicksand">
						<div class="button">  
							<button type="submit" class="bg-btn-action border-radius" id="button_billplz" /><?php _e('PAY NOW',ET_DOMAIN); ?> </button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
	<div class="modal-close"></div>
</div>
</div>
<div style="display: none; height: 0; width:0;">
    <form method="get" action="#" id="billplz_hidden_form">
        <button type="submit" class="btn  btn-primary" id="button_billplz_h" />Submit </button>
</form>
</div>